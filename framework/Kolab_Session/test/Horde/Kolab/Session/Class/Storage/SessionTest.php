<?php
/**
 * Test the session based storage driver.
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
 * Test the session based storage driver.
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
class Horde_Kolab_Session_Class_Storage_SessionTest extends Horde_Kolab_Session_SessionTestCase
{
    public function testMethodLoadHasResultQueriedObject()
    {
        $session = $this->getMock('Horde_Session', array(), array(), '', false, false);
        $session->expects($this->once())
            ->method('get')
            ->with('horde', 'kolab_session');
        $storage = new Horde_Kolab_Session_Storage_Session($session);
        $storage->load();
    }

    public function testMethodSaveHasPostconditionThatTheSessionDataWasSaved()
    {
        $session = $this->getMock('Horde_Session', array(), array(), '', false, false);
        $session->expects($this->once())
            ->method('set')
            ->with('horde', 'kolab_session', $this->isInstanceOf('Horde_Kolab_Session'));
        $kolab_session = $this->getMock('Horde_Kolab_Session');
        $storage = new Horde_Kolab_Session_Storage_Session($session);
        $storage->save($kolab_session);
    }
}