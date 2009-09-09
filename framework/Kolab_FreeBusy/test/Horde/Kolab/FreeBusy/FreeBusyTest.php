<?php
/**
 * Test the Kolab free/busy system.
 *
 * $Horde: framework/Kolab_FreeBusy/test/Horde/Kolab/FreeBusy/FreeBusyTest.php,v 1.13 2009/06/09 23:23:38 slusarz Exp $
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 *  We need the base class
 */
require_once 'Horde/Kolab/Test/Storage.php';

require_once 'Horde/Kolab/FreeBusy.php';

/**
 * Test the Kolab free/busy system.
 *
 * $Horde: framework/Kolab_FreeBusy/test/Horde/Kolab/FreeBusy/FreeBusyTest.php,v 1.13 2009/06/09 23:23:38 slusarz Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 * 
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_FreeBusyTest extends Horde_Kolab_Test_FreeBusy
{

    /**
     * Test setup.
     */
    public function setUp()
    {
        $world = $this->prepareBasicSetup();

        global $conf;
        $conf['kolab']['ldap']['phpdn'] = null;
        $conf['fb']['cache_dir']             = '/tmp';
        $conf['kolab']['freebusy']['server'] = 'https://fb.example.org/freebusy';
        $conf['fb']['use_acls'] = true;

        $this->assertTrue($world['auth']->authenticate('wrobel@example.org',
                                                        array('password' => 'none')));

        $folder = $world['storage']->getNewFolder();
        $folder->setName('Calendar');
        $this->assertNoError($folder->save(array('type' => 'event',
                                                 'default' => true)));

        $this->server = $world['server'];
        $this->auth = $world['auth'];
    }

    /**
     * Add an event.
     *
     * @return NULL
     */
    public function _addEvent($start)
    {
        include_once 'Horde/Kolab/Storage.php';

        $folder = Kolab_Storage::getShare('INBOX/Calendar', 'event');
        $data   = Kolab_Storage::getData($folder, 'event', 1);
        $object = array(
            'uid' => 1,
            'summary' => 'test',
            'start-date' => $start,
            'end-date' => $start + 120,
        );

        /* Add the event */
        $result = $data->save($object);
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->getMessage());
        }
    }

    /**
     * Test getting free/busy information.
     *
     * @return NULL
     */
    public function testFetch()
    {
        $start = time();

        $this->_addEvent($start);

        $_GET['folder'] = 'wrobel@example.org/Calendar';

        $fb = &new Horde_Kolab_FreeBusy();

        /** Trigger the free/busy cache update */
        $view = $fb->trigger();
        $this->assertTrue(is_a($view, 'Horde_Kolab_FreeBusy_View_vfb'));

        $vcal = $view->_data['fb'];

        $vfb = $vcal->findComponent('VFREEBUSY');
        $p = $vfb->getBusyPeriods();

        $this->assertTrue($p[$start] == $start + 120);
   }

    /**
     * Test triggering.
     *
     * @return NULL
     */
    public function testTrigger()
    {
        $_GET['folder'] = 'wrobel@example.org/Calendar';
        $_GET['extended'] = '1';

        $req_folder = Horde_Util::getFormData('folder', '');
        $access = &new Horde_Kolab_FreeBusy_Access();
        $result = $access->parseFolder($req_folder);
        $this->assertEquals('wrobel@example.org', $access->owner);

        $result = $this->server->uidForIdOrMailOrAlias($access->owner);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $result);

        $result = $this->server->fetch($result, 'Horde_Kolab_Server_Object_Kolab_User');
        $this->assertNoError($result);

        $fb = &new Horde_Kolab_FreeBusy();
        $view = $fb->trigger();
        $this->assertEquals('Horde_Kolab_FreeBusy_View_vfb', get_class($view));

        /** Test triggering an invalid folder */
        $_GET['folder'] = '';

        $fb = &new Horde_Kolab_FreeBusy();

        /** Trigger the free/busy cache update */
        $view = $fb->trigger();
        $this->assertTrue(is_a($view, 'Horde_Kolab_FreeBusy_View_error'));
        $this->assertEquals('No such folder ', $view->_data['error']->getMessage());
    }

    /**
     * Test triggering the folder of another user.
     *
     * @return NULL
     */
    public function testForeignTrigger()
    {
        $start = time();

        $this->_addEvent($start);

        $this->assertTrue($this->auth->authenticate('test@example.org',
                                                        array('password' => 'test')));

        $_GET['folder'] = 'wrobel@example.org/Calendar';
        $_GET['extended'] = '1';

        $fb = &new Horde_Kolab_FreeBusy();
        $view = $fb->trigger();
        $this->assertEquals('Horde_Kolab_FreeBusy_View_vfb', get_class($view));

        $vcal = $view->_data['fb'];

        $vfb = $vcal->findComponent('VFREEBUSY');
        $p = $vfb->getBusyPeriods();

        $this->assertTrue($p[$start] == $start + 120);
    }
}
