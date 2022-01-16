<?php

namespace MyLifeServer\core\model\database;

use MyLifeServer\core\model\database\DbConnector;

/**
 * @category 1. SELECT 관련
 *  (1) WHERE + 관계 연산자
 *   1) 중복 허용
 *   2) 중복 제거
 *  (2) WHERE + IN
 *  (3) 페이징
 * @category 2. INSERT 관련
 * @category 3. UPDATE 관련
 * @category 4. DELETE 관련
 * @category 5. 범용적으로 사용하는 쿼리 모음
 * @category 5. 유틸리티 메소드 모음
 */
class QueryBuilder extends DbConnector
{
    protected $none = '';
    protected $null = 'NULL';
    protected $where_all = '1'; // where 조건이 필요 없을 때 사용
    protected $current_timestamp = 'CURRENT_TIMESTAMP';
    protected $true = 1;
    protected $false = 0;

    // select 조건
    protected $distinct = 'DISTINCT';
    protected $count_method = 'count(*)';
    // 관계연산자 모음
    protected $equal = '=';
    protected $inequal = '!=';
    protected $greater_than = '>';
    protected $less_than = '<';
    protected $self_add = '+=';
    // 논리 연산자 모음
    protected $is = ' IS ';
    protected $is_not = ' IS NOT ';
    protected $or = ' OR ';
    protected $like = ' LIKE ';

    public function __construct(array $mobile_db_config)
    {
        parent::__construct($mobile_db_config);
    }

    /** --------------------------- @category 1. SELECT 관련 --------------------------- */
    // (1) 기본 SELECT
    /**
     *  1) WHERE
     * @param 부가 설명
     * $select_columns - select할 행들
     * $conditions - make_conditions메소드를 사용해 관계연산자 만들어서 파라미터에 넣는다
     * $logical_operator - default로 'AND' 논리 연산자 사용
     */
    protected function select_by_operator(
        string $table_name,
        string $select_condition,
        array $select_columns,
        array $conditions,
        string $logical_operator = ' AND '
    ): array{
        $sql_statement = sprintf(
            'SELECT %s %s FROM %s WHERE %s',
            $select_condition,
            implode(', ', array_values($select_columns)),
            $table_name,
            implode($logical_operator, array_values($conditions))
        );
        return $this->fetch_query_data($sql_statement);
    }

    // (2) 마지막 insert한 idx 값 퀴리
    protected function select_last_insert_id(): array
    {
        $sql_statement = 'select LAST_INSERT_ID()';
        return $this->fetch_query_data($sql_statement);
    }

    // (3) 페이징
    //  1) WHERE X
    public function select_page(
        string $table_name,
        array $select_columns,
        string $order_column,
        int $limit,
        int $off_set
    ): array{
        $sql_statement = sprintf(
            'SELECT %s FROM %s ORDER BY %s DESC LIMIT %s OFFSET %s',
            implode(', ', array_values($select_columns)),
            $table_name,
            $order_column,
            $limit,
            $off_set
        );
        return $this->fetch_query_data($sql_statement);
    }

    //  2) WHERE + 관계 연산자
    protected function select_page_by_operator(
        string $table_name,
        array $select_columns,
        array $conditions,
        string $order_column,
        int $limit,
        int $off_set,
        $logical_operator = ' AND '
    ): array{
        $sql_statement = sprintf(
            'SELECT %s FROM %s WHERE %s ORDER BY %s DESC LIMIT %s OFFSET %s',
            implode(', ', array_values($select_columns)),
            $table_name,
            implode($logical_operator, array_values($conditions)),
            $order_column,
            $limit,
            $off_set
        );
        return $this->fetch_query_data($sql_statement);
    }

    //  3) WHERE x, GROUP BY
    protected function select_page_by_group(
        string $table_name,
        array $select_columns,
        string $grouping_column,
        string $order_column,
        int $limit,
        int $off_set
    ): array{
        $sql_statement = sprintf(
            'SELECT %s FROM %s GROUP BY %s ORDER BY %s DESC LIMIT %s OFFSET %s',
            implode(', ', array_values($select_columns)),
            $table_name,
            $grouping_column,
            $order_column,
            $limit,
            $off_set
        );
        return $this->fetch_query_data($sql_statement);
    }

    //  4) WHERE O, GROUP BY
    protected function select_page_by_multi_operator(
        string $table_name,
        array $select_columns,
        array $conditions,
        string $grouping_column,
        string $order_column,
        int $limit,
        int $off_set,
        string $logical_operator = ' AND '
    ): array{
        $sql_statement = sprintf(
            'SELECT %s FROM %s WHERE %s GROUP BY %s ORDER BY %s DESC LIMIT %s OFFSET %s',
            implode(', ', array_values($select_columns)),
            $table_name,
            implode($logical_operator, array_values($conditions)),
            $grouping_column,
            $order_column,
            $limit,
            $off_set
        );
        return $this->fetch_query_data($sql_statement);
    }

    /** --------------------------- @category 2. INSERT 관련 --------------------------- */
    // 1) 중복 방지 X
    protected function insert_data(string $table_name, array $insert_key_value_list, bool $need_quotes = true): void
    {
        $value = $need_quotes ? "'%s'" : '%s';
        $value_glue = $need_quotes ? "','" : ',';
        $sql_statement = sprintf(
            "INSERT INTO %s (%s) VALUES ($value)",
            $table_name,
            implode(', ', array_keys($insert_key_value_list)),
            implode($value_glue, array_values($insert_key_value_list))
        );
        $this->execute_sql_statement($sql_statement);
    }

    // 2) 중복 방지 O
    protected function insert_data_avoid_duplication(string $table_name, array $insert_key_value_list, array $select_key_value_list, bool $need_quotes = true, string $logical_operator = ' AND '): void
    {
        $value = $need_quotes ? "'%s'" : '%s';
        $value_glue = $need_quotes ? "','" : ',';
        $sql_statement = sprintf(
            "INSERT INTO %s (%s) SELECT $value FROM DUAL WHERE NOT EXISTS (SELECT * FROM %s WHERE %s)",
            $table_name,
            implode(', ', array_keys($insert_key_value_list)),
            implode($value_glue, array_values($insert_key_value_list)),
            $table_name,
            implode($logical_operator, array_map(function ($selected_value, $selected_key) {return sprintf("%s = '%s'", $selected_key, $selected_value);}, $select_key_value_list, array_keys($select_key_value_list))),
        );
        $this->execute_sql_statement($sql_statement);
    }

    /** --------------------------- @category 3. UPDATE 관련 --------------------------- */
    protected function update_by_operator(string $table_name, array $conditions, array $update_conditions, string $logical_operator = ' AND '): void
    {
        $sql_statement = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table_name,
            implode(', ', array_values($update_conditions)),
            implode($logical_operator, array_values($conditions))
        );
        $this->execute_sql_statement($sql_statement);
    }

    /** --------------------------- @category 4. DELETE 관련 --------------------------- */
    protected function delete_by_operator(string $table_name, array $conditions, string $logical_operator = ' AND '): void
    {
        $sql_statement = sprintf(
            "DELETE FROM %s WHERE %s",
            $table_name,
            implode($logical_operator, array_values($conditions))
        );
        $this->execute_sql_statement($sql_statement);
    }

    protected function delete_by_updating_date(string $table_name, array $conditions, string $logical_operator = ' AND ')
    {
        $sql_statement = sprintf(
            "UPDATE %s SET delete_date = CURRENT_TIMESTAMP WHERE %s",
            $table_name,
            implode($logical_operator, array_values($conditions))
        );
        $this->execute_sql_statement($sql_statement);
    }

    /** --------------------------- @category 5. 유틸리티 메소드 모음 --------------------------- */
    /** (1) 관계연산자를 사용한 조건을 만들어주는 메소드 : @example ["idx=3", "name = 'yes'"] */
    protected function make_relational_conditions(string $operator, array $key_value_list, bool $need_quotes = true): array
    {
        $conditions = [];
        $keys = array_keys($key_value_list);

        foreach ($keys as $key) {
            $value = $key_value_list[$key];
            $conditions[] = $need_quotes ? "{$key}{$operator}'{$value}'" : "{$key}{$operator}{$value}";
        }

        return $conditions;
    }

    /** (2) IN 조건을 만들어주는 메소드 : @example ['idx */
    protected function make_in_condition(string $column_name, array $value_list): array
    {
        $sql_statement = sprintf(
            "%s IN ('%s')",
            $column_name,
            implode("', '", array_values($value_list))
        );

        return [$sql_statement];
    }

    /**
     * (3) 여러 조건들을 하나의 배열로 만들어주는 메소드
     * @param 여러 조건 배열들
     * @return array
     */
    protected function combine_conditions(...$conditions_list): array
    {
        $combined_conditions = [];

        foreach ($conditions_list as $conditions) {
            foreach ($conditions as $condition) {
                $combined_conditions[] = $condition;
            }
        }

        return $combined_conditions;
    }
}
