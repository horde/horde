<?php
/**
 * Test the mock storage driver.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test the mock storage driver.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
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