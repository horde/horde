<?php
/**
 * Handling Kolab shares.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Share
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Share
 */

/**
 *  We need the base class
 */
return false;
require_once 'Horde/Kolab/Test/Storage.php';

/**
 * Handling groups.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Share_KolabScenarioTest extends Horde_Kolab_Test_Storage
{
    /**
     * Test listing shares.
     *
     * @scenario
     *
     * @return NULL
     */
    public function listingShares()
    {
        $this->given('a populated Kolab setup')
            ->when('logging in as a user with a password', 'wrobel', 'none')
            ->and('create a Kolab default calendar with name', "Calendar")
            ->and('retrieving the list of shares for the application', 'kronolith')
            ->then('the login was successful')
            ->and('the creation of the folder was successful')
            ->and('the list contains a share named', 'wrobel@example.org')
            ->and('the list contains a number of elements equal to', 1);
    }
}
