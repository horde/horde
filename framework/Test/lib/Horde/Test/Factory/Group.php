<?php
/**
 * Generates test group services.
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
 * Generates test group services.
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
class Horde_Test_Factory_Group
{
    /**
     * Create a mock group handler for testing.
     *
     * @return Horde_Group_Mock The mock service.
     */
    public function create()
    {
        if (!class_exists('Horde_Group_Mock')) {
            throw new Horde_Test_Exception('The "Horde_Group_Mock" class is unavailable!');
        }
        return new Horde_Group_Mock();
    }
}