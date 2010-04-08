<?php
/**
 * Test the anonymous decorator with the Kolab session handler base
 * implementation.
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
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the anonymous decorator with the Kolab session handler base
 * implementation.
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
class Horde_Kolab_Session_Integration_AnonymousTest extends Horde_Kolab_Session_SessionTestCase
{
    public function testMethodConnectHasPostconditionThatTheConnectionHasBeenEstablishedAsAnonymousUserIfRequired()
    {
        $user = $this->getMock(
            'Horde_Kolab_Server_Object_Hash', array(), array(), '', false, false
        );
        $user->expects($this->exactly(5))
            ->method('getSingle')
            ->will($this->returnValue('anonymous@example.org'));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($user));
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        $anonymous = new Horde_Kolab_Session_Decorator_Anonymous(
            $session, 'anonymous', 'pass'
        );
        $anonymous->connect();
        $this->assertEquals('anonymous@example.org', $anonymous->getMail());
    }
}