<?php
/**
 * Test the file based token backend.
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
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the file based token backend.
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
class Horde_Token_Unit_FileTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        if (!empty($this->_temp_dir)) {
            $this->_rrmdir($this->_temp_dir);
        }
    }

    public function testToken()
    {
        $t = new Horde_Token_File(array('secret' => 'abc'));
        $this->assertEquals(51, strlen($t->get()));
    }

    public function testValidation()
    {
        $t = new Horde_Token_File(array('secret' => 'abc'));
        $this->assertTrue($t->isValid($t->get()));
    }

    public function testValidationWithSeed()
    {
        $t = new Horde_Token_File(array('secret' => 'abc'));
        $this->assertTrue($t->isValid($t->get('a'), 'a'));
    }

    public function testInvalidToken()
    {
        $t = new Horde_Token_File(array('secret' => 'abc'));
        $this->assertFalse($t->isValid('something'));
    }

    public function testInvalidEmptyToken()
    {
        $t = new Horde_Token_File(array('secret' => 'abc'));
        $this->assertFalse($t->isValid(''));
    }

    public function testInvalidSeed()
    {
        $t = new Horde_Token_File(array('secret' => 'abc'));
        $this->assertFalse($t->isValid($t->get('a'), 'b'));
    }

    public function testActiveToken()
    {
        $t = new Horde_Token_File(array('secret' => 'abc'));
        $this->assertTrue($t->isValid($t->get('a'), 'a', 10));
    }

    public function testImmediateTimeout()
    {
        $t = new Horde_Token_File(array('secret' => 'abc'));
        $this->assertFalse($t->isValid($t->get('a'), 'a', 0));
    }

    public function testTimeoutAfterOneSecond()
    {
        $t = new Horde_Token_File(
            array(
                'secret' => 'abc',
                'token_lifetime' => 1
            )
        );
        $token = $t->get('a');
        sleep(1);
        $this->assertFalse($t->isValid($token, 'a', 1));
        // Pack two assertions in this test to avoid sleeping twice
        $this->assertFalse($t->isValid($token, 'a'));
    }

    public function testTokenLifetimeParameter()
    {
        $t = new Horde_Token_File(
            array(
                'secret' => 'abc',
                'token_lifetime' => -1
            )
        );
        $this->assertTrue($t->isValid($t->get()));
    }

    public function testUniqueToken()
    {
        $t = new Horde_Token_File(
            array(
                'secret' => 'abc',
                'token_dir' => $this->_getTemporaryDirectory()
            )
        );
        $token = $t->get('a');
        $t->isValid($token, 'a', -1, true);
        $this->assertFalse($t->isValid($token, 'a', -1, true));
    }

    public function testNonces()
    {
        $t = new Horde_Token_File(array('secret' => 'abc'));
        $this->assertEquals(6, strlen($t->getNonce()));
    }

    /**
     * @expectedException Horde_Token_Exception_Invalid
     */
    public function testInvalidTokenException()
    {
        $t = new Horde_Token_File(array('secret' => 'abc'));
        $t->validate('something');
    }

    /**
     * @expectedException Horde_Token_Exception_Invalid
     */
    public function testInvalidSeedException()
    {
        $t = new Horde_Token_File(array('secret' => 'abc'));
        $t->validate($t->get('a'), 'b');
    }

    /**
     * @expectedException Horde_Token_Exception_Expired
     */
    public function testTimeoutException()
    {
        $t = new Horde_Token_File(
            array(
                'secret' => 'abc',
                'token_lifetime' => 1
            )
        );
        $token = $t->get('a');
        sleep(1);
        $t->validate($token, 'a');
    }

    /**
     * @expectedException Horde_Token_Exception_Used
     */
    public function testIsValidAndUnusedException()
    {
        $t = new Horde_Token_File(
            array(
                'secret' => 'abc',
                'token_dir' => $this->_getTemporaryDirectory()
            )
        );
        $token = $t->get('a');
        $t->validateUnique($token, 'a');
        $t->validateUnique($token, 'a');
    }

    /**
     * @expectedException Horde_Token_Exception
     */
    public function testInvalidConstruction()
    {
        $t = new Horde_Token_File();
    }

    private function _getTemporaryDirectory()
    {
        $this->_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR
            . 'Horde_Token_' . mt_rand();
        mkdir($this->_temp_dir);
        return $this->_temp_dir;
    }

    private function _rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (filetype($dir . DIRECTORY_SEPARATOR . $object) == 'dir') {
                        $this->_rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                    } else {
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }
}