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
     */
    public function insert_signup_user(
        string $email,
        string $password,
        string $name
    ): void {
        $this->insert_data($this->user_table, [
            'email' => $email,
            'password' => $password,
            'name' => $name
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
    public function insert_user_session(int $user_info_idx, string $session_id, string $generation_time, string $expiration_time): void
    {
        $this->insert_data($this->user_session_table, [
            'user_info_idx' => $user_info_idx,
            'session_id' => $session_id,
            'generation_time' => $generation_time,
            'expiration_time' => $expiration_time
        ]);
    }

    /**
     * (?) 로그인
     * 설명 : 유저 정보를 DB에 저장한다.
     * @param string $email
     */

    /**
     * (?) 로그아웃
     * 설명 : 유저 정보를 DB에 저장한다.
     * @param string $email
     */

    /** ----------- @category ?. 유틸리티 ----------- */
    /**
     * (?) 사용자 정보 쿼리
     * 설명 : 유저 정보를 DB에서 가져온다.
     * @param string $email - 일반 로그인 (email), TODO: 네이버 로그인 (추가 예정), 카카오 로그인 (추가 예정)
     */
    public function select_user_by_email(string $email): array
    {
        $not_delete_condition = $this->make_relational_conditions($this->is, ['delete_date' => $this->null], false);
        $condition = $this->make_relational_conditions($this->equal, ['email' => $email]);
        $conditions = $this->combine_conditions($not_delete_condition, $condition);
        return $this->select_by_operator($this->user_table, $this->none, ['*'], $conditions);
    }
}
