<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
/* =====================================================

NEW COOKIE REQUIRED CODE
===================================================== */



$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

/* allow everything can not be used with cookies*/
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $isHttps ? 1 : 0);
ini_set('session.cookie_samesite', $isHttps ? 'None' : 'Lax');
ini_set('session.use_strict_mode', 1);
// 🔑 THIS IS THE MISSING LINE
ini_set('session.cookie_domain', '.crystalhansenartographic.com');

session_start();




require __DIR__ . "/inc/bootstrap.php";
//mail has the includes.  
require_once "mail.php"; 




/*
    NOTES: THIS FEATURE AUTHENTICATION IS BACKEND IMPROVEMENTS INVOLVED IN LOGIN-FEATURE-REACT 
    
    IT SHOULD BE THE SOURCE OF TRUTH FOR ALL LOGINS ONCE COMPLETE TO BE USED WITH 
    booker.crystalhansenartographic.com
    flashcards.crystalhansenartographic.com
    unwind.crystalhansenartographic.com
    auth.crystalhansenartographic.com <-- current deployed **login-feature** react https://github.com/cryshansen/login-feature-react/

*/ 

// Optional: custom name if you want
// session_name('auth_session');

// CORS headers 

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
//$host   = parse_url($origin, PHP_URL_HOST);

$allowedOrigins = [
    'http://localhost:5173', //my react
    'https://auth.example.com'
];

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}

header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");



// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}



$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode( '/', $uri );


if ((isset($uri[3]) && $uri[3] != 'auth') || !isset($uri[4])) {
    
    header("HTTP/1.1 404 Not Found");
    exit();
}

 
require "controller/AuthController.php";
 
$objFeedController = new AuthController();


// Determine request method and call corresponding method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $strMethodName = $uri[4] . 'PostAction';
    //example $objFeedController->signinPostAction();
    $objFeedController->{$strMethodName}();
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $strMethodName = $uri[4] . 'GetAction';
    $objFeedController->{$strMethodName}();
    //$objFeedController->confirmGetAction();
    
} else {
    header("HTTP/1.1 405 Method Not Allowed");
    exit();
}






?>