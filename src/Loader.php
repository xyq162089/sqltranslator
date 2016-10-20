<?php
namespace SqlTranslator;

use SqlTranslator\DatabaseException;

class Loader
{
    /**
     * 工作路径别名
     *
     */
    const ALIASE_CALL = '>';

    /**
     * 目录分隔符
     *
     */
    const SPACE_DIR = DIRECTORY_SEPARATOR;

    /**
     * 文件后缀
     *
     */
    const FILE_EXTNAME = '.php';

    private static $_classMap  = [];
    private static $_aliases   = ['>' => __DIR__];
    private static $_instances = [];
    public static  $app;


    public static function getVersion()
    {
        return '0.0.10-dev';
    }

    /**
     * 设置/取得别名
     *
     * @static
     * @access public
     * @param string $name 名称
     * @param string $path 别名指向的路径
     * @return mixed
     */
    static function aliase($name = null, $path = null)
    {
        if (is_null($name)) {
            return self::$_aliases;
        }
        if (is_null($path)) {
            return isset(self::$_aliases[$name]) ? self::$_aliases[$name] : null;
        }
        if (is_dir($path)) {
            self::$_aliases[$name] = $path;
        }

        return true;
    }

    public static function PathName($namespace)
    {

        $namespace  = str_replace(['\\', '/'], self::SPACE_DIR, $namespace);
        $namespaces = explode(self::SPACE_DIR, $namespace);

        $aliases = self::aliase();
        if (!is_array($namespaces) || !$aliases[$namespaces[0]]) {
            return false;
        }
        //别名
        $aliase = $aliases[$namespaces[0]];
        unset($namespaces[0]);
        $namespace = implode(self::SPACE_DIR, $namespaces);
        $extend    = pathinfo($namespace);
        if (! array_key_exists('extension', $extend) && substr($namespace, -1) != self::SPACE_DIR) {
            $namespace .= self::FILE_EXTNAME;
        }

        return [
            'path' => $aliase . self::SPACE_DIR . $namespace,
            'namespace' => implode('\\', $namespaces),
            'name' => $namespace
        ];
    }

    public static function Import($namespace)
    {
        $classFile = self::PathName($namespace)['path'];
        if ($classFile === false || !is_file($classFile)) {
            return;
        }

        include($classFile);

    }

    public static function ClassName($namespace)
    {
        $className = self::PathName($namespace);
        return $className['namespace'];
    }

    /**
     * 取得指定路径的类实例
     * @param $namespace
     * @return string
     * @throws \SqlTranslator\DatabaseException
     */
    public static function Instance($namespace)
    {
        $instance = '';
        if (array_key_exists($namespace, self::$_instances)) {
            $instance = self::$_instances[$namespace];
        } else {
            try {
                self::Import($namespace);
                $classname = self::ClassName($namespace);
                self::$_instances[$namespace] = $instance = new $classname();
            } catch (DatabaseException $e) {
                throw $e;
            }
        }

        return $instance;
    }
}
