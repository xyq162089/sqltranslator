<?php
namespace SqlTranslator;

use SqlTranslator\Database;
use SqlTranslator\DIDatabase;
use SqlTranslator\Loader;
use SqlTranslator\Timer;
use SqlTranslator\Trace;
use SqlTranslator\DatabaseException;

class Oracle extends Database
{

    /**
     * 连接数据库
     * @param $config
     * @return Oracle_instance
     * @throws \SqlTranslator\DatabaseException
     */
    function connect($config)
    {
        parent::AnalyseConnect($config);
        if (!function_exists('oci_connect')) {
            throw new DatabaseException('Could not find driver.');
        }
        return new Oracle_instance($this->_user, $this->_pass, $this->_host);
    }

}

class Oracle_instance implements \SqlTranslator\DIDatabase
{
    /**
     * 连接对象实例
     *
     * @access private
     * @var object
     */
    private $_instance = null;

    /**
     * 资源实例
     *
     * @access private
     * @var object
     */
    private $_resource = null;

    /**
     * 最近一次执行SQL语句所影响的行数
     *
     * @access private
     * @staticvar
     * @var integer
     */
    private static $_rowcount = 0;

    /**
     * 取得SQL
     *
     * @access private
     * @staticvar
     * @var string
     */
    private $_sql;

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
    private $_encoding = 'utf8';


    private $_executeModes = array('SUCCESS'=> OCI_COMMIT_ON_SUCCESS, 'DEFAULT' =>OCI_DEFAULT);

    private $_executeMode = OCI_COMMIT_ON_SUCCESS;
    /**
     * 构造器
     *
     * @access public
     * @return null
     */
    function __construct($user, $pass, $config)
    {
        // 记录开始执行时间
        $trace = new Trace();
        $trace->time('SqlQueryStartTime');
        Timer::Mark('TIMER_PI_DB_CONNECT_BEGIN');
        $this->_instance || $this->_instance = oci_connect($user, $pass, $config, $this->_encoding);
        if (!$this->_instance) {
            throw new DatabaseException('Could not connect to database.');
        }
        //$this->_instance || $this->_instance = oci_pconnect($user, $pass, $config, $this->_encoding);
        Timer::Mark('TIMER_PI_DB_CONNECT_END');
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
    * 执行一条SQL语句，并返回结果记录源
    *
    * @access public
    * @param string $sql
    * @return mixed
    */
    function _query($sql)
    {
        $this->_sql = $sql = (string)$sql;
    	Timer::Mark('TIMER_PI_DB_QUERY_BEGIN');
        if (strpos($sql, ':')!== false && func_num_args() > 1) {
             preg_match('/\'(\:[A-Za-z]+)\'/i', $sql, $flag);
             $flag = $flag[1];
             $sql = str_replace('\''. $flag .'\'', $flag ,$sql);
             $data = func_get_arg(1);
        }
        if ($this->_resource = oci_parse($this->_instance, $sql)) {
             $data && oci_bind_by_name($this->_resource, $flag, $data, strlen($data));
             oci_execute($this->_resource, $this->_executeMode);
        }
        Timer::Mark('TIMER_PI_DB_QUERY_END');
        Trace::Set('TRACE_NAME_DATABASE', array($sql, sprintf('%.4f', Timer::Last())));
        $this->_catch($sql, $this->_resource);
        self::$_rowcount = oci_num_rows($this->_resource);
        return $this->_resource;
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

    function queryBindByName($sql, $data)
    {
        $this->_query($sql, $data);
        return $this->rowCount();
    }

    /**
     * 执行带返回值的操作
     * @param string $sql
     * @param int $offset
     * @param int $limit
     * @return mixed
     */
    function fetchAll($sql, $offset = 0, $limit = -1)
    {
        $resource = $this->_query($sql);
        $result = array();
        oci_fetch_all($resource, $results, $offset, $limit, OCI_FETCHSTATEMENT_BY_ROW);

        return $results;
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
        $resource = $this->fetch($sql);
        return current($resource);
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
        return oci_fetch_array($resource, OCI_ASSOC);
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
        return $this->fetchOne("SELECT $seq.Currval from dual");
    }

   /**
    * 取下一条插入的数据ID
    *
    * @access public
    * @param string $seq
    * @return integer
    */
    function nextInsertId($seq = null)
    {
        return $this->fetchOne("SELECT $seq.Nextval from dual");
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
     * 设置或获取当前数据库的取值模式
     * @param null $mode
     * @return $this
     * @throws \SqlTranslator\DatabaseException
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
    function executeMode($mode = null)
    {
        if (is_null($mode)) {
            return $this->_executeMode;
        } elseif (!isset($this->_executeModes[$mode])) {
            throw new DatabaseException('mode_unsupported');
        }
        $this->_executeMode = $this->_executeModes[$mode];
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
        $this->executeMode('DEFAULT');
        self::$_begin_action = true;
        return $this;
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

        $query = oci_commit($this->_instance);
        if ($query) {
            self::$_begin_action = false;
        }
        $this->executeMode('SUCCESS');
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
        $query = oci_rollback($this->_instance);
        if ($query) {
            self::$_begin_action = false;
        }
        $this->executeMode('SUCCESS');
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


    function lobDescriptor()
    {
    	return oci_new_descriptor($this->_instance, OCI_D_LOB);
    }
    /**
    * 捕捉数据库运行时错误
    *
    * @access private
    * @return null
    */
    private function _catch($sql = null, $resource)
    {
        if ($e = oci_error($resource)) {
            throw new DatabaseException( DEBUG ? '['.$sql.']'.$e['message'] : 'database_query_failed');
        }

        if (DEBUG && $sql) {
        	$trace = new Trace();
        	$trace->time('SqlQueryEndTime');
        	$queryTime = $trace->time('SqlQueryStartTime', 'SqlQueryEndTime');
        	if ($queryTime > 0.07) {
        		$data =  '['.date("Y-m-d H:i:s"). '] QTime:['. $queryTime.'s ]'. 'Sql:[ '.str_replace("\r\n",' ', $sql).' ] '. $_SERVER['REQUEST_URI'] .PHP_EOL;
        		$trace->save(Trace::FILE, null, $data);
        	}
        	$trace->record($this->_sql . '[ RunTime: '. $queryTime .' s ]', 'sql');
        	$trace->N('db_query_time', $queryTime);
        	$trace->N('db_query',1);

        }
    }

    function __destruct()
    {
        $this->_catch(null, $this->_instance);
        oci_close($this->_instance);
        oci_free_statement($this->_resource);

    }

}

?>
