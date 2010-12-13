<?php
/**
 * Test the factory.
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
 * Test the factory.
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
class Horde_Kolab_Format_Unit_FactoryTest
extends PHPUnit_Framework_TestCase
{
    public function testFactory()
    {
        $factory = new Horde_Kolab_Format_Factory();
        $this->assertInstanceOf(
            'Horde_Kolab_Format_Xml_Contact',
            $factory->create('XML', 'contact')
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testFactoryException()
    {
        $factory = new Horde_Kolab_Format_Factory();
        $factory->create('UNKNOWN', 'contact');
    }
}
