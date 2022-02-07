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

    // 프로필 정보 가져오기
    public function read_info(array $client_data): array
    {
        $this->check_user_session($client_data);
        $user_idx = $this->check_int_data($client_data, 'user_idx');
        $idx = $this->check_int_data($client_data, 'idx');

        $from_user_idx = $user_idx;
        $to_user_idx = $idx;

        $user_result = $this->query->select_user_by_user_idx($idx);
        if (empty($user_result)) ResponseHelper::get_instance()->error_response(204, 'non-existent user');

        $user_row = $user_result[0];
        $user_idx = (int)$user_row['user_idx'];
        $name = $user_row['name'];
        $profile_image_url = $user_row['profile_image_url'];
        $about_me = $user_row['about_me'];
        $post_count = $this->query->select_post_count($idx);
        // TODO: followers, followings 이런 거는 ArrayList에서 자주 사용하는 용어여서 개수를 셀 때는 그냥 뒤에 count를 붙여주는게 좋을 거 같음 (나중에 수정하기)
        $follower_count = $this->query->select_follower_count($to_user_idx);
        $following_count = $this->query->select_following_count($to_user_idx);
        $user_follow_result = $this->query->select_follow($from_user_idx, $to_user_idx);
        $is_follow = false; // 팔로우 했는지 여부 (default - false)
        if (!empty($user_follow_result))  $is_follow = true; // 쿼리 결과 사용자가 팔로우 했다면 $is_follow를 true로 변경

        return [
            'result' => $this->success_result,
            'user_idx' => $user_idx,
            'name' => $name,
            'profile_image_url' => $profile_image_url,
            'about_me' => $about_me,
            'post_count' => $post_count,
            'follower_count' => $follower_count,
            'following_count' => $following_count,
            'is_follow' => $is_follow
        ];
    }

    // 프로필 페이지 작성한 게시글 리스트 가져오기 (무한 스크롤링)
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

    public function read_followings(array $client_data): array
    {
        $this->check_user_session($client_data);
        $page = $this->check_int_data($client_data, 'page');
        $limit = $this->check_int_data($client_data, 'limit');
        $idx = $this->check_int_data($client_data, 'idx');
        $table_name = $this->query->follow_table;
        $start_num = ($page - 1) * $limit; // 요청하는 페이지에 시작 번호
        $is_last = false;

        $from_user_idx = $idx;

        $followings_result = $this->query->select_followings_order_by_create_date($table_name, $limit, $start_num, $from_user_idx);

        if (empty($followings_result)) ResponseHelper::get_instance()->error_response(204, 'no item');
        if (count($followings_result) < $limit) $is_last = true;

        $followings = $this->make_followings_items($followings_result);

        return [
            'result' => $this->success_result,
            'isLast' => $is_last,
            'followings' => $followings
        ];
    }

    public function read_followers(array $client_data): array
    {
        $this->check_user_session($client_data);
        $page = $this->check_int_data($client_data, 'page');
        $limit = $this->check_int_data($client_data, 'limit');
        $idx = $this->check_int_data($client_data, 'idx');
        $table_name = $this->query->follow_table;
        $start_num = ($page - 1) * $limit; // 요청하는 페이지에 시작 번호
        $is_last = false;

        $to_user_idx = $idx;

        $followers_result = $this->query->select_followers_order_by_create_date($table_name, $limit, $start_num, $to_user_idx);

        if (empty($followers_result)) ResponseHelper::get_instance()->error_response(204, 'no item');
        if (count($followers_result) < $limit) $is_last = true;

        $followers = $this->make_followers_items($followers_result);

        return [
            'result' => $this->success_result,
            'isLast' => $is_last,
            'followers' => $followers
        ];
    }

    // 팔로우, 언팔로우 - 이거 건드리고 유저 정보? 가져오는 메소드들 전부 수정을 해주어야함함
   public function update_follow(array $client_data): array
    {
        $this->check_user_session($client_data);

        $user_idx = $this->check_int_data($client_data, 'user_idx');
        $idx = $this->check_int_data($client_data, 'idx');
        $is_follow = $this->check_boolean_data($client_data, 'is_follow');

        $from_user_idx = $user_idx;
        $to_user_idx = $idx;

        // TODO: 좋아요, 댓글 개수랑은 다르게 여기서는 그냥 count 함수로 팔로워 수, 팔로잉 수 계속 가져오는 방식으로 구현을 해볼보자
        // 팔로우를 누른 상태 -> 팔로우 테이블에 팔로우가 등록되어있지 않음
        if ($is_follow) {
            // 예외 처리 : 이미 팔로우가 눌려있는 상태인 경우
            $duplicated_liked_result = $this->query->select_follow($from_user_idx, $to_user_idx);
            if ($duplicated_liked_result) ResponseHelper::get_instance()->error_response(400, 'already pressed follow');

            $this->query->insert_follow($from_user_idx, $to_user_idx);
        } else {
            // TODO: 예외 처리 : 팔로우가 없는 경우
            $this->query->delete_follow($from_user_idx, $to_user_idx);
        }

        // TODO: followers, followings 이런 거는 ArrayList에서 자주 사용하는 용어여서 개수를 셀 때는 그냥 뒤에 count를 붙여주는게 좋을 거 같음 (나중에 수정하기)
        $follower_count = $this->query->select_follower_count($to_user_idx);
        $following_count = $this->query->select_following_count($to_user_idx);

        return [
            'result' => $this->success_result,
            'follower_count' => $follower_count,
            'following_count' => $following_count,
            'is_follow' => $is_follow
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

    private function make_followings_items(array $followings_result): array
    {
        $followings = [];

        foreach ($followings_result as $followings_item_result) {
            $follow_idx = (int)$followings_item_result['follow_idx'];
            $from_user_idx = (int)$followings_item_result['from_user_idx']; // ex) 나의 팔로잉 목록을 들어가는거면 이건 "나의 인덱스"가 되어야함
            $to_user_idx = (int)$followings_item_result['to_user_idx']; // ex) 나의 팔로잉 목록을 들어가는거면 이건 "상대방의 인덱스"가 되어야함
            $followings_item['follow_idx'] = $follow_idx;
            $followings_item['from_user_idx'] = $from_user_idx;
            $followings_item['to_user_idx'] = $to_user_idx;
            // 내가 팔로우 하는 사람들의 정보를 가져와야하므로 to_user_idx로 쿼리
            $user_result = $this->query->select_user_by_user_idx($to_user_idx);
            $followings_item['name'] = $user_result[0]['name'];
            $followings_item['profile_image_url'] = $user_result[0]['profile_image_url'];
            // 내가 상대방을 팔로우 하는 지 체크 (무조건 true로 나와야함)
            $user_follow_result = $this->query->select_follow($from_user_idx, $to_user_idx);
            $is_follow = false; // 팔로우 했는지 여부 (default - false)
            if (!empty($user_follow_result))  $is_follow = true; // 쿼리 결과 사용자가 팔로우 했다면 $is_follow를 true로 변경
            $followings_item['is_follow'] = $is_follow;

            $followings_item['create_date'] = $followings_item_result['create_date'];
            $followings_item['update_date'] = $followings_item_result['update_date'];

            $followings[] = $followings_item;
        }
        return $followings;
    }

    private function make_followers_items(array $followers_result): array
    {
        $followers = [];

        foreach ($followers_result as $followers_item_result) {
            $follow_idx = (int)$followers_item_result['follow_idx'];
            $from_user_idx = (int)$followers_item_result['from_user_idx']; // ex) 나의 팔로워 목록을 들어가는거면 이건 "상대방의 인덱스"가 되어야함
            $to_user_idx = (int)$followers_item_result['to_user_idx']; // ex) 나의 팔로워 목록을 들어가는거면 이건 "나의 인덱스"가 되어야함
            $followers_item['follow_idx'] = $follow_idx;
            $followers_item['from_user_idx'] = $from_user_idx;
            $followers_item['to_user_idx'] = $to_user_idx;
            // 나를 팔로우하는 사람들의 정보를 가져와야하므로 from_user_idx로 쿼리
            $user_result = $this->query->select_user_by_user_idx($from_user_idx);
            $followers_item['name'] = $user_result[0]['name'];
            $followers_item['profile_image_url'] = $user_result[0]['profile_image_url'];
            // 나를 팔로우 하는 사람이 나를 팔로우 하는 지 체크, true, false 섞여서 나와도 됨
            $user_follow_result = $this->query->select_follow($from_user_idx, $to_user_idx);
            $is_follow = false; // 팔로우 했는지 여부 (default - false)
            if (!empty($user_follow_result))  $is_follow = true; // 쿼리 결과 사용자가 팔로우 했다면 $is_follow를 true로 변경
            $followers_item['is_follow'] = $is_follow;

            $followers_item['create_date'] = $followers_item_result['create_date'];
            $followers_item['update_date'] = $followers_item_result['update_date'];

            $followers[] = $followers_item;
        }
        return $followers;
    }
}