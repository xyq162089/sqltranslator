# sqltranslator
-------------
# SQL生成助手！
## 更新日志
+ 2016-08-15 创建项目
+ 2016-08-30 项目更新--修复了不能调用的BUG
+ 
--

#调用方式
```php
$sql = new \SqlTranslator\SqlTranslator();
echo $plugin->select->from(['a' => 'jst_book'], ['a.id', 'a.name'])
  ->joinLeft(['b' => 'jst_book_detail'], 'a.id = b.id', ['b.detail', 'b.cconte'])
  ->where('a.id=1');
exit;
```
