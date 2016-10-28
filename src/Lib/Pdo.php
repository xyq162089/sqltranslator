<?php

namespace SqlTranslator\Lib;

use SqlTranslator\Database;
use SqlTranslator\DIDatabase;
use SqlTranslator\Loader;
use SqlTranslator\Timer;
use SqlTranslator\Trace;
use SqlTranslator\DatabaseException;

class Pdo extends Database
{

	/**
	 * 连接数据库
	 * @param string $config
	 * @access public
	 * @return object
	 */
	function connect($config)
	{
		parent::AnalyseConnect($config);
        $options = array(
            \PDO::ATTR_ERRMODE       => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_ORACLE_NULLS  => \PDO::NULL_EMPTY_STRING,
        );
        $dsn = "{$this->_type}:host={$this->_host};port={$this->_port};dbname={$this->_name}";
        $instance = new Pdo_instance($dsn, $this->_user, $this->_pass, $options);
		return $instance->encoding($this->_encoding)->setNames();
	}

}

//实例
class Pdo_instance extends \PDO implements DIDatabase
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
    * 数据库取值方式
    *
    * @access private
    * @var string
    */
    private $_fetchMode = \PDO::FETCH_ASSOC;

    /**
     * 支持的取值模式
     *
     * @access private
     * @var array
     */
    private $_fetch_modes = array(
        Database::FETCH_BOTH=> \PDO::FETCH_BOTH,
        Database::FETCH_ASSOC=> \PDO::FETCH_ASSOC,
        Database::FETCH_NUM=> \PDO::FETCH_NUM,
    );

    /**
     * SQL语句执行后影响到的行数
     *
     * @access private
     * @var integer
     */
    private static $_rowcount = 0;

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
    function __construct($dsn, $user, $pass, $options = array())
    {
        try {
            parent::__construct($dsn, $user, $pass, $options);
        } catch(\PDOException $e) {
            throw new DatabaseException(DEBUG ? $e->getMessage() : 'create_db_connection_failed');
        }
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
     * 执行SQL语句并返回一个PDOStatement对象
     *
     * @param string $sql
     * @return object
     */
    private function _query($sql)
    {
        try {

            $trace = new Trace();
            $trace->time('SqlQueryStartTime');

            is_string($sql) || $sql = (string)$sql;
            $smt = $this->prepare($sql);
            $smt->execute();
            $smt->setFetchMode($this->_fetchMode);
            self::$_rowcount = $smt->rowCount();
            $trace->time('SqlQueryEndTime');

            if (DEBUG) {
                $queryTime = $trace->time('SqlQueryStartTime', 'SqlQueryEndTime', 5);
                $trace->record($smt->queryString . '[ RunTime: ' . $queryTime . ' s ]', 'sql');
                $trace->N('db_query_time', $queryTime);
                $trace->N('db_query', 1);
            }
            return $smt;
        } catch(\PDOException $e) {
            throw new DatabaseException(DEBUG ? $e->getMessage() . '  SQL['. $sql .']' : 'database_query_failed');
        }
    }

    /**
     * 返回所有数据
     *
     * @access public
     * @param string $sql
     * @return mixed
     */
    function fetchAll($sql)
    {
        return $this->_query($sql)->fetchAll($this->_fetchMode);
    }

    /**
     * 返回单行数据
     *
     * @access public
     * @param string $sql
     * @return mixed
     */
    function fetch($sql)
    {
        return $this->_query($sql)->fetch($this->_fetchMode);
    }

    /**
     * 返回第一行第一列数据，一般用在聚合函数中
     *
     * @access public
     * @param string $sql
     * @return mixed
     */
    function fetchOne($sql)
    {
        return $this->_query($sql)->fetchColumn(0);
    }

    /**
     * 执行一个SQL语句
     *
     * @access public
     * @param string $sql
     * @return int
     */
    function query($sql)
    {
        return $this->_query($sql)->rowCount();
    }

    /**
     * 返回上一次插入的数据ID
     *
     * @access public
     * @param string $seq
     * @return integer
     */
    function lastIsertId($seq = null)
    {
        return parent::lastInsertId($seq);
    }

    /**
     * 返回最近一次SQL语句执行后的影响行数
     *
     * @access public
     * @return integer
     */
    function rowCount()
    {
        return self::$_rowcount;
    }

    /**
     * 设置或获取当前的取值方式
     *
     * @access public
     * @param string $fetchMode
     * @return string/void
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
     * 缓存代理
     *
     * @access public
     * @param int $expire
     * @return object
     */
    function cache($expire = 86400)
    {
        return '';
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

}

?>
