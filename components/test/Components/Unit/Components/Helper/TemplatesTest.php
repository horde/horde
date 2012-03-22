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
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Components
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../Autoload.php';

/**
 * Test the template machinery.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Components
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Components
 */
class Components_Unit_Components_Helper_TemplatesTest
extends Components_TestCase
{
    public function testWrite()
    {
        $tdir =  $this->getTemporaryDirectory();
        $templates = new Components_Helper_Templates_Single(
            __DIR__ . '/../../../fixture/templates',
            $tdir,
            'simple',
            'target'
        );
        $templates->write();
        $this->assertTrue(file_exists($tdir . '/target'));
    }

    public function testSource()
    {
        $tdir =  $this->getTemporaryDirectory();
        $templates = new Components_Helper_Templates_Single(
            __DIR__ . '/../../../fixture/templates',
            $tdir,
            'simple',
            'target'
        );
        $templates->write();
        $this->assertEquals(
            "SIMPLE\n",
            file_get_contents($tdir . '/target')
        );
    }

    /**
     * @expectedException Components_Exception
     */
    public function testMissingSource()
    {
        $source = __DIR__ . '/NO_SUCH_TEMPLATE';
        $templates = new Components_Helper_Templates_Single($source, '', '', '');
    }

    public function testVariables()
    {
        $tdir =  $this->getTemporaryDirectory();
        $templates = new Components_Helper_Templates_Single(
            __DIR__ . '/../../../fixture/templates',
            $tdir,
            'variables',
            'target'
        );
        $templates->write(array('1' => 'One', '2' => 'Two'));
        $this->assertEquals(
            "One : Two\n",
            file_get_contents($tdir . '/target')
        );
    }

    public function testPhp()
    {
        $tdir =  $this->getTemporaryDirectory();
        $templates = new Components_Helper_Templates_Single(
            __DIR__ . '/../../../fixture/templates',
            $tdir,
            'php',
            'target'
        );
        $templates->write();
        $this->assertEquals(
            "test",
            file_get_contents($tdir . '/target')
        );
    }

    public function testInput()
    {
        $tdir =  $this->getTemporaryDirectory();
        $templates = new Components_Helper_Templates_Single(
            __DIR__ . '/../../../fixture/templates',
            $tdir,
            'input',
            'target'
        );
        $templates->write(array('input' => 'SOME INPUT'));
        $this->assertEquals(
            "SOME INPUT",
            file_get_contents($tdir . '/target')
        );
    }

    public function testDirectory()
    {
        $tdir =  $this->getTemporaryDirectory();
        $templates = new Components_Helper_Templates_Directory(
            __DIR__ . '/../../../fixture/templates/dir',
            $tdir
        );
        $templates->write(array('one' => 'One', 'two' => 'Two'));
        $this->assertEquals(
            "One",
            file_get_contents($tdir . '/one')
        );
        $this->assertEquals(
            "Two",
            file_get_contents($tdir . '/two')
        );
    }

    /**
     * @expectedException Components_Exception
     */
    public function testMissingDirectory()
    {
        new Components_Helper_Templates_Directory(
            __DIR__ . '/../../../fixture/templates/NOSUCHDIR',
            $this->getTemporaryDirectory()
        );
    }

    public function testMissingTargetDirectory()
    {
        $tdir =  $this->getTemporaryDirectory() . DIRECTORY_SEPARATOR
            . 'a' .'/b';
        $templates = new Components_Helper_Templates_Directory(
            __DIR__ . '/../../../fixture/templates/dir',
            $tdir
        );
        $templates->write(array('one' => 'One', 'two' => 'Two'));
        $this->assertEquals(
            "One",
            file_get_contents($tdir . '/one')
        );
        $this->assertEquals(
            "Two",
            file_get_contents($tdir . '/two')
        );
    }

    public function testTargetRewrite()
    {
        $tdir =  $this->getTemporaryDirectory();
        $templates = new Components_Helper_Templates_Directory(
            __DIR__ . '/../../../fixture/templates/rewrite',
            $tdir
        );
        $templates->write(array('one' => 'One'));
        $this->assertEquals(
            "One",
            file_get_contents($tdir . '/rewritten')
        );
    }

    public function testRecursiveDirectory()
    {
        $tdir =  $this->getTemporaryDirectory();
        $templates = new Components_Helper_Templates_RecursiveDirectory(
            __DIR__ . '/../../../fixture/templates/rec-dir',
            $tdir
        );
        $templates->write(array('one' => 'One', 'two' => 'Two'));
        $this->assertEquals(
            "One",
            file_get_contents($tdir . '/one/one')
        );
        $this->assertEquals(
            "Two",
            file_get_contents($tdir . '/two/two')
        );
    }

    /**
     * @expectedException Components_Exception
     */
    public function testMissingRecursiveDirectory()
    {
        new Components_Helper_Templates_RecursiveDirectory(
            __DIR__ . '/../../../fixture/templates/NOSUCHDIR',
            $this->getTemporaryDirectory()
        );
    }

    public function testMissingTargetRecursiveDirectory()
    {
        $tdir =  $this->getTemporaryDirectory() . DIRECTORY_SEPARATOR
            . 'a' .'/b';
        $templates = new Components_Helper_Templates_RecursiveDirectory(
            __DIR__ . '/../../../fixture/templates/rec-dir',
            $tdir
        );
        $templates->write(array('one' => 'One', 'two' => 'Two'));
        $this->assertEquals(
            "One",
            file_get_contents($tdir . '/one/one')
        );
        $this->assertEquals(
            "Two",
            file_get_contents($tdir . '/two/two')
        );
    }

}