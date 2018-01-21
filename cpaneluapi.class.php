<?php

/**
 * PHP class to handle connections with cPanel's UAPI and API2 specifically through cURL requests as seamlessly and simply as possible.
 *
 * For documentation on cPanel's UAPI:
 * @see https://documentation.cpanel.net/display/SDK/UAPI+Functions
 *
 * For documentation on cPanel's API2:
 * @see https://documentation.cpanel.net/display/SDK/Guide+to+cPanel+API+2
 *
 * Please use UAPI where possible, only use API2 where the equivalent doesn't exist for UAPI
 *
 * @author N1ghteyes - www.source-control.co.uk
 * @copyright 2016 N1ghteyes
 * @license license.txt The MIT License (MIT)
 * @link https://github.com/N1ghteyes/cpanel-UAPI-php-class
 *
 * @todo - im sure there is a lot that can be improved here
 */

/**
 * Class cPanelAPI
 */
class cpanelAPI
{
    public $version = '2.0';
    public $ssl = 1; //Bool - TRUE / FALSE for ssl connection
    public $port = 2083; //default for ssl servers.
    public $server;
    public $maxredirect = 0; //Number of redirects to make, typically 0 is fine. on some shared setups this will need to be increased.
    public $user;
    public $json = '';

    protected $scope; //String - Module we want to use
    protected $api;
    protected $auth;
    protected $pass;
    protected $secret;
    protected $type;
    protected $session;
    protected $method;
    protected $requestUrl;
    protected $eno;
    protected $emes;
    protected $token = FALSE;
    protected $httpMethod = 'GET';
    protected $postData = '';
    /**
     * @param $user
     * @param $pass
     * @param $server
     * @param $secret
     */
    function __construct($user, $pass, $server, $secret = FALSE)
    {
        $this->user = $user;
        $this->pass = $pass;
        $this->server = $server;
        if ($this->secret) {
            $this->secret = $secret;
            $this->set2Fa();
        }
    }

    /**
     * Include the ot class so we can generate a valid token for 2FA
     */
    protected function set2Fa()
    {
        require 'otphp/lib/otphp.php';
        $totp = new \OTPHP\TOTP($this->secret);
        $this->token = $totp->now();
    }

    public function __get($name)
    {
        switch (strtolower($name)) {
            case 'get':
                $this->httpMethod = 'GET';
                break;
            case 'post':
                $this->httpMethod = 'POST';
                break;
            case 'api2':
                $this->setApi('api2');
                break;
            case 'uapi':
                $this->setApi('uapi');
                break;
            default:
                $this->scope = $name;
        }
        return $this;
    }

    /**
     * Set the api to use for connections.
     * @param $api
     * @return $this
     * @throws Exception
     */
    protected function setApi($api)
    {
        $this->api = $api;
        $this->setMethod();
        return $this;
    }

    /**
     * Function to set the method used to communicate with the chosen api.
     * @return $this
     * @throws Exception
     */
    protected function setMethod()
    {
        switch ($this->api) {
            case 'uapi':
                $this->method = '/execute/';
                break;
            case 'api2':
                $this->method = '/json-api/cpanel/';
                break;
            default:
                throw new Exception('$this->api is not set or is incorrectly set. The only available options are \'uapi\' or \'api2\'');
        }
        return $this;
    }

    /**
     * Magic __toSting() method, allows us to return the result as raw json
     * @return mixed
     */
    public function __toString()
    {
        return $this->json;
    }

    /**
     * Magic __call method, will translate all function calls to object to API requests
     * @param $name - name of the function
     * @param $arguments - an array of arguments
     * @return mixed
     * @throws Exception
     */
    public function __call($name, $arguments)
    {
        if (count($arguments) < 1 || !is_array($arguments[0]))
            $arguments[0] = [];
        $this->json = $this->APIcall($name, $arguments[0]);
        return json_decode($this->json);
    }

    /**
     * @param $name
     * @param $arguments
     * @return bool|mixed
     * @throws Exception
     */
    protected function APIcall($name, $arguments)
    {
        $this->auth = base64_encode($this->user . ":" . $this->pass);
        $this->type = $this->ssl == 1 ? "https://" : "http://";
        $this->requestUrl = $this->type . $this->server . ':' . $this->port . $this->method;
        switch ($this->api) {
            case 'uapi':
                $this->requestUrl .= ($this->scope != '' ? $this->scope . "/" : '') . $name . '?';
                break;
            case 'api2':
                if ($this->scope == '') {
                    throw new Exception('Scope must be set.');
                }
                $this->requestUrl .= '?cpanel_jsonapi_user=' . $this->user . '&cpanel_jsonapi_apiversion=2&cpanel_jsonapi_module=' . $this->scope . '&cpanel_jsonapi_func=' . $name . '&';
                break;
            default:
                throw new Exception('$this->api is not set or is incorrectly set. The only available options are \'uapi\' or \'api2\'');
        }
        if($this->httpMethod == 'GET') {
            $this->requestUrl .= http_build_query($arguments);
        }
        if($this->httpMethod == 'POST'){
            $this->postData = http_build_query($arguments);
        }

        print $this->postData;
        return $this->curl_request($this->requestUrl);
    }

    /**
     * @param $url
     * @return bool|mixed
     */
    protected function curl_request($url)
    {
        $httpHeaders = array("Authorization: Basic " . $this->auth);
        //If we have a token then send that with the request for 2FA.
        if ($this->token) {
            $httpHeaders[] = "X-CPANEL-OTP: " . $this->token;
        }
        $ch = curl_init();
        if($this->httpMethod == 'POST'){
            $httpHeaders[] = "Content-type: multipart/form-data";
            curl_setopt($ch,CURLOPT_POSTFIELDS, $this->postData);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100020);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        $content = $this->curl_exec_follow($ch, $this->maxredirect);
        $this->eno = curl_errno($ch);
        $this->emes = curl_error($ch);

        curl_close($ch);

        return $content;
    }

    /**
     * @param $ch
     * @param null $maxredirect
     * @return bool|mixed
     */
    protected function curl_exec_follow($ch, &$maxredirect = null)
    {
        // we emulate a browser here since some websites detect
        // us as a bot and don't let us do our job
        $user_agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5)" .
            " Gecko/20041107 Firefox/1.0";
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

        $mr = $maxredirect === null ? 5 : intval($maxredirect);

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
        curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        return curl_exec($ch);
    }

    /**
     * Function to get the last request made
     * @return mixed
     */
    public function getLastRequest()
    {
        return $this->requestUrl;
    }

    /**
     * Function to return the error if there was one, or FALSE if not.
     * @return array|bool
     */
    public function getError()
    {
        if (!empty($this->eno)) {
            return ['no' => $this->eno, 'message' => $this->emes];
        }
        return FALSE;
    }
}
