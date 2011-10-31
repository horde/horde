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
            $this->_hasDate(
                $object['recurrence']['exclusion'], '2006-08-16'
            )
        );
    }

    public function testExclusion2()
    {
        $object = $this->_loadExclusions();
        $this->assertTrue(
            $this->_hasDate(
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
            $this->_hasDate(
                $object['recurrence']['exclusion'], '2006-08-16'
            )
        );
    }

    public function testReloadedExclusion2()
    {
        $object = $this->_reloadExclusions();
        $this->assertTrue(
            $this->_hasDate(
                $object['recurrence']['exclusion'], '2006-10-18'
            )
        );
    }

    public function testComplete()
    {
        $object = $this->_loadComplete();
        $this->assertTrue(
            $this->_hasDate(
                $object['recurrence']['complete'], '2006-04-05'
            )
        );
    }

    public function testComplete2()
    {
        $object = $this->_loadComplete();
        $this->assertTrue(
            $this->_hasDate(
                $object['recurrence']['complete'], '2006-07-26'
            )
        );
    }

    public function testReloadedComplete()
    {
        $object = $this->_reloadComplete();
        $this->assertTrue(
            $this->_hasDate(
                $object['recurrence']['complete'], '2006-04-05'
            )
        );
    }

    public function testReloadedComplete2()
    {
        $object = $this->_reloadComplete();
        $this->assertTrue(
            $this->_hasDate(
                $object['recurrence']['complete'], '2006-07-26'
            )
        );
    }

    private function _hasDate($dates, $date)
    {
        foreach ($dates as $value) {
            
            if ($value->format('Y-m-d') == $date) {
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

    private function _loadComplete()
    {
        return $this->getFactory()->create('XML', 'event')->load(
            file_get_contents(dirname(__FILE__) . '/../fixtures/recur_complete.xml')
        );
    }

    private function _reloadComplete()
    {
        $parser = $this->getFactory()->create('XML', 'event');
        $object = $parser->load(
            file_get_contents(dirname(__FILE__) . '/../fixtures/recur_complete.xml')
        );
        $xml = $parser->save($object);
        return $parser->load($xml);
    }
}
