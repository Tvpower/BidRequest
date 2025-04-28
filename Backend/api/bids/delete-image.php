<?php
require_once '../../utils/include_cors.php';
require_once '../../utils/bid_controller.php';

// Handle image deletion for product bids
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $controller = new BidsController();
    $controller->deleteBidImage();
} else {
    Response::error('Method not allowed', 405);
}
?>
