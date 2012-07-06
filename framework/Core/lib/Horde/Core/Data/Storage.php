<?php
/**
 * Implement temporary data storage for the Horde_Data package.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Data_Storage implements Horde_Data_Storage
{
    /* Data storage prefix. */
    const PREFIX = 'data_import';

    /**
     */
    public function get($key)
    {
        return $GLOBALS['session']->get('horde', self::PREFIX . '/' . $key);
    }

    /**
     */
    public function set($key, $value = null)
    {
        $key = self::PREFIX . '/' . $key;

        if (is_null($value)) {
            $GLOBALS['session']->remove('horde', $key);
        } else {
            $GLOBALS['session']->set('horde', $key, $value);
        }
    }

    /**
     */
    public function exists($key)
    {
        return $GLOBALS['session']->exists('horde', self::PREFIX . '/' . $key);
    }

    /**
     */
    public function clear()
    {
        return $GLOBALS['session']->remove('horde', self::PREFIX . '/');
    }

}
