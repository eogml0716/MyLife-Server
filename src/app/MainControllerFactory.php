<?php

namespace LoLApp\app;

use LoLApp\app\ConfigManager;
use LoLApp\app\controllers\BoardController;
use LoLApp\app\controllers\UserController;
use LoLApp\app\models\BoardModel;
use LoLApp\app\models\sql\BoardQuery;
use LoLApp\app\models\sql\UserQuery;
use LoLApp\app\models\UserModel;
use LoLApp\core\controller\Controller;
use LoLApp\core\controller\ControllerFactory;
use LoLApp\core\utils\ResponseHelper;

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
