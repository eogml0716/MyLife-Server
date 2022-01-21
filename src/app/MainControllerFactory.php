<?php

namespace MyLifeServer\app;

use MyLifeServer\app\ConfigManager;
use MyLifeServer\app\controllers\BoardController;
use MyLifeServer\app\controllers\UserController;
use MyLifeServer\app\models\BoardModel;
use MyLifeServer\app\models\sql\BoardQuery;
use MyLifeServer\app\models\sql\UserQuery;
use MyLifeServer\app\models\UserModel;
use MyLifeServer\core\controller\Controller;
use MyLifeServer\core\controller\ControllerFactory;
use MyLifeServer\core\utils\ResponseHelper;

class MainControllerFactory implements ControllerFactory
{
    // @override
    public function instantiate(string $type): Controller
    {
        $config_manager = ConfigManager::get_instance();
        $db_config = $config_manager->get_db_config();

        switch ($type) {
            case 'User':
                $controller = new UserController(new UserModel(new UserQuery($db_config), $config_manager));
                break;

            case 'Board':
                $controller = new BoardController(new BoardModel(new BoardQuery($db_config), $config_manager));
                break;

            default:
                ResponseHelper::get_instance()->error_response(500, 'wrong controller type');
        }

        return $controller;
    }
}
