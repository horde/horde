<?php
/**
 * Test the ParseError exception.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test the ParseError exception.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Unit_Exception_ParseErrorTest
extends PHPUnit_Framework_TestCase
{
    public function testParseError()
    {
        $exception = new Horde_Kolab_Format_Exception_ParseError('error');
        $this->assertEquals(
            "Failed parsing Kolab object input data of type string! Input was:\nerror",
            $exception->getMessage()
        );
    }

    public function testParseErrorInput()
    {
        $exception = new Horde_Kolab_Format_Exception_ParseError('error');
        $this->assertEquals(
            'error',
            $exception->getInput()
        );
    }

    public function testLongParseError()
    {
        $exception = new Horde_Kolab_Format_Exception_ParseError(
            'error67890error67890error67890error67890error67890error67890'
        );
        $this->assertEquals(
            "Failed parsing Kolab object input data of type string! Input was:\nerror67890error67890error67890error67890error67890... [shortened to 50 characters]",
            $exception->getMessage()
        );
    }
}
