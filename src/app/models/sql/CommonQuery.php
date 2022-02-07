<?php

namespace MyLifeServer\app\models\sql;

use MyLifeServer\core\model\database\Query;

class CommonQuery extends Query
{
    public function __construct(array $mobile_db_config)
    {
        parent::__construct($mobile_db_config);
    }

    /** ------------ @category ?. SELECT ------------ */
    // session_id로 유저 세션 가져오기
    public function select_user_session(string $session_id): array
    {
        $sql_statement = "SELECT * FROM user_session INNER JOIN user WHERE user.user_idx = user_session.user_idx AND session_id = '{$session_id}'";
        return $this->fetch_query_data($sql_statement);
    }

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

    // (?) 내가 팔로잉한 사람이 게시글 리스트 가져오기 - create_date 기준 정렬 TODO: 현재 구현 중, 에러 터질 수 있음
    public function select_items_order_by_create_date(int $limit, int $start_num, int $user_idx): array
    {
        $sql_statement = "SELECT * FROM board INNER JOIN follow ON board.user_idx = follow.to_user_idx WHERE follow.from_user_idx IN ('{$user_idx}') AND board.delete_date IS NULL AND follow.delete_date IS NULL ORDER BY board.create_date DESC LIMIT {$limit} OFFSET {$start_num}";
//        echo $sql_statement;
        return $this->fetch_query_data($sql_statement);
    }

    public function select_random_posts(int $limit, int $start_num): array
    {
        $sql_statement = "SELECT * FROM board WHERE delete_date IS NULL ORDER BY RAND() DESC LIMIT {$limit} OFFSET {$start_num}";
//        echo $sql_statement;
        return $this->fetch_query_data($sql_statement);
    }

    // (?) 나의 게시글 리스트 가져오기 - create_date 기준 정렬
    public function select_posts_order_by_create_date(string $table_name, int $limit, int $start_num, int $user_idx): array
    {
        $not_delete_condition = $this->make_relational_conditions($this->is, ['delete_date' => $this->null], false);
        $user_idx_condition = $this->make_relational_conditions($this->equal, ['user_idx' => $user_idx]);
        $conditions = $this->combine_conditions($not_delete_condition, $user_idx_condition);
        return $this->select_page_by_operator($table_name, ['*'], $conditions,'create_date', $limit, $start_num);
    }

    // (?) 댓글 아이템 리스트 가져오기 - create_date 기준 정렬
    public function select_comments_order_by_create_date(string $table_name, int $limit, int $start_num, int $board_idx): array
    {
        $not_delete_condition = $this->make_relational_conditions($this->is, ['delete_date' => $this->null], false);
        $board_idx_condition = $this->make_relational_conditions($this->equal, ['board_idx' => $board_idx]);
        $conditions = $this->combine_conditions($not_delete_condition, $board_idx_condition);
        return $this->select_page_by_operator($table_name, ['*'], $conditions,'create_date', $limit, $start_num);
    }

    // (?) 팔로잉 리스트 가져오기 - create_date 기준 정렬
    public function select_followings_order_by_create_date(string $table_name, int $limit, int $start_num, int $from_user_idx): array
    {
        $not_delete_condition = $this->make_relational_conditions($this->is, ['delete_date' => $this->null], false);
        $from_user_idx_condition = $this->make_relational_conditions($this->equal, ['from_user_idx' => $from_user_idx]);
        $conditions = $this->combine_conditions($not_delete_condition, $from_user_idx_condition);
        return $this->select_page_by_operator($table_name, ['*'], $conditions,'create_date', $limit, $start_num);
    }

    // (?) 팔로워 리스트 가져오기 - create_date 기준 정렬
    public function select_followers_order_by_create_date(string $table_name, int $limit, int $start_num, int $to_user_idx): array
    {
        $not_delete_condition = $this->make_relational_conditions($this->is, ['delete_date' => $this->null], false);
        $to_user_idx_condition = $this->make_relational_conditions($this->equal, ['to_user_idx' => $to_user_idx]);
        $conditions = $this->combine_conditions($not_delete_condition, $to_user_idx_condition);
        return $this->select_page_by_operator($table_name, ['*'], $conditions,'create_date', $limit, $start_num);
    }

    public function select_board_by_board_idx(int $board_idx): array
    {
        $not_delete_condition = $this->make_relational_conditions($this->is, ['delete_date' => $this->null], false);
        $board_idx_condition = $this->make_relational_conditions($this->equal, ['board_idx' => $board_idx]);
        $conditions = $this->combine_conditions($not_delete_condition, $board_idx_condition);
        return $this->select_by_operator($this->board_table, $this->none, ['*'], $conditions);
    }

    public function select_board_image_by_board_idx(int $board_idx): array
    {
        $not_delete_condition = $this->make_relational_conditions($this->is, ['delete_date' => $this->null], false);
        $board_idx_condition = $this->make_relational_conditions($this->equal, ['board_idx' => $board_idx]);
        $conditions = $this->combine_conditions($not_delete_condition, $board_idx_condition);
        return $this->select_by_operator($this->board_image_table, $this->none, ['*'], $conditions);
    }

    public function select_comment_by_comment_idx(int $comment_idx): array
    {
        $not_delete_condition = $this->make_relational_conditions($this->is, ['delete_date' => $this->null], false);
        $comment_idx_condition = $this->make_relational_conditions($this->equal, ['comment_idx' => $comment_idx]);
        $conditions = $this->combine_conditions($not_delete_condition, $comment_idx_condition);
        return $this->select_by_operator($this->comment_table, $this->none, ['*'], $conditions);
    }

    public function select_liked(int $user_idx, string $type, int $idx): array
    {
        $not_delete_condition = $this->make_relational_conditions($this->is, ['delete_date' => $this->null], false);
        $condition = $this->make_relational_conditions($this->equal, [
            'user_idx' => $user_idx,
            'type' => $type,
            'idx' => $idx
        ]);
        $conditions = $this->combine_conditions($not_delete_condition, $condition);
        return $this->select_by_operator($this->liked_table, $this->none, ['*'], $conditions);
    }

    // TODO: 그냥 like_count라고 이름을 지을 걸 그랬나 되게 불편하네
    public function select_like_count(string $type, int $idx): int
    {
        $not_delete_condition = $this->make_relational_conditions($this->is, ['delete_date' => $this->null], false);
        $condition = $this->make_relational_conditions($this->equal, [
            'type' => $type,
            'idx' => $idx
        ]);
        $conditions = $this->combine_conditions($not_delete_condition, $condition);
        return $this->select_by_operator($this->liked_table, $this->none, [$this->count_method], $conditions)[0][$this->count_method];
    }

    public function select_post_count(int $user_idx): int
    {
        $not_delete_condition = $this->make_relational_conditions($this->is, ['delete_date' => $this->null], false);
        $condition = $this->make_relational_conditions($this->equal, [
            'user_idx' => $user_idx
        ]);
        $conditions = $this->combine_conditions($not_delete_condition, $condition);
        return $this->select_by_operator($this->board_table, $this->none, [$this->count_method], $conditions)[0][$this->count_method];
    }

    public function select_comment_count(int $board_idx): int
    {
        $not_delete_condition = $this->make_relational_conditions($this->is, ['delete_date' => $this->null], false);
        $condition = $this->make_relational_conditions($this->equal, [
            'board_idx' => $board_idx
        ]);
        $conditions = $this->combine_conditions($not_delete_condition, $condition);
        return $this->select_by_operator($this->comment_table, $this->none, [$this->count_method], $conditions)[0][$this->count_method];
    }

    public function select_follow(int $from_user_idx, int $to_user_idx): array
    {
        $not_delete_condition = $this->make_relational_conditions($this->is, ['delete_date' => $this->null], false);
        $condition = $this->make_relational_conditions($this->equal, [
            'from_user_idx' => $from_user_idx,
            'to_user_idx' => $to_user_idx
        ]);
        $conditions = $this->combine_conditions($not_delete_condition, $condition);
        return $this->select_by_operator($this->follow_table, $this->none, ['*'], $conditions);
    }

    public function select_follower_count(int $to_user_idx)
    {
        $not_delete_condition = $this->make_relational_conditions($this->is, ['delete_date' => $this->null], false);
        $condition = $this->make_relational_conditions($this->equal, [
            'to_user_idx' => $to_user_idx
        ]);
        $conditions = $this->combine_conditions($not_delete_condition, $condition);
        return $this->select_by_operator($this->follow_table, $this->none, [$this->count_method], $conditions)[0][$this->count_method];
    }

    public function select_following_count(int $from_user_idx)
    {
        $not_delete_condition = $this->make_relational_conditions($this->is, ['delete_date' => $this->null], false);
        $condition = $this->make_relational_conditions($this->equal, [
            'from_user_idx' => $from_user_idx
        ]);
        $conditions = $this->combine_conditions($not_delete_condition, $condition);
        return $this->select_by_operator($this->follow_table, $this->none, [$this->count_method], $conditions)[0][$this->count_method];
    }

    public function select_search_users(string $table_name, string $search_word, int $limit, int $start_num): array
    {
        $not_delete_condition = $this->make_relational_conditions($this->is, ['delete_date' => $this->null], false);
        $condition = $this->make_relational_conditions($this->like, [
            'name' => $search_word
        ]);
        $conditions = $this->combine_conditions($not_delete_condition, $condition);
        return $this->select_page_by_operator($table_name, ['*'], $conditions, 'create_date', $limit, $start_num);
    }

    /** ------------ @category ?. CREATE ------------ */
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

    // (?) 게시글 등록
    public function insert_board(int $user_idx, string $contents): void
    {
        $this->insert_data($this->board_table, [
            'user_idx' => $user_idx,
            'contents' => $contents
        ]);
    }

    // (?) 게시글 등록 (이미지)
    public function insert_board_image(int $board_idx, string $image_url): void
    {
        $this->insert_data($this->board_image_table, [
            'board_idx' => $board_idx,
            'image_url' => $image_url
        ]);
    }

    public function insert_comment(int $user_idx, int $board_idx, string $contents): void
    {
        $this->insert_data($this->comment_table, [
            'user_idx' => $user_idx,
            'board_idx' => $board_idx,
            'contents' => $contents
        ]);
    }

    // (?) 좋아요 등록
    public function insert_liked(int $user_idx, string $type, int $idx): void
    {
        $this->insert_data($this->liked_table, [
            'user_idx' => $user_idx,
            'type' => $type,
            'idx' => $idx
        ]);
    }

    public function insert_follow(int $from_user_idx, int $to_user_idx): void
    {
        $this->insert_data($this->follow_table, [
            'from_user_idx' => $from_user_idx,
            'to_user_idx' => $to_user_idx
        ]);
    }

    /** ------------ @category ?. UPDATE ------------ */
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

    public function update_post_by_board_idx(
        int $board_idx,
        string $contents
    ): void {
        $column_condition = $this->make_relational_conditions($this->equal, ['board_idx' => $board_idx]);
        $update_conditions = $this->make_relational_conditions($this->equal, [
            'contents' => $contents
        ]);
        $this->update_by_operator($this->board_table, $column_condition, $update_conditions);
    }

    public function update_comment_by_comment_idx(
        int $comment_idx,
        string $contents
    ): void {
        $column_condition = $this->make_relational_conditions($this->equal, ['comment_idx' => $comment_idx]);
        $update_conditions = $this->make_relational_conditions($this->equal, [
            'contents' => $contents
        ]);
        $this->update_by_operator($this->comment_table, $column_condition, $update_conditions);
    }

    public function update_comment_count(string $table_name, int $idx, int $comments)
    {
        $column_condition = $this->make_relational_conditions($this->equal, ["{$table_name}_idx" => $idx]);
        // TODO: count 함수 계속 쓰면 되게 비효율적이라고 하는데... 정확한 좋아요 개수 계산을 위해서는 그냥 count 쓰는 게 낫지않나...? 아닌가?
//        $update_condition = $this->make_relational_conditions($this->equal, ['likes' => "likes{$operator}"], false);
        $update_condition = $this->make_relational_conditions($this->equal, ['comments' => $comments]);
        $this->update_by_operator($table_name, $column_condition, $update_condition);
    }

    public function update_like_count(string $table_name, int $idx, int $likes)
    {
        $column_condition = $this->make_relational_conditions($this->equal, ["{$table_name}_idx" => $idx]);
        // TODO: count 함수 계속 쓰면 되게 비효율적이라고 하는데... 정확한 좋아요 개수 계산을 위해서는 그냥 count 쓰는 게 낫지않나...? 아닌가?
//        $update_condition = $this->make_relational_conditions($this->equal, ['likes' => "likes{$operator}"], false);
        $update_condition = $this->make_relational_conditions($this->equal, ['likes' => $likes]);
        $this->update_by_operator($table_name, $column_condition, $update_condition);
    }

    public function update_user_profile_by_image_change(int $user_idx, string $profile_image_url, string $name, string $about_me)
    {
        $column_condition = $this->make_relational_conditions($this->equal, ['user_idx' => $user_idx]);
        $update_conditions = $this->make_relational_conditions($this->equal, [
            'profile_image_url' => $profile_image_url,
            'name' => $name,
            'about_me' => $about_me
        ]);
        $this->update_by_operator($this->user_table, $column_condition, $update_conditions);
    }

    public function update_user_profile(int $user_idx, string $name, string $about_me)
    {
        $column_condition = $this->make_relational_conditions($this->equal, ['user_idx' => $user_idx]);
        $update_conditions = $this->make_relational_conditions($this->equal, [
            'name' => $name,
            'about_me' => $about_me
        ]);
        $this->update_by_operator($this->user_table, $column_condition, $update_conditions);
    }

    /** ------------ @category ?. DELETE ------------ */
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

    public function delete_post_by_board_idx(int $board_idx): void
    {
        $condition = $this->make_relational_conditions($this->equal, ['board_idx' => $board_idx]);
        $this->delete_by_updating_date($this->board_table, $condition);
    }

    public function delete_post_image_by_board_idx(int $board_idx): void
    {
        $condition = $this->make_relational_conditions($this->equal, ['board_idx' => $board_idx]);
        $this->delete_by_updating_date($this->board_image_table, $condition);
    }

    public function delete_comment_by_comment_idx(int $comment_idx): void
    {
        $condition = $this->make_relational_conditions($this->equal, ['comment_idx' => $comment_idx]);
        $this->delete_by_updating_date($this->comment_table, $condition);
    }

    public function delete_liked(int $user_idx, string $type, int $idx): void
    {
        $condition = $this->make_relational_conditions($this->equal, [
            'user_idx' => $user_idx,
            'type' => $type,
            'idx' => $idx
        ]);
        $this->delete_by_updating_date($this->liked_table, $condition);
    }

    public function delete_follow(int $from_user_idx, int $to_user_idx): void
    {
        $condition = $this->make_relational_conditions($this->equal, [
            'from_user_idx' => $from_user_idx,
            'to_user_idx' => $to_user_idx
        ]);
        $this->delete_by_updating_date($this->follow_table, $condition);
    }
}