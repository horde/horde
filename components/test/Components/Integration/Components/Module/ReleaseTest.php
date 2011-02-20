<?php
/**
 * Test the Release module.
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
 * Test the Release module.
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
class Components_Integration_Components_Module_ReleaseTest
extends Components_StoryTestCase
{
    /**
     * @scenario
     */
    public function testOption()
    {
        $this->given('the default Components setup')
            ->when('calling the package with the help option')
            ->then('the help will contain the option', '-r,\s*--release');
    }

    /**
     * @scenario
     */
    public function testReleaseGeneration()
    {
        $this->given('the default Components setup')
            ->when('calling the package with the release option and a path to a component')
            ->then('a package release will be generated in the current directory');
    }

    /**
     * @scenario
     */
    public function testErrorHandling()
    {
        $this->given('the default Components setup')
            ->when('calling the package with the release option and an invalid path')
            ->then('the output should indicate an invalid package.xml')
            ->and('indicate the specific problem of the package.xml');
    }
}