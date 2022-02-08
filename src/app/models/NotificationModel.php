<?php

namespace MyLifeServer\app\models;

use Exception;
use MyLifeServer\app\ConfigManager;
use MyLifeServer\app\models\sql\CommonQuery;
use MyLifeServer\core\model\HttpRequester;
use MyLifeServer\core\model\Model;
use MyLifeServer\core\utils\ResponseHelper;
use stdClass;

class NotificationModel extends Model
{
    private $query;

    public function __construct(CommonQuery $query, ConfigManager $config_manager)
    {
        parent::__construct($query, $config_manager);
        $this->query = $query;
    }

    public function read_notifications(array $client_data): array
    {
        $user_idx = $this->check_user_session($client_data);
        $page = $this->check_int_data($client_data, 'page');
        $limit = $this->check_int_data($client_data, 'limit');
        $table_name = $this->query->notification_table;
        $start_num = ($page - 1) * $limit; // 요청하는 페이지에 시작 번호
        $is_last = false;

        $to_user_idx = $user_idx;
        $notifications_result = $this->query->select_notifications_order_by_create_date($table_name, $limit, $start_num, $to_user_idx);

        if (empty($notifications_result)) ResponseHelper::get_instance()->error_response(204, 'no item');
        if (count($notifications_result) < $limit) $is_last = true;

        $notifications = $this->make_notifications_items($notifications_result);

        return [
            'result' => $this->success_result,
            'isLast' => $is_last,
            'notifications' => $notifications
        ];
    }

    private function make_notifications_items(array $notifications_result): array
    {
        $notifications = [];

        foreach ($notifications_result as $notification_item_result) {
            $notification_item['notification_idx'] = (int)$notification_item_result['notification_idx'];
            $from_user_idx = (int)$notification_item_result['from_user_idx'];
            $notification_item['from_user_idx'] = $from_user_idx;
            $from_user_result = $this->query->select_user_by_user_idx($from_user_idx);
            $notification_item['from_profile_image_url'] = $from_user_result[0]['profile_image_url'];
            $notification_item['from_name'] = $from_user_result[0]['name'];

            $to_user_idx = (int)$notification_item_result['to_user_idx'];
            $notification_item['to_user_idx'] = $to_user_idx;
            $to_user_result = $this->query->select_user_by_user_idx($from_user_idx);
            $notification_item['to_profile_image_url'] = $to_user_result[0]['profile_image_url'];
            $notification_item['to_name'] = $to_user_result[0]['name'];

            $notification_item['notification_type'] = $notification_item_result['notification_type'];
            $notification_item['contents'] = $notification_item_result['contents'];
            $notification_item['table_type'] = $notification_item_result['table_type'];
            $notification_item['idx'] = (int)$notification_item_result['idx'];
            $notification_item['create_date'] = $notification_item_result['create_date'];
            $notification_item['update_date'] = $notification_item_result['update_date'];

            $notifications[] = $notification_item;
        }
        return $notifications;
    }
}