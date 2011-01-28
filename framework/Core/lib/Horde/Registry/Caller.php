<?php
/**
 * TODO
 *
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
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
