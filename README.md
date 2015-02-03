##cPanel UAPI PHP class

PHP class to provide an easy to use interface with cpanels UAPI.

###Known Bugs

- When running on systems with PHP safe mode turned on AND open_basedir is set to an empty string.

When the requested end point doesnt redirect, curl_exec_follow requests it twice. Once for the headers to check the HTTP Status and then once to fire the request. This is fine for getting data, however when the endpoint is used to generate something remotely, a specific use case would be an SSH key, 2 keys are generated and the SECOND one is returned.

- Work Around

If not on a shared server, ether set open_basedir or turn safe mode off.
If on a shared server, comment out the curl_exec on line 157, this will break any requests that get redirected, but should not effect most people.

##Usage

See the example files, but typical useage takes the form of:

```
//load class
$cpuapi = new cpanelUAPI('user', 'password', 'cpanel.example.com');

//Set the scope to the module we want to use. in this case, Mysql
$cpuapi->scope = 'Mysql';

//call the function we want like this. Any arguments are passed into the function as an array, in the form of param => value.
$response = $cpuapi->get_restrictions(); 
print_r($response);
```
