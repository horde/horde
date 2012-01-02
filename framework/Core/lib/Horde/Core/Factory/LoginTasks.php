<?php
/**
 * A Horde_Injector:: based Horde_LoginTasks:: factory.
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
 * A Horde_Injector:: based Horde_LoginTasks:: factory.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Core_Factory_LoginTasks extends Horde_Core_Factory_Base
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Return the Horde_LoginTasks:: instance.
     *
     * @param string $app  The current application.
     *
     * @return Horde_Core_LoginTasks|boolean  The singleton instance. Returns
     *                                        false if logintasks not
     *                                        available.
     */
    public function create($app)
    {
        if (!$GLOBALS['registry']->getAuth()) {
            return false;
        }

        if (!isset($this->_instances[$app])) {
            $this->_instances[$app] = new Horde_Core_LoginTasks(new Horde_Core_LoginTasks_Backend_Horde($app), $app);
        }

        return $this->_instances[$app];
    }

}
