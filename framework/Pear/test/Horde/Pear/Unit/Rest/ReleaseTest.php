<?php
/**
 * Test the release information parser.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Pear
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Pear
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test the release information parser.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Pear
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Unit_Rest_ReleaseTest
extends Horde_Pear_TestCase
{
    public function testName()
    {
        $this->assertEquals('Horde_Core', $this->_getReleaseWrapper()->getName());
    }

    public function testChannel()
    {
        $this->assertEquals('pear.horde.org', $this->_getReleaseWrapper()->getChannel());
    }

    public function testVersion()
    {
        $this->assertEquals('1.0.0', $this->_getReleaseWrapper()->getVersion());
    }

    public function testLicense()
    {
        $this->assertEquals('LGPL-2.1', $this->_getReleaseWrapper()->getLicense());
    }

    public function testSummary()
    {
        $this->assertEquals(
            'Horde Core Framework libraries',
            $this->_getReleaseWrapper()->getSummary()
        );
    }

    public function testDescription()
    {
        $this->assertEquals(
            'These classes provide the core functionality of the Horde Application Framework.',
            $this->_getReleaseWrapper()->getDescription()
        );
    }

    public function testNotes()
    {
        $this->assertEquals(
            '
* First stable release for Horde 4.
* [mms] Add Horde_Core_Notification_Handler_Decorator_Base.
* [mms] Add listAlarms() to methods provided by Horde_Core_Registry_Application.
* [jan] Delay sidebar creation if the sidebar is generated through JavaScript.
* [jan] Use localized application names when sorting the preference menu tree.
* [mms] Fix adding port to certain urls passed to Horde::url() (Bug #9712).
 ',
            $this->_getReleaseWrapper()->getNotes()
        );
    }

    public function testDownloadUri()
    {
        $this->assertEquals(
            'http://pear.horde.org/get/Horde_Core-1.0.0.tgz',
            $this->_getReleaseWrapper()->getDownloadUri()
        );
    }

    private function _getReleaseWrapper()
    {
        return new Horde_Pear_Rest_Release(
            $this->_getInformation()
        );
    }

    private function _getInformation()
    {
        return '<?xml version="1.0" encoding="UTF-8" ?>
<r xmlns="http://pear.php.net/dtd/rest.release" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.release http://pear.php.net/dtd/rest.release.xsd">
    <p xlink:href="/rest/p/horde_core">Horde_Core</p>
    <c>pear.horde.org</c>
    <v>1.0.0</v>
    <st>stable</st>
    <l>LGPL-2.1</l>
    <m>chuck</m>
    <s>Horde Core Framework libraries</s>
    <d>These classes provide the core functionality of the Horde Application Framework.</d>
    <da>2011-04-06 01:07:26</da>
    <n>
* First stable release for Horde 4.
* [mms] Add Horde_Core_Notification_Handler_Decorator_Base.
* [mms] Add listAlarms() to methods provided by Horde_Core_Registry_Application.
* [jan] Delay sidebar creation if the sidebar is generated through JavaScript.
* [jan] Use localized application names when sorting the preference menu tree.
* [mms] Fix adding port to certain urls passed to Horde::url() (Bug #9712).
 </n>
    <f>439824</f>
    <g>http://pear.horde.org/get/Horde_Core-1.0.0</g>
    <x xlink:href="package.1.0.0.xml"/>
</r>';
    }
}
