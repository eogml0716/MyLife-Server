<?php

namespace LoLApp\core\utils;

class ResponseHelper
{
    private static $response_helper;

    private function __construct()
    {
    }

    public static function get_instance(): self
    {
        if (empty(static::$response_helper)) {
            return new self;
        }
        return static::$response_helper;
    }

    // 개발 오류로 발생한 에러
    public function dev_error_response(int $response_code, string $error_message)
    {
        http_response_code($response_code);
        echo json_encode(['message' => $error_message], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // 클라이언트(사용자)에게 전달해야할 에러
    public function error_response(int $response_code, ?string $error_message = null): void
    {
        http_response_code($response_code);
        echo json_encode(['message' => $error_message], JSON_UNESCAPED_UNICODE);
        // LogHelper::get_instance()->write_user_log(false); // TODO: 로그 찍는 메소드
        exit();
    }
}
