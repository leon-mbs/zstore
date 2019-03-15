<?php

namespace App;

/**
 * Base class for RESTFul    
 */
abstract class RestFul
{

    /**
     * 
     * 
     * @param array $params
     */
    public function Execute($params) {

        if ($_SERVER["REQUEST_METHOD"] == "GET") {
            $this->get($params);
        };
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $this->post($params);
        };
        if ($_SERVER["REQUEST_METHOD"] == "DELETE") {
            $this->delete($params);
        };
        if ($_SERVER["REQUEST_METHOD"] == "PUT") {
            $this->put($params);
        };
    }

    public function get() {
        $this->FailAnswer();
    }

    public function post() {
        $this->FailAnswer();
    }

    public function put() {
        $this->FailAnswer();
    }

    public function delete() {
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
