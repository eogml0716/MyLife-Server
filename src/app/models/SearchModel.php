<?php

namespace MyLifeServer\app\models;

use Exception;
use MyLifeServer\app\ConfigManager;
use MyLifeServer\app\models\sql\CommonQuery;
use MyLifeServer\core\model\HttpRequester;
use MyLifeServer\core\model\Model;
use MyLifeServer\core\utils\ResponseHelper;
use stdClass;

class SearchModel extends Model
{
    private $query;

    public function __construct(CommonQuery $query, ConfigManager $config_manager)
    {
        parent::__construct($query, $config_manager);
        $this->query = $query;
    }

    public function read_users(array $client_data): array
    {
        $this->check_user_session($client_data);
        $page = $this->check_int_data($client_data, 'page');
        $limit = $this->check_int_data($client_data, 'limit');
        $search_word = $this->check_string_data($client_data, 'search_word');
        $table_name = $this->query->user_table;
        $start_num = ($page - 1) * $limit; // 요청하는 페이지에 시작 번호
        $is_last = false;

        $search_word = "%{$search_word}%";

        $users_result = $this->query->select_search_users($table_name, $search_word, $limit, $start_num);
        if (empty($users_result)) ResponseHelper::get_instance()->error_response(204, 'no item');
        if (count($users_result) < $limit) $is_last = true;

        $users = $this->make_users_items($users_result);

        return [
            'result' => $this->success_result,
            'is_last' => $is_last,
            'users' => $users
        ];
    }

    public function read_posts(array $client_data): array
    {
        $this->check_user_session($client_data);
        $page = $this->check_int_data($client_data, 'page');
        $limit = $this->check_int_data($client_data, 'limit');
        $start_num = ($page - 1) * $limit; // 요청하는 페이지에 시작 번호
        $is_last = false;

        $posts_result = $this->query->select_random_posts($limit, $start_num);

        if (empty($posts_result)) ResponseHelper::get_instance()->error_response(204, 'no item');
        if (count($posts_result) < $limit) $is_last = true;

        $square_posts = $this->make_square_post_items($posts_result);

        return [
            'result' => $this->success_result,
            'isLast' => $is_last,
            'posts' => $square_posts
        ];
    }

    /** ------------ @category ?. 유틸리티 ------------ */
    private function make_square_post_items(array $square_posts_result): array
    {
        $square_posts = [];

        foreach ($square_posts_result as $square_post_item_result) {
            $user_idx = (int)$square_post_item_result['user_idx'];
            $board_idx = (int)$square_post_item_result['board_idx'];
            $square_post_item['board_idx'] = $board_idx;
            $square_post_item['user_idx'] = $user_idx;
            // TODO: 다중 이미지 구현할 경우 해당 부분 코드 수정해주어야함
            $board_image_result = $this->query->select_board_image_by_board_idx($board_idx);
            $square_post_item['image_url'] = $board_image_result[0]['image_url'];

            $square_post_item['create_date'] = $square_post_item_result['create_date'];
            $square_post_item['update_date'] = $square_post_item_result['update_date'];

            $square_posts[] = $square_post_item;
        }
        return $square_posts;
    }

    private function make_users_items(array $users_result): array
    {
        $users = [];

        foreach ($users_result as $user_item_result) {
            $user_idx = (int)$user_item_result['user_idx'];
            $user_item['user_idx'] = $user_idx;
            $user_item['name'] = $user_item_result['name'];
            $user_item['profile_image_url'] = $user_item_result['profile_image_url'];

            $user_item['create_date'] = $user_item_result['create_date'];
            $user_item['update_date'] = $user_item_result['update_date'];

            $users[] = $user_item;
        }
        return $users;
    }
}