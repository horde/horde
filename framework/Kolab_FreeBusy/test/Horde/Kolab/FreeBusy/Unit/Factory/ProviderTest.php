<?php
/**
 * Test the factory for the data provider.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test the factory for the data provider.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Unit_Factory_ProviderTest
extends Horde_Kolab_FreeBusy_TestCase
{
    public function testRemote()
    {
        $this->assertInstanceOf(
            'Horde_Kolab_FreeBusy_Provider_Remote_PassThrough',
            $this->getRemoteProvider()
        );
    }

    public function testRemoteRedirect()
    {
        $this->assertInstanceOf(
            'Horde_Kolab_FreeBusy_Provider_Remote_Redirect',
            $this->getRemoteProvider(array('redirect' => true))
        );
    }

    public function testLocal()
    {
        $this->assertInstanceOf(
            'Horde_Kolab_FreeBusy_Provider_Local',
            $this->getLocalProvider()
        );
    }

    public function testRemoteAsLocal()
    {
        $this->assertInstanceOf(
            'Horde_Kolab_FreeBusy_Provider_Local',
            $this->getRemoteProvider(
                array('server' => 'https://example.com/freebusy')
            )
        );
    }

    public function testRemoteLog()
    {
        $this->getRemoteProvider(array('logger' => $this->getMockLogger()));
        $this->assertLogContains(
            "URL \"https://example.com/freebusy\" indicates remote free/busy server since we only offer \"https://localhost/export\". Redirecting."
        );
    }
}