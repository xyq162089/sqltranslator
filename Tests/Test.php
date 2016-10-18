<?php

$dao = (new \SqlTranslator\Database())->pick('pdo');
$sql = $dao->select()->from(['a' => 'admin_account'], ['a.id', 'a.name'])
  ->where('a.id=1');

$result = $dao->fetchAll($sql);

var_dump($result);exit;
exit;
