// utils/auth.php
<?php
class Auth {
  public static function authorize() {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
      return false;
    }

    //use better jwt verification in the future
    $token = $headers['Authorization'];
    $token = str_replace('Bearer ', '', $token);

    try {
      $payload = json_decode(base64_decode($token), true);

      //check for token expiration
      if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
      }

      return $payload;
    } catch (Exception $e) {
      return false;
    }
  }
}
?>
