<?php

use MyLifeServer\core\Router;

// 에러 로그 출력하는 코드
error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Asia/Seoul'); // 한국 시간 디폴트 timezone으로 변경

// ------------------ cors 정책 ------------------
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

// preflight 요청인 경우 해당 옵션만 제공하고 exit
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) header('Access-Control-Allow-Headers: ' .  $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) header('Access-Control-Allow-Methods: POST, PUT, GET, DELETE');
    exit();
}

// ------------------ app 실행 ------------------
//require 'vendor/autoload.php'; // composer auto loading

/**
 * 라우팅 작업
 * $router 배열을 로드한 후 direct : controller 객체 생성 및 메소드 호출
 * 라우팅 과정 설명
 * (1) 라우팅 경로들 추가하기
 *  - Router::load - Router 자기 자신 클래스 객체화 후 routes.php 파일 호출
 *  - routes.php - $router(Router 객체) 메소드로 Router 내부 변수인 $routes에 경로 입력
 * (2) 요청에 맞는 컨트롤러 생성 및 메소드 실행
 *  - direct() - 요청 타입을 선별
 *  - Router 클래스 내부 함수 , call_action을 통해 컨트롤러 생성 및 메소드 실행
 */
Router::load('src/app/routes.php')->direct($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
