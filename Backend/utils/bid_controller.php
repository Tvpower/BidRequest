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
      if (!empty($_GET['request_id'])) {
        $where .= " AND b.request_id = :request_id";
        $params[':request_id'] = $_GET['request_id'];
      }

      //filter by seller
      if (!empty($_GET['seller_id'])) {
        $where .= " AND b.seller_id = :seller_id";
        $params[':seller_id'] = $_GET['seller_id'];
      }

      //filter by status
      if (!empty($_GET['status'])) {
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
                    b.product_condition, b.product_brand, b.product_model,
                    b.delivery_time, b.submission_date, b.status,
                    s.company_name, s.rating,
                    r.title as request_title,
                    r.type as request_type
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
        // If this is a product bid, fetch images
        if ($row['request_type'] === 'product') {
          $images_stmt = $this->db->prepare("
                    SELECT
                        image_id, bid_id, image_url, is_primary, upload_date
                    FROM
                        bid_images
                    WHERE
                        bid_id = :bid_id
                    ORDER BY
                        is_primary DESC
                ");
          $images_stmt->bindParam(':bid_id', $row['bid_id']);
          $images_stmt->execute();
          
          $images = [];
          while ($image = $images_stmt->fetch(PDO::FETCH_ASSOC)) {
            $images[] = $image;
          }
          
          $row['images'] = $images;
        }
        
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
    if (empty($data->request_id) || empty($data->price) || empty($data->description)) {
      Response::error('Request ID, price, and description are required', 400);
    }
    
    // Get request type to determine if this is a product or service bid
    try {
      $type_stmt = $this->db->prepare("SELECT type FROM requests WHERE request_id = :request_id");
      $type_stmt->bindParam(':request_id', $data->request_id);
      $type_stmt->execute();
      
      if ($type_stmt->rowCount() === 0) {
        Response::error('Request not found', 404);
      }
      
      $request_type = $type_stmt->fetch(PDO::FETCH_ASSOC)['type'];
      
      // For product bids, validate product-specific fields if required
      if ($request_type === 'product' && empty($data->product_condition)) {
        Response::error('Product condition is required for product bids', 400);
      }
    } catch (Exception $e) {
      Response::error('Error: ' . $e->getMessage(), 500);
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

      //prepare delivery time and product fields
      $delivery_time = isset($data->delivery_time) ? $data->delivery_time : null;
      $product_condition = isset($data->product_condition) ? $data->product_condition : null;
      $product_brand = isset($data->product_brand) ? $data->product_brand : null;
      $product_model = isset($data->product_model) ? $data->product_model : null;

      //create new bid
      $stmt = $this->db->prepare("
                INSERT INTO bids
                (request_id, seller_id, price, description, product_condition, product_brand, product_model, delivery_time, submission_date, status)
                VALUES
                (:request_id, :seller_id, :price, :description, :product_condition, :product_brand, :product_model, :delivery_time, NOW(), 'active')
            ");

      //bind params
      $stmt->bindParam(':request_id', $data->request_id);
      $stmt->bindParam(':seller_id', $seller_id);
      $stmt->bindParam(':price', $data->price);
      $stmt->bindParam(':description', $data->description);
      $stmt->bindParam(':product_condition', $product_condition);
      $stmt->bindParam(':product_brand', $product_brand);
      $stmt->bindParam(':product_model', $product_model);
      $stmt->bindParam(':delivery_time', $delivery_time);

      //execute the query
      $stmt->execute();

      $bid_id = $this->db->lastInsertId();
      
      // Handle image uploads for product bids
      if ($request_type === 'product' && isset($data->images) && is_array($data->images)) {
        foreach ($data->images as $index => $image) {
          // Check if this is the primary image (first one is primary by default)
          $is_primary = $index === 0 ? true : (isset($image->is_primary) ? $image->is_primary : false);
          
          $image_stmt = $this->db->prepare("
                    INSERT INTO bid_images
                    (bid_id, image_url, is_primary, upload_date)
                    VALUES
                    (:bid_id, :image_url, :is_primary, NOW())
                ");
                
          $image_stmt->bindParam(':bid_id', $bid_id);
          $image_stmt->bindParam(':image_url', $image->image_url);
          $image_stmt->bindParam(':is_primary', $is_primary, PDO::PARAM_BOOL);
          $image_stmt->execute();
        }
      }

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
                    b.product_condition, b.product_brand, b.product_model,
                    b.delivery_time, b.submission_date, b.status,
                    s.company_name, s.rating, s.user_id as seller_user_id,
                    r.title as request_title, r.user_id as requester_id,
                    r.type as request_type
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
      
      // If this is a product bid, fetch images
      if ($bid['request_type'] === 'product') {
        $images_stmt = $this->db->prepare("
                SELECT
                    image_id, bid_id, image_url, is_primary, upload_date
                FROM
                    bid_images
                WHERE
                    bid_id = :bid_id
                ORDER BY
                    is_primary DESC
            ");
        $images_stmt->bindParam(':bid_id', $bid_id);
        $images_stmt->execute();
        
        $images = [];
        while ($image = $images_stmt->fetch(PDO::FETCH_ASSOC)) {
          $images[] = $image;
        }
        
        $bid['images'] = $images;
      }

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

      if (!empty($data->price)) {
        $updateFields[] = "price = :price";
        $params[':price'] = $data->price;
      }

      if (!empty($data->description)) {
        $updateFields[] = "description = :description";
        $params[':description'] = $data->description;
      }

      if (!empty($data->delivery_time)) {
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
  
  public function uploadBidImage() {
    $this->requireAuth();
    
    // Validate seller status
    $seller_id = $this->validateSellerAuth();
    
    // Check if bid_id is provided
    if (empty($_POST['bid_id'])) {
      Response::error('Bid ID is required', 400);
    }
    
    $bid_id = $_POST['bid_id'];
    
    // Verify that the bid belongs to this seller
    $bid_stmt = $this->db->prepare("
            SELECT b.bid_id, r.type 
            FROM bids b 
            JOIN requests r ON b.request_id = r.request_id 
            WHERE b.bid_id = :bid_id AND b.seller_id = :seller_id");
    $bid_stmt->bindParam(':bid_id', $bid_id);
    $bid_stmt->bindParam(':seller_id', $seller_id);
    $bid_stmt->execute();
    
    if ($bid_stmt->rowCount() === 0) {
      Response::error('Bid not found or you do not have permission to add images to this bid', 403);
    }
    
    $bid_data = $bid_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if this is a product bid
    if ($bid_data['type'] !== 'product') {
      Response::error('Images can only be added to product bids', 400);
    }
    
    // Handle file upload
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
      Response::error('No image file uploaded or upload error', 400);
    }
    
    try {
      // Create uploads directory if it doesn't exist
      $upload_dir = __DIR__ . '/../uploads/bids/';
      if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
      }
      
      // Generate unique filename
      $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
      $filename = uniqid('bid_' . $bid_id . '_') . '.' . $file_extension;
      $target_file = $upload_dir . $filename;
      
      // Move uploaded file to target directory
      if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
        throw new Exception('Failed to move uploaded file');
      }
      
      // Determine if this should be the primary image
      $is_primary = isset($_POST['is_primary']) && $_POST['is_primary'] === 'true';
      
      // If setting as primary, update any existing primary images
      if ($is_primary) {
        $update_stmt = $this->db->prepare("UPDATE bid_images SET is_primary = 0 WHERE bid_id = :bid_id");
        $update_stmt->bindParam(':bid_id', $bid_id);
        $update_stmt->execute();
      }
      
      // If this is the first image, make it primary by default
      $count_stmt = $this->db->prepare("SELECT COUNT(*) as count FROM bid_images WHERE bid_id = :bid_id");
      $count_stmt->bindParam(':bid_id', $bid_id);
      $count_stmt->execute();
      $image_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
      
      if ($image_count === 0) {
        $is_primary = true;
      }
      
      // Save image record in database
      $image_url = '/uploads/bids/' . $filename; // Relative URL for frontend use
      
      $stmt = $this->db->prepare("
                INSERT INTO bid_images
                (bid_id, image_url, is_primary, upload_date)
                VALUES
                (:bid_id, :image_url, :is_primary, NOW())
            ");
      
      $stmt->bindParam(':bid_id', $bid_id);
      $stmt->bindParam(':image_url', $image_url);
      $stmt->bindParam(':is_primary', $is_primary, PDO::PARAM_BOOL);
      $stmt->execute();
      
      $image_id = $this->db->lastInsertId();
      
      Response::success([
        'image_id' => $image_id,
        'bid_id' => $bid_id,
        'image_url' => $image_url,
        'is_primary' => $is_primary
      ], 'Image uploaded successfully', 201);
      
    } catch (Exception $e) {
      Response::error('Error uploading image: ' . $e->getMessage(), 500);
    }
  }
  
  public function deleteBidImage() {
    $this->requireAuth();
    
    // Get JSON data from the request body
    $data = $this->getRequestData();
    
    // Check if image_id is provided
    if (empty($data->image_id)) {
      Response::error('Image ID is required', 400);
    }
    
    $image_id = $data->image_id;
    $seller_id = $this->validateSellerAuth();
    
    try {
      // First, get the image details and verify ownership
      $image_stmt = $this->db->prepare("
                SELECT i.image_id, i.bid_id, i.image_url, i.is_primary, b.seller_id
                FROM bid_images i
                JOIN bids b ON i.bid_id = b.bid_id
                WHERE i.image_id = :image_id
            ");
      $image_stmt->bindParam(':image_id', $image_id);
      $image_stmt->execute();
      
      if ($image_stmt->rowCount() === 0) {
        Response::error('Image not found', 404);
      }
      
      $image_data = $image_stmt->fetch(PDO::FETCH_ASSOC);
      
      // Verify that the image belongs to a bid owned by this seller
      if ($image_data['seller_id'] != $seller_id) {
        Response::error('You do not have permission to delete this image', 403);
      }
      
      // Get the file path from the database
      $image_path = __DIR__ . '/..'. $image_data['image_url'];
      
      // Begin transaction
      $this->db->beginTransaction();
      
      // Delete the image record from the database
      $delete_stmt = $this->db->prepare("DELETE FROM bid_images WHERE image_id = :image_id");
      $delete_stmt->bindParam(':image_id', $image_id);
      $delete_stmt->execute();
      
      // If this was the primary image, set a new primary image if available
      if ($image_data['is_primary']) {
        $primary_stmt = $this->db->prepare("
                    UPDATE bid_images 
                    SET is_primary = 1 
                    WHERE bid_id = :bid_id 
                    ORDER BY upload_date DESC 
                    LIMIT 1
                ");
        $primary_stmt->bindParam(':bid_id', $image_data['bid_id']);
        $primary_stmt->execute();
      }
      
      // Commit transaction
      $this->db->commit();
      
      // Delete the physical file if it exists
      if (file_exists($image_path)) {
        unlink($image_path);
      }
      
      Response::success(null, 'Image deleted successfully');
      
    } catch (Exception $e) {
      // Rollback transaction if there was an error
      if ($this->db->inTransaction()) {
        $this->db->rollBack();
      }
      Response::error('Error deleting image: ' . $e->getMessage(), 500);
    }
  }
  
  public function setPrimaryImage() {
    $this->requireAuth();
    
    // Get JSON data from the request body
    $data = $this->getRequestData();
    
    // Check if image_id is provided
    if (empty($data->image_id)) {
      Response::error('Image ID is required', 400);
    }
    
    $image_id = $data->image_id;
    $seller_id = $this->validateSellerAuth();
    
    try {
      // First, get the image details and verify ownership
      $image_stmt = $this->db->prepare("
                SELECT i.image_id, i.bid_id, b.seller_id
                FROM bid_images i
                JOIN bids b ON i.bid_id = b.bid_id
                WHERE i.image_id = :image_id
            ");
      $image_stmt->bindParam(':image_id', $image_id);
      $image_stmt->execute();
      
      if ($image_stmt->rowCount() === 0) {
        Response::error('Image not found', 404);
      }
      
      $image_data = $image_stmt->fetch(PDO::FETCH_ASSOC);
      
      // Verify that the image belongs to a bid owned by this seller
      if ($image_data['seller_id'] != $seller_id) {
        Response::error('You do not have permission to modify this image', 403);
      }
      
      // Begin transaction
      $this->db->beginTransaction();
      
      // Reset all primary flags for this bid
      $reset_stmt = $this->db->prepare("UPDATE bid_images SET is_primary = 0 WHERE bid_id = :bid_id");
      $reset_stmt->bindParam(':bid_id', $image_data['bid_id']);
      $reset_stmt->execute();
      
      // Set the selected image as primary
      $primary_stmt = $this->db->prepare("UPDATE bid_images SET is_primary = 1 WHERE image_id = :image_id");
      $primary_stmt->bindParam(':image_id', $image_id);
      $primary_stmt->execute();
      
      // Commit transaction
      $this->db->commit();
      
      Response::success(null, 'Primary image set successfully');
      
    } catch (Exception $e) {
      // Rollback transaction if there was an error
      if ($this->db->inTransaction()) {
        $this->db->rollBack();
      }
      Response::error('Error setting primary image: ' . $e->getMessage(), 500);
    }
  }
}
?>
