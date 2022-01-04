<?php

namespace LoLApp\app;

class ConfigManager
{
    private static $config_manager;
    private $config;

    private function __construct(string $config_file_path)
    {
        $this->config = require $config_file_path;
    }

    public static function get_instance(): self
    {
        if (empty(static::$config_manager)) {
            return new self('/home/ubuntu/config.php');
        }
        return static::$config_manager;
    }

    public function get_db_config(): array
    {
        return $this->config['database'];
    }
}
