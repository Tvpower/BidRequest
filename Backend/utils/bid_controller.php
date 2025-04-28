// api/bids/bids_controller.php
<?php
require_once __DIR__ . '/controller.php';

class BidsController extends Controller {

  public function listBids() {
    try {
      //default query params
      $where = "WHERE 1=1";
      $params = [];

      //filter by request
      if (isset($_GET['request_id']) && !empty($_GET['request_id'])) {
        $where .= " AND b.request_id = :request_id";
        $params[':request_id'] = $_GET['request_id'];
      }

      //filter by seller
      if (isset($_GET['seller_id']) && !empty($_GET['seller_id'])) {
        $where .= " AND b.seller_id = :seller_id";
        $params[':seller_id'] = $_GET['seller_id'];
      }

      //filter by status
      if (isset($_GET['status']) && !empty($_GET['status'])) {
        $where .= " AND b.status = :status";
        $params[':status'] = $_GET['status'];
      }

      //prepare pagination
      $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
      $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
      $offset = ($page - 1) * $limit;

      //count records for paginations
      $count_stmt = $this->db->prepare("
                SELECT COUNT(*) as total FROM bids b
                $where
            ");

      //bind parameters for count query
      foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
      }

      $count_stmt->execute();
      $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

      //main query
      $stmt = $this->db->prepare("
                SELECT
                    b.bid_id, b.request_id, b.seller_id, b.price, b.description,
                    b.delivery_time, b.submission_date, b.status,
                    s.company_name, s.rating,
                    r.title as request_title
                FROM
                    bids b
                JOIN
                    sellers s ON b.seller_id = s.seller_id
                JOIN
                    requests r ON b.request_id = r.request_id
                $where
                ORDER BY b.price ASC
                LIMIT :offset, :limit
            ");

      //bind params for main query
      foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
      }

      $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
      $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);

      $stmt->execute();

      $bids = [];
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $bids[] = $row;
      }

      Response::success([
        'bids' => $bids,
        'pagination' => [
          'total' => $total_records,
          'page' => $page,
          'limit' => $limit,
          'total_pages' => ceil($total_records / $limit)
        ]
      ]);

    } catch (Exception $e) {
      Response::error('Error: ' . $e->getMessage(), 500);
    }
  }

  public function createBid() {
    $this->requireAuth();

    $data = $this->getRequestData();

    //validate input
    if (!isset($data->request_id) || !isset($data->price) || !isset($data->description) ||
      empty($data->request_id) || empty($data->price) || empty($data->description)) {
      Response::error('Request ID, price, and description are required', 400);
    }

    try {
      $seller_id = $this->validateSellerAuth();

      //check if req is active
      $this->checkActiveRequest($data->request_id);

      //check if seller already place a bid on the request
      $existing_bid_stmt = $this->db->prepare("
                SELECT bid_id FROM bids
                WHERE request_id = :request_id AND seller_id = :seller_id
            ");

      $existing_bid_stmt->bindParam(':request_id', $data->request_id);
      $existing_bid_stmt->bindParam(':seller_id', $seller_id);
      $existing_bid_stmt->execute();

      if ($existing_bid_stmt->rowCount() > 0) {
        Response::error('You have already placed a bid on this request', 409);
      }

      //prepare delivery time
      $delivery_time = isset($data->delivery_time) ? $data->delivery_time : null;

      //create new bid
      $stmt = $this->db->prepare("
                INSERT INTO bids
                (request_id, seller_id, price, description, delivery_time, submission_date, status)
                VALUES
                (:request_id, :seller_id, :price, :description, :delivery_time, NOW(), 'active')
            ");

      //bind params
      $stmt->bindParam(':request_id', $data->request_id);
      $stmt->bindParam(':seller_id', $seller_id);
      $stmt->bindParam(':price', $data->price);
      $stmt->bindParam(':description', $data->description);
      $stmt->bindParam(':delivery_time', $delivery_time);

      //execute the query
      $stmt->execute();

      $bid_id = $this->db->lastInsertId();

      Response::success([
        'bid_id' => $bid_id,
        'price' => $data->price
      ], 'Bid placed successfully', 201);

    } catch (Exception $e) {
      Response::error('Error: ' . $e->getMessage(), 500);
    }
  }

  public function getBid($bid_id) {
    try {
      //fetch bid details
      $stmt = $this->db->prepare("
                SELECT
                    b.bid_id, b.request_id, b.seller_id, b.price, b.description,
                    b.delivery_time, b.submission_date, b.status,
                    s.company_name, s.rating, s.user_id as seller_user_id,
                    r.title as request_title, r.user_id as requester_id
                FROM
                    bids b
                JOIN
                    sellers s ON b.seller_id = s.seller_id
                JOIN
                    requests r ON b.request_id = r.request_id
                WHERE
                    b.bid_id = :bid_id
            ");

      $stmt->bindParam(':bid_id', $bid_id);
      $stmt->execute();

      if ($stmt->rowCount() === 0) {
        Response::notFound('Bid not found');
      }

      $bid = $stmt->fetch(PDO::FETCH_ASSOC);

      Response::success($bid);

    } catch (Exception $e) {
      Response::error('Error: ' . $e->getMessage(), 500);
    }
  }

  public function updateBid($bid_id) {
    $this->requireAuth();

    $data = $this->getRequestData();

    try {
      //check if bid gets seller info
      $stmt = $this->db->prepare("
                SELECT
                    b.request_id, b.seller_id, b.status,
                    s.user_id as seller_user_id,
                    r.status as request_status,
                    r.expiration_date
                FROM
                    bids b
                JOIN
                    sellers s ON b.seller_id = s.seller_id
                JOIN
                    requests r ON b.request_id = r.request_id
                WHERE
                    b.bid_id = :bid_id
            ");

      $stmt->bindParam(':bid_id', $bid_id);
      $stmt->execute();

      if ($stmt->rowCount() === 0) {
        Response::notFound('Bid not found');
      }

      $bid_data = $stmt->fetch(PDO::FETCH_ASSOC);

      //veirfy owner
      if ($bid_data['seller_user_id'] != $this->auth['user_id']) {
        Response::error('You do not have permission to update this bid', 403);
      }

      //check if bid can be updated
      if ($bid_data['status'] !== 'active') {
        Response::error('Cannot update a bid that has been ' . $bid_data['status'], 400);
      }

      // Check if request is still active
      if ($bid_data['request_status'] !== 'active') {
        Response::error('Cannot update bid on inactive request', 400);
      }

      // Check if request has expired
      $expiration_date = new DateTime($bid_data['expiration_date']);
      $now = new DateTime();

      if ($expiration_date < $now) {
        Response::error('Request has expired', 400);
      }

      // Prepare update fields and values
      $updateFields = [];
      $params = [':bid_id' => $bid_id];

      if (isset($data->price) && !empty($data->price)) {
        $updateFields[] = "price = :price";
        $params[':price'] = $data->price;
      }

      if (isset($data->description) && !empty($data->description)) {
        $updateFields[] = "description = :description";
        $params[':description'] = $data->description;
      }

      if (isset($data->delivery_time) && !empty($data->delivery_time)) {
        $updateFields[] = "delivery_time = :delivery_time";
        $params[':delivery_time'] = $data->delivery_time;
      }

      // If there are fields to update
      if (!empty($updateFields)) {
        $sql = "UPDATE bids SET " . implode(", ", $updateFields) . " WHERE bid_id = :bid_id";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
          $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        Response::success(null, 'Bid updated successfully');
      } else {
        Response::error('No fields to update', 400);
      }

    } catch (Exception $e) {
      Response::error('Error: ' . $e->getMessage(), 500);
    }
  }

  public function deleteBid($bid_id) {
    $this->requireAuth();

    try {
      // Check if bid exists and get seller info
      $stmt = $this->db->prepare("
                SELECT
                    b.status,
                    s.user_id as seller_user_id
                FROM
                    bids b
                JOIN
                    sellers s ON b.seller_id = s.seller_id
                WHERE
                    b.bid_id = :bid_id
            ");

      $stmt->bindParam(':bid_id', $bid_id);
      $stmt->execute();

      if ($stmt->rowCount() === 0) {
        Response::notFound('Bid not found');
      }

      $bid_data = $stmt->fetch(PDO::FETCH_ASSOC);

      // Verify ownership
      if ($bid_data['seller_user_id'] != $this->auth['user_id']) {
        Response::error('You do not have permission to withdraw this bid', 403);
      }

      // Check if bid can be withdrawn (not accepted)
      if ($bid_data['status'] === 'accepted') {
        Response::error('Cannot withdraw an accepted bid', 400);
      }

      // Delete the bid
      $del_stmt = $this->db->prepare("DELETE FROM bids WHERE bid_id = :bid_id");
      $del_stmt->bindParam(':bid_id', $bid_id);
      $del_stmt->execute();

      Response::success(null, 'Bid withdrawn successfully');

    } catch (Exception $e) {
      Response::error('Error: ' . $e->getMessage(), 500);
    }
  }
}
?>
