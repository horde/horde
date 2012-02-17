<?php
/**
 * Generates preferences services for testing purposes.
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
 * Generates preferences services for testing purposes.
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
class Horde_Test_Factory_Prefs
{
    /**
     * Create a null preferences service for testing.
     *
     * @params array $params Additional options.
     * <pre>
     * 'app' - (string) The application name.
     * 'user' - (string) The current user.
     * </pre>
     *
     * @return Horde_Prefs The test service.
     */
    public function create($params)
    {
        if (!class_exists('Horde_Prefs')) {
            throw new Horde_Test_Exception('The "Horde_Prefs" class is unavailable!');
        }
        return new Horde_Prefs($params['app'], new Horde_Prefs_Storage_Null($params['user']));
    }
}