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
                    //var_dump($arrayURL);
                    $parameter=$arrayURL[1];
                    $id=(int)$arrayURL[1];
                    if(is_int($id)){
                        if ((int) method_exists($this, $func) > 0){
                            $this->$func($id);
                        }
                    }
                }  
                else if(sizeof($arrayURL)==3){
                    //var_dump($arrayURL);
                    $parameter=$arrayURL[1];
                    $id=(int)$arrayURL[1];
                    $param=(int)$arrayURL[2];
                    if(is_int($id)){
                        if ((int) method_exists($this, $func) > 0){
                            $this->$func($id, $param);
                        }
                    }
                } 
            }else{
                $this->response('', 404);
            }
            
        }
    }

    private function walls($id=null) {
        $this->cacheManager(30);
        if ($this->get_request_method() != "GET" && $this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        if(isset($id)){
            if(!is_array($id)){
                $r='SELECT * FROM `wall` where id='.$id.';';
            }
            else{
                //var_dump($id);
                $sous_r='';
                foreach ($id as $key => $value) {
                    $union='';
                    if($key!=0){
                        $union=' or ';
                    }
                    $sous_r.=$union.' id='.$value;
                }
                $r='SELECT * FROM `wall` where '.$sous_r.';';
                //var_dump($r);
                //$r='SELECT * FROM `wall`';
            }
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
    
    private function messages($id=null, $page=null) {
        $this->cacheManager(30);
        //var_dump($id."  ".$page);
        // Cross validation if the request method is GET else it will return "Not Acceptable" status
        if ($this->get_request_method() != "GET" && $this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $nb_message_par_page=30;
        $limit=" LIMIT 0,10";
        if(isset($id)){
            if(isset($page) && $page>=0){
                $debut=$page*$nb_message_par_page;
                $fin=$debut+$nb_message_par_page;
                $limit=" LIMIT ".$debut.",".$fin;
                //var_dump($debut." ".$fin);
                //$page="";
            }
            $r='SELECT * FROM `message` where idWall='.$id.$limit.';';
            if(isset($page) && $page==-1){
                $r='SELECT * FROM `message` where id='.$id.';';
            }
                
            //var_dump($r);
            //echo $r;
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
        $this->cacheManager(1);
        //$this->cacheManager(30);
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        if(isset($coord)){
            $topLeftLat=$coord->{'topLeft'}->{'lat'};
            $topLeftLon=$coord->{'topLeft'}->{'lon'};
            //$topRightLat=$coord->{'topRight'}->{'lat'};
            //$topRightLon=$coord->{'topRight'}->{'lon'};
            $bottomRightLeft=$coord->{'bottomRight'}->{'lat'};
            $bottomRightLon=$coord->{'bottomRight'}->{'lon'};
            //$bottomLeftLat=$coord->{'bottomLeft'}->{'lat'};
            //$bottomLeftLon=$coord->{'bottomLeft'}->{'lon'};
            $centreLon=$coord->{'center'}->{'lon'};
            $centreLat=$coord->{'center'}->{'lat'};
            $r='SELECT w.*, sum(m.like) as sommeLike'
             . ' FROM wall w' 
             . ' left join message m on w.id=m.idWall'
             . ' where (w.latitude between '.$bottomRightLeft.' and '.$topLeftLat
             . ' and w.longitude between '.$topLeftLon.' and '.$bottomRightLon.')'
             . ' OR ((12742000 * atan2( sqrt((POW((sin(('.$centreLat.' - w.latitude)/2)),2) + cos(w.latitude) * cos('.$centreLat.') * POW((sin(('.$centreLon.' - w.longitude)/2)),2))), sqrt(1-(POW((sin(('.$centreLat.' - w.latitude)/2)),2) + cos(w.latitude) * cos('.$centreLat.') * POW((sin(('.$centreLon.' - w.longitude)/2)),2))) ) ) < w.distance)'
             . ' group by w.id'
             . ' order by sum(m.like) desc'
             . ' LIMIT 100;';
            //echo ($r);
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
        $this->cacheManager(1);
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
            
            if(isset($nom) && isset($latitude) && isset($longitude) && isset($created) && isset($distance)){
                
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
    
    /*
     * 
        {
            "idWall": "1",
            "isImage": "0",
            "content": "C'est mon message",
            "latitude": "0.4333", 
            "longitude": "0.3",         
            "created": "1445854190",
            "description": "Description de mon message"
        }
     */
    private function insertMessage($message=null){
        $this->cacheManager(1);
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        //var_dump($wall);
        if(isset($message)){
            $idWall=addslashes($message->{'idWall'});
            $isImage=$message->{'isImage'};
            $content=$message->{'content'};
            $latitude=$message->{'latitude'};
            $longitude=$message->{'longitude'};
            $created=$message->{'created'};
            $description=addslashes($message->{'description'});
            //var_dump("ici");
            if(isset($idWall) && isset($isImage) && isset($content) && isset($latitude) && isset($longitude) && isset($created)){
                
                
                
                $r='INSERT INTO `message`(                                      
                                        `idWall`, 
                                        `like`, 
                                        `isImage`, 
                                        `content`, 
                                        `latitude`, 
                                        `longitude`, 
                                        `alert`, 
                                        `created`, 
                                        `description`) 
                                VALUES (                           
                                        '.$idWall.',
                                        0,
                                        '.$isImage.',
                                        "'.$content.'",
                                        '.$latitude.',
                                        '.$longitude.',
                                        0,
                                        '.$created.',
                                        "'.$description.'");';
                        
                //echo $r;
                $result = $this ->db
                                ->query($r); 
                    if($result){
                        $resultID = $this  ->db
                                           ->query('SELECT MAX( id ) as id FROM message'); 
                        if($resultID){
                            $row = $resultID->fetch_assoc();
                            //var_dump($row);
                            $DernierID=$row['id'];

                            if($isImage){
                                //$image_byte_code = $_REQUEST['o'];
                                $contentDecode = base64_decode($content);
                                $im = imagecreatefromstring($contentDecode);
                                if ($im !== false) {
                                    $nomFichier=$DernierID.".png";
                                    if(imagepng ( $im , "../img/".$nomFichier , 0 )){
                                        $url="http://perso.montpellier.epsi.fr/~nicolas.guigui/wallws/img/".$nomFichier;
                                        $r="UPDATE message SET content='".$url."' WHERE id=".$DernierID; 
                                        $this   ->db
                                                ->query($r); 
                                    }
                                }
                            }
                        }

                        //var_dump($DernierID);
                        $this->messages($DernierID,-1);
                     }
                }else{
                   $this->response("Erreur lors de l'insertion du message, vérifiez votre JSON" , 400); 
                }
            }
        }       
    
    
    /*
     * 
        {
            "research": "montpellier epsi"
        }
     */
    private function research($research = "") {
        $this->cacheManager(1);
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        //var_dump($wall);
        if (isset($research)) {
            $recherche = $research->{'research'};

            if (isset($recherche)) {
                $arrayRecherche = explode(" ", $recherche);
                //var_dump($arrayRecherche);
                $sous_r="";
                foreach($arrayRecherche as $key => $mot){
                    $union='';
                    if($key!=0){
                        $union=' union all ';
                    }
                    $sous_r.=$union."(select id from wall where nom LIKE '%$mot%')";
                    
                }
                $r = 'SELECT table_id.id as id, COUNT( * ) as pertinence
                        FROM ('.$sous_r.') as table_id
                        GROUP BY table_id.id
                        ORDER BY pertinence desc, table_id.id asc LIMIT 0,10;';
                //var_dump($r);
                $result = $this->db
                        ->query($r);
                if ($result) {
                    $arrayResult = array();
                    while ($row = $result->fetch_assoc()) {
                        $arrayResult[] = $row['id'];
                    }
                    $this->walls($arrayResult);
                    //$json = $this->json($arrayResult);
                    //$this->response($json, 200);
                } else {
                    $this->response("Erreur lors de la recherche, vérifiez votre JSON", 400);
                }
            }
        }
    }

//Encode array into JSON
    private function json($data) {
        $str='';
        if (is_array($data)) {
            $str= json_encode($data);
        }else{
            $str= json_encode(array());
        }
        return $str;
    }
    
    private function unJson($strData) {
        if (is_string($strData)) {
            return json_decode($strData);
        }
    }
    
    private function cacheManager($seconds_tocache){
        //$seconds_tocache = $day_tocache * 86400;
        $ts = gmdate("D, d M Y H:i:s", time() + $seconds_tocache) . " GMT";
        header("Expires: ". $ts);
        header("Pragma: cache");
        header("Cache-Control: max-age=".$seconds_tocache);
        header("User-Cache-Control: max-age=".$seconds_tocache);
    }

}

// Initiiate Library
$api = new API;
$api->processApi();
