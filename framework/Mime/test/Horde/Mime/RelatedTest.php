<?php
/**
 * Tests for the Horde_Mime_Related class.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/Autoload.php';

/**
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */
class Horde_Mime_RelatedTest extends PHPUnit_Framework_TestCase
{
    private $_part;
    private $_relatedOb;

    public function setUp()
    {
        $this->_part = Horde_Mime_Part::parseMessage(file_get_contents(__DIR__ . '/fixtures/related_msg.txt'));
        $this->_relatedOb = new Horde_Mime_Related($this->_part);
    }

    public function testStart()
    {
        $this->assertEquals(
            1,
            $this->_relatedOb->startId()
        );
    }

    public function testSearch()
    {
        $this->assertEquals(
            3,
            $this->_relatedOb->cidSearch('789')
        );
    }

    public function testIterator()
    {
        $this->assertEquals(
            array('2' => '456', '3' => '789'),
            iterator_to_array($this->_relatedOb)
        );
    }

}
