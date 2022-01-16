<?php

namespace MyLifeServer\core\controller;

interface ControllerFactory
{
    function instantiate(string $type): Controller;
}
