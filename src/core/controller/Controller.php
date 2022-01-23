<?php

namespace MyLifeServer\core\controller;

class Controller
{
    protected $get_method = 'GET';
    protected $post_method = 'POST';
    protected $put_method = 'PUT';
    protected $delete_method = 'DELETE';

    protected function get_client_data(): array
    {
        $data = file_get_contents("php://input");
        // TODO: url_encoded된 데이터로 오면 구분해주려고 만든 조건문
//        $is_url_encoded = preg_match('~%[0-9A-F]{2}~i', $data);
//        if ($is_url_encoded) {
//            parse_str(file_get_contents("php://input"), $client_data);
//            return empty($client_data) ? [] : $client_data;
//        }
        $client_data = json_decode($data, true);
        return empty($client_data) ? [] : $client_data;
    }
}
