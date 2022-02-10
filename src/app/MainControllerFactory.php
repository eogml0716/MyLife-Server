<?php

namespace MyLifeServer\app;

use MyLifeServer\core\controller\Controller;
use MyLifeServer\core\controller\ControllerFactory;
use MyLifeServer\core\utils\ResponseHelper;
use MyLifeServer\app\ConfigManager;
use MyLifeServer\app\controllers\UserController;
use MyLifeServer\app\controllers\BoardController;
use MyLifeServer\app\controllers\SearchController;
use MyLifeServer\app\controllers\NotificationController;
use MyLifeServer\app\controllers\ProfileController;
use MyLifeServer\app\controllers\ChatController;
use MyLifeServer\app\models\UserModel;
use MyLifeServer\app\models\BoardModel;
use MyLifeServer\app\models\SearchModel;
use MyLifeServer\app\models\NotificationModel;
use MyLifeServer\app\models\ProfileModel;
use MyLifeServer\app\models\ChatModel;
use MyLifeServer\app\models\sql\CommonQuery;

class MainControllerFactory implements ControllerFactory
{
    // @override
    public function instantiate(string $type): Controller
    {
        $config_manager = ConfigManager::get_instance();
        $db_config = $config_manager->get_db_config();

        switch ($type) {
            case 'User':
                $controller = new UserController(new UserModel(new CommonQuery($db_config), $config_manager));
                break;

            case 'Board':
                $controller = new BoardController(new BoardModel(new CommonQuery($db_config), $config_manager));
                break;

            case 'Search':
                $controller = new SearchController(new SearchModel(new CommonQuery($db_config), $config_manager));
                break;

            case 'Notification':
                $controller = new NotificationController(new NotificationModel(new CommonQuery($db_config), $config_manager));
                break;

            case 'Profile':
                $controller = new ProfileController(new ProfileModel(new CommonQuery($db_config), $config_manager));
                break;

            case 'Chat':
                $controller = new ChatController(new ChatModel(new CommonQuery($db_config), $config_manager));
                break;

            default:
                ResponseHelper::get_instance()->error_response(500, 'wrong controller type');
        }

        return $controller;
    }
}
