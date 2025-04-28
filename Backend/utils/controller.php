<?php
// utils/controller.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/response.php';

class Controller {
  protected $db;
  protected $auth;

  public function __construct() {
    // Set common headers
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    // Connect to database
    $database = new Database();
    $this->db = $database->connect();

    // Get auth payload if available
    $this->auth = Auth::authorize();
  }

  protected function requireAuth() {
    if (!$this->auth) {
      Response::unauthorized();
    }
  }

  protected function getRequestData() {
    return json_decode(file_get_contents("php://input"));
  }

  protected function validateSellerAuth() {
    $user_id = $this->auth['user_id'];

    $seller_stmt = $this->db->prepare("
            SELECT seller_id FROM sellers WHERE user_id = :user_id
        ");

    $seller_stmt->bindParam(':user_id', $user_id);
    $seller_stmt->execute();

    if ($seller_stmt->rowCount() === 0) {
      Response::error('Only sellers can perform this action', 403);
    }

    return $seller_stmt->fetch(PDO::FETCH_ASSOC)['seller_id'];
  }

  protected function checkActiveRequest($request_id) {
    $request_stmt = $this->db->prepare("
            SELECT status, expiration_date FROM requests
            WHERE request_id = :request_id
        ");

    $request_stmt->bindParam(':request_id', $request_id);
    $request_stmt->execute();

    if ($request_stmt->rowCount() === 0) {
      Response::notFound('Request not found');
    }

    $request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);

    // Check if request is active
    if ($request_data['status'] !== 'active') {
      Response::error('Cannot perform action on inactive request', 400);
    }

    // Check if request is expired
    $expiration_date = new DateTime($request_data['expiration_date']);
    $now = new DateTime();

    if ($expiration_date < $now) {
      Response::error('Request has expired', 400);
    }

    return $request_data;
  }
}
?>
