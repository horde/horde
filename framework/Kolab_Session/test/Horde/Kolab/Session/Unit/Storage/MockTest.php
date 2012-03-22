<?php
/**
 * Test the mock storage driver.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test the mock storage driver.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */
class Horde_Kolab_Session_Unit_Storage_MockTest extends Horde_Kolab_Session_TestCase
{
    public function testMethodLoadHasResultBooleanFalse()
    {
        $storage = new Horde_Kolab_Session_Storage_Mock('test');
        $this->assertFalse($storage->load());
    }

    public function testMethodSaveHasPostconditionThatTheSessionDataWasSaved()
    {
        $array = array(1);
        $storage = new Horde_Kolab_Session_Storage_Mock('test');
        $storage->save($array);
        $this->assertSame($array, $storage->session);
    }
}