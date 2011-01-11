<?php
/**
 * Test the template machinery.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Components
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Components
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the template machinery.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Components
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Components
 */
class Components_Unit_Components_Helper_TemplatesTest
extends Components_TestCase
{
    public function testWrite()
    {
        $tdir =  $this->getTemporaryDirectory();
        $templates = new Components_Helper_Templates_Single(
            dirname(__FILE__) . '/../../../fixture/templates',
            $tdir,
            'simple',
            'target'
        );
        $templates->write();
        $this->assertTrue(file_exists($tdir . DIRECTORY_SEPARATOR . 'target'));
    }

    public function testSource()
    {
        $tdir =  $this->getTemporaryDirectory();
        $templates = new Components_Helper_Templates_Single(
            dirname(__FILE__) . '/../../../fixture/templates',
            $tdir,
            'simple',
            'target'
        );
        $templates->write();
        $this->assertEquals(
            "SIMPLE\n",
            file_get_contents($tdir . DIRECTORY_SEPARATOR . 'target')
        );
    }

    /**
     * @expectedException Components_Exception
     */
    public function testMissingSource()
    {
        $source = dirname(__FILE__) . '/NO_SUCH_TEMPLATE';
        $templates = new Components_Helper_Templates_Single($source, '', '', '');
    }

    public function testVariables()
    {
        $tdir =  $this->getTemporaryDirectory();
        $templates = new Components_Helper_Templates_Single(
            dirname(__FILE__) . '/../../../fixture/templates',
            $tdir,
            'variables',
            'target'
        );
        $templates->write(array('1' => 'One', '2' => 'Two'));
        $this->assertEquals(
            "One : Two\n",
            file_get_contents($tdir . DIRECTORY_SEPARATOR . 'target')
        );
    }

    public function testPrefix()
    {
        $tdir =  $this->getTemporaryDirectory();
        $templates = new Components_Helper_Templates_Prefix(
            dirname(__FILE__) . '/../../../fixture/templates',
            $tdir,
            'var',
            'target'
        );
        $templates->write(array('1' => 'One', '2' => 'Two'));
        $this->assertEquals(
            "One : Two\n",
            file_get_contents($tdir . DIRECTORY_SEPARATOR . 'target')
        );
    }

    /**
     * @expectedException Components_Exception
     */
    public function testMissingPrefixTemplate()
    {
        $tdir =  $this->getTemporaryDirectory();
        $templates = new Components_Helper_Templates_Prefix(
            dirname(__FILE__) . '/../../../fixture/templates',
            $tdir,
            'NOSUCHPREFIX',
            'target'
        );
    }

    public function testPhp()
    {
        $tdir =  $this->getTemporaryDirectory();
        $templates = new Components_Helper_Templates_Single(
            dirname(__FILE__) . '/../../../fixture/templates',
            $tdir,
            'php',
            'target'
        );
        $templates->write();
        $this->assertEquals(
            "test",
            file_get_contents($tdir . DIRECTORY_SEPARATOR . 'target')
        );
    }

    public function testInput()
    {
        $tdir =  $this->getTemporaryDirectory();
        $templates = new Components_Helper_Templates_Single(
            dirname(__FILE__) . '/../../../fixture/templates',
            $tdir,
            'input',
            'target'
        );
        $templates->write(array('input' => 'SOME INPUT'));
        $this->assertEquals(
            "SOME INPUT",
            file_get_contents($tdir . DIRECTORY_SEPARATOR . 'target')
        );
    }
}