<?php

namespace MyLifeServer\app\models;

use Exception;
use MyLifeServer\app\ConfigManager;
use MyLifeServer\app\models\sql\CommonQuery;
use MyLifeServer\core\model\HttpRequester;
use MyLifeServer\core\model\Model;
use MyLifeServer\core\utils\ResponseHelper;
use stdClass;

class ChatModel extends Model
{
    private $query;

    public function __construct(CommonQuery $query, ConfigManager $config_manager)
    {
        parent::__construct($query, $config_manager);
        $this->query = $query;
    }

    // TODO: 사실 채팅방 이름이랑 open_type close일 때 open으로 변경해주는 거 빼고는 굳이 가지고 올 필요가 없기는 하지만 일단 구현해놓기
    public function read_info(array $client_data): array
    {
//        $user_idx = $this->check_user_session($client_data);
        $user_idx = $this->check_int_data($client_data, 'user_idx');
        $chat_room_idx = $this->check_int_data($client_data, 'chat_room_idx');

        $chat_room_result = $this->query->select_chat_room_by_chat_room_idx($chat_room_idx);
        $chat_room_row = $chat_room_result[0];

        $chat_room_idx = $chat_room_row['chat_room_idx'];
        $type = $chat_room_row['type'];
        if ($type == 'PERSONAL_GENERAL') {
            $other_chat_member_result = $this->query->select_chat_room_member_by_user_idx_and_not_equal($chat_room_idx, $user_idx);
            $other_user_idx = $other_chat_member_result[0]['user_idx'];

            $other_user_result = $this->query->select_user_by_user_idx($other_user_idx);
            $other_user_row = $other_user_result[0];

            $chat_room_image_url = $other_user_row['profile_image_url'];
            $chat_room_name = $other_user_row['name'];
        } else if ($type == 'GROUP_GENERAL') {
            // TODO: 일반 그룹 채팅방 관련 처리
        }

        $open_type = $chat_room_row['open_type'];
        if ($open_type == 'OPEN') {
            $this->query->update_chat_room_open_type_close_to_open($chat_room_idx);
        }
        $last_message = $chat_room_row['last_message'];
        $last_message_date = $chat_room_row['last_message_date'];
        $create_date = $chat_room_row['create_date'];
        $update_date = $chat_room_row['update_date'];

        return [
            'result' => $this->success_result,
            'chat_room_idx' => $chat_room_idx,
            'type' => $type,
            'chat_room_image_url' => $chat_room_image_url,
            'chat_room_name' => $chat_room_name,
            'open_type' => $open_type,
            'last_message' => $last_message,
            'last_message_date' => $last_message_date,
            'create_date' => $create_date,
            'update_date' => $update_date
        ];
    }

    public function read_rooms(array $client_data): array
    {
//        $user_idx = $this->check_user_session($client_data);
        $user_idx = $this->check_int_data($client_data, 'user_idx');
        $page = $this->check_int_data($client_data, 'page');
        $limit = $this->check_int_data($client_data, 'limit');
        $table_name = $this->query->chat_room_member_table;
        $start_num = ($page - 1) * $limit; // 요청하는 페이지에 시작 번호
        $is_last = false;

        $chat_room_members_result = $this->query->select_chat_rooms_order_by_create_date($table_name, $limit, $start_num, $user_idx);

        if (empty($chat_room_members_result)) ResponseHelper::get_instance()->error_response(204, 'no item');
        if (count($chat_room_members_result) < $limit) $is_last = true;

        $chat_rooms = $this->make_chat_rooms_items($chat_room_members_result, $user_idx);

        return [
            'result' => $this->success_result,
            'isLast' => $is_last,
            'chat_rooms' => $chat_rooms
        ];
    }

    public function insert_personal_room(array $client_data): array
    {
        $user_idx = $this->check_user_session($client_data);
        $idx = $this->check_int_data($client_data, 'idx'); // 상대방 유저의 인덱스, TODO: 그냥 채팅 멤버들을 list로 받아와서 처리해주면 개인 채팅이랑 그룹 채팅 굳이 안 놔도 될 듯?
        $type = 'PERSONAL_GENERAL';

        $this->query->begin_transaction();
        $this->query->insert_chat_room($type);
        $chat_room_idx = $this->query->select_inserted_id();
        $this->query->insert_chat_room_member($chat_room_idx, $user_idx, $type);
        $this->query->insert_chat_room_member($chat_room_idx, $idx, $type);
        $this->query->commit_transaction();

        $chat_room_result = $this->query->select_chat_room_by_chat_room_idx($chat_room_idx);
        $chat_room_row = $chat_room_result[0];

        $chat_room_idx = $chat_room_row['chat_room_idx'];
        $type = $chat_room_row['type'];
        if ($type == 'PERSONAL_GENERAL') {
            $other_chat_member_result = $this->query->select_chat_room_member_by_user_idx_and_not_equal($chat_room_idx, $user_idx);
            $other_user_idx = $other_chat_member_result[0]['user_idx'];
            $other_user_result = $this->query->select_user_by_user_idx($other_user_idx);
            $other_user_row = $other_user_result[0];

            $chat_room_image_url = $other_user_row['profile_image_url'];
            $chat_room_name = $other_user_row['name'];
        } else if ($type == 'GROUP_GENERAL') {
            // TODO: 일반 그룹 채팅방 관련 처리
        }
        $open_type = $chat_room_row['open_type'];
        if ($open_type == 'OPEN') {
            $this->query->update_chat_room_open_type_close_to_open($chat_room_idx);
        }
        $last_message = $chat_room_row['last_message'];
        $last_message_date = $chat_room_row['last_message_date'];
        $create_date = $chat_room_row['create_date'];
        $update_date = $chat_room_row['update_date'];

        return [
            'result' => $this->success_result,
            'chat_room_idx' => $chat_room_idx,
            'type' => $type,
            'chat_room_image_url' => $chat_room_image_url,
            'chat_room_name' => $chat_room_name,
            'open_type' => $open_type,
            'last_message' => $last_message,
            'last_message_date' => $last_message_date,
            'create_date' => $create_date,
            'update_date' => $update_date
        ];
    }

    // TODO: 무한 스크롤링 일단 보류
    public function read_messages(array $client_data): array
    {
        $user_idx = $this->check_user_session($client_data);
        $page = $this->check_int_data($client_data, 'page');
        $limit = $this->check_int_data($client_data, 'limit');
        $chat_room_idx = $this->check_int_data($client_data, 'chat_room_idx');
        $table_name = $this->query->message_table;
        $start_num = ($page - 1) * $limit; // 요청하는 페이지에 시작 번호
        $is_last = false;

//        $messages_result = $this->query->select_messages_order_by_create_date($table_name, $limit, $start_num, $chat_room_idx);
        $messages_result = $this->query->select_message_by_chat_room_idx($chat_room_idx);

        if (empty($messages_result)) ResponseHelper::get_instance()->error_response(204, 'no item');
        if (count($messages_result) < $limit) $is_last = true;

        $messages = $this->make_message_items($messages_result);

        return [
            'result' => $this->success_result,
            'isLast' => $is_last,
            'messages' => $messages
        ];
    }

    public function create_text_message(array $client_data): array
    {
        $user_idx = $this->check_user_session($client_data);
        $chat_room_idx = $this->check_int_data($client_data, 'chat_room_idx');
        $contents = $this->check_string_data($client_data, 'contents');

        $user_result = $this->query->select_user_by_user_idx($user_idx);
        if (empty($user_result)) ResponseHelper::get_instance()->error_response(204, 'non-existent user');

        $this->query->begin_transaction();
        $message_type = 'TEXT';
        $this->query->insert_message($chat_room_idx, $user_idx, $message_type, $contents);

        $message_idx = $this->query->select_inserted_id();
        $message_result = $this->query->select_message_by_message_idx($message_idx);
        $message_row = $message_result[0];

        $chat_room_idx = $message_row['chat_room_idx'];
        $user_idx = $message_row['user_idx'];
        $name = $user_result[0]['name'];
        $profile_image_url = $user_result[0]['profile_image_url'];
        $message_type = $message_row['message_type'];
        $contents = $message_row['contents'];
        $create_date = $message_row['create_date'];
        $update_date = $message_row['update_date'];

        $this->query->update_chat_room_last_message($chat_room_idx, $contents, $create_date);

        $this->query->commit_transaction();

        return [
            'result' => $this->success_result,
            'message_idx' => $message_idx,
            'chat_room_idx' => $chat_room_idx,
            'user_idx' => $user_idx,
            'name' => $name,
            'profile_image_url' => $profile_image_url,
            'message_type' => $message_type,
            'contents' => $contents,
            'create_date' => $create_date,
            'update_date' => $update_date
        ];
    }

    public function create_image_message(array $client_data): array
    {
        $user_idx = $this->check_user_session($client_data);
        $chat_room_idx = $this->check_int_data($client_data, 'chat_room_idx');
        $image = $this->check_string_data($client_data, 'image');
        $image_name = $this->check_string_data($client_data, 'image_name');

        $user_result = $this->query->select_user_by_user_idx($user_idx);
        if (empty($user_result)) ResponseHelper::get_instance()->error_response(204, 'non-existent user');

        $message_type = 'IMAGE';
        // 이미지 서버에 저장
        $image_url = $this->store_image($image, $image_name, $this->chat_message_image_folder);

        $this->query->begin_transaction();
        $this->query->insert_message($chat_room_idx, $user_idx, $message_type, $image_url);

        $message_idx = $this->query->select_inserted_id();
        $message_result = $this->query->select_message_by_message_idx($message_idx);
        $message_row = $message_result[0];

        $chat_room_idx = $message_row['chat_room_idx'];
        $user_idx = $message_row['user_idx'];
        $name = $user_result[0]['name'];
        $profile_image_url = $user_result[0]['profile_image_url'];
        $message_type = $message_row['message_type'];
        $contents = $message_row['contents'];
        $create_date = $message_row['create_date'];
        $update_date = $message_row['update_date'];

        $this->query->update_chat_room_last_message($chat_room_idx, $message_type, $create_date);
        $this->query->commit_transaction();

        return [
            'result' => $this->success_result,
            'message_idx' => $message_idx,
            'chat_room_idx' => $chat_room_idx,
            'user_idx' => $user_idx,
            'name' => $name,
            'profile_image_url' => $profile_image_url,
            'message_type' => $message_type,
            'contents' => $contents,
            'create_date' => $create_date,
            'update_date' => $update_date
        ];
    }

    public function delete_personal_room(array $client_data): array
    {
        $user_idx = $this->check_user_session($client_data);
        $chat_room_idx = $this->check_int_data($client_data, 'chat_room_idx');

        // TODO: 1:1 방 나간 사용자의 메시지는 어떻게 처리해줄 지 생각해보기, 방을 나간 사용자는 이전의 메시지를 전부 보고싶지 않을텐데 이걸 어떻게 처리할 지 생각해야함
        $this->query->update_chat_room_open_type_open_to_close();

        return [
            'result' => $this->success_result
        ];
    }

    private function make_chat_rooms_items(array $chat_rooms_result, int $user_idx): array
    {
        $chat_rooms = [];

        foreach ($chat_rooms_result as $chat_rooms_item_result) {
            $chat_room_idx = (int)$chat_rooms_item_result['chat_room_idx'];
            $chat_room_result = $this->query->select_chat_room_by_chat_room_idx($chat_room_idx);
            $chat_room_row = $chat_room_result[0];
            $type = $chat_room_row['type'];
            $chat_rooms_item['chat_room_idx'] = $chat_room_idx;
            $chat_rooms_item['type'] = $type;
            $open_type = $chat_room_row['open_type'];
            $chat_rooms_item['open_type'] = $open_type;

            if ($open_type == 'OPEN') {
                if ($type == 'PERSONAL_GENERAL') {
                    $other_chat_member_result = $this->query->select_chat_room_member_by_user_idx_and_not_equal($chat_room_idx, $user_idx);
                    $other_user_idx = (int)$other_chat_member_result[0]['user_idx'];
                    $other_user_result = $this->query->select_user_by_user_idx($other_user_idx);
                    $other_user_row = $other_user_result[0];

                    $chat_rooms_item['chat_room_image_url'] = $other_user_row['profile_image_url'];
                    $chat_rooms_item['chat_room_name'] = $other_user_row['name'];
                } else if ($type == 'GROUP_GENERAL') {
                    // TODO: 일반 그룹 채팅방 관련 처리
                }

                // 채팅방 멤버 수
                $chat_room_member_count = $this->query->select_chat_room_member_count($chat_room_idx);
                $chat_rooms_item['chat_room_member_count'] = $chat_room_member_count;

                // 채팅방 마지막 메시지
                $chat_rooms_item['last_message'] = $chat_room_row['last_message'];
                // 채팅방 마지막 메시지 시간
                $chat_rooms_item['last_message_date'] = $chat_room_row['last_message_date'];
                $chat_rooms_item['create_date'] = $chat_room_row['create_date'];
                $chat_rooms_item['update_date'] = $chat_room_row['update_date'];

                $chat_rooms[] = $chat_rooms_item;
            }
        }
        return $chat_rooms;
    }

    private function make_message_items(array $messages_result): array
    {
        $messages = [];

        foreach ($messages_result as $messages_item_result) {
            $messages_item['message_idx'] = (int)$messages_item_result['message_idx'];
            $chat_room_idx = (int)$messages_item_result['chat_room_idx'];
            $messages_item['chat_room_idx'] = $chat_room_idx;

            $user_idx = (int)$messages_item_result['user_idx'];
            $messages_item['user_idx'] = $user_idx;

            $user_result = $this->query->select_user_by_user_idx($user_idx);
            $user_row = $user_result[0];

            $messages_item['name'] = $user_row['name'];
            $messages_item['profile_image_url'] = $user_row['profile_image_url'];

            $messages_item['message_type'] = $messages_item_result['message_type'];
            $messages_item['contents'] = $messages_item_result['contents'];

            $messages_item['create_date'] = $messages_item_result['create_date'];
            $messages_item['update_date'] = $messages_item_result['update_date'];

            $messages[] = $messages_item;
        }
        return $messages;
    }
}