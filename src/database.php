<?php
namespace SqlTranslator;

class BMode_database extends BMode
{

    /**
     * 数据库取值模式：BOTH
     *
     */
    const FETCH_BOTH = 0;

    /**
     * 数据库取值模式：ASSOC
     *
     */
    const FETCH_ASSOC = 1;

    /**
     * 数据库取值模式：NUM
     *
     */
    const FETCH_NUM = 2;

    /**
     * 支持的数据库引擎
     */
    private $_engines = array('oracle', 'mysql');

    /**
     * 当前数据库引擎
     *
     */
    private $_engine = '';

    /**
     * 数据库类型
     *
     * @access protected
     * @var string
     */
    protected $_type;

    /**
     * 数据库主机名
     *
     * @access protecte
     * @var string
     */
    protected $_host;

    /**
     * 数据库名称
     *
     * @access protected
     * @var string
     */
    protected $_name;

    /**
     * 数据库用户名
     *
     * @access protected
     * @var string
     */
    protected $_user;

    /**
     * 数据库密码
     *
     * @access protected
     * @var string
     */
    protected $_pass;

    /**
     * 数据库端口
     *
     * @access protected
     * @var int
     */
    protected $_port;

    /**
     * 数据库编码
     *
     * @access protected
     * @var int
     */
    protected $_encoding = 'utf8';

    /**
     * 数据库实例
     * @var unknown_type
     */
    static $_db_pick = array();


    function engine($engine = '')
    {
    	if ($engine) {
    		$this->_engine = $engine;
    	} else {
    		return $this->_engine;
    	}

    }
    /**
     * 取得已设置的数据库连接对象实例
     *
     * @access public
     * @param string $drivername
     * @return object
     */
    function pick($database = 'default')
    {
        if (is_null(self::$_db_pick[$database])){
        	self::$_db_pick[$database] = $this->_getInstance($database);
        	BTrace::Set(TRACE_NAME_ENGINE, array('database'=> $this->_engine));
        }
        return self::$_db_pick[$database];
    }

    /**
     * 获取数据库实例
     *
     * @access private
     * @param string $engine
     * @param string $database
     * @return mixed
     */
    private function _getInstance($database)
    {
    	if ($this->_engine == 'pdo'
    	        && !class_exists($this->_engine, false)) {
    	    return null;
    	} elseif ($class = BLoader::instance("../Mode_database_{$this->_engine}")) {
    		if ($instance = $class->connect($database)) {
    		    return $instance;
    	    }
    	}
    	return null;
    }


    /**
     * 解析连接
     *
     * @access private
     * @param string $database
     * @return null
     */
    function AnalyseConnect($database = 'default')
    {
        if(!($config = B::i()->config->database[$database])) {
            throw new BDatabaseException('database_config_missing', $database);
        }

        $config = parse_url($config);
        $this->_type = $config['scheme'];
        $this->_host = $config['host'];
        $this->_name = trim($config['path'], '/');
        $this->_user = $config['user'];
        $this->_pass = $config['pass'];
        $this->_port = $config['port'];
    }

	function __get($name)
	{
		list ($database,$engine) = explode('_', $name);
		$database || $database = 'default';
		$engine || $engine = 'oracle';
		if (!in_array($engine, $this->_engines)) {
			throw new BDatabaseException('database_engine_missing', $engine);
		}
        $this->engine($engine);
        return $this->pick($database);
	}
}


//数据库引擎接口
interface DIDatabase
{

    /**
     * 获取所有数据记录
     *
     * @access public
     * @param string $sql
     * @return array
     */
    function fetchAll($sql);

    /**
     * 获取第一行第一列数据，一般用在聚合函数中
     *
     * @access public
     * @param string $sql
     * @return integer
     */
    function fetchOne($sql);

    /**
     * 获取当行数据
     *
     * @access public
     * @param string $sql
     * @return array
     */
    function fetch($sql);

    /**
     * 执行一条SQL语句，并返回受影响的行数
     *
     * @access public
     * @param string $sql
     * @return integer
     */
    function query($sql);

    /**
     * 设置或读取当前数据取值模式
     *
     * @access public
     * @param string $fetchMode
     * @return string/void
     */
    function fetchMode($mode = null);

    /**
     * 获取最近插入的一行记录的ID值
     *
     * @access public
     * @return integer
     */
    function lastInsertId($seq = null);

    /**
     * 数据库操作跟踪信息
     *
     * @access public
     * @param string $encoding
     * @return mixed
     */
    function encoding($encoding = null);

    /**
     * 获取最近一次执行的SQL语句所影响的数据表行数
     *
     * @access public
     * @return integer
     */
    function rowCount();

    /**
     * 筛选操作助手
     *
     * @access public
     * @return object
     */
    function select();

    /**
     * 删除操作助手
     *
     * @access public
     * @return object
     */
    function delete();

    /**
     * 插入操作助手
     *
     * @access public
     * @return object
     */
    function insert();

    /**
     * 更新操作助手
     *
     * @access public
     * @return object
     */
    function update();

}

?>
