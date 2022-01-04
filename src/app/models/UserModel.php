<?php

namespace LoLApp\app\models;

use Exception;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use LoLApp\app\ConfigManager;
use LoLApp\app\models\nosql\UserQuery as NosqlUserQuery;
use LoLApp\app\models\sql\UserQuery;
use LoLApp\app\utils\EmailHelper;
use LoLApp\app\utils\FirebaseRequester;
use LoLApp\core\model\HttpRequester;
use LoLApp\core\model\Model;
use LoLApp\core\utils\ResponseHelper;
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

}
