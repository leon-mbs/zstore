<?php

namespace App\API\Base;

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

    /**
     * @param array $params
     */
    public function get($params) {
        $this->FailAnswer();
    }

    /**
     * @param array $params
     */
    public function post($params) {
        $this->FailAnswer();
    }

    /**
     * @param array $params
     */
    public function put($params) {
        $this->FailAnswer();
    }

    /**
     * @param array $params
     */
    public function delete($params) {
        $this->FailAnswer();
    }

    protected function JsonAnswer($json) {
        header("Content-type: application/json");
        http_response_code(200);
        echo $json;
    }

    protected function XMLAnswer($xml) {
        header("Content-type: text/xml");
        echo $xml;
        http_response_code(200);
    }

    protected function CSVAnswer($csv) {
        header("Content-type: text/csv");
        http_response_code(200);
        echo $csv;
    }

    protected function TextAnswer($text) {
        header("Content-type: text/plain");
        http_response_code(200);
        echo $text;
    }


    protected function OKAnswer() {
        http_response_code(200);
    }

    protected function FailAnswer() {
        http_response_code(400);
    }

}
