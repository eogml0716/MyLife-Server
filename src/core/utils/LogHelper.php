<?php

namespace LoLApp\core\utils;

// TODO: 로그 쌓는 로직 변경 필요
class LogHelper
{
    private static $db_helper;
    private $sql_array = [];

    private function __construct()
    {
    }

    public static function get_instance(): self
    {
        if (empty(static::$db_helper)) {
            return new self;
        }
        return static::$db_helper;
    }

    /** --------------------------- @category 1. 로그 관련 메소드 모음 --------------------------- */
    // (1) sql 로그 저장 : 쿼리를 할 경우 sql_array에 저장(모든 작업이 마치면 user_log_write메소드를 통해 로그 저장)
    public function save_user_sql_log(String $sql_statement): void
    {
        // sql_statement에서 중복 공백이 있으면 줄인다.
        $sql_statement = preg_replace('/\s+/', ' ', $sql_statement);
        $this->sql_array[] = $sql_statement;
    }

    /**
     * (2) 로그 파일형태로 저장
     * 사용자 ip, sql, 요청이유 등에 대한 로그를 파일로 저장하는 곳
     * 전체 로그 데이터를 담을 배열 -> $user_log_array
     * 사용자가 사용한 sql 정보를 가진 배열 -> $sql_array
     */
    public function write_user_log(bool $is_success): void
    {
        $user_info_idx = $this->get_user_info_idx();
        $user_ip = $this->get_user_ip();
        $ip_info = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $user_ip)); // ip에 해당하는 나라 정보를 구함함
        $request_time = date('Y-m-d H:i:s');
        $user_log_array = [
            'user_info_idx' => empty($user_info_idx) ? 'empty idx' : $user_info_idx,
            'request_uri' => $_SERVER['REQUEST_URI'],
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'is_success' => $is_success,
            'ip' => $user_ip,
            'country' => $ip_info->geoplugin_countryName,
            'sql' => $this->sql_array,
            'request_time' => $request_time,
        ];
        $log_directory_path = "/home/swapdo/log"; //로그위치 지정
        $year_folder_name = date("Y"); //폴더 1 년도 생성
        $month_folder_name = date("n"); //폴더 2 월 생성

        // 년, 월 디렉토리를 생성
        if (!is_dir("{$log_directory_path}/{$year_folder_name}")) {
            mkdir("{$log_directory_path}/{$year_folder_name}", 0755);
        }
        if (!is_dir("{$log_directory_path}/{$year_folder_name}/{$month_folder_name}")) {
            mkdir("{$log_directory_path}/{$year_folder_name}/{$month_folder_name}", 0755);
        }
        // 모바일에서 사용하는 로그를 파일에 저장한다. 로그 파일이 없으면 생성한다 = "a" 모드
        $log_file = fopen("{$log_directory_path}/{$year_folder_name}/{$month_folder_name}/mobile_" . date('Ymd') . '.txt', 'a');
        if ($log_file) {
            fwrite($log_file, json_encode($user_log_array, JSON_UNESCAPED_UNICODE) . "\r\n");
        }
        fclose($log_file);
    }

    /** --------------------------- @category 2. 내부 메소드 모음 --------------------------- */
    // (1) 유저 idx 가져오는 메소드 //TODO: json으로 받기 때문에 POST, PUT, DELETE 로직 변경하기
    private function get_user_info_idx(): ?int
    {
        $user_info_idx = null;

        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                if (isset($_GET['user_info_idx'])) {
                    return $_GET['user_info_idx'];
                }
            case 'POST':
                if (isset($_POST['user_info_idx'])) {
                    return $_POST['user_info_idx'];
                }
            default:
                parse_str(file_get_contents("php://input"), $client_data);
                if (isset($client_data['user_info_idx'])) {
                    return $client_data['user_info_idx'];
                }
        }

        return $user_info_idx;
    }

    // (2) 사용자 ip 가져오는 메소드
    private function get_user_ip(): String
    {
        // Client IP를 가져옴 TODO: ip 우회하면 변경된 ip를 가져옴 기존 ip를 가져오지 못함. 나중에 해결할 문제
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        //ip from share internet
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return $_SERVER['REMOTE_ADDR']; //ip pass from proxy
    }
}
