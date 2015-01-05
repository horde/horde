<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Core
 */

/**
 * A wrapper around the Horde-wide HashTable instance, suitable for use with
 * objects that will be serialized.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @internal
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Core
 */
class Horde_Core_HashTable_Wrapper
{
    /**
     * Redirects calls to the HashTable object.
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array(
            array($GLOBALS['injector']->getInstance('Horde_HashTable'), $name),
            $arguments
        );
    }

    /**
     * Redirects get calls to the HashTable object.
     */
    public function __get($name)
    {
        return $GLOBALS['injector']->getInstance('Horde_HashTable')->$name;
    }

    /**
     * Redirects set calls to the HashTable object.
     */
    public function __set($name, $value)
    {
        $GLOBALS['injector']->getInstance('Horde_HashTable')->$name = $value;
    }

}
