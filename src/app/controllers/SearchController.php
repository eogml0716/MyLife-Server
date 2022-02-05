<?php

namespace MyLifeServer\app\controllers;

use MyLifeServer\app\models\SearchModel;
use MyLifeServer\core\controller\Controller;
use MyLifeServer\core\utils\ResponseHelper;

class SearchController extends Controller
{
    /*
    - models/SearchModel.php 확인 -
    SearchModel 객체를 담고 있는 변수
     */
    private $model;

    public function __construct(SearchModel $model)
    {
        $this->model = $model;
    }

}