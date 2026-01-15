<?php
/**
 * Router for PHP Built-in Development Server
 * 
 * This file handles routing for the development server.
 * Use with: php -S localhost:8000 router.php
 */

// Get the requested URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// If requesting a file or directory that exists, serve it
if (file_exists(__DIR__ . $uri)) {
    return false;
}

// Route everything else through index-auth.php
if (strpos($uri, '/index-auth.php') === 0 || strpos($uri, '/auth') === 0) {
    $_SERVER['REQUEST_URI'] = '/index-auth.php' . $uri;
    include __DIR__ . '/index-auth.php';
    return true;
}

// Route /users requests through index-users.php
if (strpos($uri, '/users') === 0) {
    $_SERVER['REQUEST_URI'] = '/index-users.php' . $uri;
    include __DIR__ . '/index-users.php';
    return true;
}

// Default: serve from index-auth.php
$_SERVER['REQUEST_URI'] = '/index-auth.php' . $uri;
include __DIR__ . '/index-auth.php';
return true;
?>
