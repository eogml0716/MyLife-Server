<?php

use MyLifeServer\core\Router;

// index.php에서 제일 처음 연결 시켜줌
// 사용자가 사이트에 접속했을 때 Router 객체를 생성해줌
$router = new Router();

/** ------------ @category 1. 유저 ------------ */
// 회원가입
$router->post('/signup', 'User@signup');

// 로그인, TODO: 자동 로그인
$router->post('/signin/:type(general|auto)', 'User@signin');
// TODO: 로그아웃
$router->delete('/signout', 'User@signout');

/** ------------ @category 2. 게시글, 댓글 ------------ */
// TODO: 게시글, 댓글 리스트 가져오기 (무한 스크롤링 or 1개)
$router->get('/read/:type(posts|post|comments|comment)', 'Board@read');
// TODO: 게시글, 댓글 추가
$router->post('/create/:type(post|comment)', 'Board@create');
// TODO: 게시글, 댓글 수정
$router->put('/update/:type(post|comment)', 'Board@update');
// TODO: 게시글, 댓글 삭제
$router->delete('/delete/:type(post|comment)', 'Board@delete');
// TODO: 게시글, 댓글 좋아요
$router->put('/update/like/:type(post|comment)', 'Board@update_like');

/** ------------ @category 4. 마이페이지 ------------ */

return $router; // 경로를 추가한 라우터 객체 반환
