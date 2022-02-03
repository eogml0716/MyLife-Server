<?php

namespace MyLifeServer\app\models\sql;

use MyLifeServer\core\model\database\Query;

class UserQuery extends Query
{
    public function __construct(array $mobile_db_config)
    {
        parent::__construct($mobile_db_config);
    }

    /** ------------ @category 1. 등록 관련 ------------ */
    /**
     * (?) 회원가입
     * 설명 : 유저 정보를 DB에 저장한다.
     * @param string $email
     * @param string $password
     * @param string $name
     * @param string $profile_image_url
     */
    public function insert_signup_user(
        string $email,
        string $password,
        string $name,
        string $profile_image_url
    ): void {
        $this->insert_data($this->user_table, [
            'email' => $email,
            'password' => $password,
            'name' => $name,
            'profile_image_url' => $profile_image_url
        ]);
    }

    /** ----------- @category 2. 로그인 (세션), 로그아웃 관련 ----------- */
    /**
     * (?) 사용자 세션 추가
     * 설명 : 유저의 세션을 DB에 추가한다.
     * @param int $user_idx
     * @param string $session_id
     * @param string $generation_time
     * @param string $expiration_time
     */
    public function insert_user_session(int $user_idx, string $session_id, string $generation_time, string $expiration_time): void
    {
        $this->insert_data($this->user_session_table, [
            'user_idx' => $user_idx,
            'session_id' => $session_id,
            'generation_time' => $generation_time,
            'expiration_time' => $expiration_time
        ]);
    }

    // session_id로 유저 세션 가져오기
    public function select_user_session(string $session_id): array
    {
        $sql_statement = "SELECT * FROM user_session as user_session INNER JOIN user as user 
                          WHERE user.user_idx = user_session.user_idx  AND session_id = '{$session_id}'";
        return $this->fetch_query_data($sql_statement);
    }

    /**
     * TODO: (?) 로그인
     * 설명 : 유저 정보를 DB에 저장한다.
     * @param string $email
     */

    /**
     * (?) 세션 아이디 갱신
     * 설명 : 유저 세션 아이디를 갱신하여준다.
     * @param string $session_id
     * @param string $expiration_time
     */
    public function update_user_session(string $session_id, string $expiration_time): void
    {
        $column_condition = $this->make_relational_conditions($this->equal,  ['session_id' => $session_id]);
        $expiration_time_condition = $this->make_relational_conditions($this->equal, ['expiration_time' => $expiration_time]);
        $this->update_by_operator($this->user_session_table, $column_condition, $expiration_time_condition);
    }

    /**
     * (?) 유저 마지막 로그인 정보 갱신
     * 설명 : 유저가 마지막으로 로그인한 시간을 갱신해준다.
     * @param string $user_idx
     */
    public function update_user_last_login(int $user_idx): void
    {
        $column_condition = $this->make_relational_conditions($this->equal, ['user_idx' => $user_idx]);
        $login_time_condition = $this->make_relational_conditions($this->equal, ['last_login_date' => $this->current_timestamp], false);
        $this->update_by_operator($this->user_table, $column_condition, $login_time_condition);
    }

    /**
     * (?) TODO:  로그아웃
     * 설명 : 유저 정보를 DB에 저장한다.
     * @param string $email
     */
    // (3) session_id로 사용자 세션 삭제 - 회원탈퇴, 로그아웃에서도 사용
    public function delete_user_session(string $session_id): void
    {
        $condition = $this->make_relational_conditions($this->equal, ['session_id' => $session_id]);
        $this->delete_by_operator($this->user_session_table, $condition);
    }

    /** ----------- @category ?. 유틸리티 ----------- */
    /**
     * (?) 사용자 정보 쿼리
     * 설명 : 유저 정보를 DB에서 가져온다.
     * @param string $email - 일반 로그인 (email), TODO: 네이버 로그인 (추가 예정), 카카오 로그인 (추가 예정)
     * @param string $name
     * @param string $passowrd
     */
    public function select_user_by_email(string $email): array
    {
        $not_delete_condition = $this->make_relational_conditions($this->is, ['delete_date' => $this->null], false);
        $condition = $this->make_relational_conditions($this->equal, ['email' => $email]);
        $conditions = $this->combine_conditions($not_delete_condition, $condition);
        return $this->select_by_operator($this->user_table, $this->none, ['*'], $conditions);
    }

    public function select_user_by_name(string $name): array
    {
        $not_delete_condition = $this->make_relational_conditions($this->is, ['delete_date' => $this->null], false);
        $condition = $this->make_relational_conditions($this->equal, ['email' => $name]);
        $conditions = $this->combine_conditions($not_delete_condition, $condition);
        return $this->select_by_operator($this->user_table, $this->none, ['*'], $conditions);
    }

    public function select_user_by_email_and_password(string $email, string $password): array
    {
        $not_delete_condition = $this->make_relational_conditions($this->is, ['delete_date' => $this->null], false);
        $condition = $this->make_relational_conditions($this->equal, [
            'email' => $email,
            'password' => $password
        ]);
        $conditions = $this->combine_conditions($not_delete_condition, $condition);
        return $this->select_by_operator($this->user_table, $this->none, ['*'], $conditions);
    }

    /**
     * (?) 사용자 세션 쿼리
     * 설명 : 세션 정보를 DB에서 가져온다.
     * @param int $user_idx
     */
    public function select_user_session_by_user_idx(int $user_idx): array
    {
        $conditions = $this->make_relational_conditions($this->equal, ['user_idx' => $user_idx]);
        return $this->select_by_operator($this->user_session_table, $this->none, ['*'], $conditions);
    }

    // 유저 인덱스로 유저 정보 쿼리
    public function select_user_by_user_idx(int $user_idx): array
    {
        $not_delete_condition = $this->make_relational_conditions($this->is, ['delete_date' => $this->null], false);
        $user_idx_condition = $this->make_relational_conditions($this->equal, ['user_idx' => $user_idx]);
        $conditions = $this->combine_conditions($not_delete_condition, $user_idx_condition);
        return $this->select_by_operator($this->user_table, $this->none, ['*'], $conditions);
    }
}
