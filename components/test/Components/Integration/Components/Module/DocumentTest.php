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
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Components
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the Document module.
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
class Components_Integration_Components_Module_DocumentTest
extends Components_StoryTestCase
{
    /**
     * @scenario
     */
    public function theDocumentModuleAddsTheOOptionInTheHelpOutput()
    {
        $this->given('the default Components setup')
            ->when('calling the package with the help option')
            ->then('the help will contain the option', '-O\s*DOCUMENT,\s*--document=DOCUMENT');
    }

    /**
     * @scenario
     */
    public function theTheOOptionGeneratesHtmlDocumentation()
    {
        $this->given('the default Components setup')
            ->when('calling the package with the document option and a path to a Horde framework component')
            ->then('the package documentation will be generated at the indicated location');
    }
}