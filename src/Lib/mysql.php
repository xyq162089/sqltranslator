<?php
namespace SqlTranslator\Lib;

use SqlTranslator\Database;
use SqlTranslator\DIDatabase;
use SqlTranslator\Loader;
use SqlTranslator\Timer;
use SqlTranslator\Trace;
use SqlTranslator\DatabaseException;

class Mysql extends Database
{
    /**
     * 连接数据库
     * @param string $config
     * @return Mysql_instance
     * @throws DatabaseException
     */
    function connect($config)
    {
        parent::AnalyseConnect($config);
        if (function_exists('mysql_connect')) {
            $instance = new Mysql_instance($this->_host, $this->_port, $this->_user, $this->_pass, $this->_name);
            $instance = $instance->encoding($this->_encoding)
                                 ->setNames();
            return $instance;
        }
        throw new DatabaseException('None');
    }

}

class Mysql_instance implements \SqlTranslator\DIDatabase
{

    /**
     * 缓存代理
     *
     * @access public
     * @staticvar
     * @var object
     */
    static $_cache_proxy = null;

    /**
     * 连接对象实例
     *
     * @access private
     * @var object
     */
    private $_instance = null;

    /**
     * 数据库取值模式
     *
     * @access private
     * @var string
     */
    private $_fetchMode = MYSQL_ASSOC;

    /**
     * 有效取值模式
     *
     * @access private
     * @var array
     */
    private $_fetch_modes = [
        Database::FETCH_BOTH => MYSQL_BOTH,
        Database::FETCH_ASSOC => MYSQL_ASSOC,
        Database::FETCH_NUM => MYSQL_NUM,
    ];

    /**
     * 最近一次执行SQL语句所影响的行数
     *
     * @access private
     * @staticvar
     * @var integer
     */
    private static $_rowcount = 0;

    /**
     * 是否在进行事务处理
     *
     * @access private
     * @staticvar
     * @var boolean
     */
    private static $_begin_action = false;

    /**
     * 数据库编码
     *
     * @access private
     * @var string
     */
    private $_encoding;

    /**
     * 构造器
     *
     * @access public
     * @return null
     */
    function __construct($host, $port, $user, $pass, $name)
    {
        $this->_instance = @mysql_connect($host . ':' . $port, $user, $pass, true);
        @mysql_select_db($name, $this->_instance);
        $this->_catch();
    }

    /**
     * 存/取数据库编码设置
     *
     * @access public
     * @param string $encoding
     * @return mixed
     */
    function encoding($encoding = null)
    {
        if (is_null($encoding)) {
            return $this->_encoding;
        }
        $this->_encoding = $encoding;

        return $this;
    }

    /**
     * 执行数据库编码设定
     *
     * @access public
     * @return object
     */
    function setNames()
    {
        $this->query("set names '{$this->_encoding}'");

        return $this;
    }

    /**
     * 执行一条SQL语句，并返回结果记录源
     *
     * @access public
     * @param string $sql
     * @return mixed
     */
    function _query($sql)
    {
        $trace = new Trace();
        $trace->time('SqlQueryStartTime');
        is_string($sql) || $sql = (string)$sql;
        Timer::Mark(TIMER_PI_DB_QUERY_BEGIN);
        $resource = @mysql_query($sql);
        $this->_catch($sql);
        $trace->time('SqlQueryEndTime');
        Timer::Mark(TIMER_PI_DB_QUERY_END);
        Trace::Set(TRACE_NAME_DATABASE, [$sql, sprintf('%.4f', Timer::Last())]);
        self::$_rowcount = is_bool($resource) ? @mysql_affected_rows($this->_instance) : @mysql_num_rows($resource);
        if (DEBUG) {
            $queryTime = $trace->time('SqlQueryStartTime', 'SqlQueryEndTime', 5);
            $trace->record($sql . '[ RunTime: ' . $queryTime . ' s ]', 'sql');
            $trace->N('db_query_time', $queryTime);
            $trace->N('db_query', 1);
        }

        return $resource;
    }

    /**
     * 执行操作，返回影响行数
     *
     * @access public
     * @param string $sql
     * @return int
     */
    function query($sql)
    {
        $this->_query($sql);

        return $this->rowCount();
    }

    /**
     * 执行带返回值的操作
     *
     * @access public
     * @param string $sql
     * @return array
     */
    function fetchAll($sql)
    {
        $resource = $this->_query($sql);
        $result   = [];
        while ($row = @mysql_fetch_array($resource, $this->_fetchMode)) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     * 获取第一行第一列的值，一般用在聚合函数
     *
     * @access public
     * @param string $sql
     * @return mixed
     */
    function fetchOne($sql)
    {
        $resource = $this->_query($sql);
        $result   = @mysql_fetch_row($resource);

        return $result[0];
    }

    /**
     * 获取单行数据
     *
     * @access public
     * @param string $sql
     * @return mixed
     */
    function fetch($sql)
    {
        $resource = $this->_query($sql);
        switch ($this->_fetchMode) {
            case MYSQL_BOTH:
                return @mysql_fetch_array($resource);
            case MYSQL_ASSOC:
                return @mysql_fetch_assoc($resource);
            case MYSQL_NUM:
                return @mysql_fetch_row($resource);
        }
    }

    /**
     * 取最近一条插入的数据ID
     *
     * @access public
     * @param string $seq
     * @return integer
     */
    function lastInsertId($seq = null)
    {
        return @mysql_insert_id();
    }

    /**
     * 返回最近执行的SQL语句所影响到的行数
     *
     * @access public
     * @return integer
     */
    function rowCount()
    {
        return self::$_rowcount;
    }

    /**
     * 缓存代理
     *
     * @see BIProxy::cache()
     */
    function cache($expire = 86400, $engine = 'memcached')
    {
        return '';
    }

    /**
     * 设置或获取当前数据库的取值模式
     *
     * @access public
     * @param int $fetchMode
     * @param mixed
     */
    function fetchMode($mode = null)
    {
        if (is_null($mode)) {
            return $this->_fetchMode;
        } elseif (!isset($this->_fetch_modes[$mode])) {
            throw new DatabaseException('mode_unsupported');
        }
        $this->_fetchMode = $this->_fetch_modes[$mode];

        return $this;
    }

    /**
     * 开始事务处理
     *
     * @access public
     * @return bool
     */
    function beginTransaction()
    {
        if (self::$_begin_action) {
            throw new DatabaseException('call_transaction_irregular');
        }
        $query = $this->_query('set autocommit = 0');
        $query && $query = $this->_query('start transaction');
        if ($query) {
            self::$_begin_action = true;

            return $query;
        }
        throw new DatabaseException('start_transaction_failed');
    }

    /**
     * 提交当前事务
     *
     * @access public
     * @return bool
     */
    function commit()
    {
        if (!self::$_begin_action) {
            throw new DatabaseException('transaction_no_started');
        }
        $query = $this->_query('commit');
        if ($query) {
            self::$_begin_action = false;
        }

        return $query;
    }

    /**
     * 回滚当前事务
     *
     * @access public
     * @return bool
     */
    function rollback()
    {
        if (!self::$_begin_action) {
            throw new DatabaseException('transaction_no_started');
        }
        $query = $this->_query('rollback');
        if ($query) {
            self::$_begin_action = false;
        }

        return $query;
    }

    /**
     * select助手
     *
     * @access public
     * @return object
     */
    function select()
    {
        return Loader::Instance('>\\SqlTranslator\\Plugin\\Select');
    }

    /**
     * delete助手
     *
     * @access public
     * @return object
     */
    function delete()
    {
        return Loader::Instance('>\\SqlTranslator\\Plugin\\Delete');
    }

    /**
     * insert助手
     *
     * @access public
     * @return object
     */
    function insert()
    {
        return Loader::Instance('>\\SqlTranslator\\Plugin\\Insert');
    }

    /**
     * update助手
     *
     * @access public
     * @return object
     */
    function update()
    {
        return Loader::Instance('>\\SqlTranslator\\Plugin\\Update');
    }

    /**
     * 捕捉数据库运行时错误
     *
     * @access private
     * @return null
     */
    private function _catch($sql = null)
    {
        if (mysql_errno()) {
            throw new DatabaseException(
                DEBUG ? ('[' . date("Y-m-d H:i:s") . '] [' . $sql . ']<br />[MESSAGE:' . mysql_error(
                    ) . ']') : 'database_query_failed'
            );
        }
    }

}

?>
