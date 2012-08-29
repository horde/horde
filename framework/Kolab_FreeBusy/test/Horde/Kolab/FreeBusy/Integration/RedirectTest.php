<?php
/**
 * Test redirection to remote systems.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Autoload.php';

/**
 * Test redirection to remote systems.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Integration_RedirectTest
extends Horde_Kolab_FreeBusy_TestCase
{
    public function testFetchRedirection()
    {
        $output = $this->dispatch(
            '/freebusy/remote@example.com.xfb',
            array(
                'writer' => array(
                    'class' => 'Horde_Controller_ResponseWriter_WebDebug'
                ),
                'provider' => array('redirect' => true)
            )
        );
        $this->assertContains('Location: https://example.com/freebusy//freebusy/remote@example.com.xfb', $output);
    }

    public function testFetchPassThrough()
    {
        $output = $this->dispatch(
            '/freebusy/remote@example.com.xfb',
            array(
                'writer' => array(
                    'class' => 'Horde_Controller_ResponseWriter_WebDebug'
                ),
            ),
            array(
                'Horde_Http_Client' => $this->getHttpClient(
                    'RESPONSE'
                )
            )
        );
        $this->assertContains('RESPONSE', $output);
    }
}