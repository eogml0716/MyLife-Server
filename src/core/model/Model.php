<?php

namespace MyLifeServer\core\model;

use Exception;
use MyLifeServer\app\ConfigManager;
use MyLifeServer\core\model\database\Query;
use MyLifeServer\core\utils\ResponseHelper;
use stdClass;

class Model
{
    private $query; // QueryBuilder(데이터 베이스 쿼리를 담당) 클래스의 객체를 저장하는 변수
    protected $server_url = 'http://15.164.0.56';
    // 이미지 저장 관리 폴더명
    protected $image_folder = 'image';

    public function __construct(Query $query, ConfigManager $config_manager)
    {
        $this->query = $query;
    }

    /** --------------------------- @category 1. 클라이언트 데이터 확인 관련 --------------------------- */
    // (1) string type
    protected function check_string_data(array $client_data, string $key): string
    {
        if (empty($client_data[$key])) {
            ResponseHelper::get_instance()->dev_error_response(400, "{$key} data empty");
        }
        return $client_data[$key];
    }

    // (2) int type
    protected function check_int_data(array $client_data, string $key): int
    {
        if (!isset($client_data[$key])) {
            ResponseHelper::get_instance()->dev_error_response(400, "{$key} data empty");
        }
        return $client_data[$key];
    }

    // (3) boolean type
    protected function check_boolean_data(array $client_data, string $key): bool
    {
        if (!isset($client_data[$key])) {
            ResponseHelper::get_instance()->dev_error_response(400, "{$key} data empty");
        }
        if ($client_data[$key] == 'false' || !$client_data[$key]) {
            return false;
        }
        return $client_data[$key];
    }

    // (4) array type
    protected function check_array_data(array $client_data, string $key): array
    {
        if (empty($client_data[$key])) {
            ResponseHelper::get_instance()->dev_error_response(400, "{$key} data empty");
        }
        return $client_data[$key];
    }

    // (5) json string
    protected function check_json_data(array $client_data, string $key): array
    {
        if (empty($client_data[$key])) {
            ResponseHelper::get_instance()->dev_error_response(400, "{$key} data empty");
        }
        $filtered_json = str_replace(['"{', '}"', '\"'], ['{', '}', '"'], $client_data[$key]);
        $decoded_json = json_decode($filtered_json, true);

        if (empty($decoded_json)) {
            ResponseHelper::get_instance()->dev_error_response(400, "{$key} data json decode error");
        }
        return $decoded_json;
    }

    /** --------------------------- @category ?. 자주 사용하는 유틸 관련 --------------------------- */
    // (1) 이미지 저장
    protected function store_image(string $encoded_image, string $description, string $folder_name)
    {
        $decoded_string = base64_decode($encoded_image);
        $new_file_name = str_replace(".", "", uniqid("{$description}:", true));
        $file_name = $new_file_name . '.' . 'jpg';
        $path = "assets/{$folder_name}/" . $file_name;
        file_put_contents($path, $decoded_string);
        return "{$this->server_url}/{$path}";
    }
}
