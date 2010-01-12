<?php
/**
 * Test the user object.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Koward
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Koward
 */

/**
 * Initialize testing for this application.
 */
require_once 'TestInit.php';

/**
 * Test the user object.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Koward
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Koward
 */
class Koward_KowardTest extends Koward_Test
{
    /**
     * Set up testing.
     *
     * @return NULL
     */
    protected function setUp()
    {
        $world = $this->prepareBasicSetup();

        $this->koward = Koward_Koward::singleton();
    }

    /**
     * Verify that the Koward object ist initialized correctly.
     *
     * @return NULL
     */
    public function testSetup()
    {
        $this->assertType('Horde_Kolab_Server', $this->koward->server);
    }

    /**
     * Verify that we can fetch objects from the Kolab server.
     *
     * @return NULL
     */
    public function testFetching()
    {
        $this->assertType('Horde_Kolab_Server_Object', $this->koward->getObject('cn=Gunnar Wrobel,dc=example,dc=org'));
    }

    /**
     * Verify token processing mechanisms.
     *
     * @return NULL
     */
    public function testToken()
    {
        // Get the token.
        $token = $this->koward->getRequestToken('test');
        // Checking it should be fine.
        $this->koward->checkRequestToken('test', $token);
        // Now we set the token to a value that will be considered a timeout.
        $_SESSION['horde_form_secrets'][$token] = time() - 100000;
        try {
            $this->koward->checkRequestToken('test', $token);
            $this->fail('The rquest token is still valid which was not expected.');
        } catch (Horde_Exception $e) {
            $this->assertContains(_("This request cannot be completed because the link you followed or the form you submitted was only valid for"), $e->getMessage());
        }
        // Now we remove the token
        unset($_SESSION['horde_form_secrets'][$token]);
        try {
            $this->koward->checkRequestToken('test', $token);
            $this->fail('The rquest token is still valid which was not expected.');
        } catch (Horde_Exception $e) {
            $this->assertEquals(_("We cannot verify that this request was really sent by you. It could be a malicious request. If you intended to perform this action, you can retry it now."), $e->getMessage());
        }
    }
}
