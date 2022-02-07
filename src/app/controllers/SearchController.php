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

    public function read(string $type): void
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case $this->get_method:
                switch ($type) {
                    case 'users':
                        $response = $this->model->read_users($_GET);
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
}