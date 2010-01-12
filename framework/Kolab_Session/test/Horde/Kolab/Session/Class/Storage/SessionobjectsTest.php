<?php
/**
 * Test the sessionobjects storage driver.
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
 * Test the sessionobjects storage driver.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Session_Class_Storage_SessionobjectsTest extends Horde_Kolab_Session_SessionTestCase
{
    public function testMethodLoadHasResultQueriedObject()
    {
        $session_objects = $this->getMock('Horde_SessionObjects', array(), array(), '', false, false);
        $session_objects->expects($this->once())
            ->method('query')
            ->with('kolab_session');
        $storage = new Horde_Kolab_Session_Storage_Sessionobjects($session_objects);
        $storage->load();
    }

    public function testMethodSaveHasPostconditionThatTheSessionDataWasSaved()
    {
        $session_objects = $this->getMock('Horde_SessionObjects', array(), array(), '', false, false);
        $session_objects->expects($this->once())
            ->method('overwrite')
            ->with('kolab_session', $this->isInstanceOf('Horde_Kolab_Session_Interface'));
        $session = $this->getMock('Horde_Kolab_Session_Interface');
        $storage = new Horde_Kolab_Session_Storage_Sessionobjects($session_objects);
        $storage->save($session);
    }
}