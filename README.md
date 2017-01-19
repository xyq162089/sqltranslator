# sqltranslator
-------------
# SQL生成助手！
## 更新日志
+ 2016-08-15 创建项目
+ 2016-08-30 项目更新--修复了不能调用的BUG
+ 2016-10-18 引入PDO，完善功能。
+ 2016-10-19 配置文件外部导入。

--

#调用方式
###查询
```php

$model = (new \SqlTranslator\Database())->config('mysql://root:#PWD@127.0.0.1:3306/demo')->pick('pdo');

$sql = $model->select()
                ->from(['a' => 'jst_book'], ['id', 'a.name', 'n' => '#NOW()'])
                ->joinLeft(['b' => 'jst_book_detail'], 'a.id = b.id', ['b.detail', 'b.cconte', 's' => '#NOW()'])
                ->where('a.id=1');
$result = $model->fetchAll($sql);

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