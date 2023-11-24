<?php
use Intervention\Image\ImageManagerStatic as Image; // Importa el namespace adecuado


class ServiceReparation
{
    private $db; // PDO object for database connection
    private $log;

    
    public function __construct()
    {
        $this->log = new Monolog\Logger("app_workshop");
        $this->connect();
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
            $this->log->pushHandler(new Monolog\Handler\StreamHandler("../logs/app_workshop.log", Monolog\Logger::INFO));
            $this->log->info("Connection successfully");
        } catch (PDOException $e) {
            // Handle connection errors here
            $this->log->pushHandler(new Monolog\Handler\StreamHandler("../logs/app_workshop.log", Monolog\Logger::ERROR));
            $this->log->error("Error connection db: " . $e->getMessage());
        }
    }

    public function insertReparation(Reparation $reparation)
    {
        try {
            
            $photo = $reparation->getPhoto();
           
            $watermark = Image::make('..\src\images\watermark.jpg')->opacity(50);
            $watermark->pixelate(4);
            $watermark->resize($photo->width(),null);
            $photo->insert($watermark, 'top-left', 0, $photo->height() - 12);
            $photo->text($reparation->getLicensePlate().$reparation->getId(), $reparation->getPhoto()->width()/2,  $reparation->getPhoto()->height()-10,function($font) {
                $font->size( 70);
                $font->color('#000000');
                $font->align('center');
                $font->valign('top');
                $font->angle(45);
            });
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

            if ($stmt->execute()) {
                $this->log->pushHandler(new Monolog\Handler\StreamHandler("../logs/app_workshop.log", Monolog\Logger::INFO));
                $this->log->info("Record inserted successfully");

                return $id;
            } else {
                throw new PDOException("Error inserting a record");
            }
        } catch (PDOException $e) {
            // Handle insertion errors here
            $this->log->pushHandler(new Monolog\Handler\StreamHandler("../logs/app_workshop.log", Monolog\Logger::ERROR));
            $this->log->error("Error inserting a record: " . $e->getMessage());

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
            if ($result) {
                $this->log->pushHandler(new Monolog\Handler\StreamHandler("../logs/app_workshop.log", Monolog\Logger::INFO));
                $this->log->info("Got reparation: " . $id);
            } else {
                $this->log->pushHandler(new Monolog\Handler\StreamHandler("../logs/app_workshop.log", Monolog\Logger::WARNING));
                $this->log->warning("Reparation doesn't exist: " . $id);
            }
            $image = Image::make($result[0]["photo"])->resize(600, 500);
            return new Reparation($result[0]["name_workshop"],$result[0]["register_date"],$result[0]["license_plate"],$image); // Return the fetched records
        } catch (PDOException $e) {
            // Handle selection errors here
            $this->log->pushHandler(new Monolog\Handler\StreamHandler("../logs/app_workshop.log", Monolog\Logger::WARNING));
            $this->log->warning("Error getting reparation: " . $id);
            return []; // Return an empty array on failure
        }
    }
}
