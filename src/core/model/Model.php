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
    protected $success_result = 'SUCCESS';
    protected $fail_result = 'FAIL';
    protected $one_hour = 3600;

    protected $server_url = 'https://rdh98.shop';

    // 이미지 저장 관련 - 폴더명
    protected $user_image_folder = 'user';
    protected $post_image_folder = 'post';

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


    /** --------------------------- @category ?. 사용자 세션 관련 --------------------------- */
    // (?) 사용자 세션 확인하는 메소드
    protected function check_user_session(array $client_data): int
    {
        if (empty($client_data['user_idx'])) ResponseHelper::get_instance()->error_response(400, 'user_idx data empty');

        $user_idx = $this->check_int_data($client_data, 'user_idx');
        $session_id = $this->get_session_id();

        if ($session_id == 'GEjkhojdjsaoJEOJHP29OJGeojs9020') return $user_idx; // postman 테스트 용 세션 아이디

        $db_user  = $this->query->select_user_by_session_id($session_id);

        if (empty($db_user)) ResponseHelper::get_instance()->error_response(400, 'no user session id in database');

        $db_user_idx = $db_user[0]['user_idx'];

        // 클라가 잘못된 세션 id로 요청한 경우
        if ($user_idx != $db_user_idx) {
            // TODO: 왜 1시간 이전으로 쿠키 유효 기간을 변경하지?
            setcookie("session_id", "", time() - $this->one_hour); // 한 시간 이전으로 쿠키 유효 기간 변경
            ResponseHelper::get_instance()->error_response(400, 'wrong user session id, need new login');
        }

        return $user_idx;
    }

    // (2) header에 보낸 세션 id를 가져온다.
    protected function get_session_id(): string
    {
        $headers = apache_request_headers();
        $session_header = isset($headers['Cookie']) ? explode("=", $headers['Cookie'])[1] : null;

        if (empty($session_header)) ResponseHelper::get_instance()->error_response(400, 'user session id not exist in header');
        if (strpos($session_header, ';')) $session_header = explode(';', $session_header)[0]; // TODO: 세션 값이 두개 오는 경우 에러 예외처리 이유 확인해보기

        return $session_header;
    }

    /** --------------------------- @category ?. 유틸리티 --------------------------- */
    /**
     * (?) 이미지 저장
     */
    protected function store_image(string $encoded_image, string $description, string $folder_name)
    {
        $decoded_string = base64_decode($encoded_image);
        $new_file_name = str_replace(".", "", uniqid("{$description}:", true));
        $file_name = $new_file_name . '.' . 'jpg';
        $path = "assets/{$folder_name}/" . $file_name;
        file_put_contents($path, $decoded_string);
        return "{$this->server_url}/{$path}";
    }

    // (?) 지정된 길이만큼 랜덤으로 단어 생성
    protected function generate_string(int $length): string
    {
        $characters = '0123456789' . 'abcdefghijklmnopqrstuvwxyz' . 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $generated_string = '';

        while ($length--) {
            // 지정된 횟수만큼 위 문자열에서 한 개를 골라서 만듬
            $generated_string .= $characters[mt_rand(0, strlen($characters) - 1)];
        }

        return $generated_string;
    }
}
