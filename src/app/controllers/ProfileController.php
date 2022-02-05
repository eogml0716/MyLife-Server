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

    // TODO: 나의 프로필, 다른 사람 프로필 가져오기 (1개), 내가 적성한 게시글, 다른 사람이 작성한 게시글 가져오기 (무한 스크롤링)
    public function read(string $type): void
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case $this->get_method:
                switch ($type) {
                    case 'me':
                        $response = $this->model->read_me($_GET);
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        break;

                    case 'mine':
                        $response = $this->model->read_mine($_GET);
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        break;

                    case 'other':
                        $response = $this->model->read_other($_GET);
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        break;

                    case 'others':
                        $response = $this->model->read_others($_GET);
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        break;

                    default:
                        ResponseHelper::get_instance()->error_response(400, 'wrong parameter type');
                }
                break;
        }
    }
}