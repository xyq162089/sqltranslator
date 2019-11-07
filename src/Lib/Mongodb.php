<?php
namespace SqlTranslator\Lib;

use MongoDB\Driver\Exception\WriteException;
use SqlTranslator\NoSql;
use SqlTranslator\DIDatabaseNoSql;
use SqlTranslator\Loader;
use SqlTranslator\Timer;
use SqlTranslator\Trace;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use MongoDB\Driver\Command;
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
     * 文档
     * @var array
     */
    public $document = [];

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
        $this->_instance = new \MongoDB\Driver\Manager($dns, $options);
    }

    /**
     * 开启事务
     * @return \MongoDB\\Session
     * @throws
     */
    public function getSession()
    {
        try {

            $session = $this->_instance->startSession();

            $session->startTransaction([
                'readConcern' => new \MongoDB\Driver\ReadConcern("snapshot"),
                'writeConcern' => new \MongoDB\Driver\WriteConcern(\MongoDb\Driver\WriteConcern::MAJORITY)
            ]);

            return $session;

        } catch (\MongoDB\Driver\Exception\Exception $e) {
            printf("Other error: %s\n", $e->getMessage());
            exit;
        }
    }

    /**
     * 事务操作语句
     * @param $data
     * @param $type
     * @return mixed
     * @throws
     */
    protected  function myExecuteBulkWrite($data,$type,$session)
    {
        try {
            $bulk = new BulkWrite;

            /*类型*/
            if (empty($type)) {
                printf("Other error: %s\n", "不支持的批处理操作类型 '{$type}'");
                exit;
            }

            $insertedIds = null;

            foreach ($data as $k => $item) {
                switch ($type) {
                    case 'insert':
                        /*插入*/
                        $insertedIds[$k] = $bulk->insert($item);
                        break;
                    case 'update':
                        /*更新*/
                        $bulk->update($item['condition'], $item['document'], $item['options']);
                        break;
                    case 'delete':
                        /*删除*/
                        $bulk->delete($item['condition'], isset($item['options']) ? $item['options'] : []);
                        break;
                    default:
                        printf("Other error: %s\n", "不支持的批处理操作类型 '{$type}'");
                        exit;
                        break;
                }
            }

            /*表名*/
            $names = $this->selectCollection();


            /*会话*/
            $options = $session ? ['session' => $session] : [];

            /*执行语句*/
            $writeResult = $this->_instance->executeBulkWrite($names, $bulk , $options);
            /*ID*/
            $ret['insertedIds'] = $insertedIds;

            if ($type == 'insert') {
                /*插入*/
                $ret['result'] = $writeResult->getInsertedCount();
            } else if($type == 'update') {
                /*更新*/
                $ret['result'] = $writeResult->getModifiedCount();
            } else if ($type == 'delete')  {
                /*删除*/
                $ret['result'] = $writeResult->getDeletedCount();
            }

            return $ret;

        } catch (\MongoDB\Driver\Exception\Exception $e) {
            printf("Other error: %s\n", $e->getMessage());
            exit;
        }
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
     * @return \MongoCollection
     */
    public function selectCollection()
    {
        return $this->_db . '.' . $this->_collection;
    }

    /**
     * 选择集合
     * @return \MongoCollection
     */
    public function collection()
    {
        return $this->_collection;
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
     * @param $sql
     * @return Command
     */
    private function _command($sql)
    {
        return new  Command($sql);
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
     * @throws
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
     * 删除（物理删除,事务）
     * @param array $where 条件
     * @throws
     * @return
     */
    public function deleteTransaction($where,$session= null)
    {

            if (empty($where)) {
                return false;
            }

            /*多条件更新*/
            $options['multi'] = true;

            $data[] = [
                'condition' => $where,
                'options' => $options
            ];

            $ret = $this->myExecuteBulkWrite($data,'delete',$session);

            return $ret;

    }

    /**
     * 删除(逻辑删除,事务)
     * @param string $where 条件
     * @param string $update 更新数据
     * @param string $session
     * @throws
     * @return
     */
    public function delTransaction($where,$update,$session=null)
    {

        /*多条件更新*/
        $options['multi'] = true;

        $data[] = [
            'condition' => $where,
            'document' => $update,
            'options' => $options
        ];

        $ret = $this->myExecuteBulkWrite($data,'update',$session);

        return $ret;

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
     * 插入数据（事务）
     * @param $data
     * @return mixed
     * @throws
     */
    public function insertTransaction($data,$session = null)
    {
        unset($data['offset']);
        unset($data['pageNum']);
        unset($data['limit']);
        unset($data['actKey']);
        unset($data['actStatus']);

        $ret = $this->myExecuteBulkWrite($data,'insert',$session);

        return $ret['insertedIds'];

    }

    /**
     * 批量插入(事务)
     * @param array $data 数据
     * @return bool
     * @throws
     */
    public function batchInsertTransaction($data,$session = null)
    {
        /*无需验证字段*/
        $model = $this->myExecuteBulkWrite($data,'insert',$session);

        return $model ? $model : false;
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
     *  更新文档数据（多条更新,事务）
     * @param array $where 条件
     * @param array $update 更新内容
     * @return bool
     * @throws
     */
    public function modifyTransaction($where,$update,$session = null)
    {
        /*验证更新内容是否存在$set*/
        if (!isset($update['$set']) && !isset($update['$inc'])) {
            printf("Other error: %s\n", '语法错误：$update 中缺少 $set 操作符');
            exit;
        }

        /*条件*/
        $options = [];

        /*多条件更新*/
        $options['multi'] = true;

        $data[] = [
            'condition' => $where,
            'document' => $update,
            'options' => $options
        ];

        $ret = $this->myExecuteBulkWrite($data,'update',$session);
    }


    /**
     * 聚合查询
     * @param $pipelines
     * @param array $options
     * @return array
     * @throws
     */
    public function aggregate($pipelines, $options = [])
    {
        if (!is_array($pipelines)) {
            return [];
        }

        if (!isset($options['cursor']) && empty($options['cursor'])) {
            $returnCursor = false;
            $options['cursor'] = new \stdClass();
        } else {
            $returnCursor = true;
        }

        $document = array_merge(
            [
                'aggregate' => $this->collection(),
                'pipeline' => $pipelines,
                'allowDiskUse' => false,
            ], $options
        );

        $cursor = $this->_instance->executeCommand($this->_db, $this->_command($document));

        if ($returnCursor) {
            return $cursor;
        }

        return $cursor->toArray();


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
