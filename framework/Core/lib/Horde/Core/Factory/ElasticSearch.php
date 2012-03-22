<?php
/**
 * A Horde_Injector:: based Horde_ElasticSearch_Client:: factory.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based Horde_ElasticSearch_Client:: factory.
 *
 * Copyright 2010-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Core
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_ElasticSearch extends Horde_Core_Factory_Injector
{
    /**
     * Return the Horde_ElasticSearch_Client instance.
     *
     * @param Horde_Injector $injector
     *
     * @return Horde_ElasticSearch_Client  The elasticsearch client
     * @throws Horde_Editor_Exception
     */
    public function create(Horde_Injector $injector)
    {
        return new Horde_ElasticSearch_Client('http://localhost:9200/', $injector->getInstance('Horde_Http_Client'));
    }
}
