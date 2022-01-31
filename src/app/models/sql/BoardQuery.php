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
    // 유저 인덱스로 유저 정보 쿼리
    public function select_user_by_user_idx(int $user_idx): array
    {
        $not_delete_condition = $this->make_relational_conditions($this->is, ['delete_date' => $this->null], false);
        $user_idx_condition = $this->make_relational_conditions($this->equal, ['user_idx' => $user_idx]);
        $conditions = $this->combine_conditions($not_delete_condition, $user_idx_condition);
        return $this->select_by_operator($this->user_table, $this->none, ['*'], $conditions);
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

    /** ------------ @category ?. UPDATE 관련 ------------ */

    /** ------------ @category ?. DELETE 관련 ------------ */


}
