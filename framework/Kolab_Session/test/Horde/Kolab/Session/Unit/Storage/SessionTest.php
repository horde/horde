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
class Horde_Kolab_Session_Unit_Storage_SessionTest extends Horde_Kolab_Session_TestCase
{
    public function setUp()
    {
        $_SESSION = array();
    }

    public function tearDown()
    {
        unset($_SESSION['kolab_session']);
    }

    public function testLoad()
    {
        $_SESSION['kolab_session'] = array('data');
        $storage = new Horde_Kolab_Session_Storage_Session($session);
        $this->assertEquals($storage->load(), array('data'));
        
    }

    public function testEmpty()
    {
        $storage = new Horde_Kolab_Session_Storage_Session($session);
        $this->assertEquals($storage->load(), array());
        
    }

    public function testSave()
    {
        $storage = new Horde_Kolab_Session_Storage_Session($session);
        $storage->save(array('data'));
        $this->assertEquals($_SESSION['kolab_session'], array('data'));
    }
}