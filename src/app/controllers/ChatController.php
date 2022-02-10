<?php

namespace MyLifeServer\app\controllers;

use MyLifeServer\app\models\ChatModel;
use MyLifeServer\core\controller\Controller;
use MyLifeServer\core\utils\ResponseHelper;

class ChatController extends Controller
{
    /*
    - models/ChatModel.php 확인 -
    ChatModel 객체를 담고 있는 변수
     */
    private $model;

    public function __construct(ChatModel $model)
    {
        $this->model = $model;
    }

    public function read(string $type): void
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case $this->get_method:
                switch ($type) {
                    case 'info':
                        $response = $this->model->read_info($_GET);
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        break;

                    case 'rooms':
                        $response = $this->model->read_rooms($_GET);
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        break;

                    case 'messages':
                        $response = $this->model->read_messages($_GET);
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        break;

                    default:
                        ResponseHelper::get_instance()->error_response(400, 'wrong parameter type');
                }
                break;
        }
    }

    public function create(string $type): void
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case $this->post_method:
                switch ($type) {
                    case 'personal_room':
                        $response = $this->model->insert_personal_room($_POST);
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        break;

                    case 'text_message':
                        $response = $this->model->create_text_message($_POST);
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        break;

                    case 'image_message':
                        $response = $this->model->create_image_message($_POST);
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        break;

                    default:
                        ResponseHelper::get_instance()->error_response(400, 'wrong parameter type');
                }
                break;
        }
    }

    public function delete(string $type): void
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case $this->delete_method:
                switch ($type) {
                    case 'personal_room':
                        $_DELETE = $this->get_client_data();
                        $response = $this->model->delete_personal_room($_DELETE);
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        break;

                    default:
                        ResponseHelper::get_instance()->error_response(400, 'wrong parameter type');
                }
                break;
        }
    }

}