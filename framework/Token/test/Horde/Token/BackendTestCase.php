<?php
/**
 * Tests that each backend should fulfil.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Token
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Token
 */

/**
 * Tests that each backend should fulfil.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Token
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Token
 */
abstract class Horde_Token_BackendTestCase extends PHPUnit_Framework_TestCase
{
    public function testToken()
    {
        $this->assertEquals(51, strlen($this->_getBackend()->get()));
    }

    public function testValidation()
    {
        $t = $this->_getBackend();
        $this->assertTrue($t->isValid($t->get()));
    }

    public function testValidationWithSeed()
    {
        $t = $this->_getBackend();
        $this->assertTrue($t->isValid($t->get('a'), 'a'));
    }

    public function testInvalidToken()
    {
        $t = $this->_getBackend();
        $this->assertFalse($t->isValid('something'));
    }

    public function testInvalidEmptyToken()
    {
        $this->assertFalse($this->_getBackend()->isValid(''));
    }

    public function testInvalidSeed()
    {
        $t = $this->_getBackend();
        $this->assertFalse($t->isValid($t->get('a'), 'b'));
    }

    public function testActiveToken()
    {
        $t = $this->_getBackend();
        $this->assertTrue($t->isValid($t->get('a'), 'a', 10));
    }

    public function testImmediateTimeout()
    {
        $t = $this->_getBackend();
        $this->assertFalse($t->isValid($t->get('a'), 'a', 0));
    }

    public function testTimeoutAfterOneSecond()
    {
        $t = $this->_getBackend(array('token_lifetime' => 1));
        $token = $t->get('a');
        sleep(1);
        $this->assertFalse($t->isValid($token, 'a', 1));
        // Pack two assertions in this test to avoid sleeping twice
        $this->assertFalse($t->isValid($token, 'a'));
    }

    public function testTokenLifetimeParameter()
    {
        $t = $this->_getBackend(array('token_lifetime' => -1));
        $this->assertTrue($t->isValid($t->get()));
    }

    public function testUniqueToken()
    {
        $t = $this->_getBackend();
        $token = $t->get('a');
        $t->isValid($token, 'a', -1, true);
        $this->assertFalse($t->isValid($token, 'a', -1, true));
    }

    public function testNonces()
    {
        $t = $this->_getBackend();
        $this->assertEquals(6, strlen($t->getNonce()));
    }

    /**
     * @expectedException Horde_Token_Exception_Invalid
     */
    public function testInvalidTokenException()
    {
        $t = $this->_getBackend();
        $t->validate('something');
    }

    /**
     * @expectedException Horde_Token_Exception_Invalid
     */
    public function testInvalidSeedException()
    {
        $t = $this->_getBackend();
        $t->validate($t->get('a'), 'b');
    }

    /**
     * @expectedException Horde_Token_Exception_Expired
     */
    public function testTimeoutException()
    {
        $t = $this->_getBackend(array('token_lifetime' => 1));
        $token = $t->get('a');
        sleep(1);
        $t->validate($token, 'a');
    }

    public function testIsValidUnique()
    {
        $t = $this->_getBackend();
        $token = $t->get('a');
        $this->assertNull($t->validateUnique($token, 'a'));
    }

    /**
     * @expectedException Horde_Token_Exception_Used
     */
    public function testIsValidAndUnusedException()
    {
        $t = $this->_getBackend();
        $token = $t->get('a');
        $t->validateUnique($token, 'a');
        $t->validateUnique($token, 'a');
    }

    /**
     * @expectedException Horde_Token_Exception_Used
     */
    public function testIsValidAndValidateException()
    {
        $t = $this->_getBackend();
        $token = $t->get('a');
        $t->isValid($token, 'a', null, true);
        $t->validateUnique($token, 'a');
    }

    abstract protected function _getBackend(array $params = array());
}