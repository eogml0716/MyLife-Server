<?php

namespace LoLApp\app\models;

use LoLApp\app\ConfigManager;
use LoLApp\app\models\sql\BoardQuery;
use LoLApp\core\model\Model;
use LoLApp\core\utils\ResponseHelper;

/**
 * @category 1. 회원가입 관련
 *  (1) 기본 회원가입
 *  (2) SNS 회원가입
 * @category 2. 로그인 관련
 *  (1) 기본 로그인
 *  (2) SNS 로그인
 *  (3) 자동 로그인
 */
class BoardModel extends Model
{
    private $query;

    public function __construct(BoardQuery $query, ConfigManager $config_manager)
    {
        parent::__construct($query, $config_manager);
        $this->query = $query;
    }

}
