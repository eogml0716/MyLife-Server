<?php
namespace MyLifeServer\app\utils;

use MyLifeServer\app\ConfigManager;
use MyLifeServer\core\model\HttpRequester;

class FirebaseRequester
{
    private $http_requester;
    private $fcm_url;
    private $server_key;

    public function __construct(HttpRequester $http_requester)
    {
        $firebase_config = ConfigManager::get_instance()->get_firebase_config();
        $this->http_requester = $http_requester;
        $this->fcm_url = $firebase_config['url'];
        $this->server_key = $firebase_config['server_key'];
    }

    /**
     * FCM 요청
     * @return FCM 요청 실패한 토큰 수
     */
    public function send_fcm(string $to, string $title, string $contents, string $priority = 'high'): int
    {
        $header = [
            "Authorization:key = {$this->server_key}",
            'Content-Type: application/json',
        ];
        $body = [
            'to' => $to,
            'priority' => $priority,
            'notification' => [
                'title' => $title,
                'body' => $contents
            ],
        ];
        $json_fcm_response = $this->http_requester->post_request($this->fcm_url, $body, $header);
        return json_decode($json_fcm_response, true)['failure'];
    }
}