<?php
class ClassLoader012345
{
    static public function init()
    {
        $self = new static;
        spl_autoload_register([$self, "load"]);
    }
    
    public function load($class)
    {
        $class = trim(str_replace("\\", "/", $class), "/");
        
        $filename = __DIR__ . "/$class.php";
        
        if (file_exists($filename)) {
            require_once $filename;
        }
    }
}

ClassLoader012345::init();
