<?php
/**
 * Test the user object.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Koward
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Koward
 */

/**
 * Test the user object.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Koward
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Koward
 */
class Koward_Test_Server_UserTest extends Horde_Kolab_Test_Server {

    /**
     * Test listing users if there are no users.
     *
     * @scenario
     *
     * @return NULL
     */
    public function listingUsersOnEmptyServer()
    {
        $this->given('the current Kolab server')
            ->when('listing all users')
            ->then('the list is an empty array');
    }
}
