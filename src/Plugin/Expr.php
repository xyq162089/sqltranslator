<?php
namespace SqlTranslator\Plugin;

use SqlTranslator\SqlTranslator;

class Expr extends SqlTranslator
{

    /**
     * 表达式变量
     *
     * @access private
     * @var string
     */
    private $_expr = '';

    /**
     * 设置表达式
     *
     * @access public
     * @param string $expr
     * @return object
     */
    function set($expr)
    {
        $this->_expr = $expr;

        return clone $this;
    }

    /**
     * 通过魔术方法返回表达式
     *
     * @access public
     * @return string
     */
    function __toString()
    {
        return $this->_expr;
    }

}

?>
