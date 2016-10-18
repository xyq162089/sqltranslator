<?php
namespace SqlTranslator;

class Timer
{


    /**
     * 时间标记点
     *
     * @access private
     * @staticvar
     *
     * @var array
     */
    private static $_marks = [];


    /**
     * 标记起始时间
     *
     * @access public
     * @static
     *
     * @return null
     */
    public static function Mark($flag = null)
    {
        if (is_null($flag)) {
            return self::$_marks;
        }
        self::$_marks[(string)$flag] = self::Time();
    }

    /**
     * 获取最近两次标记的时间值
     *
     * @access public
     * @static
     *
     * @return float
     */
    public static function Last()
    {
        $copy  = self::$_marks;
        $newer = array_pop($copy);
        $older = array_pop($copy);
        $copy  = null;

        return number_format($newer - $older, 4, '.', '');
    }

    /**
     * 获取当前点到最初点的时间值
     *
     * @access public
     * @return float
     */
    public static function Total()
    {
        $copy   = self::$_marks;
        $newest = array_pop($copy);
        $oldest = array_shift($copy);
        $copy   = null;

        return number_format($newest - $oldest, 4, '.', '');
    }

    /**
     * 选取时间点
     *
     * @access public
     * @static
     *
     * @param int $index
     * @return mixed
     */
    public static function Pick($start, $end)
    {
        if (is_null($start) || is_null($end) || !isset(self::$_marks[$start]) || !isset(self::$_marks[$end])) {
            return false;
        }
        $newer = self::$_marks[$end];
        $older = self::$_marks[$start];

        return number_format($newer - $older, 4, '.', '');;
    }

    /**
     * 时区
     *
     * @param string $timezone
     * @return b
     */
    public static function setTimezone($timezone = 'Asia/Shanghai')
    {
        return date_default_timezone_set($timezone);
    }

    /**
     * 时间记录点
     *
     * @param string $start
     * @param string $end
     * @param int $dec
     * @return f
     */
    public function setTime($start, $end = '', $dec = 3)
    {
        static $_info = [];
        if (!empty($end)) { // 统计时间
            if (!isset($_info[$end])) {
                $_info[$end] = $this->Time(true);
            }

            return number_format(($_info[$end] - $_info[$start]), $dec);
        } else { // 记录时间
            $_info[$start] = $this->Time(true);
        }
    }

    /**
     * 获取当前时间
     *
     * @access public
     * @static
     * @return int
     */
    public function Time($convert = true)
    {
        return microtime($convert);
    }

}
