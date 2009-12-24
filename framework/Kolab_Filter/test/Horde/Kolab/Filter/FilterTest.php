<?php
/**
 * Test the base filter class within the Kolab filter implementation.
 *
 * @package Horde_Kolab_Filter
 */

/**
 *  We need the unit test framework
 */
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/Extensions/OutputTestCase.php';

require_once 'Horde.php';
require_once 'Horde/Kolab/Filter/Incoming.php';

/**
 * Test the filter class.
 *
 * Copyright 2008 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Horde_Kolab_Filter
 */
class Horde_Kolab_Filter_FilterTest extends PHPUnit_Framework_TestCase
{

    /**
     * Set up testing.
     */
    protected function setUp()
    {
        $GLOBALS['conf']['log']['enabled']          = false;

        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = 'ADDR';
        $_SERVER['REMOTE_HOST'] = 'HOST';
    }


    /**
     * Test incorrect usage of the Filter
     */
    public function testIncorrectUsage()
    {
        $_SERVER['argv'] = array($_SERVER['argv'][0]);
        $parser   = &new Horde_Kolab_Filter_Incoming();
        $inh = fopen(dirname(__FILE__) . '/fixtures/tiny.eml', 'r');
        $result = $parser->parse($inh, 'echo');

        $this->assertTrue(is_a($result, 'PEAR_Error'));

        $this->assertContains('Please provide one or more recipients.', $result->getMessage());
    }
}
