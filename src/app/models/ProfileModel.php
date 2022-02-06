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

    // TODO: 프로필 정보 가져오기
    public function read_info(array $client_data): array
    {
        $this->check_user_session($client_data);
        $idx = $this->check_int_data($client_data, 'idx');

        $user_result = $this->query->select_user_by_user_idx($idx);
        if (empty($user_result)) ResponseHelper::get_instance()->error_response(204, 'non-existent user');

        // TODO: 팔로워 수, 팔로잉 수도 주어야함
        $user_row = $user_result[0];
        $user_idx = (int)$user_row['user_idx'];
        $name = $user_row['name'];
        $profile_image_url = $user_row['profile_image_url'];
        $about_me = $user_row['about_me'];
        $post_count = $this->query->select_post_count($idx);
//        $follower_count =
//        $following_count =
        return [
            'result' => $this->success_result,
            'user_idx' => $user_idx,
            'name' => $name,
            'profile_image_url' => $profile_image_url,
            'about_me' => $about_me,
            'post_count' => $post_count,
//            'follower_count'
//            'following_count'
        ];
    }

    // TODO: 프로필 페이지 작성한 게시글 리스트 가져오기 (무한 스크롤링)
    public function read_posts(array $client_data): array
    {
        $this->check_user_session($client_data);
        $page = $this->check_int_data($client_data, 'page');
        $limit = $this->check_int_data($client_data, 'limit');
        $idx = $this->check_int_data($client_data, 'idx');
        $table_name = $this->query->board_table;
        $start_num = ($page - 1) * $limit; // 요청하는 페이지에 시작 번호
        $is_last = false;

        $posts_result = $this->query->select_posts_order_by_create_date($table_name, $limit, $start_num, $idx);

        if (empty($posts_result)) ResponseHelper::get_instance()->error_response(204, 'no item');
        if (count($posts_result) < $limit) $is_last = true;

        $square_posts = $this->make_square_post_items($posts_result);

        return [
            'result' => $this->success_result,
            'isLast' => $is_last,
            'posts' => $square_posts
        ];
    }

    public function update_profile(array $client_data): array
    {
        $this->check_user_session($client_data);

        $user_idx = $this->check_int_data($client_data, 'user_idx');
        $image = $this->check_string_data($client_data, 'image');
        $image_name = $this->check_string_data($client_data, 'image_name');
        $name = $this->check_string_data($client_data, 'name');
        $about_me = $this->check_string_data($client_data, 'about_me');
        $is_image_change = $this->check_boolean_data($client_data, 'is_image_change');

        $user_result = $this->query->select_user_by_user_idx($user_idx);
        if (empty($user_result)) ResponseHelper::get_instance()->error_response(204, 'non-existent user');
        // 예외 처리 : 해당 프로필을 수정하는 유저의 인덱스 값과 클라이언트로부터 전송된 유저 인덱스 값을 비교 -> 다르면 에러 발생
        if ($user_result[0]['user_idx'] != $user_idx) ResponseHelper::get_instance()->error_response(409, 'invalid user index');

        // TODO: 자기소개 관련해서 에러 처리를 너무 이상하게 하는데 꼭 바꿀 것
        if ($about_me == "about_me") $about_me = "자기 소개가 없습니다.";
        if ($is_image_change) {
            // 이미지 서버에 저장
            $profile_image_url = $this->store_image($image, $image_name, $this->user_image_folder);
            $this->query->update_user_profile_by_image_change($user_idx, $profile_image_url, $name, $about_me);

            return [
                'result' => $this->success_result,
                'profile_image_url' => $profile_image_url,
                'name' => $name
            ];
        } else {
            $this->query->update_user_profile($user_idx, $name, $about_me);
            $user_row = $user_result[0];
            $profile_image_url = $user_row['profile_image_url'];
            return [
                'result' => $this->success_result,
                'profile_image_url' => $profile_image_url,
                'name' => $name
            ];
        }
    }

    // TODO: 팔로우, 언팔로우 - 이거 건드리고 유저 정보? 가져오는 메소드들 전부 수정을 해주어야함함
   public function update_follow(array $client_data): array
    {

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