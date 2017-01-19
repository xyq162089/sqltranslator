<?php

namespace SqlTranslator;

use SqlTranslator\Loader;

class SqlTranslator extends DatabaseAbstract
{
    /**
     * 取得SQL取值的偏移量
     *
     * @access public
     * @param integer $pager 每页显示数据量
     * @param integer $page 当前页数
     * @param integer $count 总数据量
     * @param boolean $lockpage 锁定最大页数
     * @return integer
     */
    public function offset($pager, $page, $count = 0, $lockpage = true)
    {
        if ($count) {
            $maxpage = ceil($count / $pager);
            $lockpage && $page > $maxpage && $page = $maxpage;
        }

        return $pager * ($page > 0 ? $page - 1 : 0);
    }

    /**
     * 格式化ID参数值
     *
     * @access public
     * @param string $col
     * @param mixed $value
     * @param boolean $intval 是否强制转换为整型值
     * @param boolean $filter 是否过滤数组
     * @param boolean $and 是否反取
     * @return object
     */
    public function quoteId($col, $value, $intval = true, $filter = false, $and = true)
    {
        if (is_array($value)) {
            $value = array_map($intval ? 'intval' : 'trim', $value);
            $filter && $value = array_filter($value);
            count($value) == 1 && $value = current($value);
        }
        if (is_scalar($value) && $intval) {
            $value = intval($value);
        }
        $expr = $col . (is_array($value) ? (($and ? '' : ' NOT') . ' IN(?)') : (($and ? '' : '!') . '=?'));
        if ($value === 'NULL') {
            $expr = str_replace(['!=', '='], [' IS NOT ', ' IS '], $expr);
        }

        return str_replace('?', $this->quote($value), $expr);

    }

    /**
     * 为变量添加引号
     *
     * @access public
     * @param mixed $value
     * @return mixed
     */
    public function quote($value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $val) {
                $value[$key] = $this->quote($val);
            }

            return implode(',', $value);
        } elseif (is_int($value) || is_float($value) || is_object($value) || $value == 'NULL') {
            return $value;
        } else {
            return "'" . addslashes($value) . "'";
        }
    }

    public function __get($method)
    {
        $instance = '';
        switch ($method) {
            case 'insert' :
            case 'update' :
            case 'select' :
            case 'select_oracle' :
            case 'delete' :
            case 'expr' :
                $instance = Loader::Instance('>\\SqlTranslator\\Plugin\\' . ucfirst($method));
                break;
        }

        return $instance;
    }

}

abstract class DatabaseAbstract
{

    /**
     * 为变量添加引号
     *
     * @access public
     * @param mixed $value
     * @return mixed
     */
    protected function quote($value)
    {
        return B::i()->plugin->database->quote($value);
    }

    /**
     * 格式化表达式
     * @param $col
     * @param $value
     * @return mixed
     */
    protected function quoteInto($col, $value)
    {
        return str_replace('?', $this->quote($value), $col);
    }

    /**
     * 格式化数据库字段名
     *
     * @access protected
     * @param string $col
     * @return string
     */
    protected function wrap($columns)
    {
        if ($columns == '*') {
            return $columns;
        }
        preg_match('/(^#)(.+)/s', $columns, $match);
        if (array_key_exists(1, $match) && $match[1]) {
            return $match[2];
        }

        return '`' . $columns . '`';
    }

}

?>
