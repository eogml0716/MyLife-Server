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
//        $data = file_get_contents("php://input");
        /**
         * TODO: url_encoded된 데이터로 오면 구분해주려고 만든 조건문
         * 체크 해볼 것 : $_POST는 밑에처럼 parse_str 해주지 않아도 그냥 데이터가 받아지는데, $_PUT이랑 $_DELETE는 해주어야지 받아지는 거 같다.
         */
//        $is_url_encoded = preg_match('~%[0-9A-F]{2}~i', $data);
//        if ($is_url_encoded) {
            parse_str(file_get_contents("php://input"), $client_data);
            return empty($client_data) ? [] : $client_data;
//        }
//        $client_data = json_decode($data, true);
//        return empty($client_data) ? [] : $client_data;
    }
}
