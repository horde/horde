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
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based factory for creating Horde_Core_Block_Collection
 * objects.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
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
     * @param array $apps     The applications whose blocks to list.
     * @param string $layout  The preference name for the layout
     *                        configuration.
     *
     * @return Horde_Core_Block_Collection  The singleton instance.
     * @throws Horde_Exception
     */
    public function create(array $apps = array(), $layout = 'portal_layout')
    {
        global $registry, $session;

        $apps = empty($apps)
            ? $registry->listApps()
            : array_intersect($registry->listApps(), $apps);
        sort($apps);
        $sig = hash('md5', serialize(array($apps, $layout)));

        if (!isset($this->_instances[$sig])) {
            if (!($ob = $session->retrieve('horde', 'blocks/' . $sig))) {
                $ob = new Horde_Core_Block_Collection($apps, $layout);
                $session->set('horde', 'blocks/' . $sig, $ob);
            }

            $this->_instances[$sig] = $ob;
        }

        return $this->_instances[$sig];
    }

}
