<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2013-2014 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Tests for the Horde_HashTable cache driver.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2013-2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Cache_HashtableTest
extends Horde_Imap_Client_Cache_TestBase
{
    protected function _getBackend()
    {
        $factory_hashtable = new Horde_Test_Factory_Hashtable();

        try {
            return new Horde_Imap_Client_Cache_Backend_Hashtable(array(
                'hashtable' => $factory_hashtable->create()
            ));
        } catch (Horde_Test_Exception $e) {
            $this->markTestSkipped('HashTable not available.');
        }
    }

}
