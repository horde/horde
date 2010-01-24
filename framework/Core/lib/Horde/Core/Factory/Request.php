<?php
/**
 */
class Horde_Core_Factory_Request
{
    public function create(Horde_Injector $injector)
    {
        return new Horde_Controller_Request_Http($_SERVER['REQUEST_URI']);
    }
}
