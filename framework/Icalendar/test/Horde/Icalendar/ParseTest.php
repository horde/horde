<?php
/**
 * @category   Horde
 * @package    Icalendar
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @package    Icalendar
 * @subpackage UnitTests
 */
class Horde_Icalendar_ParseTest extends Horde_Test_Case
{
    public function testEmptyData()
    {
        $ical = new Horde_Icalendar();
        $ical->parsevCalendar(file_get_contents(__DIR__ . '/fixtures/empty.ics'));
        $this->assertEquals(
            array(),
            $ical->getComponents()
        );
        $ical->parsevCalendar('');
        $this->assertEquals(
            array(),
            $ical->getComponents()
        );
    }

    public function testEscapes()
    {
        $ical = new Horde_Icalendar();
        $ical->parsevCalendar(file_get_contents(__DIR__ . '/fixtures/escapes1.ics'));
        $this->assertEquals(
            array('There is a comma (escaped with a baskslash) in this sentence and some important words after it, see anything here?'),
            $ical->getComponent(0)->getAttributeValues('DESCRIPTION')
        );
        $this->assertEquals(
            array('There are important words after this dash - see anything here or have the words gone?'),
            $ical->getComponent(1)->getAttributeValues('DESCRIPTION')
        );
        $this->assertEquals(
            array('mailto:a@b.c'),
            $ical->getComponent(1)->getAttributeValues('ORGANIZER')
        );
        $this->assertEquals(
            array('Foo'),
            $ical->getComponent(0)->getAttributeValues('CATEGORIES')
        );
        $this->assertEquals(
            array('Foo', 'Foo,Bar', 'Bar'),
            $ical->getComponent(1)->getAttributeValues('CATEGORIES')
        );
    }

    public function testQuotedParameters()
    {
        $ical = new Horde_Icalendar();
        $ical->parsevCalendar(file_get_contents(__DIR__ . '/fixtures/quoted-params.ics'));
        $attr = $ical->getComponent(0)->getAttribute('ORGANIZER', true);
        $this->assertEquals(
            'Klä,rchen; Mül:ler',
            $attr[0]['CN']
        );
    }

    public function testVcalendar20()
    {
        $ical = new Horde_Icalendar();
        $ical->parsevCalendar(file_get_contents(__DIR__ . '/fixtures/vcal20.ics'));

        $this->assertEquals(
            array(
              0 => 
              array(
                'name' => 'PRODID',
                'params' => 
                array(
                ),
                'value' => '-//Google Inc//Google Calendar 70.9054//EN',
                'values' => 
                array(
                  0 => '-//Google Inc//Google Calendar 70.9054//EN',
                ),
              ),
              1 => 
              array(
                'name' => 'VERSION',
                'params' => 
                array(
                ),
                'value' => '2.0',
                'values' => 
                array(
                  0 => '2.0',
                ),
              ),
              2 => 
              array(
                'name' => 'CALSCALE',
                'params' => 
                array(
                ),
                'value' => 'GREGORIAN',
                'values' => 
                array(
                  0 => 'GREGORIAN',
                ),
              ),
              3 => 
              array(
                'name' => 'METHOD',
                'params' => 
                array(
                ),
                'value' => 'PUBLISH',
                'values' => 
                array(
                  0 => 'PUBLISH',
                ),
              ),
              4 => 
              array(
                'name' => 'X-WR-CALNAME',
                'params' => 
                array(
                ),
                'value' => 'PEAR - PHP Extension and Application Repository',
                'values' => 
                array(
                  0 => 'PEAR - PHP Extension and Application Repository',
                ),
              ),
              5 => 
              array(
                'name' => 'X-WR-TIMEZONE',
                'params' => 
                array(
                ),
                'value' => 'Atlantic/Reykjavik',
                'values' => 
                array(
                  0 => 'Atlantic/Reykjavik',
                ),
              ),
              6 => 
              array(
                'name' => 'X-WR-CALDESC',
                'params' => 
                array(
                ),
                'value' => 'pear.php.net activity calendar, bug triage, group meetings, qa, conferences or similar',
                'values' => 
                array(
                  0 => 'pear.php.net activity calendar, bug triage, group meetings, qa, conferences or similar',
                ),
              ),
            ),
            $ical->getAllAttributes()
        );

        $this->assertEquals(
            array(
              0 => 
              array(
                'name' => 'DTSTART',
                'params' => 
                array(
                ),
                'value' => 1224950400,
                'values' => 
                array(
                  0 => 1224950400,
                ),
              ),
              1 => 
              array(
                'name' => 'DTEND',
                'params' => 
                array(
                ),
                'value' => 1224968400,
                'values' => 
                array(
                  0 => 1224968400,
                ),
              ),
              2 => 
              array(
                'name' => 'DTSTAMP',
                'params' => 
                array(
                ),
                'value' => 1219138073,
                'values' => 
                array(
                  0 => 1219138073,
                ),
              ),
              3 => 
              array(
                'name' => 'UID',
                'params' => 
                array(
                ),
                'value' => 'ntnrt4go4482q2trk18bt62c0o@google.com',
                'values' => 
                array(
                  0 => 'ntnrt4go4482q2trk18bt62c0o@google.com',
                ),
              ),
              4 => 
              array(
                'name' => 'RECURRENCE-ID',
                'params' => 
                array(
                ),
                'value' => 1224950400,
                'values' => 
                array(
                  0 => 1224950400,
                ),
              ),
              5 => 
              array(
                'name' => 'CLASS',
                'params' => 
                array(
                ),
                'value' => 'PUBLIC',
                'values' => 
                array(
                  0 => 'PUBLIC',
                ),
              ),
              6 => 
              array(
                'name' => 'CREATED',
                'params' => 
                array(
                ),
                'value' => 1204763165,
                'values' => 
                array(
                  0 => 1204763165,
                ),
              ),
              7 => 
              array(
                'name' => 'DESCRIPTION',
                'params' => 
                array(
                ),
                'value' => 'Bug Triage session

Not been invited ? Want to attend ? Let us know and we\'ll add you!',
                'values' => 
                array(
                  0 => 'Bug Triage session

Not been invited ? Want to attend ? Let us know and we\'ll add you!',
                ),
              ),
              8 => 
              array(
                'name' => 'LAST-MODIFIED',
                'params' => 
                array(
                ),
                'value' => 1216413606,
                'values' => 
                array(
                  0 => 1216413606,
                ),
              ),
              9 => 
              array(
                'name' => 'LOCATION',
                'params' => 
                array(
                ),
                'value' => '#pear-bugs Efnet',
                'values' => 
                array(
                  0 => '#pear-bugs Efnet',
                ),
              ),
              10 => 
              array(
                'name' => 'SEQUENCE',
                'params' => 
                array(
                ),
                'value' => 2,
                'values' => 
                array(
                  0 => 2,
                ),
              ),
              11 => 
              array(
                'name' => 'STATUS',
                'params' => 
                array(
                ),
                'value' => 'CONFIRMED',
                'values' => 
                array(
                  0 => 'CONFIRMED',
                ),
              ),
              12 => 
              array(
                'name' => 'SUMMARY',
                'params' => 
                array(
                ),
                'value' => 'Bug Triage',
                'values' => 
                array(
                  0 => 'Bug Triage',
                ),
              ),
              13 => 
              array(
                'name' => 'TRANSP',
                'params' => 
                array(
                ),
                'value' => 'OPAQUE',
                'values' => 
                array(
                  0 => 'OPAQUE',
                ),
              ),
              14 => 
              array(
                'name' => 'CATEGORIES',
                'params' => 
                array(
                ),
                'value' => 'foo,bar,fuz buz,blah, blah',
                'values' => 
                array(
                  0 => 'foo',
                  1 => 'bar',
                  2 => 'fuz buz',
                  3 => 'blah, blah',
                ),
              ),
            ),
            $ical->getComponent(0)->getAllAttributes()
        );
    }

    public function testBug7423()
    {
        $ical = new Horde_Icalendar();
        $ical->parsevCalendar(file_get_contents(__DIR__ . '/fixtures/bug7423.ics'));
        $this->assertEquals(
            array('SUMMARY' => 'birthday'),
            $ical->getComponent(0)->toHash(true)
        );
    }

    public function testBug14153()
    {
	$ical = new Horde_Icalendar();
	$ical->parsevCalendar(file_get_contents(__DIR__ . '/fixtures/bug14153.ics'));
	$params = $ical->getComponent(1)->getAttribute('DTSTART', true);
	$tz = $params[0]['TZID'];
	$start = $ical->getComponent(1)->getAttribute('DTSTART');
	$dtstart = new Horde_Date($start, $tz);
	$this->assertEquals((string)$dtstart, '2015-10-03 15:00:00');
    }

}
