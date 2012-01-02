<?php
/**
 * Generates the test permission service.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 */

/**
 * Generates the test permission service.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @since Horde_Test 1.2.0
 *
 * @category Horde
 * @package  Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 */
class Horde_Test_Factory_Perms
{
    /**
     * Create a null permission service for testing.
     *
     * @return Horde_Perms_Null The test service.
     */
    public function create()
    {
        if (!class_exists('Horde_Perms_Null')) {
            throw new Horde_Test_Exception('The "Horde_Perms_Null" class is unavailable!');
        }
        return new Horde_Perms_Null();
    }
}