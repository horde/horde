<?php
/**
 */
class Horde_Core_Factory_Request
{
    public function create(Horde_Injector $injector)
    {
        $request = new Horde_Controller_Request_Http();
        $request->setPath($_SERVER['REQUEST_URI']);
        return $request;
    }
}
