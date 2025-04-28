<?php
require_once '../../utils/include_cors.php';
require_once '../../utils/request_controller.php';

$controller = new RequestsController();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $controller->listRequests();
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $controller->createRequest();
} else {
  Response::error('Method not allowed', 405);
}
