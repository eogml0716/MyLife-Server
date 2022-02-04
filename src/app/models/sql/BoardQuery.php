<?php

namespace MyLifeServer\app\models\sql;

use MyLifeServer\core\model\database\Query;

class BoardQuery extends Query
{
    public function __construct(array $mobile_db_config)
    {
        parent::__construct($mobile_db_config);
    }

    /** ------------ @category ?. SELECT ------------ */
    // (?) 테이블별 리스트 가져오기 - update_date 기준 정렬
    public function select_items_order_by_update_date(string $table_name, int $limit, int $start_num): array
    {
        $not_delete_condition = $this->make_relational_conditions($this->is, ['delete_date' => $this->null], false);
        return $this->select_page_by_operator($table_name, ['*'], $not_delete_condition,'update_date', $limit, $start_num);
    }

    // 유저 인덱스로 유저 정보 쿼리
    public function select_user_by_user_idx(int $user_idx): array
    {
        $not_delete_condition = $this->make_relational_conditions($this->is, ['delete_date' => $this->null], false);
        $user_idx_condition = $this->make_relational_conditions($this->equal, ['user_idx' => $user_idx]);
        $conditions = $this->combine_conditions($not_delete_condition, $user_idx_condition);
        return $this->select_by_operator($this->user_table, $this->none, ['*'], $conditions);
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

    /** ------------ @category ?. CREATE 관련 ------------ */
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

    // (?) 좋아요 등록
    public function insert_liked(int $user_idx, string $type, int $idx): void
    {
        $this->insert_data($this->liked_table, [
            'user_idx' => $user_idx,
            'type' => $type,
            'idx' => $idx
        ]);
    }

    /** ------------ @category ?. UPDATE 관련 ------------ */
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

    public function update_like_count(string $table_name, int $idx, string $operator)
    {
        $column_condition = $this->make_relational_conditions($this->equal, ["{$table_name}_idx" => $idx]);
        $update_condition = $this->make_relational_conditions($this->equal, ['likes' => "likes{$operator}"], false);
        $this->update_by_operator($table_name, $column_condition, $update_condition);
    }

    /** ------------ @category ?. DELETE 관련 ------------ */
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

}
