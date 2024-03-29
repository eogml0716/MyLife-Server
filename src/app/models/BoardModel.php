<?php

namespace MyLifeServer\app\models;

use MyLifeServer\app\utils\FirebaseRequester;
use MyLifeServer\app\ConfigManager;
use MyLifeServer\app\models\sql\CommonQuery;
use MyLifeServer\core\model\HttpRequester;
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

    public function __construct(CommonQuery $query, ConfigManager $config_manager)
    {
        parent::__construct($query, $config_manager);
        $this->query = $query;
    }

    // 내가 팔로잉한 사람의 게시글 리스트 가져오기 (무한 스크롤링)
    public function read_posts(array $client_data): array
    {
//        $user_idx = $this->check_user_session($client_data);
        $user_idx = $this->check_int_data($client_data, 'user_idx');
        // TODO: 팔로잉한 사람들 게시글만 가져올 수 있게 커스터마이징 해야함
        $page = $this->check_int_data($client_data, 'page');
        $limit = $this->check_int_data($client_data, 'limit');
        $start_num = ($page - 1) * $limit; // 요청하는 페이지에 시작 번호
        $is_last = false;

        $following_count = $this->query->select_following_count($user_idx);
        $posts_result = $this->query->select_items_order_by_create_date($limit, $start_num, $user_idx, $following_count);

        if (empty($posts_result)) ResponseHelper::get_instance()->error_response(204, 'no item');
        if (count($posts_result) < $limit) $is_last = true;

        $posts = $this->make_post_items($posts_result, $user_idx);

        return [
            'result' => $this->success_result,
            'isLast' => $is_last,
            'posts' => $posts
        ];
    }

    // 댓글 리스트 가져오기 (무한 스크롤링)
    public function read_comments(array $client_data): array
    {
        $user_idx = $this->check_user_session($client_data);

        $page = $this->check_int_data($client_data, 'page');
        $limit = $this->check_int_data($client_data, 'limit');
        $board_idx = $this->check_int_data($client_data, 'board_idx');
        $table_name = $this->query->comment_table;
        $start_num = ($page - 1) * $limit; // 요청하는 페이지에 시작 번호
        $is_last = false;

        $comments_result = $this->query->select_comments_order_by_create_date($table_name, $limit, $start_num, $board_idx);

        if (empty($comments_result)) ResponseHelper::get_instance()->error_response(204, 'no item');
        if (count($comments_result) < $limit) $is_last = true;

        $comments = $this->make_comment_items($comments_result, $user_idx);

        return [
            'result' => $this->success_result,
            'isLast' => $is_last,
            'comments' => $comments
        ];
    }

    // 게시글 가져오기 (1개)
    public function read_post(array $client_data): array
    {
        $user_idx= $this->check_user_session($client_data);

        $board_idx = $this->check_int_data($client_data, 'board_idx');

        $user_result = $this->query->select_user_by_user_idx($user_idx);
        if (empty($user_result)) ResponseHelper::get_instance()->error_response(204, 'non-existent user');
        $board_result = $this->query->select_board_by_board_idx($board_idx);
        if (empty($board_result)) ResponseHelper::get_instance()->error_response(204, 'non-existent post');
        $board_image_result = $this->query->select_board_image_by_board_idx($board_idx);
        if (empty($board_image_result)) ResponseHelper::get_instance()->error_response(204, 'non-existent post image');

        $board_row = $board_result[0];
        $board_image_row = $board_image_result[0];

        $db_board_idx = (int)$board_row['board_idx'];
        $db_user_idx = (int)$board_row['user_idx'];

        $post_user_result = $this->query->select_user_by_user_idx($db_user_idx);
        $post_user_row = $post_user_result[0];

        $db_name = $post_user_row['name'];
        $db_profile_image_url = $post_user_row['profile_image_url'];
        $db_image_url = $board_image_row['image_url'];
        $db_contents = $board_row['contents'];
        $db_likes = (int)$board_row['likes'];
        $db_comments = (int)$board_row['comments'];
        // 해당 요청을 보낸 유저의 게시글 좋아요 boolean값 가져오기
        $user_like_result = $this->query->select_liked($user_idx, 'POST', $board_idx);
        $is_like = false; // 좋아요 했는지 여부 (default - false)
        if (!empty($user_like_result))  $is_like = true; // 쿼리 결과 사용자가 좋아요 했다면 $is_user_like true로 변경
        $db_create_date = $board_row['create_date'];

        return [
            'result' => $this->success_result,
            'board_idx' => $db_board_idx,
            'user_idx' => $db_user_idx,
            'name' => $db_name,
            'profile_image_url' => $db_profile_image_url,
            'image_url' => $db_image_url,
            'contents' => $db_contents,
            'likes' => $db_likes,
            'comments' => $db_comments,
            'is_like' => $is_like,
            'create_date' => $db_create_date,
        ];
    }

    // 게시글 추가
    public function create_post(array $client_data): array
    {
        $user_idx = $this->check_user_session($client_data);
        $image = $this->check_string_data($client_data, 'image');
        $image_name = $this->check_string_data($client_data, 'image_name');
        $contents = $this->check_string_data($client_data, 'contents');

        $user_result = $this->query->select_user_by_user_idx($user_idx);
        if (empty($user_result)) ResponseHelper::get_instance()->error_response(204, 'non-existent user');

        // 이미지 서버에 저장
        $image_url = $this->store_image($image, $image_name, $this->post_image_folder);

        $this->query->begin_transaction();
        $this->query->insert_board($user_idx, $contents);
        $board_idx = $this->query->select_inserted_id();
        $this->query->insert_board_image($board_idx, $image_url);

        $board_result = $this->query->select_board_by_board_idx($board_idx);
        $db_user_idx = $board_result[0]['user_idx'];

        $follow_result = $this->query->select_follow_by_to_user_idx($db_user_idx);

        // TODO: foreach문과, for문 중에서 어느 것이 더 퍼포먼스적으로 우위에 있을까?
        for ($index = 0; $index < count($follow_result); $index++) {
            $from_user_idx = $user_idx;
            $to_user_idx = $follow_result[$index]['from_user_idx'];

            if ($from_user_idx != $to_user_idx) {
                $from_user_result = $this->query->select_user_by_user_idx($from_user_idx);
                $to_user_result = $this->query->select_user_by_user_idx($to_user_idx);

                $from_name = $from_user_result[0]['name'];
                $to_name = $to_user_result[0]['name'];

                $notification_type = "NEW_POST";
                $contents = "{$to_name}님이 팔로우 하시는 {$from_name}님이 게시글을 업로드 하였습니다.";
                $table_type = $this->query->board_table;
                $idx = $board_idx;

                $this->query->insert_notification($from_user_idx, $to_user_idx, $notification_type, $contents, $table_type, $idx);
                $to_firebase_token = $to_user_result[0]['firebase_token'];
                if (!empty($to_firebase_token)) {
                    $firebase_requester = new FirebaseRequester(new HttpRequester());
                    $firebase_requester->send_fcm($to_firebase_token, $notification_type, $contents);
                }
            }
        }
        $this->query->commit_transaction();

        return [
            'result' => $this->success_result,
        ];
    }

    // 댓글 추가 TODO: 댓글 수정쪽 리사이클러뷰 구현 방식이 바뀌게 되면 return 값이라든가 구현 방식이 바뀔 수 있음
    public function create_comment(array $client_data): array
    {
        $user_idx = $this->check_user_session($client_data);
        $board_idx = $this->check_int_data($client_data, 'board_idx');
        $contents = $this->check_string_data($client_data, 'contents');

        $user_result = $this->query->select_user_by_user_idx($user_idx);
        if (empty($user_result)) ResponseHelper::get_instance()->error_response(204, 'non-existent user');
        $board_result = $this->query->select_board_by_board_idx($board_idx);
        if (empty($board_result)) ResponseHelper::get_instance()->error_response(204, 'non-existent post');

        $this->query->begin_transaction();
        $this->query->insert_comment($user_idx, $board_idx, $contents);
        $comment_count = $this->query->select_comment_count($board_idx);
        $this->query->update_comment_count('board', $board_idx, $comment_count);
        // 내 게시글에 댓글이 달렸을 때 (여기서 "나"는 현재 로그인 해서 이용 중인 유저를 꼭 가리키는 게 아님), TODO: FCM 처리하기
        $board_result = $this->query->select_board_by_board_idx($board_idx);

        $from_user_idx = $user_idx;
        $to_user_idx = $board_result[0]['user_idx'];

        if ($from_user_idx != $to_user_idx) {
            $from_user_result = $this->query->select_user_by_user_idx($from_user_idx);
            $to_user_result = $this->query->select_user_by_user_idx($to_user_idx);

            $from_name = $from_user_result[0]['name'];
            $to_name = $to_user_result[0]['name'];

            $notification_type = "NEW_COMMENT";
            $contents = "{$from_name}님이 {$to_name}님의 게시글에 댓글을 남겼습니다.";
            $table_type = $this->query->board_table;
            $idx = $board_idx;

            $this->query->insert_notification($from_user_idx, $to_user_idx, $notification_type, $contents, $table_type, $idx);
            $to_firebase_token = $to_user_result[0]['firebase_token'];
            if (!empty($to_firebase_token)) {
                $firebase_requester = new FirebaseRequester(new HttpRequester());
                $firebase_requester->send_fcm($to_firebase_token, $notification_type, $contents);
            }
        }
        $this->query->commit_transaction();

        return [
            'result' => $this->success_result,
        ];
    }

    // 게시글 수정
    public function update_post(array $client_data): array
    {
        $user_idx = $this->check_user_session($client_data);

        $board_idx = $this->check_int_data($client_data, 'board_idx');
        $image = $this->check_string_data($client_data, 'image');
        $image_name = $this->check_string_data($client_data, 'image_name');
        $contents = $this->check_string_data($client_data, 'contents');
        $is_image_change = $this->check_boolean_data($client_data, 'is_image_change');

        $user_result = $this->query->select_user_by_user_idx($user_idx);
        if (empty($user_result)) ResponseHelper::get_instance()->error_response(204, 'non-existent user');
        $board_result = $this->query->select_board_by_board_idx($board_idx);
        if (empty($board_result)) ResponseHelper::get_instance()->error_response(204, 'non-existent post');
        // 예외 처리 : 해당 글을 작성한 유저의 인덱스 값과 클라이언트로부터 전송된 유저 인덱스 값을 비교 -> 다르면 에러 발생
        if ($board_result[0]['user_idx'] != $user_idx) ResponseHelper::get_instance()->error_response(409, 'invalid user index');

        if ($is_image_change) {
            $this->query->begin_transaction();
            // 이미지 서버에 저장
            $image_url = $this->store_image($image, $image_name, $this->post_image_folder);
            // TODO: 게시글이 1개인 경우에 이런 식으로 게시글 이미지를 수정할 수 있다. 게시글을 여러 개로 변경할 경우 바꿔야함
            // 게시글 이미지 삭제
            $this->query->delete_post_image_by_board_idx($board_idx);
            // 게시글 이미지 저장
            $this->query->insert_board_image($board_idx, $image_url);
            $this->query->update_post_by_board_idx($board_idx, $contents);
            $this->query->commit_transaction();
        } else {
            $this->query->update_post_by_board_idx($board_idx, $contents);
        }

        return [
            'result' => $this->success_result
        ];
    }

    // 댓글 수정, TODO: 댓글 수정쪽 리사이클러뷰 구현 방식이 바뀌게 되면 return 값이라든가 구현 방식이 바뀔 수 있음
    public function update_comment(array $client_data): array
    {
        $user_idx = $this->check_user_session($client_data);

        $board_idx = $this->check_int_data($client_data, 'board_idx');
        $comment_idx = $this->check_int_data($client_data, 'comment_idx');
        $contents = $this->check_string_data($client_data, 'contents');

        $user_result = $this->query->select_user_by_user_idx($user_idx);
        if (empty($user_result)) ResponseHelper::get_instance()->error_response(204, 'non-existent user');
        $board_result = $this->query->select_board_by_board_idx($board_idx);
        if (empty($board_result)) ResponseHelper::get_instance()->error_response(204, 'non-existent post');
        $comment_result = $this->query->select_comment_by_comment_idx($comment_idx);
        if (empty($comment_result)) ResponseHelper::get_instance()->error_response(204, 'non-existent comment');
        // 예외 처리 : 해당 글을 작성한 유저의 인덱스 값과 클라이언트로부터 전송된 유저 인덱스 값을 비교 -> 다르면 에러 발생
        if ($comment_result[0]['user_idx'] != $user_idx) ResponseHelper::get_instance()->error_response(409, 'invalid user index');

        $this->query->update_comment_by_comment_idx($board_idx, $contents);

        return [
            'result' => $this->success_result,
            'comment_idx' => $comment_idx,
            'contents' => $contents
        ];
    }

    // 게시글 삭제
    public function delete_post(array $client_data): array
    {
        $user_idx = $this->check_user_session($client_data);
        $board_idx = $this->check_int_data($client_data, 'board_idx');

        $user_result = $this->query->select_user_by_user_idx($user_idx);
        if (empty($user_result)) ResponseHelper::get_instance()->error_response(204, 'non-existent user');
        $board_result = $this->query->select_board_by_board_idx($board_idx);
        if (empty($board_result)) ResponseHelper::get_instance()->error_response(204, 'non-existent post');
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
        $user_idx = $this->check_user_session($client_data);

        $comment_idx = $this->check_int_data($client_data, 'comment_idx');

        // 예외 처리 : 해당 글을 작성한 유저의 인덱스 값과 클라이언트로부터 전송된 유저 인덱스 값을 비교 -> 다르면 에러 발생
        $comment_result = $this->query->select_comment_by_comment_idx($comment_idx);
        // 예외 처리 : 해당 글을 작성한 유저의 인덱스 값과 클라이언트로부터 전송된 유저 인덱스 값을 비교 -> 다르면 에러 발생
        if ($comment_result[0]['user_idx'] != $user_idx) ResponseHelper::get_instance()->error_response(409, 'invalid user index');

        $comment_row = $comment_result[0];
        $board_idx = $comment_row['board_idx'];

        // 댓글 삭제
        $this->query->begin_transaction();
        $this->query->delete_comment_by_comment_idx($comment_idx);
        $comment_count = $this->query->select_comment_count($board_idx);
        $this->query->update_comment_count('board', $board_idx, $comment_count);
        $this->query->commit_transaction();

        return [
            'result' => $this->success_result
        ];
    }

    // 좋아요
    public function update_like(array $client_data): array
    {
        $user_idx = $this->check_user_session($client_data);

        $type = $this->check_string_data($client_data, 'type');
        $idx = $this->check_int_data($client_data, 'idx');
        $is_like = $this->check_boolean_data($client_data, 'is_like');

        // type 값을 소문자나 대문자 섞어서 오는 경우 방지, (그냥 내가 테스트할 때 에러나면 귀찮음)
        $type_upper = strtoupper($type);

        $this->query->begin_transaction();
        // 좋아요를 누른 상태 -> 좋아요 테이블에 좋아요가 등록되어있지 않음
        if ($is_like) {
            // 예외 처리 : 이미 좋아요가 눌려있는 상태인 경우
            $duplicated_liked_result = $this->query->select_liked($user_idx, $type_upper, $idx);
            if ($duplicated_liked_result) ResponseHelper::get_instance()->error_response(400, 'already pressed like');

            $this->query->insert_liked($user_idx, $type_upper, $idx);
            if ($type_upper == 'POST') {
                // 누군가 내 게시글에 좋아요를 눌렀을 때 (여기서 "나"는 현재 로그인 해서 이용 중인 유저를 꼭 가리키는 게 아님), TODO: FCM 처리하기
                $liked_idx = $this->query->select_inserted_id();
                $liked_result = $this->query->select_liked_by_liked_idx($liked_idx);

                $board_idx = $liked_result[0]['idx'];
                $board_result = $this->query->select_board_by_board_idx($board_idx);

                $from_user_idx = $user_idx;
                $to_user_idx = $board_result[0]['user_idx'];

                if ($from_user_idx != $to_user_idx) {
                    $from_user_result = $this->query->select_user_by_user_idx($from_user_idx);
                    $to_user_result = $this->query->select_user_by_user_idx($to_user_idx);

                    $from_name = $from_user_result[0]['name'];
                    $to_name = $to_user_result[0]['name'];

                    $notification_type = "NEW_POST_LIKE";
                    $contents = "{$from_name}님이 {$to_name}님의 게시글에 좋아요를 눌렀습니다.";
                    $table_type = $this->query->board_table;
                    $idx = $board_idx;

                    $this->query->insert_notification($from_user_idx, $to_user_idx, $notification_type, $contents, $table_type, $idx);

                    $to_firebase_token = $to_user_result[0]['firebase_token'];
                    if (!empty($to_firebase_token)) {
                        $firebase_requester = new FirebaseRequester(new HttpRequester());
                        $firebase_requester->send_fcm($to_firebase_token, $notification_type, $contents);
                    }
                }
            } else if ($type_upper == 'COMMENT') {
                // 누군가 내 댓글에 좋아요를 눌렀을 때 (여기서 "나"는 현재 로그인 해서 이용 중인 유저를 꼭 가리키는 게 아님), TODO: FCM 처리하기
                $liked_idx = $this->query->select_inserted_id();
                $liked_result = $this->query->select_liked_by_liked_idx($liked_idx);

                $comment_idx = $liked_result[0]['idx'];
                $comment_result = $this->query->select_comment_by_comment_idx($comment_idx);

                $from_user_idx = $user_idx;
                $to_user_idx = $comment_result[0]['user_idx'];

                if ($from_user_idx != $to_user_idx) {
                    $from_user_result = $this->query->select_user_by_user_idx($from_user_idx);
                    $to_user_result = $this->query->select_user_by_user_idx($to_user_idx);

                    $from_name = $from_user_result[0]['name'];
                    $to_name = $to_user_result[0]['name'];

                    $notification_type = "NEW_COMMENT_LIKE";
                    $contents = "{$from_name}님이 {$to_name}님의 댓글에 좋아요를 눌렀습니다.";
                    $table_type = $this->query->comment_table;
                    $idx = $comment_idx;

                    $this->query->insert_notification($from_user_idx, $to_user_idx, $notification_type, $contents, $table_type, $idx);
                    $to_firebase_token = $to_user_result[0]['firebase_token'];
                    if (!empty($to_firebase_token)) {
                        $firebase_requester = new FirebaseRequester(new HttpRequester());
                        $firebase_requester->send_fcm($to_firebase_token, $notification_type, $contents);
                    }
                }
            }
        } else {
            // TODO: 예외 처리 : 좋아요가 없는 경우
            $this->query->delete_liked($user_idx, $type_upper, $idx);
        }

        // 좋아요 개수 업데이트
        switch ($type_upper) {
            case 'POST':
                // TODO: 처음에 board라고 해놓은 거 진작에 안 고쳐서 이거 switch문 쓰고있네 씨 발, 고치자
                $like_count = $this->query->select_like_count($type_upper, $idx);
                $this->query->update_like_count('board', $idx, $like_count);
                $board_result = $this->query->select_board_by_board_idx($idx);
                $likes = (int)$board_result[0]['likes'];
                break;

            case 'COMMENT':
                $like_count = $this->query->select_like_count($type_upper, $idx);
                $this->query->update_like_count('comment', $idx, $like_count);
                $comment_result = $this->query->select_comment_by_comment_idx($idx);
                $likes = (int)$comment_result[0]['likes'];
                break;
        }
        $this->query->commit_transaction();

        return [
            'result' => $this->success_result,
            'likes' => $likes,
            'is_like' => $is_like
        ];
    }

    /** ------------ @category ?. 유틸리티 ------------ */
    private function make_post_items(array $posts_result, int $my_user_idx): array
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

            // 유저의 게시글 좋아요 boolean값 가져오기
            $user_like_result = $this->query->select_liked($my_user_idx, 'POST', $board_idx);
            $is_like = false; // 좋아요 했는지 여부 (default - false)
            if (!empty($user_like_result))  $is_like = true; // 쿼리 결과 사용자가 좋아요 했다면 $is_like true로 변경
            $post_item['is_like'] = $is_like;

            $post_item['create_date'] = $post_item_result['create_date'];
            $post_item['update_date'] = $post_item_result['update_date'];

            $posts[] = $post_item;
        }
        return $posts;
    }

    private function make_comment_items(array $comments_result, int $my_user_idx): array
    {
        $comments = [];

        foreach ($comments_result as $comment_item_result) {
            $user_idx = (int)$comment_item_result['user_idx'];
            $board_idx = (int)$comment_item_result['board_idx'];
            $comment_idx = (int)$comment_item_result['comment_idx'];
            $comment_item['user_idx'] = $user_idx;
            $comment_item['board_idx'] = $board_idx;
            $comment_item['comment_idx'] = $comment_idx;

            $user_result = $this->query->select_user_by_user_idx($user_idx);
            $comment_item['name'] = $user_result[0]['name'];
            $comment_item['profile_image_url'] = $user_result[0]['profile_image_url'];

            $comment_item['contents'] = $comment_item_result['contents'];
            $comment_item['likes'] = (int)$comment_item_result['likes'];

            // 유저의 게시글 좋아요 boolean값 가져오기
            $user_like_result = $this->query->select_liked($my_user_idx, 'COMMENT', $comment_idx);
            $is_like = false; // 좋아요 했는지 여부 (default - false)
            if (!empty($user_like_result))  $is_like = true; // 쿼리 결과 사용자가 좋아요 했다면 $is_user_like true로 변경
            $comment_item['is_like'] = $is_like;

            $comment_item['create_date'] = $comment_item_result['create_date'];
            $comment_item['update_date'] = $comment_item_result['update_date'];

            $comments[] = $comment_item;
        }
        return $comments;
    }
}
