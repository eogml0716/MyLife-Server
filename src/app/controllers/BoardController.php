<?php

namespace MyLifeServer\app\controllers;

use MyLifeServer\app\models\BoardModel;
use MyLifeServer\core\controller\Controller;
use MyLifeServer\core\utils\ResponseHelper;

class BoardController extends Controller
{
    /*
    - models/BoardModel.php 확인 -
    BoardModel 객체를 담고 있는 변수 : 게시판과 관련된 데이터, 로직 등을 관리한다.
     */
    private $model;

    public function __construct(BoardModel $model)
    {
        $this->model = $model;
    }

    // 게시글, 댓글 리스트 가져오기 (무한 스크롤링 or 1개)
    public function read(string $type): void
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case $this->get_method:
                switch ($type) {
                    case 'posts':
                        $response = $this->model->read_posts($_GET);
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        break;

                    case 'post':
                        $response = $this->model->read_post($_GET);
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        break;

                    case 'comments':
                        $response = $this->model->read_comments($_GET);
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        break;

                    default:
                        ResponseHelper::get_instance()->error_response(400, 'wrong parameter type');
                }
                break;
        }
    }

    // 게시글, 댓글 추가
    public function create(string $type): void
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case $this->post_method:
                switch ($type) {
                    case 'post':
                        $response = $this->model->create_post($_POST);
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        break;

                    case 'comment':
                        $response = $this->model->create_comment($_POST);
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        break;

                    default:
                        ResponseHelper::get_instance()->error_response(400, 'wrong parameter type');
                }
                break;
        }
    }

    // 게시글, 댓글 수정
    public function update(string $type): void
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case $this->put_method:
                switch ($type) {
                    case 'post':
                        $_PUT = $this->get_client_data();
                        $response = $this->model->update_post($_PUT);
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        break;

                    case 'comment':
                        $_PUT = $this->get_client_data();
                        $response = $this->model->update_comment($_PUT);
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        break;

                    default:
                        ResponseHelper::get_instance()->error_response(400, 'wrong parameter type');
                }
                break;
        }
    }

    // 게시글, 댓글 삭제
    public function delete(string $type): void
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case $this->delete_method:
                switch ($type) {
                    case 'post':
                        $_DELETE = $this->get_client_data();
                        $response = $this->model->delete_post($_DELETE);
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        break;

                    case 'comment':
                        $_DELETE = $this->get_client_data();
                        $response = $this->model->delete_comment($_DELETE);
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        break;

                    default:
                        ResponseHelper::get_instance()->error_response(400, 'wrong parameter type');
                }
                break;
        }
    }

    // 좋아요
    public function update_like(): void
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case $this->put_method:
                $_PUT = $this->get_client_data();
                $response = $this->model->update_like($_PUT);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                break;
        }
    }

}
