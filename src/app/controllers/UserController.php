<?php

namespace LoLApp\app\controllers;

use LoLApp\app\models\UserModel;
use LoLApp\core\controller\Controller;

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



}
