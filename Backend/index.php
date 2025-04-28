<?php
/**
 * BidRequest - Main Application Entry Point
 *
 * This is the main entry point for the BidRequest application.
 * It handles routing requests to the appropriate API endpoints.
 */

//set error reporting (disable in production by setting to 0)
error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_DEBUG') === 'true' ? 1 : 0);

//define base path constant
define('BASE_PATH', __DIR__);

//set headers for API responses
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

//handle preflight OPTIONS requests for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  header("HTTP/1.1 200 OK");
  exit;
}

//include database and response utilities
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/utils/response.php';

//parse request URI
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/'; // Update this if your app is in a subdirectory

//remove base path and query string
$path = parse_url($requestUri, PHP_URL_PATH);
$path = substr($path, strlen($basePath));
$path = trim($path, '/');

//if no path, redirect to welcome page or serve documentation
if (empty($path)) {
  include BASE_PATH . '/welcome.php';
  exit;
}

//extract path segments for routing
$segments = explode('/', $path);

//basic routing to API endpoints
if ($segments[0] === 'api' && isset($segments[1])) {
  $resourceType = $segments[1]; // e.g., 'auth', 'bids', 'requests', 'categories'

  //endpoint file (index.php is default if not specified)
  $endpoint = isset($segments[2]) ? $segments[2] : 'index';

  //if there's an ID parameter in the URL
  $id = null;
  if (isset($segments[3])) {
    $id = $segments[3];
  }

  //construct file path to the appropriate controller
  $apiFilePath = BASE_PATH . '/api/' . $resourceType . '/' . $endpoint . '.php';

  if (file_exists($apiFilePath)) {
    //pass ID parameter if present
    if ($id !== null) {
      $_GET['id'] = $id;
    }

    //load the API endpoint
    require $apiFilePath;
    exit;
  }
}

//if no route matched, return 404
header("HTTP/1.1 404 Not Found");
echo json_encode([
  'success' => false,
  'message' => 'Endpoint not found: ' . $path,
  'data' => null
]);
exit;
