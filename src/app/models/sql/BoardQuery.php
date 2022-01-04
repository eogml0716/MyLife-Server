<?php

namespace LoLApp\app\models\sql;

use LoLApp\core\model\database\Query;

class BoardQuery extends Query
{
    public function __construct(array $mobile_db_config)
    {
        parent::__construct($mobile_db_config);
    }

}
