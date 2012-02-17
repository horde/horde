<?php
/**
 * TODO
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Core
 */
class Horde_Registry_Caller
{
    /**
     * TODO
     */
    protected $registry;

    /**
     * TODO
     */
    protected $api;

    /**
     * TODO
     */
    public function __construct($registry, $api)
    {
        $this->registry = $registry;
        $this->api = $api;
    }

    /**
     * TODO
     *
     * @throws Horde_Exception
     */
    public function __call($method, $args)
    {
        return $this->registry->call($this->api . '/' . $method, $args);
    }

}
