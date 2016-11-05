##cPanel UAPI and API2 PHP class

PHP class to provide an easy to use interface with cPanel’s UAPI and API2.
Uses php magic functions to provide a simple and powerful interface.

v2.0 is not backwards compatible, and will likley under go a few more changes, see the changelog for details.
The class has been renamed to cpanelAPI.
Some more testing is required.

##Usage

See the example files, but typical useage takes the form of:

###Instantiate the class
```
$capi = new cpanelAPI('user', 'password', 'cpanel.example.com');
```
The API we want to use and the Module (also called Scope) are now protected and are set by `__get()`.
The request layout looks like this: $capi->api->Module->request(args[])

For example. We want to use the UAPI to call the Mysql get_restrictions function.
```
$response = $capi->uapi->Mysql->get_restrictions(); 
print_r($response);
```

Now that we have set both the api AND the Module, we can call other functions within this api and scope without specifying them again
We have database prefixing enabled so we have to pass the usename into this function
see https://documentation.cpanel.net/display/SDK/UAPI+Functions+-+Mysql%3A%3Acreate_database
```
$response = $capi->create_database(['name' => $capi->user.'_MyDatabase']);
print_r($response);
```

we can also change the scope without respecifying the api, note that the Module call is case sensative.
```
$response = $capi->SSL->list_certs();
```

###API2

API2 is used in exactly the same way as the UAPI
```
$cpapi2 = new cpanelAPI('user', 'password', 'cpanel.example.com');
```

For example. We want to use the API2 to add a subdomain
```
$response = $capi->api2->SubDomain->addsubdomain(['rootdomain' => 'domain.com', 'domain' => 'sub']); 
print_r($response);
```
