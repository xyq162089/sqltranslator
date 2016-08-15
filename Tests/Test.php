<?php

namespace SqlTranslator\Tests;

use SqlTranslator\Tool\SqlTranslator;

class Test
{
    public function CreateSql($type = 'select')
    {
        $plugin = new SqlTranslator();
        var_dump($plugin);exit;
    }
}


(new Test())->CreateSql();
