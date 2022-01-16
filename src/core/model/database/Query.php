<?php

namespace MyLifeServer\core\model\database;

use MyLifeServer\core\model\database\QueryBuilder;
use MyLifeServer\core\utils\ResponseHelper;

class Query extends QueryBuilder
{
    // 사용자 관련 테이블명
    public $user = 'user';

    public function __construct(array $db_config)
    {
        parent::__construct($db_config);
    }

    // (1) 마지막으로 insert한 idx가져오기
    public function select_inserted_id(): int
    {
        if ($this->select_last_insert_id()[0]['LAST_INSERT_ID()'] == 0) {
            ResponseHelper::get_instance()->error_response(409, 'No data was saved due to a duplicate value entry.');
        }
        return $this->select_last_insert_id()[0]['LAST_INSERT_ID()'];
    }
}
