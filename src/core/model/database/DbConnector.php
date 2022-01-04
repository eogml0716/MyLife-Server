<?php

namespace LoLApp\core\model\database;

use Exception;
use LoLApp\core\utils\LogHelper;
use LoLApp\core\utils\ResponseHelper;
use PDO;
use PDOException;
use PDOStatement;

class DbConnector
{
    private $pdo;
    private $is_transaction_on = false; // 트렌젝션이 실행됬는지 여부
    private $log_helper; // DBHelper 싱글턴 객체

    public function __construct(array $mobile_db_config)
    {
        // $pdo : php에서 제공하는 pdo 클래스를 사용해 db와 연결 및 쿼리를 담당하는 변수
        $this->pdo = $this->connect_db($mobile_db_config);
        $this->log_helper = LogHelper::get_instance();
    }

    /**
     * config.php 파일에 있는 key-value 배열에서 mobile_db를 선택하여 인자로 사용
     * (config.php 파일 위치 : /home/ubuntu/config.php -> db 정보를 담고 있는 배열이 선언되어 있어 서버 루트 폴더 밖에 존재한다.)
     */
    private function connect_db(array $databaseConfig): PDO
    {
        try {
            return new PDO(
                $databaseConfig['connection'] . ';dbname=' . $databaseConfig['dbName'],
                $databaseConfig['userName'],
                $databaseConfig['password'],
                $databaseConfig['options']
            );
        } catch (PDOException $e) {
            ResponseHelper::get_instance()->error_response(500, $e->getMessage());
        }
    }

    // 트랜젝션 실행하는 메소드
    public function begin_transaction(): void
    {
        try {
            $this->is_transaction_on = $this->pdo->beginTransaction();
            if (!$this->is_transaction_on) {
                ResponseHelper::get_instance()->error_response(500, 'begin transaction failed');
            }
        } catch (PDOException $e) {
            ResponseHelper::get_instance()->error_response(500, $e->getMessage());
        }
    }

    // 트랜잭션 commit 하는 메소드
    public function commit_transaction(): void
    {
        try {
            $this->is_transaction_on = false;
            if (!$this->pdo->commit()) {
                ResponseHelper::get_instance()->error_response(500, 'commit transaction failed');
            }
        } catch (PDOException $e) {
            ResponseHelper::get_instance()->error_response(500, $e->getMessage());
        }
    }

    // 입력 받은 sql문으로 db에서 쿼리하는 메소드
    protected function execute_sql_statement(string $sql_statement): PDOStatement
    {
        try {
            $pdo_statement = $this->pdo->prepare($sql_statement); // prepare 메소드를 사용하여 mysqli_real_escape_string와 같이 안전하게 쿼리 가능
            $pdo_statement->execute();
            $this->log_helper->save_user_sql_log($sql_statement);

            return $pdo_statement;
        } catch (Exception $e) {
            if ($this->is_transaction_on) {
                $this->pdo->rollBack(); // 트랜잭션이 실행되어 있다면 그 동안의 모든 쿼리 롤백
            }
            ResponseHelper::get_instance()->error_response(500, $e->getMessage());
        }
    }

    // SELECT 문의 경우 쿼리 결과를 배열로 반환하는 메소드
    protected function fetch_query_data(string $sql_statement): array
    {
        $pdo_statement = $this->execute_sql_statement($sql_statement);
        return $pdo_statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
