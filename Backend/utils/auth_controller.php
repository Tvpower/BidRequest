<?php
// utils/auth_controller.php
require_once __DIR__ . '/controller.php';

class AuthController extends Controller {

  public function register() {
    $data = $this->getRequestData();

    // Validate input
    if (empty($data->username) || empty($data->email) || empty($data->password) || empty($data->user_type)) {
      Response::error('All fields are required (username, email, password, user_type)', 400);
    }

    // Validate email format
    if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
      Response::error('Invalid email format', 400);
    }

    // Validate user type
    if ($data->user_type !== 'buyer' && $data->user_type !== 'seller') {
      Response::error('User type must be either "buyer" or "seller"', 400);
    }

    try {
      // Check if email already exists
      $stmt = $this->db->prepare("SELECT email FROM users WHERE email = :email");
      $stmt->bindParam(':email', $data->email);
      $stmt->execute();

      if ($stmt->rowCount() > 0) {
        Response::error('Email already exists', 409);
      }

      // Hash the password
      $hashed_password = password_hash($data->password, PASSWORD_DEFAULT);

      // Begin transaction
      $this->db->beginTransaction();

      // Create user
      $stmt = $this->db->prepare("
                INSERT INTO users (username, email, password, registration_date, user_type)
                VALUES (:username, :email, :password, NOW(), :user_type)
            ");

      $stmt->bindParam(':username', $data->username);
      $stmt->bindParam(':email', $data->email);
      $stmt->bindParam(':password', $hashed_password);
      $stmt->bindParam(':user_type', $data->user_type);

      $stmt->execute();

      $user_id = $this->db->lastInsertId();

      // If seller, create seller profile
      if ($data->user_type === 'seller') {
        $company_name = isset($data->company_name) ? $data->company_name : $data->username . "'s Company";
        $contact_info = isset($data->contact_info) ? $data->contact_info : $data->email;

        $stmt = $this->db->prepare("
                    INSERT INTO sellers (user_id, company_name, contact_info, rating, verification_status)
                    VALUES (:user_id, :company_name, :contact_info, 0, 'pending')
                ");

        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':company_name', $company_name);
        $stmt->bindParam(':contact_info', $contact_info);

        $stmt->execute();
      }

      $this->db->commit();

      Response::success([
        'user_id' => $user_id,
        'username' => $data->username,
        'email' => $data->email,
        'user_type' => $data->user_type
      ], 'User registered successfully', 201);

    } catch (Exception $e) {
      if ($this->db->inTransaction()) {
        $this->db->rollBack();
      }
      Response::error('Error: ' . $e->getMessage(), 500);
    }
  }

  public function login() {
    $data = $this->getRequestData();

    // Validate input
    if (empty($data->email) || empty($data->password)) {
      Response::error('Email and password are required', 400);
    }

    try {
      // Find user by email
      $stmt = $this->db->prepare("
                SELECT user_id, username, email, password, user_type
                FROM users
                WHERE email = :email
            ");

      $stmt->bindParam(':email', $data->email);
      $stmt->execute();

      if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify password
        if (password_verify($data->password, $user['password'])) {
          // Remove password from response
          unset($user['password']);

          // Create JWT token
          $issued_at = time();
          $expiration = $issued_at + (60 * 60); // 1 hour
          $payload = [
            'iat' => $issued_at,
            'exp' => $expiration,
            'user_id' => $user['user_id'],
            'user_type' => $user['user_type']
          ];

          // Simple token encoding - use a proper JWT library in production
          $jwt = base64_encode(json_encode($payload));

          Response::success([
            'token' => $jwt,
            'user' => $user
          ], 'Login successful');
        } else {
          Response::error('Invalid credentials', 401);
        }
      } else {
        Response::error('User not found', 404);
      }
    } catch (Exception $e) {
      Response::error('Error: ' . $e->getMessage(), 500);
    }
  }
}

