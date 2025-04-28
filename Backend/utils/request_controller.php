<?php
// utils/request_controller.php
require_once __DIR__ . '/controller.php';

class RequestsController extends Controller {

  public function listRequests() {
    try {
      // Default query parameters
      $where = "WHERE 1=1";
      $params = [];

      // Filter by category
      if (!empty($_GET['category_id'])) {
        $where .= " AND r.category_id = :category_id";
        $params[':category_id'] = $_GET['category_id'];
      }

      // Filter by status
      if (!empty($_GET['status'])) {
        $where .= " AND r.status = :status";
        $params[':status'] = $_GET['status'];
      }

      // Filter by user (for "my requests" functionality)
      if (!empty($_GET['user_id'])) {
        // Check if user is authorized
        if ($this->auth && $this->auth['user_id'] == $_GET['user_id']) {
          $where .= " AND r.user_id = :user_id";
          $params[':user_id'] = $_GET['user_id'];
        } else {
          Response::unauthorized();
        }
      }

      // Prepare pagination
      $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
      $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
      $offset = ($page - 1) * $limit;

      // Count total records for pagination
      $count_stmt = $this->db->prepare("
                SELECT COUNT(*) as total FROM requests r
                $where
            ");

      foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
      }

      $count_stmt->execute();
      $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

      // Main query
      $stmt = $this->db->prepare("
                SELECT
                    r.request_id, r.title, r.description, r.creation_date, r.expiration_date, r.status,
                    u.username as requester_name,
                    c.name as category_name
                FROM
                    requests r
                JOIN
                    users u ON r.user_id = u.user_id
                JOIN
                    categories c ON r.category_id = c.category_id
                $where
                ORDER BY r.creation_date DESC
                LIMIT :offset, :limit
            ");

      foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
      }

      $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
      $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);

      $stmt->execute();

      $requests = [];
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $requests[] = $row;
      }

      Response::success([
        'requests' => $requests,
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

  public function createRequest() {
    $this->requireAuth();

    $data = $this->getRequestData();

    // Validate input
    if (!isset($data->title) || !isset($data->description) || !isset($data->category_id) ||
      empty($data->title) || empty($data->description) || empty($data->category_id)) {
      Response::error('Title, description, and category are required', 400);
    }

    try {
      // Begin transaction
      $this->db->beginTransaction();

      // Set expiration date (default to 14 days from now if not provided)
      $expiration_date = isset($data->expiration_date) && !empty($data->expiration_date)
        ? $data->expiration_date
        : date('Y-m-d H:i:s', strtotime('+14 days'));

      // Create request
      $stmt = $this->db->prepare("
                INSERT INTO requests
                (user_id, title, description, category_id, creation_date, expiration_date, status)
                VALUES
                (:user_id, :title, :description, :category_id, NOW(), :expiration_date, 'active')
            ");

      $stmt->bindParam(':user_id', $this->auth['user_id']);
      $stmt->bindParam(':title', $data->title);
      $stmt->bindParam(':description', $data->description);
      $stmt->bindParam(':category_id', $data->category_id);
      $stmt->bindParam(':expiration_date', $expiration_date);

      $stmt->execute();

      $request_id = $this->db->lastInsertId();

      // Add specifications if provided
      if (isset($data->specifications) && is_array($data->specifications)) {
        foreach ($data->specifications as $spec) {
          $spec_stmt = $this->db->prepare("
                        INSERT INTO request_details
                        (request_id, specification_type, specification_value)
                        VALUES
                        (:request_id, :type, :value)
                    ");

          $spec_stmt->bindParam(':request_id', $request_id);
          $spec_stmt->bindParam(':type', $spec->type);
          $spec_stmt->bindParam(':value', $spec->value);

          $spec_stmt->execute();
        }
      }

      $this->db->commit();

      Response::success([
        'request_id' => $request_id,
        'title' => $data->title
      ], 'Request created successfully', 201);

    } catch (Exception $e) {
      if ($this->db->inTransaction()) {
        $this->db->rollBack();
      }
      Response::error('Error: ' . $e->getMessage(), 500);
    }
  }

  public function getRequest($request_id) {
    try {
      // Fetch request details
      $stmt = $this->db->prepare("
                SELECT
                    r.request_id, r.user_id, r.title, r.description, r.category_id,
                    r.creation_date, r.expiration_date, r.status,
                    u.username as requester_name,
                    c.name as category_name
                FROM
                    requests r
                JOIN
                    users u ON r.user_id = u.user_id
                JOIN
                    categories c ON r.category_id = c.category_id
                WHERE
                    r.request_id = :request_id
            ");

      $stmt->bindParam(':request_id', $request_id);
      $stmt->execute();

      if ($stmt->rowCount() === 0) {
        Response::notFound('Request not found');
      }

      $request = $stmt->fetch(PDO::FETCH_ASSOC);

      // Fetch specifications
      $spec_stmt = $this->db->prepare("
                SELECT
                    detail_id, specification_type, specification_value
                FROM
                    request_details
                WHERE
                    request_id = :request_id
            ");

      $spec_stmt->bindParam(':request_id', $request_id);
      $spec_stmt->execute();

      $specifications = [];
      while ($spec = $spec_stmt->fetch(PDO::FETCH_ASSOC)) {
        $specifications[] = $spec;
      }

      // Fetch bids
      $bid_stmt = $this->db->prepare("
                SELECT
                    b.bid_id, b.seller_id, b.price, b.description, b.delivery_time,
                    b.submission_date, b.status,
                    s.company_name, s.rating
                FROM
                    bids b
                JOIN
                    sellers s ON b.seller_id = s.seller_id
                WHERE
                    b.request_id = :request_id
                ORDER BY
                    b.price ASC
            ");

      $bid_stmt->bindParam(':request_id', $request_id);
      $bid_stmt->execute();

      $bids = [];
      while ($bid = $bid_stmt->fetch(PDO::FETCH_ASSOC)) {
        $bids[] = $bid;
      }

      // Add to response
      $request['specifications'] = $specifications;
      $request['bids'] = $bids;

      Response::success($request);

    } catch (Exception $e) {
      Response::error('Error: ' . $e->getMessage(), 500);
    }
  }

  public function updateRequest($request_id) {
    $this->requireAuth();

    $data = $this->getRequestData();

    try {
      // Check if user owns this request
      $stmt = $this->db->prepare("
                SELECT user_id, status FROM requests WHERE request_id = :request_id
            ");

      $stmt->bindParam(':request_id', $request_id);
      $stmt->execute();

      if ($stmt->rowCount() === 0) {
        Response::notFound('Request not found');
      }

      $request_data = $stmt->fetch(PDO::FETCH_ASSOC);

      // Verify ownership
      if ($request_data['user_id'] != $this->auth['user_id']) {
        Response::error('You do not have permission to update this request', 403);
      }

      // Check if request can be updated
      if ($request_data['status'] === 'closed' || $request_data['status'] === 'completed') {
        Response::error('Cannot update a closed or completed request', 400);
      }

      // Begin transaction
      $this->db->beginTransaction();

      // Prepare update fields
      $updateFields = [];
      $params = [':request_id' => $request_id];

      if (isset($data->title) && !empty($data->title)) {
        $updateFields[] = "title = :title";
        $params[':title'] = $data->title;
      }

      if (isset($data->description) && !empty($data->description)) {
        $updateFields[] = "description = :description";
        $params[':description'] = $data->description;
      }

      if (isset($data->category_id) && !empty($data->category_id)) {
        $updateFields[] = "category_id = :category_id";
        $params[':category_id'] = $data->category_id;
      }

      if (isset($data->expiration_date) && !empty($data->expiration_date)) {
        $updateFields[] = "expiration_date = :expiration_date";
        $params[':expiration_date'] = $data->expiration_date;
      }

      if (isset($data->status) && !empty($data->status)) {
        $updateFields[] = "status = :status";
        $params[':status'] = $data->status;
      }

      // Update request if fields were provided
      if (!empty($updateFields)) {
        $sql = "UPDATE requests SET " . implode(", ", $updateFields) . " WHERE request_id = :request_id";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
          $stmt->bindValue($key, $value);
        }

        $stmt->execute();
      }

      // Update specifications if provided
      if (isset($data->specifications) && is_array($data->specifications)) {
        // Delete existing specs
        $del_stmt = $this->db->prepare("DELETE FROM request_details WHERE request_id = :request_id");
        $del_stmt->bindParam(':request_id', $request_id);
        $del_stmt->execute();

        // Add new specs
        foreach ($data->specifications as $spec) {
          $spec_stmt = $this->db->prepare("
                        INSERT INTO request_details
                        (request_id, specification_type, specification_value)
                        VALUES
                        (:request_id, :type, :value)
                    ");

          $spec_stmt->bindParam(':request_id', $request_id);
          $spec_stmt->bindParam(':type', $spec->type);
          $spec_stmt->bindParam(':value', $spec->value);

          $spec_stmt->execute();
        }
      }

      $this->db->commit();

      Response::success(null, 'Request updated successfully');

    } catch (Exception $e) {
      if ($this->db->inTransaction()) {
        $this->db->rollBack();
      }
      Response::error('Error: ' . $e->getMessage(), 500);
    }
  }

  public function deleteRequest($request_id) {
    $this->requireAuth();

    try {
      // Check if user owns this request
      $stmt = $this->db->prepare("
                SELECT user_id, status FROM requests WHERE request_id = :request_id
            ");

      $stmt->bindParam(':request_id', $request_id);
      $stmt->execute();

      if ($stmt->rowCount() === 0) {
        Response::notFound('Request not found');
      }

      $request_data = $stmt->fetch(PDO::FETCH_ASSOC);

      // Verify ownership
      if ($request_data['user_id'] != $this->auth['user_id']) {
        Response::error('You do not have permission to delete this request', 403);
      }

      // Check if request has active bids
      $bid_stmt = $this->db->prepare("
                SELECT COUNT(*) as bid_count
                FROM bids
                WHERE request_id = :request_id AND status = 'active'
            ");

      $bid_stmt->bindParam(':request_id', $request_id);
      $bid_stmt->execute();

      $bid_count = $bid_stmt->fetch(PDO::FETCH_ASSOC)['bid_count'];

      if ($bid_count > 0) {
        // Close instead of delete if has bids
        $update_stmt = $this->db->prepare("
                    UPDATE requests
                    SET status = 'closed', expiration_date = NOW()
                    WHERE request_id = :request_id
                ");

        $update_stmt->bindParam(':request_id', $request_id);
        $update_stmt->execute();

        Response::success(null, 'Request has active bids and has been closed instead of deleted');
      } else {
        // Begin transaction
        $this->db->beginTransaction();

        // Delete request details
        $del_details = $this->db->prepare("DELETE FROM request_details WHERE request_id = :request_id");
        $del_details->bindParam(':request_id', $request_id);
        $del_details->execute();

        // Delete request
        $del_request = $this->db->prepare("DELETE FROM requests WHERE request_id = :request_id");
        $del_request->bindParam(':request_id', $request_id);
        $del_request->execute();

        $this->db->commit();

        Response::success(null, 'Request deleted successfully');
      }
    } catch (Exception $e) {
      if ($this->db->inTransaction()) {
        $this->db->rollBack();
      }

      Response::error('Error: ' . $e->getMessage(), 500);
    }
  }
}
?>
