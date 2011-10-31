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
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test recurrence handling
 *
 * Copyright 2007-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Integration_RecurrenceTest
extends Horde_Kolab_Format_TestCase
{
    public function testBug6388()
    {
        $xml   = $this->getFactory()->create('XML', 'event');
        $recur = file_get_contents(dirname(__FILE__) . '/../fixtures/recur_fail.xml');
        // Check that the xml fails because of a missing interval value
        try {
            $xml->load($recur);
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue($e instanceOf Horde_Kolab_Format_Exception);
        }
    }

    public function testRecurrenceEnd()
    {
        $object = $this->_loadExclusions();
        $this->assertInstanceOf('DateTime', $object['recurrence']['range']);
    }

    public function testExclusion()
    {
        $object = $this->_loadExclusions();
        $this->assertTrue(
            $this->_hasException(
                $object['recurrence']['exclusion'], '2006-08-16'
            )
        );
    }

    public function testExclusion2()
    {
        $object = $this->_loadExclusions();
        $this->assertTrue(
            $this->_hasException(
                $object['recurrence']['exclusion'], '2006-10-18'
            )
        );
    }

    public function testReloadedRecurrenceEnd()
    {
        $object = $this->_reloadExclusions();
        $this->assertInstanceOf('DateTime', $object['recurrence']['range']);
    }

    public function testReloadedExclusion()
    {
        $object = $this->_reloadExclusions();
        $this->assertTrue(
            $this->_hasException(
                $object['recurrence']['exclusion'], '2006-08-16'
            )
        );
    }

    public function testReloadedExclusion2()
    {
        $object = $this->_reloadExclusions();
        $this->assertTrue(
            $this->_hasException(
                $object['recurrence']['exclusion'], '2006-10-18'
            )
        );
    }

    /**
     * Test completion handling.
     *
     * @return NULL
     */
    public function testCompletions()
    {
        $this->markTestIncomplete('TODO');
        $xml = $this->getFactory()->create('XML', 'event');

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

    private function _hasException($exclusions, $date)
    {
        foreach ($exclusions as $exclusion) {
            
            if ($exclusion->format('Y-m-d') == $date) {
                return true;
            }
        }
        return false;
    }

    private function _loadExclusions()
    {
        return $this->getFactory()->create('XML', 'event')->load(
            file_get_contents(dirname(__FILE__) . '/../fixtures/recur.xml')
        );
    }

    private function _reloadExclusions()
    {
        $parser = $this->getFactory()->create('XML', 'event');
        $object = $parser->load(
            file_get_contents(dirname(__FILE__) . '/../fixtures/recur.xml')
        );
        $xml = $parser->save($object);
        return $parser->load($xml);
    }

}
