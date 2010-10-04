<?php
/**
 * Test the Installer module.
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
 * Test the Installer module.
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
class Components_Integration_Components_Module_InstallerTest
extends Components_StoryTestCase
{
    /**
     * @scenario
     */
    public function theInstallerModuleAddsTheIOptionInTheHelpOutput()
    {
        $this->given('the default Components setup')
            ->when('calling the package with the help option')
            ->then('the help will contain the "i" option.');
    }

    /**
     * @scenario
     */
    public function theTheIOptionInstallsThePackageFromTheCurrentTree()
    {
        $this->given('the default Components setup')
            ->when('calling the package with the install option and a path to a Horde framework component')
            ->then('a new PEAR configuration file will be installed')
            ->and('the dummy PEAR package will be installed')
            ->and('the non-Horde dependencies of the component will get installed')
            ->and('the Horde dependencies of the component will get installed from the current tree')
            ->and('the component will be installed')
            ->and('the installation requires no network access.');
    }
}