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
        $duplicated_email_result = $this->query->select_user_by_email($email);
        if ($duplicated_email_result) ResponseHelper::get_instance()->error_response(400, 'already registerd email');

        $duplicated_name_result = $this->query->select_user_by_name($name);
        if ($duplicated_name_result) ResponseHelper::get_instance()->error_response(400, 'already registerd name');

        // 유저 기본 프로필 이미지
        $profile_image_url = "{$this->server_url}/assets/user/null.png";

        $this->query->insert_signup_user($email, $password, $name, $profile_image_url);

        return [
            'result' => $this->success_result
        ];
    }

    /** ----------- @category 2. 로그인 (세션), 로그아웃 관련 ----------- */
    // (?) 로그인 (일반)
    public function general_signin(array $client_data): array
    {
        // TODO: 일단 일반 로그인으로 구현, 자동, 네이버, 카카오 로그인과 메소드를 합쳐서 사용할 지 분리해서 사용할 지는 추후에 결정
//        $login_type = $this->check_string_data($client_data, 'login_type');
        $email = $this->check_string_data($client_data, 'email');
        $password = $this->check_string_data($client_data, 'password');

        // 이메일, 비밀번호로 회원가입 여부 확인
        $registered_user_result = $this->query->select_user_by_email_and_password($email, $password);

        // 유저 정보가 없으면 에러 발생
        if (empty($registered_user_result)) ResponseHelper::get_instance()->error_response(204, 'no such user'); // TODO: 바꿔놓기

        // 가입한 사용자인 경우
        $registered_user_row = $registered_user_result[0]; // $registered_user_row : 가입한 사용자 정보(유저 인덱스 + 프로필 이미지)를 가져옴 (type: array)
        $user_idx = (int)$registered_user_row['user_idx'];
        $name = $registered_user_row['name']; // 클라이언트에 보내주기 위해서 DB에서 가져옴
        $profile_image_url = $registered_user_row['profile_image_url'];

        $this->query->begin_transaction();
        // 유저의 마지막 로그인 시간 수정
        $this->query->update_user_last_login($user_idx);

        // 기존 새션 정보가 있는지 확인
        $db_user_session_result = $this->query->select_user_session_by_user_idx($user_idx);

        // 기존 세션 정보가 없다면
        if (empty($db_user_session_result)) {
            /** @internal 세션 생성 - 세션의 생명주기는 3주(21일)이다 */
            $session_array = $this->generate_session(true);
            $session_id = $session_array['session_id'];
            $generation_time = $session_array['generation_time'];
            $expiration_time = $session_array['expiration_time'];
            // 세션 정보를 저장
            $this->query->insert_user_session($user_idx, $session_id, $generation_time, $expiration_time);
        } else {
            $session_id = $db_user_session_result[0]['session_id'];
            $expiration_time = date('Y-m-d H:i:s', time() + (21 * 24 * 60 * 60));
            // 기존 세션 정보가 있다면 새로운 세션 아이디로 업데이트
            $this->query->update_user_session($session_id, $expiration_time);
        }
        $this->query->commit_transaction();

        setcookie('session_id', $session_id); // 세션 아이디를 헤더에 담아 보낸다.

        return [
            'result' => $this->success_result,
            'user_idx' => $user_idx,
            'email' => $email,
            'name' => $name,
            'profile_image_url' => $profile_image_url
        ];
    }

    // TODO: (?) 자동 로그인

    // TODO: (?) 로그인 (네이버)

    // TODO: (?) 로그인 (카카오)

    // TODO: (?) 로그아웃


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
