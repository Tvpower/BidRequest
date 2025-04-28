<?php
require_once '../../utils/include_cors.php';
require_once '../../utils/bid_controller.php';

// Handle image uploads for product bids
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new BidsController();
    $controller->uploadBidImage();
} else {
    Response::error('Method not allowed', 405);
}
?>
