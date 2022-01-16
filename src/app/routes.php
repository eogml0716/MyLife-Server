<?php

use MyLifeServer\core\Router;

// index.php에서 제일 처음 연결 시켜줌
// 사용자가 사이트에 접속했을 때 Router 객체를 생성해줌
$router = new Router();

/** ------------ @category 1. 사용자 ------------ */
// 회원가입
$router->post('/signup', 'User@signup');

// 로그인, 세션, 로그아웃 관련
$router->post('/signin/:type(general|auto)', 'User@signin');
$router->delete('/signout', 'User@signout');

return $router; // 경로를 추가한 라우터 객체 반환
