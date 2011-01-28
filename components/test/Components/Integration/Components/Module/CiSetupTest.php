<?php
/**
 * Test the CI setup module.
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
 * Test the CI setup module.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
class Components_Integration_Components_Module_CiSetupTest
extends Components_StoryTestCase
{
    /**
     * @scenario
     */
    public function theCisetupModuleAddsTheCOptionInTheHelpOutput()
    {
        $this->given('the default Components setup')
            ->when('calling the package with the help option')
            ->then('the help will contain the option', '-c\s*CISETUP,\s*--cisetup=CISETUP');
    }

    /**
     * @scenario
     */
    public function theCisetupModuleAddsTheCapitalCOptionInTheHelpOutput()
    {
        $this->given('the default Components setup')
            ->when('calling the package with the help option')
            ->then('the help will contain the option', '-C\s*CIPREBUILD,\s*--ciprebuild=CIPREBUILD');
    }

    /**
     * @scenario
     */
    public function theCisetupOptionsFailsWithoutAValidToolsdirOption()
    {
        $this->given('the default Components setup')
            ->when(
                'calling the package with the cisetup option and paths',
                'test',
                dirname(dirname(dirname(dirname(__FILE__)))) . '/fixture/simple'
            )
            ->then('the call will fail with', 'You are required to set the path to a PEAR tool environment.');
    }

    /**
     * @scenario
     */
    public function theCisetupOptionsFailsWithoutAValidPearrcOption()
    {
        $this->given('the default Components setup')
            ->when(
                'calling the package with the cisetup, toolsdir options and path',
                'test',
                dirname(dirname(dirname(dirname(__FILE__)))) . '/fixture/simple'
            )
            ->then('the call will fail with', 'You are required to set the path to a PEAR environment for this package');
    }

    /**
     * @scenario
     */
    public function theCisetupOptionCreatesATemplateBaseCiConfigurationForAComponent()
    {
        $this->given('the default Components setup')
            ->when(
                'calling the package with the cisetup, toolsdir, pearrc options and path',
                dirname(dirname(dirname(dirname(__FILE__)))) . '/fixture/simple'
            )
            ->then('the CI configuration will be installed.');
    }

    /**
     * @scenario
     */
    public function theCiprebuildOptionsFailsWithoutAValidToolsdirOption()
    {
        $this->given('the default Components setup')
            ->when(
                'calling the package with the ciprebuild option and path',
                dirname(dirname(dirname(dirname(__FILE__)))) . '/fixture/simple'
            )
            ->then('the call will fail with', 'You are required to set the path to a PEAR tool environment.');
    }

    /**
     * @scenario
     */
    public function theCiprebuildOptionCreatesATemplateBaseCiBuildScriptForAComponent()
    {
        $this->given('the default Components setup')
            ->when(
                'calling the package with the ciprebuild, toolsdir option and path',
                dirname(dirname(dirname(dirname(__FILE__)))) . '/fixture/simple'
            )
            ->then('the CI build script will be installed.');
    }

    /**
     * @scenario
     */
    public function theCisetupOptionCreatesABaseCiConfigurationForAComponentFromAUserTemplate()
    {
        $this->given('the default Components setup')
            ->when(
                'calling the package with the cisetup, toolsdir, pearrc, template options and path',
                dirname(dirname(dirname(dirname(__FILE__)))) . '/fixture/simple'
            )
            ->then('the CI configuration will be installed according to the specified template.');
    }

    /**
     * @scenario
     */
    public function theCiprebuildOptionCreatesABaseCiConfigurationForAComponentFromAUserTemplate()
    {
        $this->given('the default Components setup')
            ->when(
                'calling the package with the ciprebuild, toolsdir, template options and path',
                dirname(dirname(dirname(dirname(__FILE__)))) . '/fixture/simple'
            )
            ->then('the CI build script will be installed according to the specified template.');
    }
}