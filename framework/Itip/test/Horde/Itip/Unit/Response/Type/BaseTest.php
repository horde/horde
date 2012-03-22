<?php
/**
 * Test the base response definition.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Itip
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Itip
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../Autoload.php';

/**
 * Test the base response definition.
 *
 * Copyright 2010 Kolab Systems AG
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Itip
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Itip
 */
class Horde_Itip_Unit_Response_Type_BaseTest
extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Horde_Itip_Exception
     */
    public function testExceptionOnUndefinedRequest()
    {
        $type = new Horde_Itip_Response_Type_Accept(
            new Horde_Itip_Resource_Base('', '')
        );
        $type->getRequest();
    }
}
