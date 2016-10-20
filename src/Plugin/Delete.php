<?php
namespace SqlTranslator\Plugin;

use SqlTranslator\SqlTranslator;

class Delete extends SqlTranslator
{

    const FLAG_DELETE = 'delete';

    const FLAG_FROM = 'from';

    const FLAG_WHERE = 'where';

    const FLAG_AND = 'and';

    const FLAG_OR = 'or';

    const FLAG_AS = 'as';

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
        self::FLAG_DELETE => 'DELETE',
        self::FLAG_FROM => 'FROM',
        self::FLAG_WHERE => 'WHERE',
        self::FLAG_AND => 'AND',
        self::FLAG_OR => 'OR',
        self::FLAG_AS => 'AS',
    ];

    /**
     * sql语句
     *
     * @access private
     * @var string
     */
    private $_sql = '';

    /**
     * 强制模式
     * @var bool
     */
    private $_mandatory = false;

    /**
     * 初始化
     *
     * @access private
     * @return object
     */
    private function _init()
    {
        $this->_parts = [
            self::FLAG_FROM => [],
            self::FLAG_WHERE => []
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
     * 要删除的表
     *
     * @access public
     * @param array $table
     * @param array $columns
     * @return object
     */
    function from($table)
    {
        $this->_parts[self::FLAG_FROM][] = (array)$table;

        return $this;
    }

    /**
     * 删除条件
     *
     * @access public
     * @param string $cond
     * @param mixed $value
     * @return object
     */
    function where($cond, $value = null, $and = true)
    {
        $_part = is_null($value) ? $cond : $this->quoteInto($cond, $value);
        $this->_parts[self::FLAG_WHERE][] = [$_part, $and];

        return $this;
    }

    function mandatory($type = false)
    {
        $this->_mandatory = $type;

        return $this;
    }

    /**
     * 拼凑sql语句
     *
     * @access private
     * @return string
     */
    function _toString()
    {

        $this->_sql = $_tmp_tabs = $_from_string = $_where_string = '';

        if ($_from = $this->_parts[self::FLAG_FROM]) {
            foreach ($_from as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $v) {
                        $_tmp_tabs .= $this->wrap($v) . ', ';
                        break;
                    }
                }
            }
            $_from_string .= $_tmp_tabs;
        }

        if ($_where = $this->_parts[self::FLAG_WHERE]) {
            $_tmp_where = '';
            foreach ($_where as $key => $val) {
                $_tmp_where .= $val[0] . ' ' . ($val[1] ? $this->_keys[self::FLAG_AND] : $this->_keys[self::FLAG_OR]) . ' ';
            }
            $_where_string .= $_tmp_where;
        } else {
            if (!$this->_mandatory) {
                $this->_sql = 'Non-mandatory mode, the delete must be conditional';

                return $this->_init();
                exit;
            }
        }

        if ($_from_string) {
            $this->_sql .= $this->_keys[self::FLAG_DELETE] . ' ' . $this->_keys[self::FLAG_FROM] . ' ' . trim(
                    $_from_string, ', '
                );
            if ($_where_string) {
                $_where_string = rtrim(
                    $_where_string, $this->_keys[self::FLAG_AND] . $this->_keys[self::FLAG_OR] . ' '
                );
                $this->_sql .= ' ' . $this->_keys[self::FLAG_WHERE] . ' ' . $_where_string;
            }
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
