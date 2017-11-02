<?php
namespace SqlTranslator\Lib;

use SqlTranslator\NoSql;
use SqlTranslator\DIDatabaseNoSql;
use SqlTranslator\Loader;
use SqlTranslator\Timer;
use SqlTranslator\Trace;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\WriteConcern;
use MongoDB\Driver\BulkWriteException;
use SqlTranslator\DatabaseException;

class Mongodb extends NoSql
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

        $option['connect']  = true;
        $option['username'] = $this->_user;
        $option['password'] = $this->_pass;
        if ($this->_name !== null) {
            $option['db'] = $this->_name;
        }

        //$dsn      = "mongodb://{$this->_host}:{$this->_port}";

        $instance = new Mongodb_instance($config, $option);

        return $instance;
    }

}

class Mongodb_instance implements DIDatabaseNoSql
{
    /**
     * 连接对象实例
     *
     * @access private
     * @var object
     */
    private $_instance = null;

    /**
     * 数据库名称
     *
     * @access private
     * @var object
     */
    private $_db = null;

    /**
     * 集合名称
     *
     * @access private
     * @var object
     */
    private $_collection = null;

    /**
     * 是否在进行事务处理
     *
     * @access private
     * @staticvar
     * @var boolean
     */
    private static $_begin_action = false;

    /**
     * 构造器
     * Mongodb_instance constructor.
     * @param $dns
     * @param array $options
     */
    public function __construct($dns, $options = [])
    {
        //'mongodb://work:work@121.199.53.9:27017/narada'
        $this->_db       = $options['db'];
        $this->_instance = new Manager($dns, $options);
    }

    /**
     * 设置集合
     * @param $collection
     * @return \MongoCollection
     */
    public function setCollection($collection)
    {
        if ($collection) {
            $this->_collection = $collection;

        }

        return $this;
    }

    /**
     * 选择集合
     * @param $collection
     * @return \MongoCollection
     */
    public function selectCollection()
    {
        return $this->_db . '.' . $this->_collection;
    }

    /**
     * mongodb 执行命令
     *
     * @param string $sql
     * @return object
     */
    private function _query($sql)
    {
        $options = [];

        return new Query($sql, $options);
    }

    /**
     * mongodb 执行命令
     *
     * @param object $bulk
     * @return object
     */
    private function _executeBulkWrite($bulk)
    {
        try {
            $writeConcern = new WriteConcern(WriteConcern::MAJORITY, 1000);
            $result       = $this->_instance->executeBulkWrite($this->selectCollection(), $bulk, $writeConcern);
        } catch (BulkWriteException $e) {
            $result = $e->getWriteResult();

            // Check if the write concern could not be fulfilled
            if ($writeConcernError = $result->getWriteConcernError()) {
                printf("%s (%d): %s\n",
                       $writeConcernError->getMessage(),
                       $writeConcernError->getCode(),
                       var_export($writeConcernError->getInfo(), true)
                );
                exit;
            }

            // Check if any write operations did not complete at all
            foreach ($result->getWriteErrors() as $writeError) {
                printf("Operation#%d: %s (%d)\n",
                       $writeError->getIndex(),
                       $writeError->getMessage(),
                       $writeError->getCode()
                );
                exit;
            }
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            printf("Other error: %s\n", $e->getMessage());
            exit;
        }

        return $result;
    }

    /**
     * 返回所有数据
     *
     * @access public
     * @param string $sql
     * @return mixed
     */
    public function fetchAll($sql)
    {
        return $this->fetch($sql);
    }

    /**
     * 返回单行数据
     *
     * @access public
     * @param array $sql
     * @return mixed
     */
    public function fetch($sql)
    {
        $rows = $this->_instance->executeQuery($this->selectCollection(), $this->_query($sql))
                                ->toArray();

        return $rows;
    }

    /**
     * 返回第一行第一列数据，一般用在聚合函数中
     *
     * @access public
     * @param string $sql
     * @return mixed
     */
    public function fetchOne($sql)
    {
        return $this->fetch($sql);
    }

    /**
     * 执行一个SQL语句
     *
     * @access public
     * @param string $sql
     * @return int
     */
    public function query($sql)
    {
    }

    /**
     * delete助手
     *
     * @param array $filter
     * @access public
     * @return object
     */
    public function delete($filter)
    {
        $bulk = new BulkWrite;
        $bulk->delete($filter);

        return $this->_executeBulkWrite($bulk)->getDeletedCount();
    }

    /**
     * insert助手
     *
     * @param array $data
     * @access public
     * @return object
     */
    public function insert($data)
    {
        $bulk = new BulkWrite;
        $bulk->insert($data);

        return $this->_executeBulkWrite($bulk)->getInsertedCount();
    }

    /**
     * update助手
     *
     * @param array $filter
     * @param array $data
     * @access public
     * @return object
     */
    public function update($filter, $data)
    {
        $bulk = new BulkWrite;
        $bulk->update($filter, ['$set' => $data]);

        return $this->_executeBulkWrite($bulk)->getModifiedCount();
    }

    /**
     * 设置或读取当前数据取值模式
     *
     * @access public
     * @param string $mode
     * @return string/void
     */
    public function fetchMode($mode = null)
    {
    }

    /**
     * 获取最近插入的一行记录的ID值
     *
     * @access public
     * @return integer
     */
    public function lastInsertId($seq = null)
    {
    }
}
