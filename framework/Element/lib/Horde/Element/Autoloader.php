<?php

require_once 'Horde/Autoloader.php';
require_once 'Horde/Autoloader/ClassPathMapper.php';
require_once 'Horde/Autoloader/ClassPathMapper/Default.php';

class Horde_Element_Autoloader extends Horde_Autoloader
{
    public function __construct()
    {
        foreach (array_reverse(explode(PATH_SEPARATOR, get_include_path())) as $path) {
            if ($path == '.') { continue; }
            $path = realpath($path);
            if ($path) {
                $this->addClassPathMapper(new Horde_Autoloader_ClassPathMapper_Default($path));
            }
        }
    }
}

$__autoloader = new Horde_Element_Autoloader();
$__autoloader->registerAutoloader();
