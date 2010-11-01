<?php
/**
 * Copyright 2007-2008 Maintainable Software, LLC
 * Copyright 2006-2010 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage UnitTests
 */

/**
 * @group      view
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage UnitTests
 */
class Horde_View_Helper_DateTest extends Horde_Test_Case
{
    public function setUp()
    {
        $this->helper = new Horde_View_Helper_Date(new Horde_View());
    }

    public function testDistanceInWords()
    {
        $from = mktime(21, 45, 0, 6, 6, 2004);

        // 0..1 with $includeSeconds
        $this->assertEquals('less than 5 seconds',
                            $this->helper->distanceOfTimeInWords($from, $from + 0, true));
        $this->assertEquals('less than 5 seconds',
                            $this->helper->distanceOfTimeInWords($from, $from + 4, true));
        $this->assertEquals('less than 10 seconds',
                            $this->helper->distanceOfTimeInWords($from, $from + 5, true));
        $this->assertEquals('less than 10 seconds',
                            $this->helper->distanceOfTimeInWords($from, $from + 9, true));
        $this->assertEquals('less than 20 seconds',
                            $this->helper->distanceOfTimeInWords($from, $from + 10, true));
        $this->assertEquals('less than 20 seconds',
                            $this->helper->distanceOfTimeInWords($from, $from + 19, true));
        $this->assertEquals('half a minute',
                            $this->helper->distanceOfTimeInWords($from, $from + 20, true));
        $this->assertEquals('half a minute',
                            $this->helper->distanceOfTimeInWords($from, $from + 39, true));
        $this->assertEquals('less than a minute',
                            $this->helper->distanceOfTimeInWords($from, $from + 40, true));
        $this->assertEquals('less than a minute',
                            $this->helper->distanceOfTimeInWords($from, $from + 59, true));
        $this->assertEquals('1 minute',
                            $this->helper->distanceOfTimeInWords($from, $from + 60, true));
        $this->assertEquals('1 minute',
                            $this->helper->distanceOfTimeInWords($from, $from + 89, true));

        // First case 0..1
        $this->assertEquals('less than a minute',
                            $this->helper->distanceOfTimeInWords($from, $from + 0));
        $this->assertEquals('less than a minute',
                            $this->helper->distanceOfTimeInWords($from, $from + 29));
        $this->assertEquals('1 minute',
                            $this->helper->distanceOfTimeInWords($from, $from + 30));
        $this->assertEquals('1 minute',
                            $this->helper->distanceOfTimeInWords($from, $from + (1*60) + 29));

        // 2..44
        $this->assertEquals('2 minutes',
                            $this->helper->distanceOfTimeInWords($from, $from + (1*60) + 30));
        $this->assertEquals('44 minutes',
                            $this->helper->distanceOfTimeInWords($from, $from + (44*60) + 29));

        // 45..89
        $this->assertEquals('about 1 hour',
                            $this->helper->distanceOfTimeInWords($from, $from + (44*60) + 30));
        $this->assertEquals('about 1 hour',
                            $this->helper->distanceOfTimeInWords($from, $from + (89*60) + 29));

        // 90..1439
        $this->assertEquals('about 2 hours',
                            $this->helper->distanceOfTimeInWords($from, $from + (89*60) + 30));
        $this->assertEquals('about 24 hours',
                            $this->helper->distanceOfTimeInWords($from, $from + (23*3600) + (59*60) + 29));

        // 2880..43199
        $this->assertEquals('2 days',
                            $this->helper->distanceOfTimeInWords($from, $from + (47*3600) + (59*60) + 30));
        $this->assertEquals('29 days',
                            $this->helper->distanceOfTimeInWords($from, $from + (29*86400) + (23*3600) + (59*60) + 29));

        // 43200..86399
        $this->assertEquals('about 1 month',
                            $this->helper->distanceOfTimeInWords($from, $from + (29*86400) + (23*3600) + (59*60) + 30));
        $this->assertEquals('about 1 month',
                            $this->helper->distanceOfTimeInWords($from, $from + (59*86400) + (23*3600) + (59*60) + 29));

        // 86400..525599
        $this->assertEquals('2 months',
                            $this->helper->distanceOfTimeInWords($from, $from + (59*86400) + (23*3600) + (59*60) + 30));

        $this->assertEquals('12 months',
                            $this->helper->distanceOfTimeInWords($from, $from + (1*31557600) - 31));

        // 525960..1051919
        $this->assertEquals('about 1 year',
                            $this->helper->distanceOfTimeInWords($from, $from + (1*31557600) - 30));
        $this->assertEquals('about 1 year',
                            $this->helper->distanceOfTimeInWords($from, $from + (2*31557600) - 31));

        // > 1051920
        $this->assertEquals('over 2 years',
                            $this->helper->distanceOfTimeInWords($from, $from + (2*31557600) - 30));
        $this->assertEquals('over 10 years',
                            $this->helper->distanceOfTimeInWords($from, $from + (10*31557600)));

        // test to < from
        $this->assertEquals('about 4 hours',
                            $this->helper->distanceOfTimeInWords($from + (4*3600), $from));
        $this->assertEquals('less than 20 seconds',
                            $this->helper->distanceOfTimeInWords($from + 19, $from, true));
    }

    public function testDistanceInWordsWithIntegers()
    {
        $this->markTestIncomplete('not yet passing');

        $from = mktime(21, 45, 0, 6, 6, 2004);

        // test with integers (not yet passing)
        $this->assertEquals('less than a minute',
                            $this->helper->distanceOfTimeInWords(59));
        $this->assertEquals('about 1 hour',
                            $this->helper->distanceOfTimeInWords(60*60));
        $this->assertEquals('less than a minute',
                            $this->helper->distanceOfTimeInWords(0, 59));
        $this->assertEquals('about 1 hour',
                            $this->helper->distanceOfTimeInWords(60*60, 0));
    }
}
