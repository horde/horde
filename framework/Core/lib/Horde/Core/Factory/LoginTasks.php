<?php
/**
 * A Horde_Injector:: based Horde_LoginTasks:: factory.
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
 * A Horde_Injector:: based Horde_LoginTasks:: factory.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
class Horde_Core_Factory_LoginTasks
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
     * @return Horde_LoginTasks|boolean  The singleton instance. Returns false
     *                                   if logintasks not available.
     */
    public function create($app)
    {
        if (!$GLOBALS['registry']->getAuth()) {
            return false;
        }

        if (!isset($this->_instances[$app])) {
            $this->_instances[$app] = new Horde_LoginTasks(new Horde_Core_LoginTasks_Backend_Horde($app));
        }

        return $this->_instances[$app];
    }

}
