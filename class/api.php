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

    private $db = NULL;

    public function __construct() {
        parent::__construct(); // Init parent contructor
        $this->dbConnect(); // Initiate Database connection
    }

//Database connection
    private function dbConnect() {
        $this->db = mysql_connect(self::DB_SERVER, self::DB_USER, self::DB_PASSWORD);
        if ($this->db)
            mysql_select_db(self::DB, $this->db);
    }

//Public method for access api.
//This method dynmically call the method based on the query string
    public function processApi() {
        $func = strtolower(trim(str_replace("/", "", $_REQUEST['rquest'])));
        if ((int) method_exists($this, $func) > 0)
            $this->$func();
        else
            $this->response('', 404);
// If the method not exist with in this class, response would be "Page not found".
    }

    private function walls() {
// Cross validation if the request method is GET else it will return "Not Acceptable" status
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $sql = mysql_query("SELECT * FROM `wall`", $this->db);
        if (mysql_num_rows($sql) > 0) {
            $result = array();
            while ($rlt = mysql_fetch_array($sql, MYSQL_ASSOC)) {
                $result[] = $rlt;
            }
// If success everythig is good send header as "OK" and return list of users in JSON format
            $this->response($this->json($result), 200);
        }
        $this->response('', 204); // If no records "No Content" status
    }

    private function users() {
//..............
    }

    private function deleteUser() {
//............
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
