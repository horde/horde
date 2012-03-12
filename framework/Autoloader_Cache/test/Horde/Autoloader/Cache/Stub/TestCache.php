<?php

require_once 'Horde/Autoloader.php';
require_once 'Horde/Autoloader/Cache.php';

class Horde_Autoloader_Cache_Stub_TestCache
extends Horde_Autoloader_Cache
{
    public function getType()
    {
        return $this->_cachetype;
    }

    public function getCache()
    {
        return $this->_cache;
    }
}