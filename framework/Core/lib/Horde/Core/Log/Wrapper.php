<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Core
 */

/**
 * A wrapper around the Horde-wide logger, suitable for use with objects
 * that will be serialized.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @internal
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Core
 */
class Horde_Core_Log_Wrapper
{
    /**
     * Log instance.
     *
     * @var Horde_Core_Log_Logger
     */
    private $instance;

    /**
     * Redirects calls to the logger object.
     */
    public function __call($name, $arguments)
    {
        if (!isset($instance)) {
            $instance = $GLOBALS['injector']->getInstance('Horde_Log_Logger');
        }

        return call_user_func_array(array($instance, $name), $arguments);
    }

    /**
     * Nothing is serialized in this class.
     */
    public function __sleep()
    {
    }

}
