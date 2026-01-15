<?php
require __DIR__ . "/inc/bootstrap.php";
//mail has the includes.  
require_once "mail.php"; 


// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}





$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode( '/', $uri );
file_put_contents("log.txt","Getting Uri:". print_r($uri,true)."\n", FILE_APPEND );

if ((isset($uri[3]) && $uri[3] != 'users') || !isset($uri[4])) {
    
    header("HTTP/1.1 404 Not Found");
    exit();
}

 
require "controller/UserController.php";
 
$objFeedController = new UserController();

//$strMethodName = $uri[3] . 'Action';
//$objFeedController->{$strMethodName}();


// Determine request method and call corresponding method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $strMethodName = $uri[4] . 'PostAction';
    //example $objFeedController->signinPostAction();
    $objFeedController->{$strMethodName}();
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $strMethodName = $uri[4] . 'Action';
    $objFeedController->{$strMethodName}();
    //$objFeedController->confirmGetAction();
    
} else {
    header("HTTP/1.1 405 Method Not Allowed");
    exit();
}






?>