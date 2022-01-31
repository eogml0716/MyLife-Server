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

    // TODO: 게시글 리스트 가져오기 (무한 스크롤링)
    public function read_posts(array $client_data): array
    {
        return [
            'result' => $this->success_result
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

    // TODO: 게시글 추가
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
        // 이미지 파일 권한을 변경
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

    // TODO: 게시글 수정
    public function update_post(array $client_data): array
    {
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

    // TODO: 게시글 삭제
    public function delete_post(array $client_data): array
    {
        return [
            'result' => $this->success_result
        ];
    }

    // TODO: 댓글 삭제
    public function delete_comment(array $client_data): array
    {
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
}
