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
```php

$model = (new \SqlTranslator\Database())->config('mysql://root:w88123@172.16.35.128:3306/lvcheng')->pick('pdo');
$sql = $model->select()->from(['a' => 'admin_account'], ['a.id', 'a.name'])
  ->where('a.id=1');
$result = $model->fetchAll($sql);

var_dump($result);exit;
exit;

```
