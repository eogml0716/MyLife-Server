<?php

namespace MyLifeServer\app\controllers;

use MyLifeServer\app\models\ProfileModel;
use MyLifeServer\core\controller\Controller;
use MyLifeServer\core\utils\ResponseHelper;

class ProfileController extends Controller
{
    /*
    - models/ProfileController.php 확인 -
    ProfileController 객체를 담고 있는 변수
     */
    private $model;

    public function __construct(ProfileModel $model)
    {
        $this->model = $model;
    }

    // 프로필 가져오기 (1개), 프로필 페이지 작성한 게시글 가져오기 (무한 스크롤링)
    public function read(string $type): void
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case $this->get_method:
                switch ($type) {
                    case 'info':
                        $response = $this->model->read_info($_GET);
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        break;

                    case 'posts':
                        $response = $this->model->read_posts($_GET);
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        break;

                    default:
                        ResponseHelper::get_instance()->error_response(400, 'wrong parameter type');
                }
                break;
        }
    }

    public function update_profile(): void
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case $this->put_method:
                $_PUT = $this->get_client_data();
                $response = $this->model->update_profile($_PUT);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                break;
        }
    }

    // 팔로우, 언팔로우
    public function update_follow(): void
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case $this->put_method:
                $_PUT = $this->get_client_data();
                $response = $this->model->update_follow($_PUT);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                break;
        }
    }
}