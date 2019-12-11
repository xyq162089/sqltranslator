<?php
namespace SqlTranslator;

use SqlTranslator\Loader;
use SqlTranslator\Timer;
use SqlTranslator\DatabaseException;

defined('DEBUG') or define('DEBUG', false);

class NoSql
{
    /**
     * 支持的数据库配置
     */
    private $_options = [];

    /**
     * 支持的数据库引擎
     */
    private $_engines = ['mongodb'];

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
    static $_encoding = 'utf8';

    /**
     * 数据库实例
     * @var unknown_type
     */
    protected static $_db_pick = [];

    /**
     * 数据库配置
     * @param string $config
     * @return $this
     */
    public function config($config = '')
    {
        if ($config) {
            $this->_config = $config;
        }

        return $this;

    }

    /**
     * 数据库连接参数
     * @param array $option
     * @return $this
     */
    public function option($option = [])
    {
        if ($option) {
            $this->_options = $option;
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
    public function encoding($encoding = null)
    {
        if (is_null($encoding)) {
            return self::$_encoding;
        }
        self::$_encoding = $encoding;

        return $this;
    }

    /**
     * 单例模式返回数据库连接对象实例
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
                return $instance;
            }
        }

        return null;
    }

    /**
     * 解析连接
     * @throws \SqlTranslator\DatabaseException
     */
    public function AnalyseConnect($config)
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

    public function __get($engine)
    {
        if (!in_array($engine, $this->_engines)) {
            throw new DatabaseException('database_engine_missing', $engine);
        }

        return $this->pick($engine);
    }
}


//数据库引擎接口
interface DIDatabaseNoSql
{
    /**
     * 开启会话
     * @return mixed
     */
    public function getSession();

    /**
     * 获取当行数据
     *
     * @access public
     * @param string $sql
     * @return array
     */
    public function fetchOne($sql,$options = []);

    /**
     * 获取数据
     *
     * @access public
     * @param string $sql
     * @return array
     */
    public function fetch($sql,$options = []);

    /**
     * 执行一条SQL语句
     *
     * @access public
     * @param string $sql
     * @return integer
     */
    public function query($sql);

    /**
     * 设置或读取当前数据取值模式
     *
     * @access public
     * @param string $mode
     * @return string/void
     */
    public function fetchMode($mode = null);

    /**
     * 删除操作助手
     * @param $filter
     * @return mixed
     */
    public function delete($filter);

    /**
     * 删除(物理删除,事务)
     * @param $where
     * @param null $session
     * @return mixed
     */
    public function deleteTransaction($where,$session= null);

    /**
     * 删除(逻辑删除,事务)
     * @param $where
     * @param $update
     * @param null $session
     * @return mixed
     */
    public function delTransaction($where,$update,$session=null);

    /**
     * 插入操作助手
     *
     * @param $data
     * @access public
     * @return object
     */
    public function insert($data);

    /**
     * 插入数据（事务）
     * @param $data
     * @param null $session
     * @return mixed
     */
    public function insertTransaction($data,$session = null);

    /**
     * 批量插入(事务)
     * @param $data
     * @param null $session
     * @return mixed
     */
    public function batchInsertTransaction($data,$session = null);

    /**
     * 更新操作助手
     *
     * @param $filter
     * @param $data
     * @access public
     * @return object
     */
    public function update($filter, $data);

    /**
     * 更新（事务）
     * @param $where
     * @param $update
     * @param null $session
     * @return mixed
     */
    public function modifyTransaction($where,$update,$session = null);

    /**
     * 聚合操作助手
     * @param $pipelines
     * @param array $options
     * @return mixed
     */
    public function aggregate($pipelines, $options = []);

}
