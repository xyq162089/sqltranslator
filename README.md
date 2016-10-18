# sqltranslator
-------------
# SQL生成助手！
## 更新日志
+ 2016-08-15 创建项目
+ 2016-08-30 项目更新--修复了不能调用的BUG
+ 2016-10-18 引入PDO，完善功能。
--

#调用方式
```php
$dao = (new \SqlTranslator\Database())->pick('pdo');
$sql = $dao->select()->from(['a' => 'admin_account'], ['a.id', 'a.name'])
  ->where('a.id=1');

$result = $dao->fetchAll($sql);

var_dump($result);exit;
exit;

```
