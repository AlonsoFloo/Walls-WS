<?php

include "rest.php";


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

    const DB_SERVER = "mysql.montpellier.epsi.fr";
    const DB_USER = "nicolas.guigui";
    const DB_PASSWORD = "epsi491YYK";
    const DB = "walls";
    const DB_PORT = "5206";

    private $db = NULL;

    public function __construct() {
        parent::__construct(); // Init parent contructor
        $this->dbConnect(); // Initiate Database connection
    }

//Database connection
    private function dbConnect() {
        //$this->db = mysql_connect(self::DB_SERVER, self::DB_USER, self::DB_PASSWORD, self::DB, self::DB_PORT);
        $this->db = new mysqli(self::DB_SERVER, self::DB_USER, self::DB_PASSWORD, self::DB, self::DB_PORT);
        //if ($this->db)
            //mysql_select_db(self::DB, $this->db);
    }

//Public method for access api.
//This method dynmically call the method based on the query string
    public function processApi() {
        if(isset($_REQUEST['request'])){
            $arrayURL=explode("/",strtolower(trim($_REQUEST['request'])));
            if(sizeof($arrayURL)>0){
                $func=$arrayURL[0];
                if(sizeof($arrayURL)==1){
                    if ((int) method_exists($this, $func) > 0){
                        $this->$func();
                    }
                }else if(sizeof($arrayURL)==2){
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
            /*$func = strtolower(trim(str_replace("/", "", $_REQUEST['request'])));
            if ((int) method_exists($this, $func) > 0){
                $this->$func();
            }
            else{
                $this->response('', 404);
            }*/
        }
// If the method not exist with in this class, response would be "Page not found".
    }

    private function walls() {
// Cross validation if the request method is GET else it will return "Not Acceptable" status
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        //$sql = mysql_query("SELECT * FROM `wall`", $this->db);
        $result = $this ->db
                        ->query("SELECT * FROM `wall`");         
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

    private function users() {
           echo "user";
    }

    private function deleteUser() {

    }

//Encode array into JSON
    private function json($data) {
        if (is_array($data)) {
            return json_encode($data);
        }
    }

}

// Initiiate Library
$api = new API;
$api->processApi();
