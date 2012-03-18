<?php
/**
 * Test the address rewriting filter.
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
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test the address rewriting filter.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Filter_Unit_Helper_AddressFilterTest
extends PHPUnit_Framework_TestCase
{
    public function testFilter()
    {
        $fp = fopen('php://memory', 'w+');
        fputs($fp, "hello\n");
        rewind($fp);
        stream_filter_register('addresses', 'Horde_Kolab_Filter_Helper_AddressFilter');
        stream_filter_append($fp, 'addresses');
        $this->assertEquals("hello\n", stream_get_contents($fp));
    }

    public function testFilterEmptiesUnsetParameterOne()
    {
        $fp = fopen('php://memory', 'w+');
        fputs($fp, "hello%1\$s\n");
        rewind($fp);
        stream_filter_register('addresses', 'Horde_Kolab_Filter_Helper_AddressFilter');
        stream_filter_append($fp, 'addresses');
        $this->assertEquals("hello\n", stream_get_contents($fp));
    }

    public function testFilterEmptiesUnsetParameterTwo()
    {
        $fp = fopen('php://memory', 'w+');
        fputs($fp, "hello%2\$s\n");
        rewind($fp);
        stream_filter_register('addresses', 'Horde_Kolab_Filter_Helper_AddressFilter');
        stream_filter_append($fp, 'addresses');
        $this->assertEquals("hello\n", stream_get_contents($fp));
    }

    public function testFilterSetsParameterSender()
    {
        $fp = fopen('php://memory', 'w+');
        fputs($fp, "hello %1\$s\n");
        rewind($fp);
        stream_filter_register('addresses', 'Horde_Kolab_Filter_Helper_AddressFilter');
        stream_filter_append($fp, 'addresses', STREAM_FILTER_READ, array('sender' => 'sender'));
        $this->assertEquals("hello sender\n", stream_get_contents($fp));
    }

    public function testFilterSetsParameterRecipient()
    {
        $fp = fopen('php://memory', 'w+');
        fputs($fp, "hello %2\$s\n");
        rewind($fp);
        stream_filter_register('addresses', 'Horde_Kolab_Filter_Helper_AddressFilter');
        stream_filter_append($fp, 'addresses', STREAM_FILTER_READ, array('recipient' => 'recipient'));
        $this->assertEquals("hello recipient\n", stream_get_contents($fp));
    }

    /**
     * @dataProvider provideBrokenParameters
     */
    public function testFilterHandlesBrokenParameter($param)
    {
        $fp = fopen('php://memory', 'w+');
        $append = "hello $param\n";
        fputs($fp, str_repeat('a', 16384 - strlen($append)) . $append . str_repeat('test', 300));
        rewind($fp);
        stream_filter_register('addresses', 'Horde_Kolab_Filter_Helper_AddressFilter');
        stream_filter_append($fp, 'addresses');
        fread($fp, 16384 - strlen($append));
        $this->assertEquals("hello $param\n", fread($fp, strlen($append)));
    }

    public function provideBrokenParameters()
    {
        return array(
            array("%"),
            array("%1"),
            array("%2"),
            array("%2\$"),
            array("%2\$\ns"),
        );
    }
}