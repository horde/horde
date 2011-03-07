<?php
/**
 * Test the Components package.
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
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the Components package.
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
class Components_Integration_ComponentsTest
extends Components_StoryTestCase
{
    /**
     * @scenario
     */
    public function theHelpOptionResultsInHelpOutput()
    {
        $this->given('the default Components setup')
            ->when('calling the package with the help option')
            ->then('the help will be displayed');
    }

    /**
     * @scenario
     */
    public function hmkFailsInDirectoryWithNoPackageXml()
    {
        $this->given('the default Components setup')
            ->when('calling hmk in a directory without package xml')
            ->then('the call will fail with', 'You are not in a component directory:');
    }

    /**
     * @scenario
     */
    public function hmkSucceedsInDirectoryWithPackageXml()
    {
        $this->given('the default Components setup')
            ->when('calling hmk in a directory with package xml')
            ->then('the call will succeed');
    }

    /**
     * @scenario
     */
    public function theDevpackageModuleAddsTheDOptionInTheHelpOutput()
    {
        $this->given('the default Components setup')
            ->when('calling the package with the help option')
            ->then('the help will contain the "z" option.');
    }

}