<?php

namespace App;

/**
 * Base class for RESTFul    
 */
abstract class RestFul
{

    public function Execute($id) {

        if ($_SERVER["REQUEST_METHOD"] == "GET") {
            $this->get($id);
        };
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $this->post($_POST["data"]);
        };
        if ($_SERVER["REQUEST_METHOD"] == "DELETE") {
            $this->delete($id);
        };
        if ($_SERVER["REQUEST_METHOD"] == "PUT") {
            $this->put($id, $_REQUEST["data"]);
        };
    }

    public function get($id = 0) {
        $this->FailAnswer();
    }

    public function post($data) {
        $this->FailAnswer();
    }

    public function put($id, $data) {
        $this->FailAnswer();
    }

    public function delete($id) {
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
