<?php
/**
 * Generates an alarm setup for the test situation.
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
 * Generates an alarm setup for the test situation.
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
class Horde_Test_Factory_Alarm
{
    /**
     * Create a mock alarm system for testing.
     *
     * @return Horde_Alarm_Null The mock alarm system.
     */
    public function create()
    {
        if (!class_exists('Horde_Alarm')) {
            throw new Horde_Test_Exception('The "Horde_Alarm" class is unavailable!');
        }
        return new Horde_Alarm_Null();
    }
}