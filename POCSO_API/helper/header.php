<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
date_default_timezone_set('Asia/Calcutta');
// Get current timestamp
$request_time = date('d-m-Y h:i:s a', time());

require_once('autoload_finder.php');

try {
    $currentDirectory = __DIR__;
    $rootDirectory = findAndRequireAutoload($currentDirectory);
    $envFilePath = $rootDirectory . '/.env';

    // Check if the .env file exists
    if (file_exists($envFilePath)) {
        $dotenv = Dotenv\Dotenv::createImmutable($rootDirectory);
        $dotenv->load();
    } else {
        die("env file is not found");
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}

$environment = $_ENV['ENV'];
$debug = $_ENV['DEBUG'];

if ($debug && $environment == 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 'On');
} elseif ($debug && $environment == 'production') {
    die("Disable Debug to false in Production Environment");
} elseif (!$debug && $environment == 'production') {
    error_reporting(0);
} else {
    error_reporting(0);
}
require_once('auth.php');
