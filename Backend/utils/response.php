<?php
class Response {
  public static function json($data = null, $message = '', $success = false, $code = 200) {
    header("Content-Type: application/json; charset=UTF-8");
    http_response_code($code);

    echo json_encode([
      'success' => $success,
      'message' => $message,
      'data' => $data
    ]);

    exit;
  }

  public static function success($data = null, $message = 'Success', $code = 200) {
    self::json($data, $message, true, $code);
  }

  public static function error($message = 'Error', $code = 400) {
    self::json(null, $message, false, $code);
  }

  public static function unauthorized() {
    self::error('Unauthorized access', 401);
  }

  public static function notFound($message = 'Resource not found') {
    self::error($message, 404);
  }
}
?>
