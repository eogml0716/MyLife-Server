<?php

namespace MyLifeServer\app\controllers;

use MyLifeServer\app\models\NotificationModel;
use MyLifeServer\core\controller\Controller;
use MyLifeServer\core\utils\ResponseHelper;

class NotificationController extends Controller
{
    /*
    - models/NotificationModel.php 확인 -
    NotificationModel 객체를 담고 있는 변수
     */
    private $model;

    public function __construct(NotificationModel $model)
    {
        $this->model = $model;
    }

    public function read(string $type): void
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case $this->get_method:
                switch ($type) {
                    case 'notifications':
                        $response = $this->model->read_notifications($_GET);
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        break;

                    default:
                        ResponseHelper::get_instance()->error_response(400, 'wrong parameter type');
                }
                break;
        }
    }
}