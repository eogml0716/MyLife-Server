<?php

namespace MyLifeServer\app\controllers;

use MyLifeServer\app\models\UserModel;
use MyLifeServer\core\controller\Controller;
use MyLifeServer\core\utils\ResponseHelper;

class UserController extends Controller
{
    /*
    - models/UserModel.php 확인 -
    UserModel 객체를 담고 있는 변수 : 유저와 관련된 데이터, 로직 등을 관리한다.
     */
    private $model;

    public function __construct(UserModel $model)
    {
        $this->model = $model;
    }

    /** ------------ @category 1. 등록 관련 ------------ */
    // (1) 회원가입
    public function signup(): void
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case $this->post_method:
                $response = $this->model->signup($_POST);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                break;
        }
    }

    /** ----------- @category 2. 로그인 (세션), 로그아웃 관련 ----------- */
    // (1) 로그인
    public function signin(string $type): void
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case $this->post_method:
                switch ($type) {
                    case 'general':
                        $response = $this->model->general_signin($_POST);
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        break;

                        // 자동 로그인
                    case 'auto':
                        $response = $this->model->auto_signin($_POST);
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        break;

                        // TODO: 네이버 로그인
//                    case 'naver':
//                        $_POST = $this->get_client_data();
//                        $response = $this->model->auto_signin($_POST);
//                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
//                        break;
                       // TODO: 카카오 로그인
//                    case 'kakao':
//                        $_POST = $this->get_client_data();
//                        $response = $this->model->auto_signin($_POST);
//                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
//                        break;

                    default:
                        ResponseHelper::get_instance()->error_response(400, 'wrong login type');
                }
                break;
        }
    }

    // (?) 로그아웃
    public function signout(): void
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case $this->delete_method:
                $_DELETE = $this->get_client_data();
                $response = $this->model->signout($_DELETE);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                break;
        }
    }
}
