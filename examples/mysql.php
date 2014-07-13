<?php

/**
 * For documentation on cPanel's UAPI:
 * @see https://documentation.cpanel.net/display/SDK/UAPI+Functions
 *
 * @author N1ghteyes - www.source-control.co.uk
 * @copyright 2014 N1ghteyes
 * @license license.txt The MIT License (MIT)
 * @link https://github.com/N1ghteyes/cpanel-UAPI-php-class
 */

include "../cpaneluapi.class.php"; //include the class file
$uapi = new cpanelUAPI('cPuser', 'cPpass', 'cPanel.example.com'); //instantiate the object

$database = 'database';
$databaseuser = 'databaseuser';
$databasepass = 'databasepass';

/**
 * Mysql - Create a database and user, then assign the user to that database.
 * For a full list of functions available for the Mysql module, see: https://documentation.cpanel.net/display/SDK/Mysql
 * Mysql requires cPanel 11.44 +
 */

$uapi->scope = 'Mysql'; // set the scope to the module we want to use. NOTE: this IS case sensitive.

//If database prefixing is enabled, this parameter must include the database prefix for the account.
//This is normally the account username, followed by an underscore. e.g. cPuser_database.
// ----
//Arguments are passed by an array, where a url parameter of ?name=database is needed, it is passed with
//the array key as the parameter e.g. array('name' => 'database').

$uapi->create_database(array('name' => $database)); //Create the database
$uapi->create_user(array('name' => $databaseuser, 'password' => $databasepass)); //create a user for the new database


//After you create the user, you must use the set_privileges_on_database function call to grant access to the
//user for a database.
//add the user, set all privileges - add specific privileges by comma separation. e.g. 'DELETE,UPDATE,CREATE,ALTER'
$uapi->set_privileges_on_database(array('user' => $databaseuser, 'database' => $database, 'privileges' => 'ALL'));
