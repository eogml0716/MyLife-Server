<?php

namespace LoLApp\core\controller;

interface ControllerFactory
{
    function instantiate(string $type): Controller;
}
