<?php
/**
 * Test the PearPackageXml module.
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
 * Test the PearPackageXml module.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
class Components_Integration_Components_Module_PearPackageXmlTest
extends Components_StoryTestCase
{

    /**
     * @scenario
     */
    public function thePearpackagexmlModuleAddsThePOptionInTheHelpOutput()
    {
        $this->given('the default Components setup')
            ->when('calling the package with the help option')
            ->then('the help will contain the "p" option.');
    }

    /**
     * @scenario
     */
    public function thePearpackagexmlModuleAddsTheUOptionInTheHelpOutput()
    {
        $this->given('the default Components setup')
            ->when('calling the package with the help option')
            ->then('the help will contain the "u" option.');
    }

    /**
     * @scenario
     */
    public function thePOptionFailsWithoutAValidPackagePath()
    {
        $this->given('the default Components setup')
            ->when('calling the package with the packagexml option and the path', '')
            ->then('the call will fail with', 'Please specify the path of the PEAR package!');
    }

    /**
     * @scenario
     */
    public function thePOptionFailsWithoutAValidDirectoryPath()
    {
        $this->given('the default Components setup')
            ->when(
                'calling the package with the packagexml option and the path',
                dirname(dirname(dirname(dirname(__FILE__)))) . '/fixture/DOESNOTEXIST'
            )
            ->then('the call will fail with', 'specifies no directory');
    }

    /**
     * @scenario
     */
    public function thePOptionFailsWithoutAValidPackage()
    {
        $this->given('the default Components setup')
            ->when(
                'calling the package with the packagexml option and the path',
                dirname(dirname(dirname(dirname(__FILE__)))) . '/fixture'
            )
            ->then('the call will fail with', 'There is no package.xml at');
    }

    /**
     * @scenario
     */
    public function thePOptionProvidesAnUpdatedPackageXml()
    {
        $this->given('the default Components setup')
            ->when('calling the package with the packagexml option and a Horde component')
            ->then('the new package.xml of the Horde element will be printed.');
    }

    /**
     * @scenario
     */
    public function thePOptionWithThePearrcOptionProvidesAnUpdatedPackageXml()
    {
        $this->given('the default Components setup')
            ->when('calling the package with the pearrc, the packagexml option, and a Horde component')
            ->then('the new package.xml of the Horde element will be printed.');
    }

    /**
     * @scenario
     */
    public function thePOptionProvidesAnUpdatedPackageXmlWhichRetainsReplaceTasks()
    {
        $this->given('the default Components setup')
            ->when('calling the package with the packagexml option and a Horde component')
            ->then('the new package.xml of the Horde component will retain all "replace" tasks.');
    }

    /**
     * @scenario
     */
    public function thePOptionProvidesAnUpdatedPackageXmlWithDefaultInstallLocations()
    {
        $this->given('the default Components setup')
            ->when('calling the package with the packagexml option and a Horde component')
            ->then('the new package.xml will install java script files in a default location')
            ->and('the new package.xml will install migration files in a default location')
            ->and('the new package.xml will install script files in a default location');
    }


    /**
     * @todo Test (and fix) the reactions to three more scenarios:
     *  - invalid XML in the package.xml (e.g. tag missing)
     *  - empty file list
     *  - file list with just one entry.
     *
     * All three scenarios yield errors which are still hard to
     * understand.
     */

}