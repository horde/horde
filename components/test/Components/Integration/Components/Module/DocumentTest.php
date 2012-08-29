<?php
/**
 * Test the Document module.
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
 * Test the Document module.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
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
class Components_Integration_Components_Module_DocumentTest
extends Components_StoryTestCase
{
    /**
     * @scenario
     */
    public function theDocumentModuleAddsTheOOptionInTheHelpOutput()
    {
        $this->markTestIncomplete();
        $this->given('the default Components setup')
            ->when('calling the package with the help option')
            ->then('the help will contain the option', '-O\s*DOCUMENT,\s*--document=DOCUMENT');
    }

    /**
     * @scenario
     */
    public function theTheOOptionGeneratesHtmlDocumentation()
    {
        $this->markTestIncomplete();
        $this->given('the default Components setup')
            ->when('calling the package with the document option and a path to a Horde framework component')
            ->then('the package documentation will be generated at the indicated location');
    }
}