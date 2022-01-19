<?php

namespace MyLifeServer\app\models;

use Exception;
use MyLifeServer\app\ConfigManager;
use MyLifeServer\app\models\sql\UserQuery;
use MyLifeServer\core\model\HttpRequester;
use MyLifeServer\core\model\Model;
use MyLifeServer\core\utils\ResponseHelper;
use stdClass;

/**
 * @category 1. 회원가입 관련
 *  (1) 기본 회원가입
 *  (2) SNS 회원가입
 * @category 2. 로그인 관련
 *  (1) 기본 로그인
 *  (2) SNS 로그인
 *  (3) 자동 로그인
 */
class UserModel extends Model
{
    private $query;

    public function __construct(UserQuery $query, ConfigManager $config_manager)
    {
        parent::__construct($query, $config_manager);
        $this->query = $query;
    }

    /** ------------ @category 1. 등록 관련 ------------ */
    // (?) 회원가입
    public function signup(array $client_data): array
    {
        // 사용자 데이터 받아오기
        $email = $this->check_string_data($client_data, 'email');
        $password =  $this->check_string_data($client_data, 'password');
        $name =  $this->check_string_data($client_data, 'name');

        // 예외 처리 : 이미 회원가입이 된 사용자인 경우
        $duplicated_user_result = $this->query->select_user_by_email($email);
        if ($duplicated_user_result) ResponseHelper::get_instance()->error_response(400, 'already registerd');

        $this->query->insert_signup_user($email, $password, $name);

//        $user_idx = $this->query->select_inserted_id(); // 마지막으로 회원가입 완료한 user_idx 가져오기, TODO: 마지막에 회원가입을 완료한 다른 아이디랑 겹쳐서 들어오면...?
//        // 세션 생성 - 세션의 생명주기는 3주(21일)
//        $session_array = $this->generate_session(true);
//        $session_id = $session_array['session_id'];
//        $generation_time = $session_array['generation_time'];
//        $expiration_time = $session_array['expiration_time'];
//
//        // 사용자 세션 정보를 DB에 저장한다.
//        $this->query->insert_user_session($user_idx, $session_id, $generation_time, $expiration_time);
//
//        // TODO: 쿠키 발생 시간, 만료 시간을 DB에 저장하는데 setcookie에서도 설정을 해줄 수 있는 걸로 아는데 굳이 분리할 필요가 있을까?
//        setcookie('session_id', $session_id); // 사용자 세션 아이디를 쿠키에 넣어서 응답한다.

        return [
            'result' => $this->success_result
        ];
    }

    /** ----------- @category 2. 로그인 (세션), 로그아웃 관련 ----------- */
    // (?) 로그인 (자동)
    public function auto_signin(array $client_data): array
    {
        $device_model = $this->check_string_data($client_data, 'device_model');
        $app_version =  $this->check_string_data($client_data, 'app_version');
        $session_id = $this->get_session_id(); // 클라가 보낸 세션 id를 받음
        $session_array = $this->generate_session(false); // 세션 id를 제외한 생성 시간, 만기 시간 생성
        $generation_time = $session_array['generation_time'];
        $expiration_time = $session_array['expiration_time'];

        // 사용자 세션 id로 세션 확인
        $user_session_result = $this->query->select_user_session($session_id);

        // 테이블에 세션 정보가 없다면 쿠키 제거
        if (empty($user_session_result)) {
            setcookie('session_id', '', time() - $this->one_hour); // 세션 관련 쿠키 삭제
            ResponseHelper::get_instance()->error_response(400, 'wrong session, need new login');
        }

        $session_row = $user_session_result[0];  // 디비에 저장된 세션의 만료일을 가져온다
        $db_expiration_time = $session_row['expiration_time'];
        $login_type = $session_row['login_type'];
        $user_info_idx = (int)$session_row['user_info_idx'];
        $profile_image_idx = (int)$session_row['profile_image_idx'];
        $profile_image_name = $session_row['profile_image_url'];

        // 세션이 만료된 경우 - 세션 테이블, 쿠키에서 세션 데이터 삭제
        if ($generation_time > $db_expiration_time) {
            $this->query->delete_user_session($session_id); // 세션 테이블에서 세션 삭제
            setcookie('session_id', '', time() - $this->one_hour); // 세션 관련 쿠키 삭제
            ResponseHelper::get_instance()->error_response(400, 'session expiration, need new login');
        }

        // 프로필 이미지가 존재한다면 url 만들어줌
        if (isset($profile_image_name) && $profile_image_name != 'empty_image') $profile_image_url = SERVER_URL . $this->get_user_image_dir($user_info_idx) . $profile_image_name;

        // 세션이 만료되지 않은 경우
        $this->query->begin_transaction();
        // 사용자 세션 업데이트
        $this->query->update_user_session($session_id, $expiration_time);
        // 유저의 마지막 로그인 시간, 앱버전 정보를 수정
        $this->query->update_user_last_login($generation_time, $user_info_idx, $device_model, $app_version);
        $this->query->commit_transaction();

        // 응답할 때 쿠키에 사용자가 보낸 세션 아이디를 담아서 보냄
        setcookie('session_id', $session_id);
        // 응답할 때 사용자 인덱스 + 이미지 정보를 보냄
        return [
            'result' => $this->success_result,
            'login_type' => $login_type,
            'user_info_idx' => $user_info_idx,
            'profile_image_idx' => isset($profile_image_idx) ? $profile_image_idx : NULL,
            'profile_image_url' => isset($profile_image_url) ? $profile_image_url : NULL
        ];
    }

    // (?) 로그인 (일반)
    public function general_signin(array $client_data): array
    {
        $login_type = $this->check_string_data($client_data, 'login_type');
        $identifier = $this->check_string_data($client_data, 'identifier'); // kakao 로그인에 필요한 이메일
        $device_model = $this->check_string_data($client_data, 'device_model');
        $app_version = $this->check_string_data($client_data, 'app_version');

        // 회원가입이 되어 있는지 확인
        $registered_user_result = $this->query->select_user_by_identifier($login_type, $identifier);

        // 유저 정보가 없으면 에러 발생
        if (empty($registered_user_result)) ResponseHelper::get_instance()->error_response(204, 'no such user'); // TODO: 바꿔놓기

        // 이미 가입한 사용자인 경우
        $registered_user_row = $registered_user_result[0]; // $registered_user_row : 가입한 사용자 정보(유저 인덱스 + 프로필 이미지)를 가져옴 (type: array)
        $user_info_idx = (int)$registered_user_row['user_info_idx'];
        $profile_image_idx = (int)$registered_user_row['profile_image_idx'];
        $profile_image_name = $registered_user_row['profile_image_url'];

        // 프로필 이미지가 존재한다면 url 만들어줌
        if (isset($profile_image_name) && $profile_image_name != 'empty_image') $profile_image_url = SERVER_URL . $this->get_user_image_dir($user_info_idx) . $profile_image_name;

        $this->query->begin_transaction();
        // 유저의 마지막 로그인 시간, 앱버전 정보를 수정한다.
        $this->query->update_user_last_login($user_info_idx, $device_model, $app_version);

        // 기존 새션 정보가 있는지 확인
        $db_user_session_result = $this->query->select_user_session_user_idx($user_info_idx, $device_model);

        // 기존 세션 정보가 없다면
        if (empty($db_user_session_result)) {
            /** @internal 세션 생성 - 세션의 생명주기는 3주(21일)이다 */
            $session_array = $this->generate_session(true);
            $session_id = $session_array['session_id'];
            $generation_time = $session_array['generation_time'];
            $expiration_time = $session_array['expiration_time'];
            // 세션 정보를 저장
            $this->query->insert_user_session($user_info_idx, $session_id, $device_model, $generation_time, $expiration_time);
        } else {
            $session_id = $db_user_session_result[0]['session_id'];
            $expiration_time = date('Y-m-d H:i:s', time() + (21 * 24 * 60 * 60));
            // 기존 세션 정보가 있다면 새로운 세션 아이디로 업데이트
            $this->query->update_user_session($session_id, $expiration_time, $device_model, $app_version);
        }
        $this->query->commit_transaction();

        setcookie('session_id', $session_id); // 세션 아이디를 헤더에 담아 보낸다.

        return [
            'result' => $this->success_result,
            'message' => 'create session',
            'user_info_idx' => $user_info_idx,
            'profile_image_idx' => isset($profile_image_idx) ? $profile_image_idx : NULL,
            'profile_image_url' => isset($profile_image_url) ? $profile_image_url : NULL
        ];
    }

    // (3) 로그인 (네이버)

    // (4) 로그인 (카카오)

    // (?) 로그아웃


    /** ----------- @category ?. 유틸리티 ----------- */
    private function generate_session(bool $need_session_id): array
    {
        if ($need_session_id) $session_id = $this->generate_string(40); // 새로운 세션 id가 필요하면 생성

        $session_period = 21;
        $next_week = time() + ($session_period * 24 * 60 * 60);
        $generation_time = date('Y-m-d H:i:s');
        $expiration_time = date('Y-m-d H:i:s', $next_week);

        return [
            'session_id' => isset($session_id) ? $session_id : null,
            'session_period' => $session_period,
            'generation_time' => $generation_time,
            'expiration_time' => $expiration_time
        ];
    }
}
