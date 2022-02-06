<?php

use MyLifeServer\core\Router;

// index.php에서 제일 처음 연결 시켜줌
// 사용자가 사이트에 접속했을 때 Router 객체를 생성해줌
$router = new Router();

/** ------------ @category 1. 유저 ------------ */
// 회원가입
$router->post('/signup', 'User@signup');

// 로그인 (일반, 자동)
$router->post('/signin/:type(general|auto)', 'User@signin');
// 로그아웃
$router->delete('/signout', 'User@signout');

/** ------------ @category 2. 게시글, 댓글 ------------ */
// 게시글, 댓글 리스트 가져오기 (무한 스크롤링 or 1개) - TODO: 게시글의 경우 팔로우한 사람 게시글만 가져오게 변경하기
$router->get('/read/:type(posts|post|comments|comment)', 'Board@read');
// 게시글, 댓글 추가
$router->post('/create/:type(post|comment)', 'Board@create');
// 게시글, 댓글 수정
$router->put('/update/:type(post|comment)', 'Board@update');
// 게시글, 댓글 삭제
$router->delete('/delete/:type(post|comment)', 'Board@delete');
// 좋아요
$router->put('/update/like', 'Board@update_like');

/** ------------ @category 2. 검색 탭 관련 ------------ */
// TODO: 유저 검색

// TODO: 게시글 랜덤으로 가져오기 (무한 스크롤링)

/** ------------ @category 2. 알림 탭 관련 ------------ */
// TODO: 알림 추가(?)

// TODO: 알림 가져오기 (무한 스크롤링)

/** ------------ @category 4. 마이페이지 ------------ */
// 프로필 가져오기 (1개), 프로필 페이지 작성한 게시글 가져오기 (무한 스크롤링)
$router->get('/read/profile/:type(info|posts)', 'Profile@read');
// 나의 프로필 수정하기
$router->put('/update/profile', 'Profile@update_profile');
// TODO: 팔로잉, 팔로워 가져오기 (무한 스크롤링)
$router->get('/read/follow/:type(following|follower)', 'Profile@read_follow');
// TODO: 팔로우, 언팔로우 - 좋아요랑 구현 방식이 비슷할 거 같은데
$router->put('/update/profile/follow', 'Profile@update_follow');

return $router; // 경로를 추가한 라우터 객체 반환
