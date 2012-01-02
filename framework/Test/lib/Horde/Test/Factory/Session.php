<?php
/**
 * Generates a dummy session.
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
 * Generates a dummy session.
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
class Horde_Test_Factory_Session
{
    /**
     * Create a mock session for testing.
     *
     * @return Horde_Session The mock session.
     */
    public function create()
    {
        if (!class_exists('Horde_Session')) {
            throw new Horde_Test_Exception('The "Horde_Session" class is unavailable!');
        }
        $session = new Horde_Session();
        $session->sessionHandler = new Horde_SessionHandler(
            new Horde_SessionHandler_Storage_Builtin()
        );
        return $session;
    }
}