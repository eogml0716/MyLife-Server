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

}