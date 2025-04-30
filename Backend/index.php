<?php
/**
 * BidRequest - Main Application Entry Point
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_DEBUG') === 'true' ? 1 : 0);

// Define base path constant
const BASE_PATH = __DIR__;

// Set CORS headers - Update this for your actual domain in production
$allowed_origins = ['http://localhost:4200', 'https://bidrequest-app-a765r.ondigitalocean.app'];
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

if (in_array($origin, $allowed_origins)) {
  header("Access-Control-Allow-Origin: $origin");
}
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Handle preflight OPTIONS requests for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  header("HTTP/1.1 200 OK");
  exit;
}

// Load environment variables if .env file exists
if (file_exists(BASE_PATH . '/.env')) {
  $env = parse_ini_file(BASE_PATH . '/.env');
  foreach($env as $key => $value) {
    if (!getenv($key)) {
      putenv("$key=$value");
    }
  }
}

// Include database and response utilities
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/utils/response.php';

// Error handling for debugging
try {
  // Process request
  processRequest();
} catch (Exception $e) {
  // Log error
  error_log('Error: ' . $e->getMessage());
  // Return error response
  header("HTTP/1.1 500 Internal Server Error");
  echo json_encode([
    'success' => false,
    'message' => 'Server Error: ' . $e->getMessage(),
    'data' => null
  ]);
  exit;
}

/**
 * Process the request
 */
function processRequest() {
  // Parse request URI
  $requestUri = $_SERVER['REQUEST_URI'];

  // Remove query string and trailing slashes
  $path = parse_url($requestUri, PHP_URL_PATH);
  $path = trim($path, '/');

  // If no path, redirect to the welcome page
  if (empty($path)) {
    include BASE_PATH . '/welcome.php';
    exit;
  }

  // Extract path segments for routing
  $segments = explode('/', $path);

  // Basic routing to API endpoints
  if (count($segments) > 0 && $segments[0] === 'api' && isset($segments[1])) {
    $resourceType = $segments[1]; // e.g., 'auth', 'bids', 'requests', 'categories'

    // Endpoint file (index.php is default if not specified)
    $endpoint = isset($segments[2]) ? $segments[2] : 'index';

    // If there's an ID parameter in the URL
    $id = null;
    if (isset($segments[3])) {
      $id = $segments[3];
    }

    // Construct file path to the appropriate controller
    $apiFilePath = BASE_PATH . '/api/' . $resourceType . '/' . $endpoint . '.php';

    if (file_exists($apiFilePath)) {
      // Pass ID parameter if present
      if ($id !== null) {
        $_GET['id'] = $id;
      }

      // Load the API endpoint
      require $apiFilePath;
      exit;
    }
  }

  // If no route matched, return 404
  header("HTTP/1.1 404 Not Found");
  echo json_encode([
    'success' => false,
    'message' => 'Endpoint not found: ' . $path,
    'data' => null
  ]);
  exit;
}
?>
