# sqltranslator
-------------
# SQL生成助手！
## 更新日志
+ 2016-08-15 创建项目
+ 2016-08-30 项目更新--修复了不能调用的BUG
+ 2016-10-18 引入PDO，完善功能。
+ 2016-10-19 配置文件外部导入。
+ 2017-01-19 查询字段格式统一，新增插入唯一时更新功能
+ 2017-04-11 新增行锁接口
+ 2019-11-07 新增mongodb 事务 mongodb4.0以上,需要开启复制集 ( mongo shell rs.initiate() )
--

#调用方式
###查询
```php

$model = (new \SqlTranslator\Database())->config('mysql://root:#PWD@127.0.0.1:3306/demo')->pick('pdo');

$sql = $model->select()
                ->from(['a' => 'jst_book'], ['id', 'a.name', 'n' => '#NOW()'])
                ->joinLeft(['b' => 'jst_book_detail'], 'a.id = b.id', ['b.detail', 'b.cconte', 's' => '#NOW()'])
                ->where('a.id=1')->lock();
$result = $model->fetchAll($sql);

var_dump($result);exit;
exit;

```
-----------------


###开启事务
```php
mysql
$model = (new \SqlTranslator\Database())->config('mysql://root:#PWD@127.0.0.1:3306/demo')->pick('pdo');
$translator = new \SqlTranslator\SqlTranslator();
try {
  $model->beginTransaction();
  $sql = $model->select()
                  ->from(['a' => 'jst_book'], ['id', 'a.name', 'n' => '#NOW()'])
                  ->joinLeft(['b' => 'jst_book_detail'], 'a.id = b.id', ['b.detail', 'b.cconte', 's' => '#NOW()'])
                  ->where('a.id=1')->lock();
  $result = $model->fetchAll($sql);
  $model->commit();
  return $oid;
} catch (\Exception $e) {
    $model->rollBack();

    return false;
}
var_dump($result);exit;
exit;

mongodb

$model = (new \SqlTranslator\Database())->config('mongodb://root:#PWD@127.0.0.1:27017/demo')->pick('mongodb');
$user = new User();
$session = $user->getSession();
try {
    $data = [
         'phone' => '11111111111'
    ];
 
    $ret = $user->addTransaction($data,$session);
 
    /*提交事务*/
    $session->commitTransaction();
 
    return $ret;
} catch (\Exception $e) {
    /*回滚*/
    $session->abortTransaction();
    return false;
}
var_dump($result);exit;
exit;


```

-----------------

###新增
```php

$insert = $model->insert()
                    ->into(
                        'table', [
                                      'name',
                                      'phone',
                                      'type',
                                      'price',
                                      'price_type',
                                      'order_count',
                                  ]
                    )
                    ->values(
                        [
                            $params['name'],
                            $params['phone'],
                            $params['type'],
                            (float)$params['price'],
                            $params['price_type'],
                            0,
                        ]
                    )
                    ->duplicate(['order_count' => 1, 'name' => $params['name']]);

$result = $model->query($sql);

exit;

```
-----------------

###修改

```php

        $update    = $this->_db_translator->update
                               ->set($classname::tableName(), $params)
                               ->where('id=?', $id);

```                               
-----------------

###删除
```php
$delete    = $model->delete()
                               ->from('table')
                               ->where('id=?', $id);

```                           
