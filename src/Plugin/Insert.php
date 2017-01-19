<?php
namespace SqlTranslator\Plugin;

use SqlTranslator\SqlTranslator;

class Insert extends SqlTranslator
{

    const FLAG_INSERT = 'insert';

    const FLAG_VALUES = 'values';

    const FLAG_DUPLICATE = 'ON DUPLICATE KEY UPDATE';

    /**
     * 语句组成部分初始值
     *
     * @access private
     * @var array
     */
    private $_parts = [];

    /**
     * sql关健字
     *
     * @access private
     * @var array
     */
    private $_keys = [
        self::FLAG_INSERT => 'INSERT INTO',
        self::FLAG_VALUES => 'VALUES',
        self::FLAG_DUPLICATE => 'DUPLICATE',
    ];

    /**
     * 插入到的表
     *
     * @access private
     * @var string
     */
    private $_table = '';

    /**
     * sql语句
     *
     * @access private
     * @var string
     */
    private $_sql = '';

    /**
     * 初始化
     *
     * @access private
     * @return object
     */
    private function _init()
    {
        $this->_parts = [
            self::FLAG_INSERT => [],
            self::FLAG_VALUES => [],
            self::FLAG_DUPLICATE => []
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
     * 要插入数据的表
     *
     * @access public
     * @param mixed $table 要插入的表, '字符串或数组'
     * @param array $columns 要插入到哪些字段
     * @return object
     */
    function into($table, $columns)
    {
        $this->_table = is_array($table) ? current($table) : $table;
        $this->_parts[self::FLAG_INSERT] = (array)$columns;

        return $this;
    }

    /**
     * 要插入的数据
     *
     * @access public
     * @param array $values 内容
     * @return object
     */
    function values(array $values)
    {
        $this->_parts[self::FLAG_VALUES][] = (array)$values;

        return $this;
    }

    /**
     * 插入失败时更新
     *
     * @access public
     * @param array $values 要修改的数据
     * @return object
     */
    function duplicate(array $values)
    {
        $this->_parts[self::FLAG_DUPLICATE] = $values;

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

        $_insert_value = $_insert_fields = $_insert_duplicate = [];
        $this->_sql    = '';
        if ($this->_parts[self::FLAG_INSERT]) {
            foreach ($this->_parts[self::FLAG_INSERT] as $k => &$v) {
                $v = $this->wrap($v);
            }
            unset($k, $v);
            $_insert_fields = implode(', ', $this->_parts[self::FLAG_INSERT]);
        }

        if ($_values = $this->_parts[self::FLAG_VALUES]) {
            foreach ($_values as $key => $val) {
                $_tmp_values = array_values($val);
                foreach ($_tmp_values as $k => $v) {
                    $_tmp_values[$k] = $this->quote($v);
                }
                $_insert_value[] = implode(', ', $_tmp_values);
            }
        }

        if ($_duplicate = $this->_parts[self::FLAG_DUPLICATE]) {
            foreach ($_duplicate as $k => $v) {
                $_insert_duplicate[] = $k .'='. (gettype ($v) === 'integer' ? $k . ($v > 0 ? '+' : '-') . abs($v) : '\'' . $v . '\'');
            }
        }


        if ($this->_table && $_insert_fields && $_insert_value) {
            $this->_sql .= $this->_keys[self::FLAG_INSERT] . ' ' . $this->wrap(
                    $this->_table
                ) . '(' . $_insert_fields . ') ';
            if (count($_insert_value) == 1) {
                $this->_sql .= self::FLAG_VALUES . ' ( ' . $_insert_value[0] . ' ) ';
            } else {
                foreach ($_insert_value as $k => $v) {
                    $_insert_value[$k] = 'select ' . $v . ' from dual ';
                }
                $this->_sql .= implode(' union ', $_insert_value);
            }
            if ($_insert_duplicate) {
                $this->_sql .= ' '.self::FLAG_DUPLICATE . ' ' . implode(', ', $_insert_duplicate);
            }
        }

        return $this->_init();

    }

    /**
     * 字符串输出
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
