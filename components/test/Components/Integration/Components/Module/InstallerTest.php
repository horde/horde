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
            ->then('the help will contain the option', '-i\s*INSTALL,\s*--install=INSTALL');
    }

    /**
     * @scenario
     */
    public function theTheIOptionListsThePackagesToBeInstalledWhenPretendHasBeenSelected()
    {
        $this->given('the default Components setup')
            ->when('calling the package with the install option, the pretend option and a path to a Horde framework component')
            ->then('the dummy PEAR package will be listed')
            ->and('the non-Horde dependencies of the component would be installed')
            ->and('the Horde dependencies of the component would be installed')
            ->and('the old-style Horde dependencies of the component would be installed')
            ->and('the component will be listed');
    }

    /**
     * @scenario
     */
    public function theTheIOptionAllowsToAvoidIncludingAllOptionalPackages()
    {
        $this->given('the default Components setup')
            ->when('calling the package with the install option, a path to a Horde framework component, and the following include/exclude options', '', '')
            ->then('the Optional package will not be listed')
            ->and('the Console_Getopt package will not be listed')
            ->and('the PECL will package will not be listed');
    }

    /**
     * @scenario
     */
    public function theTheIOptionAllowsToIncludeSpecificChannels()
    {
        $this->given('the default Components setup')
            ->when('calling the package with the install option, a path to a Horde framework component, and the following include/exclude options', 'channel:pear.php.net,channel:pear.horde.org', '')
            ->then('the Optional package will be listed')
            ->and('the Console_Getopt package will be listed')
            ->and('the PECL will package will not be listed');
    }

    /**
     * @scenario
     */
    public function theTheIOptionAllowsToIncludeSpecificPackages()
    {
        $this->given('the default Components setup')
            ->when('calling the package with the install option, a path to a Horde framework component, and the following include/exclude options', 'Console_Getopt,Optional', '')
            ->then('the Optional package will be listed')
            ->and('the Console_Getopt package will be listed')
            ->and('the PECL will package will not be listed');
    }

    /**
     * @scenario
     */
    public function theTheIOptionAllowsToExcludeAllOptionalPackages()
    {
        $this->given('the default Components setup')
            ->when('calling the package with the install option, a path to a Horde framework component, and the following include/exclude options', 'channel:pear.horde.org,channel:pear.php.net', 'ALL')
            ->then('the Optional package will not be listed')
            ->and('the Console_Getopt package will not be listed')
            ->and('the PECL will package will not be listed');
    }

    /**
     * @scenario
     */
    public function theTheIOptionAllowsToExcludeSpecificChannels()
    {
        $this->given('the default Components setup')
            ->when('calling the package with the install option, a path to a Horde framework component, and the following include/exclude options', 'channel:pear.php.net,channel:pear.horde.org', 'channel:pecl.php.net')
            ->then('the Optional package will be listed')
            ->and('the Console_Getopt package will be listed')
            ->and('the PECL will package will not be listed');
    }

    /**
     * @scenario
     */
    public function theTheIOptionAllowsToExcludeSpecificPackages()
    {
        $this->given('the default Components setup')
            ->when('calling the package with the install option, a path to a Horde framework component, and the following include/exclude options', 'ALL', 'pecl.php.net/PECL')
            ->then('the Optional package will be listed')
            ->and('the Console_Getopt package will be listed')
            ->and('the PECL will package will not be listed');
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