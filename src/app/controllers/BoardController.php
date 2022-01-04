<?php

namespace LoLApp\app\controllers;

use LoLApp\app\models\BoardModel;
use LoLApp\core\controller\Controller;

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

}
