<?php

namespace MyLifeServer\app\models;

use Exception;
use MyLifeServer\app\ConfigManager;
use MyLifeServer\app\models\sql\UserQuery;
use MyLifeServer\core\model\HttpRequester;
use MyLifeServer\core\model\Model;
use MyLifeServer\core\utils\ResponseHelper;
use stdClass;

/**
 * @category 1. 회원가입 관련
 *  (1) 기본 회원가입
 *  (2) SNS 회원가입
 * @category 2. 로그인 관련
 *  (1) 기본 로그인
 *  (2) SNS 로그인
 *  (3) 자동 로그인
 */
class UserModel extends Model
{
    private $query;

    public function __construct(UserQuery $query, ConfigManager $config_manager)
    {
        parent::__construct($query, $config_manager);
        $this->query = $query;
    }

    /** ------------ @category 1. 등록 관련 ------------ */
    // (1) 회원가입
    public function signup(): void
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case $this->post_method:
                $response = $this->model->register_user($_POST);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                break;
        }
    }
}
