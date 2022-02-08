<?php

namespace MyLifeServer\app\models;

use Exception;
use MyLifeServer\app\ConfigManager;
use MyLifeServer\app\models\sql\CommonQuery;
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

    public function __construct(CommonQuery $query, ConfigManager $config_manager)
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
        $password = $this->check_string_data($client_data, 'password');
        $name = $this->check_string_data($client_data, 'name');

        // 예외 처리 : 이미 회원가입이 된 사용자인 경우
        $duplicated_email_result = $this->query->select_user_by_email($email);
        if ($duplicated_email_result) ResponseHelper::get_instance()->error_response(400, 'already registerd email');

        $duplicated_name_result = $this->query->select_user_by_name($name);
        if ($duplicated_name_result) ResponseHelper::get_instance()->error_response(400, 'already registerd name');

        // 유저 기본 프로필 이미지
        $profile_image_url = "{$this->server_url}/assets/user/null.png";

        $this->query->begin_transaction();
        $this->query->insert_signup_user($email, $password, $name, $profile_image_url);
        $user_idx = $this->query->select_inserted_id();
        $this->query->commit_transaction();
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
        if (empty($registered_user_result)) ResponseHelper::get_instance()->error_response(204, 'non-existent user'); // TODO: 바꿔놓기

        // 가입한 사용자인 경우
        $registered_user_row = $registered_user_result[0]; // $registered_user_row : 가입한 사용자 정보(유저 인덱스 + 프로필 이미지)를 가져옴 (type: array)
        $user_idx = (int)$registered_user_row['user_idx'];
        $name = $registered_user_row['name']; // 클라이언트에 보내주기 위해서 DB에서 가져옴
        $profile_image_url = $registered_user_row['profile_image_url'];
        $about_me = $registered_user_row['about_me'];

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
            'profile_image_url' => $profile_image_url,
            'about_me' => $about_me
        ];
    }

    // 로그인 (자동)
    public function auto_signin(array $client_data): array
    {
        $user_idx = $this->check_int_data($client_data, 'user_idx');

        $session_id = $this->get_session_id(); // 클라가 보낸 세션 id를 받음
        $session_array = $this->generate_session(false); // 세션 id를 제외한 생성 시간, 만기 시간 생성
        $generation_time = $session_array['generation_time'];
        $expiration_time = $session_array['expiration_time'];

        $user_result = $this->query->select_user_by_user_idx($user_idx);
        if (empty($user_result)) ResponseHelper::get_instance()->error_response(204, 'non-existent user');
        // 유저의 session_id로 세션이 존재하는지 확인한다.
        $user_session_result = $this->query->select_user_session($session_id);
        if ($user_session_result[0]['user_idx'] != $user_idx) ResponseHelper::get_instance()->error_response(204, 'invalid user index');

        // 테이블에 세션 정보가 없다면 쿠키 제거
        if (empty($user_session_result)) {
            setcookie('session_id', '', time() - $this->one_hour); // 세션 관련 쿠키 삭제
            ResponseHelper::get_instance()->error_response(400, 'wrong session, need new login');
        }

        $db_expiration_time = $user_session_result[0]['expiration_time'];
        $db_user_idx = (int)$user_session_result[0]['user_idx'];
        $db_email = (int)$user_session_result[0]['email'];
        $db_name = $user_session_result[0]['name']; // 클라이언트에 보내주기 위해서 DB에서 가져옴
        $db_profile_image_url = $user_session_result[0]['profile_image_url'];
        $db_about_me = $user_session_result[0]['about_me'];

        // 세션이 만료된 경우 - 세션 테이블, 쿠키에서 세션 데이터 삭제
        if ($generation_time > $db_expiration_time) {
            $this->query->delete_user_session($session_id); // 세션 테이블에서 세션 삭제
            setcookie('session_id', '', time() - $this->one_hour); // 세션 관련 쿠키 삭제
            ResponseHelper::get_instance()->error_response(400, 'session expiration, need new login');
        }

        // 세션이 만료되지 않은 경우
        $this->query->begin_transaction();
        // 사용자 세션 업데이트
        $this->query->update_user_session($session_id, $expiration_time);
        // 유저의 마지막 로그인 시간 및 정보 수정
        $this->query->update_user_last_login($db_user_idx);
        $this->query->commit_transaction();

        // 응답할 때 쿠키에 사용자가 보낸 세션 아이디를 담아서 보낸다.
        setcookie('session_id', $session_id);
        // 응답할 때 클라이언트에 유저 정보를 보낸다.
        return [
            'result' => $this->success_result,
            'user_idx' => $db_user_idx,
            'email' => $db_email,
            'name' => $db_name,
            'profile_image_url' => $db_profile_image_url,
            'about_me' => $db_about_me
        ];
    }

    // TODO: (?) 로그인 (네이버)

    // TODO: (?) 로그인 (카카오)

    // (?) 로그아웃
    public function signout(array $client_data): array
    {
        $this->check_user_session($client_data);
        $session_id = $this->get_session_id();

        $this->query->delete_user_session($session_id); // 사용자 세션을 디비에서 삭제한다.

        setcookie("session_id", "", time() - $this->one_hour); // 서버에서 보낼 응답 데이터에 세션을 삭제한다.

        return [
            'result' => $this->success_result
        ];
    }

    // TODO: 유저가 각기 다른 기기로 접속할 경우 계속해서 Firebase token이 변경되고 해당 기기로만 알림이 가게 되므로 따로 테이블을 빼서 관리하는 게 좋을 거 같다.
    public function upload_firebase_token(array $client_data): array
    {
        $user_idx = $this->check_user_session($client_data);
        $firebase_token = $this->check_string_data($client_data, 'firebase_token');

        $this->query->update_firebase_token($user_idx, $firebase_token);

        return [
            'result' => $this->success_result,
            'firebase_token' => $firebase_token
        ];
    }

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
