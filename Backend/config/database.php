<?php
// Load environment  variables from the env
//DONT HARCODE CREDENTIALS ON UR CODE OK!!!

if(file_exists(__DIR__ . '/../.env') ){
  $env = parse_ini_file(__DIR__ . '/../.env');
  foreach($env as $key => $value){
    putenv("$key=$value");
  }
}

class Database{
  private $host;
  private $port;
  private $db_name;
  private $username;
  private $password;
  private $conn;

  public function __construct(){
    //load environment variables
    $this->host = getenv('DB_HOST');
    $this->port = getenv('DB_PORT');
    $this->db_name = getenv('DB_NAME');
    $this->username = getenv('DB_USER');
    $this->password = getenv('<PASSWORD>');
  }

  //connect to database

  /**
   * @throws Exception
   */
  public function connect() {
    $this->conn = null;

    try {
      $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};sslmode=REQUIRED";
      $options = [
        PDO::ATTR_ERRMODE=> PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        ];

      $this->conn = new PDO($dsn, $this->username, $this->password, $options);
    } catch (PDOException $e) {
      error_log("Connection failed: " . $e->getMessage());
      throw new Exception("Datbase connection failed. Try again later.");
    }

    return $this->conn;
  }
}
?>
