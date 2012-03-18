<?php
/**
 * Test the CLI handling.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Filter
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Filter
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Autoload.php';

/**
 * Test the CLI handling.
 *
 * Copyright 2008 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Filter
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Filter
 */
class Horde_Kolab_Filter_Integration_CliTest
extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $_SERVER['argv'] = array('test');
    }

    public function tearDown()
    {
        unset($_SERVER['argv']);
    }

    /**
     * Test incorrect usage of the Filter.
     */
    public function testIncorrectUsage()
    {
        $_SERVER['argv'] = array($_SERVER['argv'][0]);
        $filter = new Horde_Kolab_Filter();
        $inh = fopen(__DIR__ . '/../fixtures/tiny.eml', 'r');
        try {
            $result = $filter->main('Incoming', $inh, 'echo');
        } catch (Horde_Kolab_Filter_Exception $e) {
            $this->assertContains(
                'Please provide one or more recipients.',
                $e->getMessage()
            );
            return;
        }
        $this->assertFail('No exception!');
    }

    /**
     * Test incorrect usage of the Filter by providing an invalid option.
     */
    public function testIncorrectUsageWithInvalidOption()
    {
        setlocale(LC_MESSAGES, 'C');
        $_SERVER['argv'] = array(
            $_SERVER['argv'][0],
            '--recipient'
        );
        $filter = new Horde_Kolab_Filter();
        $inh = fopen(__DIR__ . '/../fixtures/tiny.eml', 'r');
        try {
            $result = $filter->main('Incoming', $inh, 'echo');
        } catch (Horde_Kolab_Filter_Exception $e) {
            $this->assertContains(
                'error: --recipient option requires an argument',
                $e->getMessage()
            );
            return;
        }
        $this->assertFail('No exception!');
    }
}
