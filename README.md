cPanel UAPI and API2 PHP class
===
[![GitHub release](https://img.shields.io/github/release/N1ghteyes/cpanel-UAPI-php-class.svg?style=flat-square)](https://github.com/N1ghteyes/cpanel-UAPI-php-class/releases)

PHP class to provide an easy-to-use interface with cPanel's UAPI and API2.
Uses PHP magic functions to provide a simple and powerful interface.

v2.0 is not backwards compatible with v1.x, and will likley undergo a few more changes. See the [changelog](changelog.txt) for details.
The class has been renamed to `cpanelAPI`.
Some more testing is required.

## Usage

See the example files, but typical usage takes the form of:

### Instantiate the class
```php
$cPanel = new cpanelAPI('user', 'password', 'cpanel.example.com');
```
The API we want to use and the Module (also called Scope) are now protected and are set by `__get()`.

The request layout looks like this: `$cPanel->api->method->Module->request(args[])`

The `->method` part should be replaced with `->get` for GET requests and `->post` for POST requests, or omitted to default to GET requests.

As an example, suppose we want to use the UAPI to call the [Mysql::get_server_information](https://documentation.cpanel.net/display/SDK/UAPI+Functions+-+Mysql%3A%3Aget_server_information) function:

```php
$response = $cPanel->uapi->Mysql->get_server_information();
var_dump($response);
```

Now that we have set both the API *and* the Module, we can call other functions within this API and Module without specifying them again:

```php
$response = $cPanel->create_database(['name' => $cPanel->user.'_MyDatabase']);
var_dump($response);
```

We can also change the Module scope without respecifying the API.  Note that the Module call is case-sensitive.

```php
$response = $cPanel->SSL->list_certs();
```

#### File upload example

```php
$cPanel = new cpanelAPI($username, $password, $hostname);
$cPanel->uapi->post->Fileman
       ->upload_files(['dir' => REMOTE_PATH_RELATIVE_TO_HOME,
                       'file-1' => new CURLFile(LOCAL_PATH_TO_FILE)
                       ]);
```

### API2

API2 is used in exactly the same way as the UAPI

```php
$cPanel = new cpanelAPI('user', 'password', 'cpanel.example.com');
```

For example, suppose we want to use the API2 to add a subdomain:

```php
$response = $cPanel->api2->SubDomain->addsubdomain(['rootdomain' => 'domain.com', 'domain' => 'sub']);
var_dump($response);
```

### Two-Factor Authentication

To use this class on a cPanel instance with two-factor authentication (2FA), you need to pass the secret into the class constructor:

```php
$cPanel = new cpanelAPI('user', 'password', 'cpanel.example.com', 'secret');
```

The secret can be found on the 2FA setup page. See [Two-Factor Authentication for cPanel – Configure two-factor authentication](https://documentation.cpanel.net/display/ALD/Two-Factor+Authentication+for+cPanel#Two-FactorAuthenticationforcPanel-Configure2FA) for details.
