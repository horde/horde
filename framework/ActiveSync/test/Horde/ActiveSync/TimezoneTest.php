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
    /**
     * Test building an Offset hash from a given ActiveSync style base64 encoded
     * timezone structure.
     */
    public function testOffsetsFromSyncTZ()
    {
        // America/Los_Angeles GMT-8:00
        $blob = '4AEAACgARwBNAFQALQAwADgAOgAwADAAKQAgAFAAYQBjAGkAZgBpAGMAIABUAGkAbQBlACAAKABVAFMAIAAmACAAQwAAA AsAAAABAAIAAAAAAAAAAAAAACgARwBNAFQALQAwADgAOgAwADAAKQAgAFAAYQBjAGkAZgBpAGMAIABUAGkAbQBlACAAKA BVAFMAIAAmACAAQwAAAAMAAAACAAIAAAAAAAAAxP///w==';
        $tz = Horde_ActiveSync_Timezone::getOffsetsFromSyncTZ($blob);

        $expected = array(
            'bias' => 480,
            //'stdname' => '(GMT-08:00) Pacific Time (US & C',
            'stdyear' => 0,
            'stdmonth' => 11,
            'stdday' => 0,
            'stdweek' => 1,
            'stdhour' => 2,
            'stdminute' => 0,
            'stdsecond' => 0,
            'stdmillis' => 0,
            'stdbias' => 0,
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
            'timezonedst' => -60
        );

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $tz[$key]);
        }
    }

    /**
     * Test creating a Offset hash for a given timezone.
     */
    public function testGetOffsetsFromDate()
    {
        // The actual time doesn't matter, we really only need a year and a
        // timezone that we are interested in.
        $date = new Horde_Date(time(), 'America/Los_Angeles');
        $tz = Horde_ActiveSync_Timezone::getOffsetsFromDate($date);
        
        /* We don't set the name here */
        $expected = array(
            'bias' => 480,
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
        );

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $tz[$key]);
        }
    }

    /**
     * Test generating an ActiveSync TZ structure given a TZ Offset hash
     */
    public function testGetSyncTZFromOffsets()
    {
         $offsets = array(
            'bias' => 480,
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
        );

        $tz = Horde_ActiveSync_Timezone::getSyncTZFromOffsets($offsets);
        $this->assertEquals('4AEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAsAAAABAAIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAACAAIAAAAAAAAAxP///w==', $tz);
    }
}