<?php
/**
 * Generates the history service for testing purposes.
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
 * Generates the history service for testing purposes.
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
class Horde_Test_Factory_History
{
    /**
     * Create a mock history service for testing.
     *
     * @params array $params Additional options.
     * <pre>
     * 'user' - (string) The current user.
     * </pre>
     *
     * @return Horde_History The test service.
     */
    public function create($params)
    {
        if (!class_exists('Horde_History')) {
            throw new Horde_Test_Exception('The "Horde_History" class is unavailable!');
        }
        return new Horde_History_Mock($params['user']);
    }
}