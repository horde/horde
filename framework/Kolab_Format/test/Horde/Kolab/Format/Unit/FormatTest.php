<?php
/**
 * Test the format entry point.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the format entry point.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Unit_FormatTest
extends PHPUnit_Framework_TestCase
{
    public function testFactory()
    {
        $this->assertInstanceOf(
            'Horde_Kolab_Format_Xml_Contact',
            Horde_Kolab_Format::factory('XML', 'contact')
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testFactoryException()
    {
        Horde_Kolab_Format::factory('UNKNOWN', 'contact');
    }


}
