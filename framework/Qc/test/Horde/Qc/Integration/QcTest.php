<?php
/**
 * Test the Qc package.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Qc
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Qc
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the Qc package.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Qc
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Qc
 */
class Horde_Qc_Integration_QcTest
extends Horde_Qc_StoryTestCase
{
    /**
     * @scenario
     */
    public function theHelpOptionResultsInHelpOutput()
    {
        $this->given('the default QC package setup')
            ->when('calling the package with the help option')
            ->then('the help will be displayed');
    }

    /**
     * @scenario
     */
    public function thePearpackagexmlModuleAddsThePOptionInTheHelpOutput()
    {
        $this->given('the default QC package setup')
            ->when('calling the package with the help option')
            ->then('the help will contain the "p" option.');
    }

    /**
     * @scenario
     */
    public function thePearpackagexmlModuleAddsTheUOptionInTheHelpOutput()
    {
        $this->given('the default QC package setup')
            ->when('calling the package with the help option')
            ->then('the help will contain the "u" option.');
    }
}