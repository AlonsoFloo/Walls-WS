<?php

include "rest.php";
include "../const/const.php";

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Wall
 *
 * @author Nicolas
 */
class API extends Rest_Rest {

    public $data = "";

    private $db = NULL;

    public function __construct() {
        parent::__construct(); // Init parent contructor
        $this->dbConnect(); // Initiate Database connection
    }

//Database connection
    private function dbConnect() {
        $this->db = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB, DB_PORT);
    }

//Public method for access api.
//This method dynmically call the method based on the query string
    public function processApi() {
        if(isset($_REQUEST['request'])){
            $arrayURL=explode("/",strtolower(trim($_REQUEST['request'])));
            $raw=file_get_contents("php://input");
            if(sizeof($arrayURL)>0){
                $func=$arrayURL[0];
                if(sizeof($arrayURL)==1){
                    //détécter si il y a des paramètres envoyé en JSON
                    if(!empty($raw)){
                        $json=$this->unJson($raw);
                        //var_dump($json);
                        if((int) method_exists($this, $func) > 0){
                            $this->$func($json);
                        }
                    }else if((int) method_exists($this, $func) > 0){
                        $this->$func();
                    }
                }else if(sizeof($arrayURL)==2){
                    $parameter=$arrayURL[1];
                    $id=(int)$arrayURL[1];
                    if(is_int($id)){
                        if ((int) method_exists($this, $func) > 0){
                            $this->$func($id);
                        }
                    }
                }  
            }else{
                $this->response('', 404);
            }
            
        }
    }

    private function walls($id=null) {
        if ($this->get_request_method() != "GET" && $this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        if(isset($id)){
            $r='SELECT * FROM `wall` where id='.$id.';';
        }else{
            $r='SELECT * FROM `wall`;';
        }
        $result = $this ->db
                        ->query($r);         
        if($result){
            $arrayResult=array();
            while($row = $result->fetch_assoc()){
                $arrayResult[]=$row;
            }
            $json=$this->json($arrayResult);
            $this->response($json, 200);
        }else{
            $this->response('', 204); // If no records "No Content" status*/
        }
    }
    
    private function messages($id=null) {
        // Cross validation if the request method is GET else it will return "Not Acceptable" status
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        if(isset($id)){
            $r='SELECT * FROM `message` where idWall='.$id.';';
        }else{
            $r='SELECT * FROM `message`;';
        }
        $result = $this ->db
                        ->query($r);         
        if($result){
            $arrayResult=array();
            while($row = $result->fetch_assoc()){
                $arrayResult[]=$row;
            }
            $json=$this->json($arrayResult);
            $this->response($json, 200);
        }else{
            $this->response('', 204); // If no records "No Content" status
        }
    }
    
    /*
    {
    "topLeft": {
        "lat": 0.43,
        "lon": 0.3
    },
    "topRight": {
        "lat": 0.45,
        "lon": 0.32
    },
    "bottomRight": {
        "lat": 0.69,
        "lon": 0.8
    },
    "bottomLeft": {
        "lat": 0.96,
        "lon": 0.85
    }
     */
    private function wallsFromCoord($coord=null){
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        if(isset($coord)){
            $topLeftLat=$coord->{'topLeft'}->{'lat'};
            //$topLeftLon=$coord->{'topLeft'}->{'lon'};
            //$topRightLat=$coord->{'topRight'}->{'lat'};
            $topRightLon=$coord->{'topRight'}->{'lon'};
            $bottomRightLeft=$coord->{'bottomRight'}->{'lat'};
            $bottomRightLon=$coord->{'bottomRight'}->{'lon'};
            //$bottomLeftLat=$coord->{'bottomLeft'}->{'lat'};
            //$bottomLeftLon=$coord->{'bottomLeft'}->{'lon'};
            $r='SELECT w.*, sum(m.like) as sommeLike'
             . ' FROM wall w' 
             . ' left join message m on w.id=m.idWall'
             . ' where w.latitude between '.$bottomRightLeft.' and '.$topLeftLat
             . ' and w.longitude between '.$topRightLon.' and '.$bottomRightLon
             . ' group by m.idWall'
             . ' order by sommeLike desc'
             . ' LIMIT 100;';
            echo ($r);
            $result = $this ->db
                            ->query($r); 
            if($result){
                $arrayResult=array();
                while($row = $result->fetch_assoc()){
                    $arrayResult[]=$row;
                }
                $json=$this->json($arrayResult);
                $this->response($json, 200);
            }else{
               
                $this->response('', 204); // If no records "No Content" status
            }
        }else{
            $this->response('Les coordonnées ne sont pas indiqués dans le body de la requête', 400);
        }   
    }
    
    /*
        {
            "nom": "EPSI Montpellier",
            "latitude": "43.642057000000000000000000000000",
            "longitude": "3.838275000000000000000000000000",
            "distance": "30",
            "created": "1445854190",
            "description": "Mur de l'EPSI Montpellier taactac"
        }
     */
    private function insertWall($wall=null){
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        //var_dump($wall);
        if(isset($wall)){
            $nom=addslashes($wall->{'nom'});
            $latitude=$wall->{'latitude'};
            $longitude=$wall->{'longitude'};
            $distance=$wall->{'distance'};
            $created=$wall->{'created'};
            $description=addslashes($wall->{'description'});
            
            if(isset($nom) && isset($latitude) && isset($longitude) && isset($distance)){
                
                $r='INSERT INTO `wall`('
                                    . '`nom`, '
                                    . '`latitude`, '
                                    . '`longitude`, '
                                    . '`distance`, '
                                    . '`created`, '
                                    . '`alert`, '
                                    . '`description`) '
                                    . 'VALUES ('
                                    . "'".$nom."',"
                                    . $latitude.','
                                    . $longitude.','
                                    . $distance.','
                                    . $created.','
                                    . '0,'
                                    . "'".$description."');";
                //echo $r;
                $result = $this ->db
                                ->query($r); 
                if($result){
                     $resultID = $this  ->db
                                        ->query('SELECT MAX( id ) as id FROM wall'); 
                     if($resultID){
                         $row = $resultID->fetch_assoc();
                         //var_dump($row);
                         $DernierID=$row['id'];
                         //var_dump($DernierID);
                         $this->walls($DernierID);
                     }
                }else{
                   $this->response("Erreur lors de l'insertion du mur, vérifiez votre JSON" , 400); 
                }
            }
        }       
    }

//Encode array into JSON
    private function json($data) {
        if (is_array($data)) {
            return json_encode($data);
        }
    }
    
    private function unJson($strData) {
        if (is_string($strData)) {
            return json_decode($strData);
        }
    }

}

// Initiiate Library
$api = new API;
$api->processApi();
