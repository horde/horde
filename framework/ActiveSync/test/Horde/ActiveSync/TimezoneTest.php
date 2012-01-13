<?php
/*
 * Unit tests for Horde_ActiveSync_Timezone utilities
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package ActiveSync
 */
class Horde_ActiveSync_TimezoneTest extends Horde_Test_Case
{

    protected $_offsets = array(
        'America/New_York'    => array('bias' => 300,
                                       'stdname' => '',
                                       'stdyear' => 0,
                                       'stdmonth' => 11,
                                       'stdday' => 0,
                                       'stdweek' => 1,
                                       'stdhour' => 2,
                                       'stdminute' => 0,
                                       'stdsecond' => 0,
                                       'stdmillis' => 0,
                                       'stdbias' => 0,
                                       'dstname' => '',
                                       'dstyear' => 0,
                                       'dstmonth' => 3,
                                       'dstday' => 0,
                                       'dstweek' => 2,
                                       'dsthour' => 2,
                                       'dstminute' => 0,
                                       'dstsecond' => 0,
                                       'dstmillis' => 0,
                                       'dstbias' => -60),
        'Europe/Berlin'       => array('bias' => -60,
                                       'stdname' => '',
                                       'stdyear' => 0,
                                       'stdmonth' => 10,
                                       'stdday' => 0,
                                       'stdweek' => 5,
                                       'stdhour' => 3,
                                       'stdminute' => 0,
                                       'stdsecond' => 0,
                                       'stdmillis' => 0,
                                       'stdbias' => 0,
                                       'dstname' => '',
                                       'dstyear' => 0,
                                       'dstmonth' => 3,
                                       'dstday' => 0,
                                       'dstweek' => 5,
                                       'dsthour' => 2,
                                       'dstminute' => 0,
                                       'dstsecond' => 0,
                                       'dstmillis' => 0,
                                       'dstbias' => -60),
        'America/Los_Angeles' => array('bias' => 480,
                                       'stdname' => '',
                                       'stdyear' => 0,
                                       'stdmonth' => 11,
                                       'stdday' => 0,
                                       'stdweek' => 1,
                                       'stdhour' => 2,
                                       'stdminute' => 0,
                                       'stdsecond' => 0,
                                       'stdmillis' => 0,
                                       'stdbias' => 0,
                                       'dstname' => '',
                                       'dstyear' => 0,
                                       'dstmonth' => 3,
                                       'dstday' => 0,
                                       'dstweek' => 2,
                                       'dsthour' => 2,
                                       'dstminute' => 0,
                                       'dstsecond' => 0,
                                       'dstmillis' => 0,
                                       'dstbias' => -60,
                                       'timezone' => 480,
                                       'timezonedst' => -60),
        'America/Phoenix'     => array('bias' => 420,
                                       'stdname' => '',
                                       'stdyear' => 0,
                                       'stdmonth' => 0,
                                       'stdday' => 0,
                                       'stdweek' => 0,
                                       'stdhour' => 0,
                                       'stdminute' => 0,
                                       'stdsecond' => 0,
                                       'stdmillis' => 0,
                                       'stdbias' => 0,
                                       'dstname' => '',
                                       'dstyear' => 0,
                                       'dstmonth' => 0,
                                       'dstday' => 0,
                                       'dstweek' => 0,
                                       'dsthour' => 0,
                                       'dstminute' => 0,
                                       'dstsecond' => 0,
                                       'dstmillis' => 0,
                                       'dstbias' => 0)
    );

    protected $_packed = array(
        'America/New_York'    => 'LAEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAsAAAABAAIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAACAAIAAAAAAAAAxP///w==',
        'America/Los_Angeles' => '4AEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAsAAAABAAIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAACAAIAAAAAAAAAxP///w==',
        'Europe/Berlin'       => 'xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAAFAAMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAFAAIAAAAAAAAAxP///w==',
        'America/Phoenix'     => 'pAEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA==',
    );


    public function setUp()
    {
        $this->_oldTimezone = date_default_timezone_get();
        date_default_timezone_set('America/New_York');
    }

    public function tearDown()
    {
        date_default_timezone_set($this->_oldTimezone);
    }

    /**
     * Test building an Offset hash from a given ActiveSync style base64 encoded
     * timezone structure.
     */
    public function testOffsetsFromSyncTZ()
    {
        foreach ($this->_packed as $tz => $blob) {
            $offsets = Horde_ActiveSync_Timezone::getOffsetsFromSyncTZ($blob);
            foreach ($this->_offsets[$tz] as $key => $value) {
                $this->assertEquals($value, $offsets[$key]);
            }
        }
    }

    /**
     * Test creating a Offset hash for a given timezone.
     */
    public function testGetOffsetsFromDate()
    {
        // The actual time doesn't matter, we really only need a year and a
        // timezone that we are interested in.
        foreach ($this->_offsets as $tz => $expected) {
            $date = new Horde_Date('2011-07-01', $tz);
            $offsets = Horde_ActiveSync_Timezone::getOffsetsFromDate($date);
            foreach ($offsets as $key => $value) {
                $this->assertEquals($expected[$key], $value);
            }
        }
    }

    /**
     * Test generating an ActiveSync TZ structure given a TZ Offset hash
     */
    public function testGetSyncTZFromOffsets()
    {
        foreach ($this->_offsets as $tz => $offsets) {
            $blob = Horde_ActiveSync_Timezone::getSyncTZFromOffsets($offsets);
            $this->assertEquals($this->_packed[$tz], $blob);
        }
    }

    /**
     * Test guessing a timezone identifier from an ActiveSync timezone
     * structure.
     */
    public function testGuessTimezoneFromOffsets()
    {
        $timezones = new Horde_ActiveSync_Timezone();

        // Test general functionality, with expected timezone.
        foreach ($this->_packed as $tz => $blob) {
            $guessed = $timezones->getTimezone($blob, $tz);
            $this->assertEquals($tz, $guessed);
        }

        // Test without a known timezone
        $guessed = $timezones->getTimezone($this->_packed['America/New_York']);
        $this->assertEquals('EST', $guessed);

        $guessed = $timezones->getTimezone($this->_packed['Europe/Berlin']);
        $this->assertEquals('CET', $guessed);
    }

}