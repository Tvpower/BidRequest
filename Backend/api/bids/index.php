<?php
require_once '../../utils/include_cors.php';
require_once '../../utils/bid_controller.php';

$controller = new BidsController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $controller->createBid();
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $controller->listBids();
} else {
  Response::error('Method not allowed', 405);
}
