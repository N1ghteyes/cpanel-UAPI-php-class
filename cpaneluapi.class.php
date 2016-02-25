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
 */

/**
 * Class cPanelUAPI
 * Added for backwards compatibility
 */
class cpanelUAPI extends cpanelAPI{
  function __construct($user, $pass, $server){
    parent::__construct($user, $pass, $server);
    $this->setApi('uapi');
  }
}

/**
 * Class cPanelAPI2
 * Added to set api2 info.
 */
class cpanelAPI2 extends cpanelAPI{
  function __construct($user, $pass, $server){
    parent::__construct($user, $pass, $server);
    $this->setApi('api2');
  }
}

/**
 * Class cPanelAPI
 */
class cpanelAPI
{
  public $version = '1.1';
  public $scope = ""; //String - Module we want to use
  public $ssl = 1; //Bool - TRUE / FALSE for ssl connection
  public $port = 2083; //default for ssl servers.
  public $server;
  public $maxredirect = 0; //Number of redirects to make, typically 0 is fine. on some shared setups this will need to be increased.

  protected $api;
  protected $auth;
  protected $user;
  protected $pass;
  protected $secret;
  protected $type;
  protected $session;
  protected $method;
  protected $requestUrl;

  /**
   * @param $user
   * @param $pass
   * @param $server
   */
  function __construct($user, $pass, $server)
  {
    $this->user = $user;
    $this->pass = $pass;
    $this->server = $server;
  }

  protected function setApi($api){
    $this->api = $api;
    $this->setMethod();
  }

  /**
   * Magic __call method, will translate all function calls to object to API requests
   * @param $name - name of the function
   * @param $arguments - an array of arguments
   * @return mixed
   * @throws Exception
   */
  public function  __call($name, $arguments)
  {
    if (count($arguments) < 1 || !is_array($arguments[0]))
      $arguments[0] = array();
    return json_decode($this->APIcall($name, $arguments[0]));
  }

  public function getLastRequest(){
    return $this->requestUrl;
  }

  protected function setMethod(){
    switch($this->api){
      case 'uapi':
        $this->method = '/execute/';
            break;
      case 'api2':
        $this->method = '/json-api/cpanel/';
            break;
      default:
            throw new Exception('$this->api is not set or is incorrectly set. The only available options are \'uapi\' or \'api2\'');
    }
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
    switch($this->api){
      case 'uapi':
        $this->requestUrl .= ($this->scope != '' ? $this->scope . "/" : '') . $name . '?';
        break;
      case 'api2':
        if($this->scope == ''){
          throw new Exception('Scope must be set.');
        }
        $this->requestUrl .= '?cpanel_jsonapi_user='.$this->user.'&cpanel_jsonapi_apiversion=2&cpanel_jsonapi_module='.$this->scope.'&cpanel_jsonapi_func='.$name.'&';
        break;
      default:
        throw new Exception('$this->api is not set or is incorrectly set. The only available options are \'uapi\' or \'api2\'');
    }
    foreach ($arguments as $key => $value) {
      $this->requestUrl .= $key . "=" . $value . "&";
    }

    return $this->curl_request($this->requestUrl);
  }

  /**
   * @param $url
   * @return bool|mixed
   */
  protected function curl_request($url)
  {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Basic " . $this->auth));
    curl_setopt($ch, CURLOPT_TIMEOUT, 100020);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    $content = $this->curl_exec_follow($ch, $this->maxredirect);
    $err = curl_errno($ch);
    $errmsg = curl_error($ch);
    $header = curl_getinfo($ch);

    curl_close($ch);

    $header['errno'] = $err;
    $header['errmsg'] = $errmsg;
    $header['content'] = $content;

    return $header['content'];
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

    if (ini_get('open_basedir') == '' && ini_get('safe_mode') == 'Off') {

      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
      curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    } else {

      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);

      if ($mr > 0) {
        $original_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $newurl = $original_url;

        $rch = curl_copy_handle($ch);

        curl_setopt($rch, CURLOPT_HEADER, TRUE);
        curl_setopt($rch, CURLOPT_NOBODY, TRUE);
        curl_setopt($rch, CURLOPT_FORBID_REUSE, FALSE);
        do {
          curl_setopt($rch, CURLOPT_URL, $newurl);
          $header = curl_exec($rch);
          if (curl_errno($rch)) {
            $code = 0;
          } else {
            $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
            if ($code == 301 || $code == 302) {
              preg_match('/Location:(.*?)\n/', $header, $matches);
              $newurl = trim(array_pop($matches));

              // if no scheme is present then the new url is a
              // relative path and thus needs some extra care
              if (!preg_match("/^https?:/i", $newurl)) {
                $newurl = $original_url . $newurl;
              }
            } else {
              $code = 0;
            }
          }
        } while ($code && --$mr);

        curl_close($rch);

        if (!$mr) {
          if ($maxredirect === null)
            trigger_error('Too many redirects.', E_USER_WARNING);
          else
            $maxredirect = 0;

          return FALSE;
        }
        curl_setopt($ch, CURLOPT_URL, $newurl);
      }
    }
    return curl_exec($ch);
  }
}