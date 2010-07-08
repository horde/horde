<?php
/**
 * Test the base filter class within the Kolab filter implementation.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Filter
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Filter
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Autoload.php';

/**
 * Test the filter class.
 *
 * Copyright 2008 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category   Kolab
 * @package    Kolab_Filter
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Filter
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
        try {
            $result = $parser->parse($inh, 'echo');
        } catch (Horde_Kolab_Filter_Exception $e) {
            $this->assertContains('Please provide one or more recipients.', $e->getMessage());
            return;
        }
        $this->assertFail('No exception!');
    }
}
