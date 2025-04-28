
<?php
require_once '../../utils/bid_controller.php';

//Check if bid_id is provided
if (empty($_GET['id'])) {
  Response::error('Bid ID is required', 400);
}

$bid_id = $_GET['id'];
$controller = new BidsController();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $controller->getBid($bid_id);
} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
  $controller->updateBid($bid_id);
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
  $controller->deleteBid($bid_id);
} else {
  Response::error('Method not allowed', 405);
}
