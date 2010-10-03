<?php
/**
 * Test the Kolab auth handler.
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
 * Test the Kolab auth handler.
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
class Horde_Auth_Kolab_Class_KolabTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->markTestIncomplete('Needs some love');
        $this->session = $this->getMock('Horde_Kolab_Session');
        $this->factory = $this->getMock('Horde_Kolab_Session_Factory_Interface');

        if (!defined('HORDE_BASE')) {
            define('HORDE_BASE', '/nowhere');
        }
    }

    public function testMethodSetsessionHasParameterSession()
    {
        throw new PHPUnit_Framework_IncompleteTestError('Horde_Auth_Kolab::setSession() does not exist.');
        $auth = new Horde_Auth_Kolab();
        $auth->setSession($this->session);
    }

    public function testMethodGetsessionHasResultSession()
    {
        throw new PHPUnit_Framework_IncompleteTestError('Horde_Auth_Kolab::setSession() does not exist.');
        $auth = new Horde_Auth_Kolab();
        $auth->setSession($this->session);
        $this->assertType(
            'Horde_Kolab_Session',
            $auth->getSession('user', array('password' => 'test'))
        );
    }

    public function testMethodGetsessionHasResultSessionFromTheFactoryIfTheSessionWasUnset()
    {
        $auth = new Horde_Auth_Kolab();
        throw new PHPUnit_Framework_IncompleteTestError('Horde_Auth_Kolab::setSessionFactory() does not exist.');
        $auth->setSessionFactory($this->factory);
        $this->factory->expects($this->once())
            ->method('getSession')
            ->with('user', array('password' => 'test'))
            ->will($this->returnValue($this->session));
        $this->assertType(
            'Horde_Kolab_Session',
            $auth->getSession('user', array('password' => 'test'))
        );
    }

    public function testMethodAuthenticateHasResultBooleanTrueIfTheConnectionWasSuccessful()
    {
        $auth = new Horde_Auth_Kolab();
        throw new PHPUnit_Framework_IncompleteTestError('Horde_Auth_Kolab::setSessionFactory() does not exist.');
        $auth->setSessionFactory($this->factory);
        $this->factory->expects($this->once())
            ->method('getSession')
            ->with('user', array('password' => 'test'))
            ->will($this->returnValue($this->session));
        $this->assertTrue(
            $auth->authenticate('user', array('password' => 'test'), false)
        );
    }

    public function testMethodAuthenticateHasPostconditionThatTheUserIdIsBeingRewrittenIfRequired()
    {
        $auth = new Horde_Auth_Kolab();
        throw new PHPUnit_Framework_IncompleteTestError('Horde_Auth_Kolab::setSessionFactory() does not exist.');
        $auth->setSessionFactory($this->factory);
        $this->factory->expects($this->once())
            ->method('getSession')
            ->with('user', array('password' => 'test'))
            ->will($this->returnValue($this->session));
        $this->session->expects($this->once())
            ->method('getMail')
            ->will($this->returnValue('mail@example.org'));
        /* Untestable with the way the Auth driver is currently structured */
        $auth->authenticate('user', array('password' => 'test'), false);
    }

    public function testMethodAuthenticateThrowsExceptionIfTheLoginFailed()
    {
        $auth = new Horde_Auth_Kolab();
        throw new PHPUnit_Framework_IncompleteTestError('Horde_Auth_Kolab::setSessionFactory() does not exist.');
        $auth->setSessionFactory($this->factory);
        $this->factory->expects($this->once())
            ->method('getSession')
            ->with('user', array('password' => 'test'))
            ->will($this->throwException(new Horde_Kolab_Session_Exception('Error')));
        $auth->authenticate('user', array('password' => 'test'), false);
        $this->assertEquals(Horde_Auth::REASON_FAILED, Horde_Auth::getAuthError());
    }

    public function testMethodAuthenticateThrowsExceptionIfTheCredentialsWereInvalid()
    {
        $auth = new Horde_Auth_Kolab();
        throw new PHPUnit_Framework_IncompleteTestError('Horde_Auth_Kolab::setSessionFactory() does not exist.');
        $auth->setSessionFactory($this->factory);
        $this->factory->expects($this->once())
            ->method('getSession')
            ->with('user', array('password' => 'test'))
            ->will($this->throwException(new Horde_Kolab_Session_Exception_Badlogin('Error')));
        $auth->authenticate('user', array('password' => 'test'), false);
        $this->assertEquals(Horde_Auth::REASON_BADLOGIN, Horde_Auth::getAuthError());
    }
}
