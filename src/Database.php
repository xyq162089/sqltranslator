<?php
namespace SqlTranslator;

use SqlTranslator\Loader;
use SqlTranslator\Timer;
use SqlTranslator\DatabaseException;

defined('DEBUG') or define('DEBUG', false);

class Database
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
    private $_engines = ['oracle', 'mysql', 'pdo'];

    /**
     * 当前数据库配置
     *
     */
    private $_config = '';

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
    protected $_encoding = 'utf8mb4';

    /**
     * 数据库实例
     * @var unknown_type
     */
    protected static $_db_pick = [];


    public function config($config = '')
    {
        if ($config) {
            $this->_config = $config;
        }

        return $this;

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
     * 取得已设置的数据库连接对象实例
     *
     * @access public
     * @param string $database
     * @return object
     */
    public function pick($database = 'pdo')
    {
        Timer::setTimezone();
        if (!array_key_exists($database, self::$_db_pick)) {
            self::$_db_pick[$database] = $this->_getInstance($database);
        }

        return self::$_db_pick[$database];
    }

    /**
     * 获取数据库实例
     *
     * @access private
     * @param string $database 数据库引擎
     * @return mixed
     */
    private function _getInstance($database)
    {
        if ($class = Loader::Instance('>\\SqlTranslator\\Lib\\' . ucfirst($database))) {
            if ($instance = $class->connect($this->_config)) {
                return $instance->encoding($this->encoding())->setNames();
            }
        }

        return null;
    }

    /**
     * 解析连接
     * @throws \SqlTranslator\DatabaseException
     */
    function AnalyseConnect($config)
    {
        if ($config) {
            $config      = parse_url($config);
            $this->_type = $config['scheme'];
            $this->_host = $config['host'];
            $this->_name = trim($config['path'], '/');
            $this->_user = $config['user'];
            $this->_pass = $config['pass'];
            $this->_port = $config['port'];
        } else {
            throw new DatabaseException('database_config_missing');
        }
    }

    function __get($engine)
    {
        if (!in_array($engine, $this->_engines)) {
            throw new DatabaseException('database_engine_missing', $engine);
        }

        return $this->pick($engine);
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

/**
 * 可缓存组件接口，凡实现此接口的对象皆可调用cache方法缓存其本身
 *
 */
interface BIProxy
{

    /**
     * 缓存代理方法
     *
     * @access public
     * @param int $expire 缓存时间
     * @return object 缓存代理器
     */
    function cache($expire = 86400, $engine = null);

}

?>
