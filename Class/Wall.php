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
class Wall extends rest {

    /**
     * Public method for access api. 
     * This method dynmically call the method based on the query string 
     * */
    public function processApi($func) {
        $func = strtolower(trim(str_replace("/", "", $_REQUEST['rquest'])));
        if ((int) method_exists($this, $func) > 0)
            $this->$func();
        else
            $this->response('', 404); // si la fonction n’existe pas, la réponse sera "Page not found". 
    }
    
    /**
     * Encode array into JSON 
     */
    private function json($data) {
        if (is_array($data)) {
            return json_encode($data);
        }
    }

    /**
     * Vérification existence preco 
     * numanc : $numanc
     */
    private function res_exist() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $my_preco = new Model_Precos;
        $numanc = $this->_request['numanc'];
        $res = $my_preco->fetchRow("numanc='$numanc'");
        if (is_object($res)) {
            $this->response($this->json($res->toArray()), 200);
        } else {
            $this->response('', 204);
        }
    }
    
    
    /**
     * Suppression preco 
     * numanc : $numanc
     */
    private function suppr_res() {
        if ($this->get_request_method() != "DELETE") {
            $this->response('', 406);
        }
        $my_preco = new Model_Precos;
        $numanc = $this->_request['numanc'];
        $ret = $my_preco->delete($numanc);
        If ($ret) {
            $success = array('status' => "Success", "msg" => "Element supprimé.");
            $this->response($this->json($success), 200);
        } else {
            $this->response('', 204);
        }
    }

}
