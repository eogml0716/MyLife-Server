<?php

namespace MyLifeServer\core\controller;

class Controller
{
    protected $get_method = 'GET';
    protected $postMyLifeServer_method = 'POST';
    protected $put_method = 'PUT';
    protected $delete_method = 'DELETE';

    protected function get_client_data(): array
    {
        $json_string = file_get_contents("php://input");
        $client_data = json_decode($json_string, true);
        return empty($client_data) ? [] : $client_data;
    }
}
