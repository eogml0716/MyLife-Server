<?php

namespace MyLifeServer\app\models;

use Exception;
use MyLifeServer\app\ConfigManager;
use MyLifeServer\app\models\sql\NotificationQuery;
use MyLifeServer\core\model\HttpRequester;
use MyLifeServer\core\model\Model;
use MyLifeServer\core\utils\ResponseHelper;
use stdClass;

class NotificationModel extends Model
{
    private $query;

    public function __construct(NotificationQuery $query, ConfigManager $config_manager)
    {
        parent::__construct($query, $config_manager);
        $this->query = $query;
    }

}