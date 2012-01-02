<?php
/**
 * Generates registry services for testing purposes.
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
 * Generates registry services for testing purposes.
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
class Horde_Test_Factory_Registry
{
    /**
     * Create a stub registry service for testing.
     *
     * @params array $params Additional options.
     * <pre>
     * 'app' - (string) The application name.
     * 'user' - (string) The current user.
     * </pre>
     *
     * @return Horde_Test_Stub_Registry The test registry.
     */
    public function create($params)
    {
        return new Horde_Test_Stub_Registry($params['user'], $params['app']);
    }
}