<?php
// api/bids/accept.php

// Set headers
global $db;
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database connection
require_once '../../config/database.php';

// Initialize response array
$response = [
  'success' => false,
  'message' => '',
  'data' => null
];

// Simple authorization check (This should be improved in production)
function authorize() {
  $headers = getallheaders();
  if (!isset($headers['Authorization'])) {
    return false;
  }

  // In production, use a proper JWT validation
  $token = $headers['Authorization'];
  $token = str_replace('Bearer ', '', $token);

  try {
    $payload = json_decode(base64_decode($token), true);

    // Check if token is expired
    if (isset($payload['exp']) && $payload['exp'] < time()) {
      return false;
    }

    return $payload;
  } catch (Exception $e) {
    return false;
  }
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  $response['message'] = 'Only POST requests are allowed';
  http_response_code(405); // Method Not Allowed
  echo json_encode($response);
  exit;
}

// Check authorization
$auth = authorize();
if (!$auth) {
  $response['message'] = 'Unauthorized access';
  http_response_code(401);
  echo json_encode($response);
  exit;
}

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate input
if (empty($data->bid_id)) {
  $response['message'] = 'Bid ID is required';
  http_response_code(400);
  echo json_encode($response);
  exit;
}

$bid_id = $data->bid_id;

try {
  // Create DB instance and connect
  $database = new Database();
  $db = $database->connect();

  // Start transaction
  $db->beginTransaction();

  // Get bid and request details
  $stmt = $db->prepare("
        SELECT
            b.request_id, b.seller_id, b.price, b.status as bid_status,
            r.user_id as requester_id, r.status as request_status
        FROM
            bids b
        JOIN
            requests r ON b.request_id = r.request_id
        WHERE
            b.bid_id = :bid_id
    ");

  $stmt->bindParam(':bid_id', $bid_id);
  $stmt->execute();

  if ($stmt->rowCount() === 0) {
    $response['message'] = 'Bid not found';
    http_response_code(404);
    echo json_encode($response);
    exit;
  }

  $bid_data = $stmt->fetch(PDO::FETCH_ASSOC);

  // Verify the user is the requester
  if ($bid_data['requester_id'] != $auth['user_id']) {
    $response['message'] = 'You do not have permission to accept this bid';
    http_response_code(403);
    echo json_encode($response);
    exit;
  }

  // Check if bid is still active
  if ($bid_data['bid_status'] !== 'active') {
    $response['message'] = 'This bid is no longer active';
    http_response_code(400);
    echo json_encode($response);
    exit;
  }

  // Check if request is still active
  if ($bid_data['request_status'] !== 'active') {
    $response['message'] = 'This request is no longer active';
    http_response_code(400);
    echo json_encode($response);
    exit;
  }

  // Update the accepted bid status
  $update_bid = $db->prepare("
        UPDATE bids
        SET status = 'accepted'
        WHERE bid_id = :bid_id
    ");

  $update_bid->bindParam(':bid_id', $bid_id);
  $update_bid->execute();

  // Mark all other bids as rejected
  $reject_bids = $db->prepare("
        UPDATE bids
        SET status = 'rejected'
        WHERE request_id = :request_id AND bid_id != :bid_id
    ");

  $reject_bids->bindParam(':request_id', $bid_data['request_id']);
  $reject_bids->bindParam(':bid_id', $bid_id);
  $reject_bids->execute();

  // Update request status to 'in_progress'
  $update_request = $db->prepare("
        UPDATE requests
        SET status = 'in_progress'
        WHERE request_id = :request_id
    ");

  $update_request->bindParam(':request_id', $bid_data['request_id']);
  $update_request->execute();

  // Create a transaction record
  $create_transaction = $db->prepare("
        INSERT INTO transactions
        (request_id, bid_id, user_id, seller_id, amount, payment_status, payment_date)
        VALUES
        (:request_id, :bid_id, :user_id, :seller_id, :amount, 'pending', NULL)
    ");

  $create_transaction->bindParam(':request_id', $bid_data['request_id']);
  $create_transaction->bindParam(':bid_id', $bid_id);
  $create_transaction->bindParam(':user_id', $auth['user_id']);
  $create_transaction->bindParam(':seller_id', $bid_data['seller_id']);
  $create_transaction->bindParam(':amount', $bid_data['price']);
  $create_transaction->execute();

  $transaction_id = $db->lastInsertId();

  // Commit the transaction
  $db->commit();

  $response['success'] = true;
  $response['message'] = 'Bid accepted successfully';
  $response['data'] = [
    'transaction_id' => $transaction_id,
    'request_id' => $bid_data['request_id'],
    'bid_id' => $bid_id
  ];
  http_response_code(200);
} catch (Exception $e) {
  // If something goes wrong, roll back the transaction
  if ($db->inTransaction()) {
    $db->rollBack();
  }

  $response['message'] = 'Error: ' . $e->getMessage();
  http_response_code(500);
}

// Return response
echo json_encode($response);
