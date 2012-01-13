<?php
/**
 * Generates test cache.
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
 * Generates test cache.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @since Horde_Test 1.3.0
 *
 * @category Horde
 * @package  Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 */
class Horde_Test_Factory_Cache
{
    /**
     * Create a mock cache for testing.
     *
     * @return Horde_Cache The mock cache.
     */
    public function create()
    {
        if (!class_exists('Horde_Cache')) {
            throw new Horde_Test_Exception('The "Horde_Cache" class is unavailable!');
        }
        return new Horde_Cache(new Horde_Cache_Storage_Mock());
    }
}