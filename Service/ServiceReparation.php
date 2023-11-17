<?php

class ServiceReparation
{
    private $db; // PDO object for database connection
    private $log;

    public function __construct()
    {
        $this->log = new Monolog\Logger("LogDB");
    }
    public function connect()
    {
        // Load database configuration from ini file
        $config = parse_ini_file('../db_config.ini');
       

        // Establish a database connection using PDO
        try {
            $this->db = new PDO(
                "mysql:host={$config['DB_HOST']};dbname={$config['DB_NAME']}",
                $config['DB_USER'],
                $config['DB_PASS']
            );
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->log->pushHandler(new Monolog\Handler\StreamHandler("../logs/LogDB.log", Monolog\Logger::INFO));
            $this->log->info("Connection successfully");
        } catch (PDOException $e) {
            // Handle connection errors here
            $this->log->pushHandler(new Monolog\Handler\StreamHandler("../logs/LogDB.log", Monolog\Logger::ERROR));
            $this->log->error("Error connection db: " . $e->getMessage());
        }
    }

    public function insertReparation(Reparation $reparation)
    {
        try {
            // Prepare the SQL query for inserting a reparation record
            $query = "INSERT INTO Reparation (id,name_workshop, register_date, license_plate, photo) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);

            $id = $reparation->getId();
            $nameWorkshop = $reparation->getNameWorkshop();
            $registerDate = $reparation->getRegisterDate();
           
            $licensePlate = $reparation->getLicensePlate();
            $photo = $reparation->getPhoto();

            $imageData = $photo->encode('data-url');
            // Bind parameters and execute the query
            $stmt->bindParam(1, $id);
            $stmt->bindParam(2, $nameWorkshop);
            $stmt->bindParam(3, $registerDate);
            $stmt->bindParam(4, $licensePlate);
            $stmt->bindParam(5, $imageData, PDO::PARAM_LOB);

     
            if($stmt->execute()){
                 $this->log->pushHandler(new Monolog\Handler\StreamHandler("../logs/LogDB.log", Monolog\Logger::INFO));
                $this->log->info("Record inserted successfully");
            }else{
                 $this->log->pushHandler(new Monolog\Handler\StreamHandler("../logs/LogDB.log", Monolog\Logger::ERROR));
                $this->log->info("Error inserting Reparation");
            }

        } catch (PDOException $e) {
            // Handle insertion errors here
            $this->log->pushHandler(new Monolog\Handler\StreamHandler("../logs/LogDB.log", Monolog\Logger::ERROR));
            $this->log->error("Error inserting a record: ". $e->getMessage());
    
            return false; // Return false on failure
        }
    }

    public function getReparation($id)
    {
        try {
            // Prepare the SQL query for selecting a reparation record based on id
            $query = "SELECT * FROM Reparation WHERE id = ?";
            $stmt = $this->db->prepare($query);

            // Bind parameters and execute the query
            $stmt->bindParam(1, $id);
            $stmt->execute();

            // Fetch the result set as an associative array
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if($result){
                $this->log->pushHandler(new Monolog\Handler\StreamHandler("../logs/LogDB.log", Monolog\Logger::INFO));
                $this->log->info("Got reparation: ".$id);
            }else{
                $this->log->pushHandler(new Monolog\Handler\StreamHandler("../logs/LogDB.log", Monolog\Logger::ERROR));
                $this->log->info("Reparation doesn't exist: ".$id);
            }
            return $result; // Return the fetched records
        } catch (PDOException $e) {
            // Handle selection errors here
            $this->log->pushHandler(new Monolog\Handler\StreamHandler("../logs/LogDB.log", Monolog\Logger::ERROR));
            echo "Error getting reparation: " . $e->getMessage();
            return []; // Return an empty array on failure
        }
    }
}
