<?php
namespace SqlTranslator;

use SqlTranslator\Loader;

class DatabaseException extends \Exception
{
    /**
     * 构造函数
     */
    function __construct()
    {
        $args    = func_get_args();
        $message = $args[0];

        if (preg_match('/^[a-z_A-Z\d]+$/', $message)) {
            $package = require(Loader::PathName('>\\Exception\\cn')['path']);

            array_key_exists($message, $package) && $message = $package[$message];
        }
        if (count($args) > 1) {
            array_shift($args);
            $message = vsprintf($message, $args);
        }
        parent::__construct($message);
    }

}


?>
