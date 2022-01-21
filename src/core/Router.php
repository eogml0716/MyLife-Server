<?php

namespace MyLifeServer\core;

use MyLifeServer\app\MainControllerFactory;
use MyLifeServer\core\utils\ResponseHelper;

class Router
{
    // GET과 POST를 구별해서 저장하기 위해 각각의 key 생성
    private $routes = ['GET' => [], 'POST' => [], 'PUT' => [], 'DELETE' => []];
    private $regex_routes = ['GET' => [], 'POST' => [], 'PUT' => [], 'DELETE' => []];

    /**  -------------------------- @category 1. 라우트 추가 -------------------------- */
    // $router 객체를 생성, $file_path = router.php 파일을 로드하여 routes 데이터 입력 후 반환하는 메소드
    public static function load(string $file_path): self
    {
        return require $file_path;
    }

    // routes.php 파일에서 라우팅 위치를 정할 때 다음 메소드를 사용하여 $routes['GET']에 저장
    public function get(string $path, string $action): void
    {
        $this->add_routes('GET', $path, $action);
    }

    // routes.php 파일에서 라우팅 위치를 정할 때 다음 메소드를 사용하여 $routes['POST']에 저장
    public function post(string $path, string $action): void
    {
        $this->add_routes('POST', $path, $action);
    }

    // routes.php 파일에서 라우팅 위치를 정할 때 다음 메소드를 사용하여 $routes['PUT']에 저장
    public function put(string $path, string $action): void
    {
        $this->add_routes('PUT', $path, $action);
    }

    // routes.php 파일에서 라우팅 위치를 정할 때 다음 메소드를 사용하여 $routes['DELETE']에 저장
    public function delete(string $path, string $action): void
    {
        $this->add_routes('DELETE', $path, $action);
    }

    private function add_routes(string $method, string $path, string $action): void
    {
        if (strpos($path, ':') !== false) {
            $path = str_replace('/', '\/', $path); // / => \/
            $path = preg_replace('/:.[^\/]+(?=\([^\/]+\))/', '', $path);
            $path = preg_replace('/(:[^\\\]+)/', "([^\/]+)", $path); // :params => /제외 모든 단어 정규식
            $this->regex_routes[$method]["/^{$path}$/"] = $action;
            return;
        }
        $this->routes[$method][$path] = $action;
    }

    /**  -------------------------- @category 2. 라우터 실행 -------------------------- */
    /**
     * request_type 을 선별하고 page의 end point 를 반환
     * Memo : explode - '@'가 존재하면 string을 분할시키고 배열에 저장한다
     */
    public function direct(string $request_uri, string $request_method): void
    {
        $path = parse_url($request_uri, PHP_URL_PATH); // TODO: traiing slash 지워야하면 rtrim 작업 해주기

        if (isset($this->routes[$request_method][$path])) {
            list($controller_type, $method) = explode('@', $this->routes[$request_method][$path]);
            $this->call_action($controller_type, $method);
            return;
        }
        foreach ($this->regex_routes[$request_method] as $regexPath => $action) {
            if (preg_match_all($regexPath, $path, $matches) === 1) {
                $params = [];

                foreach ($matches as $index => $value) {
                    if ($index === 0) {
                        continue;
                    }
                    $params[] = $value[0];
                }
                list($controller_type, $method) = explode('@', $this->regex_routes[$request_method][$regexPath]);
                $this->call_action($controller_type, $method, $params);
                return;
            }
        }
        ResponseHelper::get_instance()->dev_error_response(404, "wrong page"); // 잘못된 페이지, 요청 메소드일 때
    }

    // controller 객체를 생성하고 $action(controller클래스 메소드)를 실행하는 메소드 + 파일에 로그 쓰기
    private function call_action(string $controller_type, string $method, array $params = []): void
    {
        $factory = new MainControllerFactory();
        $controller = $factory->instantiate($controller_type);

        if (!method_exists($controller, $method)) {
            ResponseHelper::get_instance()->dev_error_response(500, 'wrong controller method');
        }
        $controller->$method(...$params); // 컨트롤러 객체의 내부 메소드 실행
    }
}
