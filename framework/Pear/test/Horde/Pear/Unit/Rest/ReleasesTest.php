<?php
/**
 * Test the releases parser.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Pear
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Pear
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test the releases parser.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Pear
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Unit_Rest_ReleasesTest
extends Horde_Pear_TestCase
{
    public function testCount()
    {
        $rl = $this->_getReleases();
        $this->assertEquals(5, count($rl));
    }

    public function testVersion()
    {
        $rl = $this->_getReleases();
        $this->assertEquals('1.2.0', (string)$rl->r[0]->v);
    }

    public function testStability()
    {
        $rl = $this->_getReleases();
        $this->assertEquals('stable', (string)$rl->r[0]->s);
    }

    public function testGetReleases()
    {
        $this->assertEquals(
            array(
                '1.0.0' => 'stable',
                '1.0.0alpha1' => 'alpha',
                '1.0.0beta1' => 'beta',
                '1.0.1' => 'stable',
                '1.2.0' => 'stable',
            ),
            $this->_getReleases()->getReleases()
        );
    }

    public function testVersions()
    {
        $this->assertEquals(
            array('1.2.0', '1.0.1', '1.0.0', '1.0.0beta1', '1.0.0alpha1'), 
            $this->_getReleases()->listReleases()
        );
    }

    public function testGetReleaseStability()
    {
        $this->assertEquals(
            'stable', 
            $this->_getReleases()->getReleaseStability('1.2.0')
        );
    }

    /**
     * @expectedException Horde_Pear_Exception
     */
    public function testGetInvalidReleasesStability()
    {
        $this->_getReleases()->getReleaseStability('0.0.2');
    }

    private function _getReleases()
    {
        return new Horde_Pear_Rest_Releases(
            $this->_getInput()
        );
    }

    private function _getInput()
    {
        return '<?xml version="1.0" encoding="UTF-8" ?>
<a xmlns="http://pear.php.net/dtd/rest.allreleases" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink"     xsi:schemaLocation="http://pear.php.net/dtd/rest.allreleases http://pear.php.net/dtd/rest.allreleases.xsd">
    <p>Horde_Core</p>
    <c>pear.horde.org</c>
    <r>
        <v>1.2.0</v>
        <s>stable</s>
    </r>
    <r>
        <v>1.0.1</v>
        <s>stable</s>
    </r>
    <r>
        <v>1.0.0</v>
        <s>stable</s>
    </r>
    <r>
        <v>1.0.0beta1</v>
        <s>beta</s>
    </r>
    <r>
        <v>1.0.0alpha1</v>
        <s>alpha</s>
    </r>

</a>';
    }
}
