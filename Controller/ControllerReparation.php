<?php
    require_once "../Service/ServiceReparation.php";
    require_once "../Model/Reparation.php";

class ControllerReparation{

    private ServiceReparation $service;

    public function __construct(){
        $this->service = new ServiceReparation();
    }
    
    public function insertReparation(Reparation $reparation){
        $uuid = $this->service->insertReparation($reparation);
        return $uuid;
    }  
    
    public function getReparation(string $id){
        $reparation = $this->service->getReparation($id);
        return  $reparation;
    }

    public function getService(){
        return $this->service;
    }
}

