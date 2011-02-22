<?php
/**
 * A Horde_Injector:: based factory for creating Horde_Core_Block_Collection
 * objects.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based factory for creating Horde_Core_Block_Collection
 * objects.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_BlockCollection extends Horde_Core_Factory_Base
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Return the Block_Collection instance.
     *
     * @param array $apps  The applications whose blocks to list.
     *
     * @return Horde_Core_Block_Collection  The singleton instance.
     * @throws Horde_Exception
     */
    public function create(array $apps = array())
    {
        sort($apps);
        $sig = hash('md5', serialize($apps));

        if (!isset($this->_instances[$sig])) {
            $this->_instances[$sig] = new Horde_Core_Block_Collection($apps);
        }

        return $this->_instances[$sig];
    }

}
