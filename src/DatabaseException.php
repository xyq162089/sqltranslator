<?php
namespace sqltranslator;

use sqltranslator\Loader;

class DatabaseException extends \Exception
{
    /**
     * 构造函数
     */
    function __construct()
    {
    	$args = func_get_args();
    	$message = $args[0];

        if (preg_match('/^[a-z_A-Z\d]+$/', $message)) {
            $package = require(Loader::PathName('>\\Exception\\cn')['path']);

            array_key_exists($message, $package) && $message = $package[$message];
        }
        if (count($args) > 1) {
            array_shift($args);
            $message = vsprintf($message, $args);
        }
        echo $message;
        exit;
        parent::__construct($message);
    }

    /**
     * 异常处理句柄
     *
     * @access public
     * @static
     * @param object $e
     * @return null
     */
    public static function Handler($e)
    {
    	exit(self::_Display($e));
    }

    /**
     * 设置发生异常时的处理句柄
     *
     * @access public
     * @static
     * @return null
     */
    public static function Report()
    {
        //抛错误级别
        if (DEBUG) {
            ini_set('display_errors', '1');
            error_reporting(E_ALL^E_NOTICE^E_WARNING);
        } else {
            error_reporting(0);
        }
        set_exception_handler(array(__CLASS__, 'Handler'));
    }

    /**
     * 显示异常调试信息
     *
     * @access public
     * @return null
     */
    public function display($e = null)
    {
        $e || $e = $this;
        exit(self::_Display($e));
    }

    /**
     * 显示异常调试信息
     *
     * @access public
     * @static
     * @param object $e
     * @param string $templatefile
     * @return string
     */
    private static function _Display($message)
    {
    	$trace = $e->getTrace();
        $trace = array_shift($trace);
        $source = '';
        $includes = '';
        if ($file = $trace['file']) {
            $line = $trace['line'];
            $lines = file($file);
            $lines_offset = 8;
            $b_line = max($line - $lines_offset-1, 0);
            $e_line = min($line + $lines_offset, count($lines));
            for ($i = $b_line; $i < $e_line; $i++) {
                $source .= ($i === $line-1)
                    ? '<div class="error">' . htmlspecialchars(sprintf("%04d：%s", $i+1, str_replace("\t", "    ", $lines[$i]))) . '</div>'
                    : htmlspecialchars(sprintf("%04d：%s", $i+1, str_replace("\t", "    ", $lines[$i])));
            }
        }
        foreach (get_included_files() as $k=> $v) {
            $includes .= sprintf("#%02d：%s\r\n", $k+1, str_replace("\t", "    ", $v));
        }
        $trace_string = $e->getTraceAsString ();
        $message = $e->getMessage();
        $tpl = BLoader::PathName('../View/exception.tpl');
        $footerinfo = strftime('%Y-%m-%d %H:%M:%S', time()) . ' ' . $_SERVER['SERVER_SOFTWARE']. ' '. B::NAME. '/'. B::VERSION;
        $debug_content = $no_debug_content = '';
        if (file_exists($tpl) && $logcontent = $tplcontent = file_get_contents($tpl)) {
            //debug时显示内容
            $debug_content = str_replace(array('<{detail}>', '<{/detail}>'), '', $tplcontent);
            $debug_content = str_replace('<{$e}>', get_class($e), $debug_content);
            $debug_content = str_replace('<{$description}>', $message, $debug_content);
            $debug_content = str_replace('<{$sourcefile}>', $file, $debug_content);
            $debug_content = str_replace('<{$line}>', $line, $debug_content);
            $debug_content = str_replace('<{$source}>', $source, $debug_content);
            $debug_content = str_replace('<{$stacktrace}>', $trace_string, $debug_content);
            $debug_content = str_replace('<{$includefiles}>', $includes, $debug_content);
            $debug_content = str_replace('<{$footerinfo}>', $footerinfo, $debug_content);
            //no-debug时显示
            $no_debug_content = preg_replace('/<{detail}>[\s\S]+<{\/detail}>/', '', $tplcontent);
            $no_debug_content = str_replace('<{$e}>', get_class($e), $no_debug_content);
            $no_debug_content = str_replace('<{$description}>', $e->getMessage(), $no_debug_content);
            $no_debug_content = str_replace('<{$footerinfo}>', $footerinfo, $no_debug_content);
            //写日志

        }
        return $debug_content;
    }

}


?>
