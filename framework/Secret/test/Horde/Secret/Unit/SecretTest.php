<?php
/**
 * Test the secret class.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Secret
 * @subpackage UnitTests
 * @author     Michael Slusarz <slusarz@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Secret
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the secret class.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Secret
 * @subpackage UnitTests
 * @author     Michael Slusarz <slusarz@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Secret
 */

class Horde_Secret_Unit_SecretTest extends PHPUnit_Framework_TestCase
{
    public function test8BitKey()
    {
        $secret = new Horde_Secret();

        $key = "\x88";
        $plaintext = "\x01\x01\x01\x01\x01\x01\x01\x01";

        $this->assertEquals($plaintext, $secret->read($key, $secret->write($key, $plaintext)));
    }

    public function test64BitKey()
    {
        $secret = new Horde_Secret();

        $key = "\x00\x00\x00\x00\x00\x00\x00\x00";
        $plaintext = "\x01\x01\x01\x01\x01\x01\x01\x01";

        $this->assertEquals($plaintext, $secret->read($key, $secret->write($key, $plaintext)));
    }

    public function test128BitKey()
    {
        $secret = new Horde_Secret();

        $key = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F";
        $plaintext = "\x01\x01\x01\x01\x01\x01\x01\x01";

        $this->assertEquals($plaintext, $secret->read($key, $secret->write($key, $plaintext)));
    }

}
