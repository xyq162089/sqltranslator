<?php
namespace SqlTranslator;

use SqlTranslator\Timer;
use SqlTranslator\Loader;

class Trace
{

    // 日志记录方式
    const SYSTEM = 0;
    const MAIL   = 1;
    const TCP    = 2;
    const FILE   = 3;

    /**
     * 初始化内存使用
     *
     * @var int
     */
    private $_startUseMems = 0;

    /**
     * 记录信息
     *
     * @var array
     */
    public static $log = [];

    /**
     * 日志文件大小 100M
     */
    private $_logFileSize = 104857600;

    // 日期格式
    private static $format = '[ Y-m-d H:i:s ]';


    /**
     * 跟踪信息
     *
     * @access private
     * @var array
     */
    private static $_trace = [];

    /**
     * 设置跟踪信息
     *
     * @access public
     * @param string $flag
     * @param mixed $data
     * @return bool
     */
    public static function Set($flag, $data)
    {
        self::$_trace[$flag][] = $data;

        return true;
    }

    /**
     * 获取跟踪信息
     *
     * @access public
     * @param string $flag
     * @return mixed
     */
    public static function Get($flag = null)
    {
        return is_null($flag) ? self::$_trace : self::$_trace[$flag];
    }

    public function init()
    {
        $this->_startUseMems = memory_get_usage();
    }

    /**
     * 时间记录点
     *
     * @param string $start
     * @param string $end
     * @return f
     */
    public function time($start, $end = '')
    {
        return (new Timer())->setTime($start, $end);
    }

    // 设置和获取统计数据
    public function N($key, $step = 0)
    {
        static $_num = [];
        if (!isset($_num[$key])) {
            $_num[$key] = 0;
        }
        if (empty($step)) {
            return $_num[$key];
        } else {
            $_num[$key] = $_num[$key] + (float)$step;
        }
    }

    // 显示内存开销
    public function getMemory()
    {
        $startMem = $this->_startUseMems;
        $endMem   = array_sum(explode(' ', memory_get_usage()));

        return number_format(($endMem - $startMem) / 1024);
    }

    // 写入记录
    public function record($message, $type = 'log')
    {
        $now         = date(self::$format);
        self::$log[] = $_SERVER['REQUEST_URI'] . "-{$now}: {$message}\r\n";
    }

    public function save($type = self::FILE, $destination = '', $data = '')
    {
        if (empty($destination)) {
            $destination = Loader::PathName('>/log/') . date('y_m_d') . ".log";
        }
        if (self::FILE == $type) { // 文件方式记录日志信息
            //检测日志文件大小，超过配置大小则备份日志文件重新生成
            if (is_file($destination) && floor($this->_logFileSize) <= filesize($destination)) {
                @rename($destination, dirname($destination) . '/' . time() . '-' . basename($destination));
            }
            //if ($this->time('systemStartTime', 'systemEndTime', 5) > 5)
            $data = $data ? $data : implode(PHP_EOL, self::$log);
            @file_put_contents($destination, $data, FILE_APPEND);
        }

        return true;
    }

    public function clearLog()
    {
        self::$log = [];
    }

    public function getLog()
    {
        return self::$log;
    }


}
