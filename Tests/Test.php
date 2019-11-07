<?php

namespace base;
require '../src/Database.php';
require '../src/SqlTranslator.php';
require '../src/Loader.php';
require '../src/Timer.php';
require '../src/Trace.php';

use SqlTranslator\SqlTranslator;
use SqlTranslator\Database;


$dao = (new Database())->pick('pdo');
$sql = $dao->select()->from(['a' => 'admin_account'], ['a.id', 'a.name'])
  ->where('a.id=1');

$result = $dao->fetchAll($sql);

var_dump($result);exit;
exit;
