<?php

// Server configuration
set_time_limit(0);
date_default_timezone_set('UTC');
error_reporting(E_ALL);

ini_set('display_errors', 1);
ini_set('memory_limit', '1024M');

// The true ROOT_PATH is one folder up
define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);

// Start sessions if they are not started yet
if (!session_id() && php_sapi_name() != "cli"){
	session_start();
}

// Load config file
$config = require_once ROOT_PATH . "config.php";

// Validate the config file
if(!isset($config) || empty($config)){
	echo 'Config file is missing.';
	exit();
}

if(!isset($config['db']) || empty($config['db'])){
	echo 'No configuration for database detected.';
	exit();
}

// Vendor loads
require ROOT_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

// Load the core files
require_once ROOT_PATH . 'core/App.class.php';
require_once ROOT_PATH . 'core/Loader.class.php';
require_once ROOT_PATH . 'db/Database.class.php';

$db = new Database($config['db']);
$loader = new Loader();

$app = new App($config);
$app->db($db);
$app->loader($loader);
$app->run();

//$db->showDebugger();