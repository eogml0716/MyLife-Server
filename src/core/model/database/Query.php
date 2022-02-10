<?php

namespace MyLifeServer\core\model\database;

use MyLifeServer\core\model\database\QueryBuilder;
use MyLifeServer\core\utils\ResponseHelper;

class Query extends QueryBuilder
{
    // 사용자 관련 테이블명
    public $user_table = 'user';
    public $user_session_table = 'user_session';
    public $board_table = 'board';
    public $board_image_table = 'board_image';
    public $comment_table = 'comment';
    public $liked_table = 'liked';
    public $follow_table = 'follow';
    public $notification_table = 'notification';
    public $chat_room_table = 'chat_room';
    public $message_table = 'message';
    public $chat_room_member_table = 'chat_room_member';

    public function __construct(array $db_config)
    {
        parent::__construct($db_config);
    }

    // (1) 마지막으로 insert한 idx가져오기
    public function select_inserted_id(): int
    {
        if ($this->select_last_insert_id()[0]['LAST_INSERT_ID()'] == 0) {
            ResponseHelper::get_instance()->error_response(409, 'No data was saved due to a duplicate value entry.');
        }
        return $this->select_last_insert_id()[0]['LAST_INSERT_ID()'];
    }

    // (2) 세션 id 값으로 user_idx 가져오는 메소드
    public function select_user_by_session_id(string $session_id): array
    {
        $condition_list = $this->make_relational_conditions($this->equal, ['session_id' => $session_id]);
        return $this->select_by_operator($this->user_session_table, $this->none, ['user_idx'], $condition_list);
    }
}
