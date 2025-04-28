<?php
// utils/cors.php

// Allow requests from Angular development server
header("Access-Control-Allow-Origin: http://localhost:4200");

// Allow specific HTTP methods
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Allow specific headers
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Allow credentials (cookies, authorization headers)
header("Access-Control-Allow-Credentials: true");

// Cache preflight response for 1 hour (3600 seconds)
header("Access-Control-Max-Age: 3600");

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Return 200 OK status for preflight requests
    http_response_code(200);
    exit;
}
