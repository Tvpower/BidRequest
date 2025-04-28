<?php
require_once '../../utils/include_cors.php';
require_once '../../utils/bid_controller.php';

// Handle setting a primary image for product bids
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $controller = new BidsController();
    $controller->setPrimaryImage();
} else {
    Response::error('Method not allowed', 405);
}
?>
