<?php
namespace SqlTranslator\Plugin;

use SqlTranslator\SqlTranslator;

class SelectOracle extends SqlTranslator
{

    const FLAG_SELECT = 'select';

    const FLAG_FROM = 'from';

    const FLAG_JOIN = 'join';

    const FLAG_JOIN_LEFT = 'joinLeft';

    const FLAG_JOIN_INNER = 'joinInner';

    const FLAG_JOIN_RIGHT = 'joinRight';

    const FLAG_JOIN_FULL = 'joinFull';

    const FLAG_WHERE = 'where';

    const FLAG_AND = 'and';

    const FLAG_OR = 'or';

    const FLAG_ON = 'on';

    const FLAG_AS = 'as';

    const FLAG_GROUP = 'group';

    const FLAG_HAVING = 'having';

    const FLAG_ORDER = 'order';

    const FLAG_ASC = 'asc';

    const FLAG_DESC = 'desc';

    const FLAG_LIMIT = 'limit';

    const FLAG_OFFSET = 'offset';

    const FLAG_LOCK = 'forupdate';

    /**
     * 语句组成部分初始值
     *
     * @access private
     * @var array
     */
    private $_parts = [];

    /**
     * sql语句关健字
     *
     * @access private
     * @var array
     */
    private $_keys = [
        self::FLAG_SELECT => 'SELECT',
        self::FLAG_FROM => 'FROM',
        self::FLAG_JOIN_LEFT => 'LEFT JOIN',
        self::FLAG_JOIN_INNER => 'INNER JOIN',
        self::FLAG_JOIN_RIGHT => 'RIGHT JOIN',
        self::FLAG_JOIN_FULL => 'FULL JOIN',
        self::FLAG_WHERE => 'WHERE',
        self::FLAG_AND => 'AND',
        self::FLAG_OR => 'OR',
        self::FLAG_ON => 'ON',
        self::FLAG_AS => 'AS',
        self::FLAG_ORDER => 'ORDER BY',
        self::FLAG_ASC => 'ASC',
        self::FLAG_DESC => 'DESC',
        self::FLAG_GROUP => 'GROUP BY',
        self::FLAG_HAVING => 'HAVING',
        self::FLAG_LIMIT => 'LIMIT',
        self::FLAG_OFFSET => 'OFFSET',
        self::FLAG_LOCK => 'FOR UPDATE'
    ];

    /**
     * sql语句
     *
     * @access private
     * @var string
     */
    private $_sql = '';

    /**
     * 初始化各SQL组成部分
     *
     * @access private
     * @return object
     */
    private function _init()
    {
        $this->_parts = [
            self::FLAG_FROM => [],
            self::FLAG_JOIN => [],
            self::FLAG_WHERE => [],
            self::FLAG_ORDER => [],
            self::FLAG_GROUP => [],
            self::FLAG_HAVING => [],
            self::FLAG_LIMIT => null,
            self::FLAG_OFFSET => null
        ];

        return $this;
    }

    /**
     * 构造器
     *
     * @access public
     * @return null
     */
    function __construct()
    {
        $this->_init();
    }

    /**
     * 要查询的表
     *
     * @access public
     * @param array $table
     * @param array $columns
     * @return object
     */
    function from($table, $columns = ['*'])
    {
        $this->_parts[self::FLAG_FROM][] = [$table, (array)$columns];

        return $this;
    }

    /**
     * 查询条件
     *
     * @access public
     * @param string $cond
     * @param mixed $value
     * @return object
     */
    function where($cond, $value = null, $and = true)
    {
        $_part                            = is_null($value) ? $cond : $this->quoteInto($cond, $value);
        $this->_parts[self::FLAG_WHERE][] = [$_part, $and];

        return $this;
    }

    /**
     * 分组方法
     *
     * @access public
     * @param mixed $column
     * @return object
     */
    function group($column)
    {
        if (is_array($column)) {
            foreach ($column as $v) {
                $this->_parts[self::FLAG_GROUP][] = $v;
            }
        } else {
            $this->_parts[self::FLAG_GROUP][] = $column;
        }

        return $this;
    }

    /**
     * 分组条件
     *
     * @access public
     * @param mixed $column
     * @return object
     */
    function having($cond, $value = null, $and = true)
    {
        $_part                             = is_null($value) ? $cond : $this->quoteInto($cond, $value);
        $this->_parts[self::FLAG_HAVING][] = [$_part, $and];

        return $this;
    }

    /**
     * 限制条件
     *
     * @access public
     * @param int $count
     * @param int $offset
     * @return object
     */
    function limit($count = 0, $offset = 0)
    {
        $this->_parts[self::FLAG_LIMIT]  = !$count ?: $count;
        $this->_parts[self::FLAG_OFFSET] = !$offset ?: $offset;

        return $this;
    }

    /**
     * 排序条件
     *
     * @access public
     * @param string $col
     * @param mixed $value
     * @return object
     */
    function order($col, $asc = false)
    {
        $this->_parts[self::FLAG_ORDER][] = [$col, $asc];

        return $this;
    }

    /**
     * 左联接
     *
     * @access public
     * @param array $table
     * @param string $cond
     * @param array $fetch
     * @return object
     */
    function joinLeft($table, $cond, $fetch = ['*'])
    {
        return $this->_join($table, $cond, $fetch, self::FLAG_JOIN_LEFT);
    }

    /**
     * 内联接
     *
     * @access public
     * @param array $table
     * @param string $cond
     * @param array $fetch
     * @return object
     */
    function joinInner($table, $cond, $fetch = ['*'])
    {
        return $this->_join($table, $cond, $fetch, self::FLAG_JOIN_INNER);
    }

    /**
     * 右联接
     *
     * @access public
     * @param array $table
     * @param string $cond
     * @param array $fetch
     * @return object
     */
    function joinRight($table, $cond, $fetch = ['*'])
    {
        return $this->_join($table, $cond, $fetch, self::FLAG_JOIN_RIGHT);
    }

    /**
     * 行锁
     * @return object
     */
    function lock()
    {
        $this->_parts[self::FLAG_LOCK][] = true;

        return $this;
    }

    /**
     * 添加联接
     *
     * @access private
     * @param array $table 表名
     * @param string $cond 联接条件
     * @param array $fetch 当前表取值字段
     * @param string $jointype 联接类型
     * @return object
     */
    private function _join($table, $cond, $fetch, $jointype)
    {
        $this->_parts[self::FLAG_JOIN][] = [$jointype, $table, $cond, $fetch];

        return $this;
    }

    /**
     * 拼凑sql语句
     *
     * @access private
     * @return string
     */
    private function _toString()
    {

        $this->_sql = $_select_string = $_from_string = $_join_string = $_where_string = $_order_string = $_limit_string = $_group_string = $_having_string = '';

        if ($_from = $this->_parts[self::FLAG_FROM]) {
            $_tmp_cols = '';
            $_tmp_tabs = '';
            foreach ($_from as $key => $val) {
                if (is_array($val) && 2 == count($val)) {
                    $_other_name = '';
                    if (is_array($val[0])) {
                        $k = key($val[0]);
                        $v = current($val[0]);
                    } else {
                        $k = $v = $val[0];
                    }
                    if ($_other_name = $this->wrap($k)) {
                        $_tmp_tabs .= $this->wrap($v) . ' ' . $_other_name . ', ';
                    } else {
                        $_tmp_tabs .= $this->wrap($v) . ', ';
                    }
                    foreach ($val[1] as $k => $v) {
                        if (!is_integer($k)) {
                            $hasSet = false;
                            if ($_other_name) {
                                if (strpos($v, 'DISTINCT') === 0) {
                                    $hasSet     = true;
                                    $colName    = trim(substr($v, strlen('DISTINCT')));
                                    $val[1][$k] = 'DISTINCT ' . $_other_name . '.' . $this->wrap(
                                            $colName
                                        ) . ' ' . $this->_keys[self::FLAG_AS] . ' ' . $this->wrap($k);
                                    //非函数 以及数据库函数
                                } elseif (!preg_match('/\w+\(.*?\)/', $v) && !is_object($v)) {
                                    $hasSet     = true;
                                    $val[1][$k] = $_other_name . '.' . $this->wrap(
                                            $v
                                        ) . ' ' . $this->_keys[self::FLAG_AS] . ' ' . $this->wrap($k);
                                }
                            }

                            if (!$hasSet) {
                                $val[1][$k] = $this->wrap($v) . ' ' . $this->_keys[self::FLAG_AS] . ' ' . $this->wrap(
                                        $k
                                    );;
                            }
                        } else {
                            if ($_other_name) {
                                if (strpos($v, 'DISTINCT') === 0) {
                                    $colName    = trim(substr($v, strlen('DISTINCT')));
                                    $val[1][$k] = 'DISTINCT ' . $_other_name . '.' . $this->wrap($colName);
                                } elseif (!preg_match('/\w+\(.*?\)/', $v) && !is_object($v)) {
                                    $val[1][$k] = $_other_name . '.' . $this->wrap($v);
                                }
                            }
                        }
                    }
                    $_tmp_cols .= implode(', ', $val[1]) . ', ';
                }
            }
            $_select_string .= $_tmp_cols;
            $_from_string .= $_tmp_tabs;
        }

        if ($_join = $this->_parts[self::FLAG_JOIN]) {
            $_tmp_join = '';
            foreach ($_join as $key => $val) {
                $_flag    = $val[0];
                $_tables  = $val[1];
                $_cond    = $val[2];
                $_columns = $val[3];
                if (in_array(
                    $_flag, [self::FLAG_JOIN_LEFT, self::FLAG_JOIN_INNER, self::FLAG_JOIN_RIGHT, self::FLAG_JOIN_FULL]
                )) {
                    $_tmp_tabs   = '';
                    $_tmp_cols   = '';
                    $_other_name = '';
                    if (is_array($_tables)) {
                        $k = key($_tables);
                        $v = current($_tables);
                    } else {
                        $k = $v = $_tables;
                    }
                    if ($_other_name = $this->wrap($k)) {
                        $_tmp_tabs .= $this->wrap($v) . ' ' . $_other_name . ', ';
                    } else {
                        $_tmp_tabs .= $this->wrap($v) . ', ';
                    }

                    $_tmp_tabs = trim($_tmp_tabs, ', ');
                    $_tmp_join .= $this->_keys[$_flag] . ' ' . $_tmp_tabs . ' ' . $this->_keys[self::FLAG_ON] . ' ' . $_cond . ' ';
                    foreach ($_columns as $k => $v) {
                        if (!preg_match('/.*?\(.*?\)/', $v) && !is_object($v)) {
                            $_other_name && $_columns[$k] = $_other_name . '.' . $this->wrap($v);
                        }
                        if (!is_integer($k)) {
                            $_columns[$k] = $_columns[$k] . ' ' . $this->_keys[self::FLAG_AS] . ' ' . $this->wrap($k);
                        }
                    }
                    $_tmp_cols = implode(', ', $_columns) . ', ';
                    if (trim($_tmp_cols, ', ')) {
                        $_select_string .= $_tmp_cols;
                    }
                }
            }
            $_join_string = $_tmp_join;
        }

        if ($_where = $this->_parts[self::FLAG_WHERE]) {
            $_tmp_where = '';
            foreach ($_where as $key => $val) {
                $_tmp_where .= $val[0] . ' ' . ($val[1] ? $this->_keys[self::FLAG_AND] : $this->_keys[self::FLAG_OR]) . ' ';
            }
            $_where_string .= $_tmp_where;
        }

        if ($_group = $this->_parts[self::FLAG_GROUP]) {
            $_tmp_group = '';
            foreach ($_group as $key => $val) {
                if ($_tmp_group == '') {
                    $_tmp_group .= $val;
                } else {
                    $_tmp_group .= ', ' . $val;
                }
            }
            $_group_string .= $_tmp_group;
        }

        if ($_having = $this->_parts[self::FLAG_HAVING]) {
            $_tmp_having = '';
            foreach ($_having as $key => $val) {
                $_tmp_having .= $val[0] . ' ' . ($val[1] ? $this->_keys[self::FLAG_AND] : $this->_keys[self::FLAG_OR]) . ' ';
            }
            $_having_string .= $_tmp_having;
        }

        if ($_order = $this->_parts[self::FLAG_ORDER]) {
            $_tmp_order = '';
            foreach ($_order as $key => $val) {
                if (strpos(strtolower($val[0]), ' asc') !== false || strpos(strtolower($val[0]), ' desc') !== false) {
                    $_tmp_order .= $val[0] . ', ';
                } else {
                    $_tmp_order .= $val[0] . ' ' . ($val[1] == 'none' ? '' : ($val[1] ? $this->_keys[self::FLAG_ASC] : $this->_keys[self::FLAG_DESC])) . ', ';
                }
            }
            $_order_string = $_tmp_order;
        }

        if ($_limit = $this->_parts[self::FLAG_LIMIT]) {

            $_limit_string = $_where_string ? ' rownum <' . $_limit : ' ' . $this->_keys[self::FLAG_WHERE] . ' rownum <' . $_limit;
        }

        if ($this->_parts[self::FLAG_LOCK]) {
            $_lock_string = ' ' . $this->_keys[self::FLAG_LOCK];
        }

        $_select_string = $_select_string ? $this->_keys[self::FLAG_SELECT] . ' ' . trim($_select_string, ', ') : '';
        $_from_string   = $_from_string ? ' ' . $this->_keys[self::FLAG_FROM] . ' ' . trim($_from_string, ', ') : '';
        $_join_string   = $_join_string ? ' ' . trim($_join_string) : '';
        $_where_string  = $_where_string ? ' ' . $this->_keys[self::FLAG_WHERE] . ' ' . rtrim(
                $_where_string, $this->_keys[self::FLAG_AND] . $this->_keys[self::FLAG_OR] . ' '
            ) : '';

        $_group_string  = $_group_string ? ' ' . $this->_keys[self::FLAG_GROUP] . ' ' . trim($_group_string, ', ') : '';
        $_having_string = $_having_string ? ' ' . $this->_keys[self::FLAG_HAVING] . ' ' . rtrim(
                $_having_string, $this->_keys[self::FLAG_AND] . $this->_keys[self::FLAG_OR] . ' '
            ) : '';
        $_order_string  = $_order_string ? ' ' . $this->_keys[self::FLAG_ORDER] . ' ' . trim($_order_string, ', ') : '';
        //$_limit_string = $_where_string ? $_limit_string : ' WHERE ' . $_limit_string;

        if ($_select_string) {
            $this->_sql .= $_select_string . $_from_string . $_join_string . $_where_string . $_group_string . $_having_string . $_order_string . $_limit_string . $_lock_string;
        }

        return $this->_init();

    }

    /**
     * 字符串化
     *
     * @access public
     * @return string
     */
    function __toString()
    {
        return $this->_toString()->_sql;
    }

}

?>
