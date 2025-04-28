<?php
// Include CORS headers
require_once '../../utils/include_cors.php';
require_once '../../utils/auth_controller.php';

$controller = new AuthController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $controller->register();
} else {
  Response::error('Method not allowed', 405);
}
