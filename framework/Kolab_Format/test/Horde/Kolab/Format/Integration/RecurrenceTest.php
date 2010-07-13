<?php
/**
 * Test recurrence handling within the Kolab format implementation.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test recurrence handling
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Integration_RecurrenceTest
extends PHPUnit_Framework_TestCase
{

    /**
     * Set up testing.
     *
     * @return NULL
     */
    protected function setUp()
    {
        @include_once 'Horde/Date/Recurrence.php';

        if (!class_exists('Horde_Date_Recurrence')) {
            $this->markTestSkipped('The Horde_Date_Recurrence class is missing.');
        }

        $GLOBALS['registry']->setCharset('utf-8');

        $this->_oldTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');
    }

    public function tearDown()
    {
        date_default_timezone_set($this->_oldTimezone);
    }

    /**
     * Test for http://bugs.horde.org/ticket/?id=6388
     *
     * @return NULL
     */
    public function testBug6388()
    {
        $xml = Horde_Kolab_Format::factory('XML', 'event');

        // Load XML
        $recur = file_get_contents(dirname(__FILE__) . '/fixtures/recur.xml');

        // Load XML
        $xml   = &Horde_Kolab_Format::factory('XML', 'event');
        $recur = file_get_contents(dirname(__FILE__) . '/fixtures/recur_fail.xml');

        // Check that the xml fails because of a missing interval value
        try {
            $xml->load($recur);
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue($e instanceOf Horde_Kolab_Format_Exception);
        }
    }


    /**
     * Test exception handling.
     *
     * @return NULL
     */
    public function testExceptions()
    {
        $xml = Horde_Kolab_Format::factory('XML', 'event');

        // Load XML
        $recur = file_get_contents(dirname(__FILE__) . '/fixtures/recur.xml');

        $object = $xml->load($recur);

        $r = new Horde_Date_Recurrence($object['start-date']);
        $r->fromHash($object['recurrence']);

        $this->assertTrue($r->hasRecurEnd());
        $this->assertTrue($r->hasException(2006, 8, 16));
        $this->assertTrue($r->hasException(2006, 10, 18));

        $object['recurrence'] = $r->toHash();
        $recur                = $xml->save($object);
        $object               = $xml->load($recur);

        $s = new Horde_Date_Recurrence($object['start-date']);
        $s->fromHash($object['recurrence']);

        $this->assertTrue($s->hasRecurEnd());
        $this->assertTrue($s->hasException(2006, 8, 16));
        $this->assertTrue($s->hasException(2006, 10, 18));
    }

    /**
     * Test completion handling.
     *
     * @return NULL
     */
    public function testCompletions()
    {
        $xml = Horde_Kolab_Format::factory('XML', 'event');

        $r = new Horde_Date_Recurrence(0);
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->addException(1970, 1, 1);
        $r->addCompletion(1970, 1, 2);
        $r->addException(1970, 1, 3);
        $r->addCompletion(1970, 1, 4);
        $r->setRecurEnd(new Horde_Date(86400*3));

        $object               = array('uid' => 0, 'start-date' => 0,
                                      'end-date' => 60);
        $object['recurrence'] = $r->toHash();
        $recur                = $xml->save($object);
        $object               = $xml->load($recur);

        $s = new Horde_Date_Recurrence(0);
        $s->fromHash($object['recurrence']);

        $this->assertTrue($s->hasRecurEnd());
        $this->assertTrue($s->hasException(1970, 1, 1));
        $this->assertTrue($s->hasCompletion(1970, 1, 2));
        $this->assertTrue($s->hasException(1970, 1, 3));
        $this->assertTrue($s->hasCompletion(1970, 1, 4));
        $this->assertEquals(2, count($s->getCompletions()));
        $this->assertEquals(2, count($s->getExceptions()));
        $this->assertFalse($s->hasActiveRecurrence());

        $s->deleteCompletion(1970, 1, 2);
        $this->assertEquals(1, count($s->getCompletions()));
        $s->deleteCompletion(1970, 1, 4);
        $this->assertEquals(0, count($s->getCompletions()));
    }
}
