<?php
/**
 * Stub for testing the Registry object.
 * Needed because we need to access a protected method.
 *
 * PHP version 5
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 * @package  Registry
 */

/**
 * Stub for testing the Registry object.
 * Needed because we need to access a protected method.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 * @package  Registry
 */
class Horde_Core_Stub_Registry extends Horde_Registry
{
    /**
     */
    public function __construct($session_flags = 0, array $args = array())
    {
    }

    /**
     */
    public function mergeConfig(array $a1, array $a2)
    {
        return $this->_mergeConfig($a1, $a2);
    }

}
