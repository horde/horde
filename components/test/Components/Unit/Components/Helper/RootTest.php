<?php
/**
 * Test the root helper.
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
 * Test the root helper.
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
class Components_Unit_Components_Helper_RootTest
extends Components_TestCase
{
    /**
     * @expectedException Components_Exception
     */
    public function testInvalidCwd()
    {
        $this->changeDirectory('/');
        $root = new Components_Helper_Root();
        $root->getRoot();
    }

    public function testValidCwd()
    {
        $path = dirname(__FILE__) . '/../../../fixture';
        $this->changeDirectory($path);
        $root = new Components_Helper_Root();
        $this->assertEquals(realpath($path), realpath($root->getRoot()));
    }

    public function testValidSubCwd()
    {
        $path = dirname(__FILE__) . '/../../../fixture';
        $this->changeDirectory($path . '/horde');
        $root = new Components_Helper_Root();
        $this->assertEquals(realpath($path), realpath($root->getRoot()));
    }

    /**
     * @expectedException Components_Exception
     */
    public function testInvalidPath()
    {
        $this->changeDirectory('/');
        $root = new Components_Helper_Root('/');
        $root->getRoot();
    }

    public function testDetermineRootInTestFixture()
    {
        $path = dirname(__FILE__) . '/../../../fixture';
        $root = new Components_Helper_Root($path);
        $this->assertEquals($path, $root->getRoot());
    }

    public function testDetermineRootInSubdirectory()
    {
        $path = dirname(__FILE__) . '/../../../fixture';
        $root = new Components_Helper_Root($path . '/horde');
        $this->assertEquals($path, $root->getRoot());
    }

    /**
     * @expectedException Components_Exception
     */
    public function testInvalidOption()
    {
        $this->changeDirectory('/');
        $root = new Components_Helper_Root(
            null, null, array('horde_root' => '/')
        );
        $root->getRoot();
    }

    public function testDetermineRootViaOption()
    {
        $path = dirname(__FILE__) . '/../../../fixture';
        $root = new Components_Helper_Root(
            null, null, array('horde_root' => $path)
        );
        $this->assertEquals($path, $root->getRoot());
    }

    /**
     * @expectedException Components_Exception
     */
    public function testDetermineRootViaOptionSubdirectory()
    {
        $this->changeDirectory('/');
        $path = dirname(__FILE__) . '/../../../fixture';
        $root = new Components_Helper_Root(
            null, null, array('horde_root' => $path . '/horde')
        );
        $root->getRoot();
    }

    /**
     * @expectedException Components_Exception
     */
    public function testInvalidComponent()
    {
        $this->changeDirectory('/');
        $root = new Components_Helper_Root(null, $this->getComponent('/'));
        $root->getRoot();
    }

    public function testDetermineRootViaComponent()
    {
        $path = dirname(__FILE__) . '/../../../fixture';
        $root = new Components_Helper_Root(
            null, $this->getComponent($path . '/framework/Install')
        );
        $this->assertEquals($path, $root->getRoot());
    }

}