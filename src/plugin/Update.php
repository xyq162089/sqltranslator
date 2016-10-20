<?php
namespace sqltranslator\plugin;

use sqltranslator\SqlTranslator;

class Update extends SqlTranslator
{

    const FLAG_UPDATE = 'update';

    const FLAG_SET = 'set';

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
        self::FLAG_UPDATE => 'UPDATE',
        self::FLAG_SET => 'SET',
        self::FLAG_WHERE => 'WHERE',
        self::FLAG_AND => 'AND',
        self::FLAG_OR => 'OR',
        self::FLAG_AS => ' ',
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
            self::FLAG_UPDATE => [],
            self::FLAG_SET => [],
            self::FLAG_WHERE => [],
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
     * 要更新的表及更新值
     *
     * @access public
     * @param mixed $table 要更新的表
     * @param array $updates 要更新的字段及其值
     * @return object
     */
    function set($table, $updates = [])
    {
        $this->_parts[self::FLAG_UPDATE][] = (array)$table;
        $this->_parts[self::FLAG_SET][]    = (array)$updates;

        return $this;
    }

    /**
     * 更新条件
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
    private function _toString()
    {

        $this->_sql = $_table_string = $_set_string = $_where_string = '';

        if (($_update = $this->_parts[self::FLAG_UPDATE]) && ($_set = $this->_parts[self::FLAG_SET])) {
            $_tmp_tabs = '';
            $_tmp_sets = '';
            foreach ($_update as $key => $val) {
                if (is_array($val)) {
                    $_other_name = '';
                    list($k, $v) = each($val);
                    if (!is_integer($k)) {
                        $_tmp_tabs .= $this->wrap($v) . ' ' . $this->_keys[self::FLAG_AS] . ' ' . $this->wrap(
                                $k
                            ) . ', ';
                        $_other_name = $this->wrap($k);
                    } else {
                        $_tmp_tabs .= $this->wrap($v) . ', ';
                    }
                    if (isset($_set[$key])) {
                        $_tmp_update = [];
                        foreach ($_set[$key] as $k => $v) {
                            if ($_other_name) {
                                $_tmp_update[] = $_other_name . '.' . $this->wrap($k) . '=' . (is_object(
                                        $v
                                    ) ? $v : $this->quote($v));
                            } else {
                                $_tmp_update[] = $this->wrap($k) . '=' . (is_object($v) ? $v : $this->quote($v));
                            }
                        }
                        $_tmp_update = implode(', ', $_tmp_update);
                        $_tmp_sets .= $_tmp_update . ', ';
                    }
                }
            }
            $_table_string .= $_tmp_tabs;
            $_set_string .= $_tmp_sets;
        }

        if ($_where = $this->_parts[self::FLAG_WHERE]) {
            $_tmp_where = '';
            foreach ($_where as $key => $val) {
                $_tmp_where .= $val[0] . ' ' . ($val[1] ? $this->_keys[self::FLAG_AND] : $this->_keys[self::FLAG_OR]) . ' ';
            }
            $_where_string .= $_tmp_where;
        } else {
            if (!$this->_mandatory) {
                $this->_sql = 'Non-mandatory mode, the update must be conditional';

                return $this->_init();
                exit;
            }
        }

        $_table_string = trim($_table_string, ', ');
        $_set_string   = trim($_set_string, ', ');
        $_where_string = rtrim($_where_string, $this->_keys[self::FLAG_AND] . $this->_keys[self::FLAG_OR] . ' ');

        if ($_table_string) {
            $this->_sql .= $this->_keys[self::FLAG_UPDATE] . ' ' . $_table_string;
            if ($_set_string) {
                $this->_sql .= ' ' . $this->_keys[self::FLAG_SET] . ' ' . $_set_string;
                if ($_where_string) {
                    $this->_sql .= ' ' . $this->_keys[self::FLAG_WHERE] . ' ' . $_where_string;
                }
            } else {
                $this->_sql = '';
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
