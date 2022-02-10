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

// 파이어베이스 토큰 업로드
$router->post('/firebase', 'User@firebase');

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
// 유저 검색, 게시글 랜덤으로 가져오기 (무한 스크롤링)
$router->get('/read/search/:type(users|posts)', 'Search@read');

/** ------------ @category 2. 알림 탭 관련 ------------ */
// TODO: 알림 가져오기 (무한 스크롤링)
$router->get('/read/:type(notifications)', 'Notification@read');
/** ------------ @category 4. 마이페이지 ------------ */
// 프로필 가져오기 (1개), 프로필 페이지 작성한 게시글 가져오기 (무한 스크롤링)
$router->get('/read/profile/:type(info|posts)', 'Profile@read');
// 나의 프로필 수정하기
$router->put('/update/profile', 'Profile@update_profile');
// 팔로잉, 팔로워 가져오기 (무한 스크롤링)
$router->get('/read/follow/:type(followings|followers)', 'Profile@read_follow');
// 팔로우, 언팔로우 - 좋아요랑 구현 방식이 비슷할 거 같은데
$router->put('/update/profile/follow', 'Profile@update_follow');

/** ------------ @category 4. 채팅 관련 ------------ */
/**
 * 채팅방 목록 불러오기 (무한 스크롤링), 채팅방 정보 불러오기
 * 채팅 메시지 목록(?) 불러오기 (무한 스크롤링(?)) - TODO: 근데 이건 위로 무한 스크롤링인데 안드로이드에서 위로 스크롤링하면 최신께 역순으로(?) 나오는 식으로 해야할 듯, 일단 기능 완성시키고 1순위로 고치기
 */
$router->get('/read/chat/:type(info|rooms|messages)', 'Chat@read');
// 채팅방 만들기, 텍스트 메시지 저장하기, 이미지 메시지 저장하기
$router->post('/create/chat/:type(personal_room|text_message|image_message)', 'Chat@create');
/**
 * TODO: 채팅방 나가기
 * TODO: 1:1 채팅방 openType 바꾸기, 근데 채팅방을 나가게 되면 기존 채팅내역도 다 삭제를 해주어야하는데 그냥 단순 삭제 해버리면, 안 나간 사람도 나간 사람의 채팅이 삭제되게 되는건데 이거 어떻게 처리할 지도 생각해보기
 */
$router->delete('/delete/chat/:type(personal_room)', 'Chat@delete');

return $router; // 경로를 추가한 라우터 객체 반환
