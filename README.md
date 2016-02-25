##cPanel UAPI and API2 PHP class

PHP class to provide an easy to use interface with cpanels UAPI and API2 (as of version 1.1).
The implimentation is exactly the same fot both, the processing done in the class are slightly different.

##Usage

See the example files, but typical useage takes the form of:

###UAPI
```
//load UAPI2 class
$cpuapi = new cpanelUAPI('user', 'password', 'cpanel.example.com');

//Set the scope to the module we want to use. in this case, Mysql
$cpuapi->scope = 'Mysql';

//call the function we want like this. Any arguments are passed into the function as an array, in the form of param => value.
$response = $cpuapi->get_restrictions(); 
print_r($response);
```

###API2
```
//load API2 class
$cpapi2 = new cpanelAPI2('user', 'password', 'cpanel.example.com');

//Set the scope to the module we want to use. in this case, SubDomain
$cpapi2->scope = 'SubDomain';

//call the function we want like this. Any arguments are passed into the function as an array, in the form of param => value.
$response = $cpapi2->addsubdomain(array('rootdomain' => ''domain.com, 'domain' => 'sub')); 
print_r($response);
```
