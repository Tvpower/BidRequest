<?php
require_once '../../utils/request_controller.php';

if (empty($_GET['id'])) {
  Response::error('Request ID is required', 400);
}

$request_id = $_GET['id'];
$controller = new RequestsController();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $controller->getRequest($request_id);
} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
  $controller->updateRequest($request_id);
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
  $controller->deleteRequest($request_id);
} else {
  Response::error('Method not allowed', 405);
}
