<?php

namespace MyLifeServer\app\models;

use MyLifeServer\app\ConfigManager;
use MyLifeServer\app\models\sql\BoardQuery;
use MyLifeServer\core\model\Model;
use MyLifeServer\core\utils\ResponseHelper;

/**
 * @category 1. 회원가입 관련
 *  (1) 기본 회원가입
 *  (2) SNS 회원가입
 * @category 2. 로그인 관련
 *  (1) 기본 로그인
 *  (2) SNS 로그인
 *  (3) 자동 로그인
 */
class BoardModel extends Model
{
    private $query;

    public function __construct(BoardQuery $query, ConfigManager $config_manager)
    {
        parent::__construct($query, $config_manager);
        $this->query = $query;
    }

    // 게시글 리스트 가져오기 (무한 스크롤링)
    public function read_posts(array $client_data): array
    {
//        $this->check_user_session($client_data);
        // TODO: 팔로잉한 사람들 게시글만 가져올 수 있게 커스터마이징 해야함
        $page = $this->check_int_data($client_data, 'page');
        $limit = $this->check_int_data($client_data, 'limit');
        $table_name = $this->query->board_table;
        $start_num = ($page - 1) * $limit; // 요청하는 페이지에 시작 번호
        $is_last = false;

        $posts_result = $this->query->select_items_order_by_update_date($table_name, $limit, $start_num);

        if (empty($posts_result)) ResponseHelper::get_instance()->error_response(204, 'no item');
        if (count($posts_result) < $limit) $is_last = true;

        $posts = $this->make_post_items($posts_result);

        return [
            'result' => $this->success_result,
            'isLast' => $is_last,
            'posts' => $posts
        ];
    }

    // TODO: 댓글 리스트 가져오기 (무한 스크롤링)
    public function read_comments(array $client_data): array
    {
        return [
            'result' => $this->success_result
        ];
    }

    // TODO: 게시글 가져오기 (1개)
    public function read_post(array $client_data): array
    {
        return [
            'result' => $this->success_result
        ];
    }

    // TODO: 댓글 가져오기 (1개)
    public function read_comment(array $client_data): array
    {
        return [
            'result' => $this->success_result,
        ];
    }

    // 게시글 추가
    public function create_post(array $client_data): array
    {
        $this->check_user_session($client_data);
        $user_idx = $this->check_string_data($client_data, 'user_idx');
        $image = $this->check_string_data($client_data, 'image');
        $image_name = $this->check_string_data($client_data, 'image_name');
        $contents = $this->check_string_data($client_data, 'contents');

        $user_result = $this->query->select_user_by_user_idx($user_idx);
        if (empty($user_result)) ResponseHelper::get_instance()->error_response(409, 'non-existent user');

        // 이미지 서버에 저장
        $image_url = $this->store_image($image, $image_name, $this->post_image_folder);
        // TODO: 이미지 파일 권한을 변경, 이걸 왜 해야할까?
        chmod($image_url, 0755);

        $this->query->begin_transaction();
        $this->query->insert_board($user_idx, $contents);
        $board_idx = $this->query->select_inserted_id();
        $this->query->insert_board_image($board_idx, $image_url);
        $this->query->commit_transaction();

        return [
            'result' => $this->success_result,
        ];
    }

    // TODO: 댓글 추가
    public function create_comment(array $client_data): array
    {
        return [
            'result' => $this->success_result
        ];
    }

    // 게시글 수정
    public function update_post(array $client_data): array
    {
        $this->check_user_session($client_data);

        $board_idx = $this->check_int_data($client_data, 'board_idx');
        $user_idx = $this->check_string_data($client_data, 'user_idx');
        $image = $this->check_string_data($client_data, 'image');
        $image_name = $this->check_string_data($client_data, 'image_name');
        $contents = $this->check_string_data($client_data, 'contents');
        $is_image_change = $this->check_boolean_data($client_data, 'is_image_change');

        $user_result = $this->query->select_user_by_user_idx($user_idx);
        if (empty($user_result)) ResponseHelper::get_instance()->error_response(409, 'non-existent user');
        $board_result = $this->query->select_board_by_board_idx($board_idx);
        if (empty($board_result)) ResponseHelper::get_instance()->error_response(409, 'non-existent post');
        // 예외 처리 : 해당 글을 작성한 유저의 인덱스 값과 클라이언트로부터 전송된 유저 인덱스 값을 비교 -> 다르면 에러 발생
        if ($board_result[0]['user_idx'] != $user_idx) ResponseHelper::get_instance()->error_response(409, 'invalid user index');

        if ($is_image_change) {

        } else {

        }

        return [
            'result' => $this->success_result
        ];
    }

    // TODO: 댓글 수정
    public function update_comment(array $client_data): array
    {
        return [
            'result' => $this->success_result
        ];
    }

    // 게시글 삭제
    public function delete_post(array $client_data): array
    {
        $this->check_user_session($client_data);

        $user_idx = $this->check_int_data($client_data, 'user_idx');
        $board_idx = $this->check_int_data($client_data, 'board_idx');

        $user_result = $this->query->select_user_by_user_idx($user_idx);
        if (empty($user_result)) ResponseHelper::get_instance()->error_response(409, 'non-existent user');
        $board_result = $this->query->select_board_by_board_idx($board_idx);
        if (empty($board_result)) ResponseHelper::get_instance()->error_response(409, 'non-existent post');
        // 예외 처리 : 해당 글을 작성한 유저의 인덱스 값과 클라이언트로부터 전송된 유저 인덱스 값을 비교 -> 다르면 에러 발생
        if ($board_result[0]['user_idx'] != $user_idx) ResponseHelper::get_instance()->error_response(409, 'invalid user index');

        $this->query->begin_transaction();
        // 게시글 삭제
        $this->query->delete_post_by_board_idx($board_idx);
        // 게시글 이미지 삭제
        $this->query->delete_post_image_by_board_idx($board_idx);
        $this->query->commit_transaction();

        // TODO: 이미지 파일 삭제하기

        return [
            'result' => $this->success_result
        ];
    }

    // 댓글 삭제
    public function delete_comment(array $client_data): array
    {
        $this->check_user_session($client_data);

        $user_idx = $this->check_int_data($client_data, 'user_idx');
        $comment_idx = $this->check_int_data($client_data, 'comment_idx');

        // 예외 처리 : 해당 글을 작성한 유저의 인덱스 값과 클라이언트로부터 전송된 유저 인덱스 값을 비교 -> 다르면 에러 발생
        $comment_result = $this->query->select_comment_by_comment_idx($comment_idx);
        // 예외 처리 : 해당 글을 작성한 유저의 인덱스 값과 클라이언트로부터 전송된 유저 인덱스 값을 비교 -> 다르면 에러 발생
        if ($comment_result['user_idx'] != $user_idx) ResponseHelper::get_instance()->error_response(409, 'invalid user index');

        // 댓글 삭제
        $this->query->delete_comment_by_comment_idx($comment_idx);

        return [
            'result' => $this->success_result
        ];
    }

    // TODO: 게시글 좋아요
    public function update_like_post(array $client_data): array
    {
        return [
            'result' => $this->success_result
        ];
    }

    // TODO: 댓글 좋아요
    public function update_like_comment(array $client_data): array
    {
        return [
            'result' => $this->success_result
        ];
    }

    /** ------------ @category ?. 유틸리티 ------------ */
    private function make_post_items(array $posts_result): array
    {
        $posts = [];

        foreach ($posts_result as $post_item_result) {
            $user_idx = (int)$post_item_result['user_idx'];
            $board_idx = (int)$post_item_result['board_idx'];
            $post_item['board_idx'] = $board_idx;
            $post_item['user_idx'] = $user_idx;
            $user_result = $this->query->select_user_by_user_idx($user_idx);
            $post_item['name'] = $user_result[0]['name'];
            $post_item['profile_image_url'] = $user_result[0]['profile_image_url'];
            // TODO: 다중 이미지 구현할 경우 해당 부분 코드 수정해주어야함
            $board_image_result = $this->query->select_board_image_by_board_idx($board_idx);
            $post_item['image_url'] = $board_image_result[0]['image_url'];
            $post_item['contents'] = $post_item_result['contents'];
            $post_item['likes'] = (int)$post_item_result['likes'];
            $post_item['comments'] = (int)$post_item_result['comments'];
            $post_item['create_date'] = $post_item_result['create_date'];
            $post_item['update_date'] = $post_item_result['update_date'];
            $post_item['delete_date'] = $post_item_result['delete_date'];
            $posts[] = $post_item;
        }
        return $posts;
    }
}
