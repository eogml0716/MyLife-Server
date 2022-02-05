<?php

namespace MyLifeServer\app\models;

use Exception;
use MyLifeServer\app\ConfigManager;
use MyLifeServer\app\models\sql\CommonQuery;
use MyLifeServer\core\model\HttpRequester;
use MyLifeServer\core\model\Model;
use MyLifeServer\core\utils\ResponseHelper;
use stdClass;

class ProfileModel extends Model
{
    private $query;

    public function __construct(CommonQuery $query, ConfigManager $config_manager)
    {
        parent::__construct($query, $config_manager);
        $this->query = $query;
    }

    // TODO: 나의 프로필 가져오기
    public function read_me(array $client_data): array
    {

        return [
            'result' => $this->success_result
        ];
    }

    // TODO: 내가 작성한 게시글 가져오기 (무한 스크롤링)
    public function read_mine(array $client_data): array
    {
        $this->check_user_session($client_data);
        $page = $this->check_int_data($client_data, 'page');
        $limit = $this->check_int_data($client_data, 'limit');
        $user_idx = $this->check_int_data($client_data, 'user_idx');
        $table_name = $this->query->board_table;
        $start_num = ($page - 1) * $limit; // 요청하는 페이지에 시작 번호
        $is_last = false;

        $my_posts_result = $this->query->select_my_posts_order_by_create_date($table_name, $limit, $start_num, $user_idx);

        if (empty($my_posts_result)) ResponseHelper::get_instance()->error_response(204, 'no item');
        if (count($my_posts_result) < $limit) $is_last = true;

        $square_posts = $this->make_square_post_items($my_posts_result);

        return [
            'result' => $this->success_result,
            'isLast' => $is_last,
            'posts' => $square_posts
        ];
    }

    // TODO: 다른 사람의 프로필 가져오기
    public function read_other(array $client_data): array
    {

        return [
            'result' => $this->success_result
        ];
    }

    // TODO: 다른 사람이 작성한 게시글 가져오기 (무한 스크롤링)
    public function read_others(array $client_data): array
    {

        return [
            'result' => $this->success_result
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
}