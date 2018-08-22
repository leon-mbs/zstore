<?php

namespace App;

/**
 * Base class for RESTFul    
 */
abstract class RestFul
{

    public function Execute($id,$key=null ) {

        if ($_SERVER["REQUEST_METHOD"] == "GET") {
            $this->get($id,$key );
        };
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $this->post($id,$key);
        };
        if ($_SERVER["REQUEST_METHOD"] == "DELETE") {
            $this->delete($id,$key);
        };
        if ($_SERVER["REQUEST_METHOD"] == "PUT") {
            $this->put($id,$key);
        };
    }

    public function get($id,$key=null) {
        $this->FailAnswer();
    }

    public function post($id,$key=null) {
        $this->FailAnswer();
    }

    public function put($id,$key=null) {
        $this->FailAnswer();
    }

    public function delete($id,$key=null) {
        $this->FailAnswer();
    }

    protected function JsonAnswer($json) {
        header("Content-type: application/json");
        echo $json;
    }

    protected function XMLAnswer($xml) {
        header("Content-type: text/xml");
        echo $xml;
    }

    protected function CSVAnswer($csv) {
        header("Content-type: text/csv");
        echo $csv;
    }

    protected function TextAnswer($text) {
        header("Content-type: text/plain");
        echo $text;
    }

    protected function OKAnswer() {
        http_response_code(200);
    }

    protected function FailAnswer() {
        http_response_code(400);
    }

}
