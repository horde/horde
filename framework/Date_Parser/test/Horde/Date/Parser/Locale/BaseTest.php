<?php
/**
 * @category   Horde
 * @package    Horde_Date
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @package    Horde_Date
 * @subpackage UnitTests
 */
class Horde_Date_Parser_Locale_BaseTest extends Horde_Test_Case
{
    public function setUp()
    {
        // Wed Aug 16 14:00:00 UTC 2006
        $this->now = new Horde_Date('2006-08-16 14:00:00');
        $this->parser = Horde_Date_Parser::factory(array('now' => $this->now));
    }

    public function testTodayAt11()
    {
        $this->assertEquals('2006-08-16 11:00:00', (string)$this->parser->parse('today at 11'));
    }

    public function testTomorrow()
    {
        $this->assertEquals('2006-08-17 12:00:00', (string)$this->parser->parse('tomorrow'));
    }

    public function testMay27()
    {
        $this->assertEquals('2007-05-27 12:00:00', (string)$this->parser->parse('may 27'));
    }

    public function testThursday()
    {
        $this->assertEquals('2006-08-17 12:00:00', (string)$this->parser->parse('thursday'));
    }

    public function testNextMonth()
    {
        $this->assertEquals('2006-09-15 12:00:00', (string)$this->parser->parse('next month'));
    }

    public function testLastWeekTuesday()
    {
        $this->assertEquals('2006-08-08 12:00:00', (string)$this->parser->parse('last week tuesday'));
    }

    public function test3YearsAgo()
    {
        $this->assertEquals('2003-08-16 14:00:00', (string)$this->parser->parse('3 years ago'));
        $this->assertEquals('2003-08-16 14:00:00', (string)$this->parser->parse('three years ago'));
    }

    public function test6InTheMorning()
    {
        $this->assertEquals('2006-08-16 06:00:00', (string)$this->parser->parse('6 in the morning'));
    }

    public function testAfternoonYesterday()
    {
        $this->assertEquals('2006-08-15 15:00:00', (string)$this->parser->parse('afternoon yesterday'));
    }

    public function test3rdWednesdayInNovember()
    {
        $this->assertEquals('2006-11-15 12:00:00', (string)$this->parser->parse('3rd wednesday in november'));
    }

    public function test4thDayLastWeek()
    {
        $this->assertEquals('2006-08-09 12:00:00', (string)$this->parser->parse('4th day last week'));
    }

    public function testTwoMonthsAgoThisFriday()
    {
        $this->assertEquals('2006-06-18 12:00:00', (string)$this->parser->parse('two months ago this friday'));
    }

    public function testParseGuessDates_rm_sd()
    {
        $time = $this->parser->parse("may 27");
        $this->assertEquals(new Horde_Date('2007-05-27 12:00:00'), $time);

        $time = $this->parser->parse("may 28", array('context' => 'past'));
        $this->assertEquals(new Horde_Date('2006-05-28 12:00:00'), $time);

        $time = $this->parser->parse("may 28 5pm", array('context' => 'past'));
        $this->assertEquals(new Horde_Date('2006-05-28 17:00:00'), $time);

        $time = $this->parser->parse("may 28 at 5pm", array('context' => 'past'));
        $this->assertEquals(new Horde_Date('2006-05-28 17:00:00'), $time);

        $time = $this->parser->parse("may 28 at 5:32.19pm", array('context' => 'past'));
        $this->assertEquals(new Horde_Date('2006-05-28 17:32:19'), $time);
    }

    public function testParseGuessDates_rm_od()
    {
        $time = $this->parser->parse("may 27th");
        $this->assertEquals(new Horde_Date('2007-05-27 12:00:00'), $time);

        $time = $this->parser->parse("may 27th", array('context' => 'past'));
        $this->assertEquals(new Horde_Date('2006-05-27 12:00:00'), $time);

        $time = $this->parser->parse("may 27th 5:00 pm", array('context' => 'past'));
        $this->assertEquals(new Horde_Date('2006-05-27 17:00:00'), $time);

        $time = $this->parser->parse("may 27th at 5pm", array('context' => 'past'));
        $this->assertEquals(new Horde_Date('2006-05-27 17:00:00'), $time);

        $time = $this->parser->parse("may 27th at 5", array('ambiguousTimeRange' => 'none'));
        $this->assertEquals(new Horde_Date('2007-05-27 05:00:00'), $time);
    }

    public function testParseGuessDates_rm_sy()
    {
        $time = $this->parser->parse("June 1979");
        $this->assertEquals(new Horde_Date('1979-06-16 00:00:00'), $time);

        $time = $this->parser->parse("dec 79");
        $this->assertEquals(new Horde_Date('1979-12-16 12:00:00'), $time);
    }

    public function testParseGuessDates_rm_sd_sy()
    {
        $time = $this->parser->parse("jan 3 2010");
        $this->assertEquals(new Horde_Date('2010-01-03 12:00:00'), $time);

        $time = $this->parser->parse("jan 3 2010 midnight");
        $this->assertEquals(new Horde_Date('2010-01-4 00:00:00'), $time);

        $time = $this->parser->parse("jan 3 2010 at midnight");
        $this->assertEquals(new Horde_Date('2010-01-04 00:00:00'), $time);

        $time = $this->parser->parse("jan 3 2010 at 4", array('ambiguousTimeRange' => 'none'));
        $this->assertEquals(new Horde_Date('2010-01-03 04:00:00'), $time);

        $time = $this->parser->parse("January 12, '00");
        $this->assertEquals(new Horde_Date('2000-01-12 12:00:00'), $time);

        $time = $this->parser->parse("may 27 79");
        $this->assertEquals(new Horde_Date('1979-05-27 12:00:00'), $time);

        $time = $this->parser->parse("may 27 79 4:30");
        $this->assertEquals(new Horde_Date('1979-05-27 16:30:00'), $time);

        $time = $this->parser->parse("may 27 79 at 4:30", array('ambiguousTimeRange' => 'none'));
        $this->assertEquals(new Horde_Date('1979-05-27 04:30:00'), $time);
    }

    public function testParseGuessDates_sd_rm_sy()
    {
        $time = $this->parser->parse("3 jan 2010");
        $this->assertEquals(new Horde_Date('2010-01-03 12:00:00'), $time);

        $time = $this->parser->parse("3 jan 2010 4pm");
        $this->assertEquals(new Horde_Date('2010-01-03 16:00:00'), $time);
    }

    public function testParseGuessDates_sm_sd_sy()
    {
        $time = $this->parser->parse("5/27/1979");
        $this->assertEquals(new Horde_Date('1979-05-27 12:00:00'), $time);

        $time = $this->parser->parse("5/27/1979 4am");
        $this->assertEquals(new Horde_Date('1979-05-27 04:00:00'), $time);
    }

    public function testParseGuessDates_sd_sm_sy()
    {
        /*
        $time = $this->parser->parse("27/5/1979");
        $this->assertEquals(new Horde_Date('1979, 5, 27, 12'), $time);

        $time = $this->parser->parse("27/5/1979 @ 0700");
        $this->assertEquals(new Horde_Date('1979, 5, 27, 7'), $time);
        */
    }

    public function testParseGuessDates_sm_sy()
    {
        /*
        $time = $this->parser->parse("05/06");
        $this->assertEquals(new Horde_Date('2006, 5, 16, 12'), $time);

        $time = $this->parser->parse("12/06");
        $this->assertEquals(new Horde_Date('2006, 12, 16, 12'), $time);

        $time = $this->parser->parse("13/06");
    assert_equal nil, time
        */
    }

    public function testParseGuessDates_sy_sm_sd()
    {
        /*
        $time = $this->parser->parse("2000-1-1");
        $this->assertEquals(new Horde_Date('2000, 1, 1, 12'), $time);

        $time = $this->parser->parse("2006-08-20");
        $this->assertEquals(new Horde_Date('2006, 8, 20, 12'), $time);

        $time = $this->parser->parse("2006-08-20 7pm");
        $this->assertEquals(new Horde_Date('2006, 8, 20, 19'), $time);

        $time = $this->parser->parse("2006-08-20 03:00");
        $this->assertEquals(new Horde_Date('2006, 8, 20, 3'), $time);

        $time = $this->parser->parse("2006-08-20 03:30:30");
        $this->assertEquals(new Horde_Date('2006, 8, 20, 3, 30, 30'), $time);

        $time = $this->parser->parse("2006-08-20 15:30:30");
        $this->assertEquals(new Horde_Date('2006, 8, 20, 15, 30, 30'), $time);

        $time = $this->parser->parse("2006-08-20 15:30.30");
        $this->assertEquals(new Horde_Date('2006, 8, 20, 15, 30, 30'), $time);
        */
    }

    public function testParseGuessDates_rdn_rm_rd_rt_rtz_ry()
    {
        /*
        $time = $this->parser->parse("Mon Apr 02 17:00:00 PDT 2007");
        $this->assertEquals(new Horde_Date('2007, 4, 2, 17'), $time);

    now = Time.now
        $time = $this->parser->parse(now.to_s)
    assert_equal now.to_s, time.to_s
        */
    }

    public function testParseGuessDates_rm_sd_rt()
    {
        /*
              $time = $this->parser->parse("jan 5 13:00");
              $this->assertEquals(new Horde_Date('2007, 1, 5, 13'), $time);
        */
    }

    public function testParseGuessDatesOverflow()
    {
        /*
        # due to limitations of the Time class, these don't work

        $time = $this->parser->parse("may 40");
    assert_equal nil, time

        $time = $this->parser->parse("may 27 40");
    assert_equal nil, time

        $time = $this->parser->parse("1800-08-20");
    assert_equal nil, time
        */
    }

    public function testParseGuessR()
    {
        /*
        $time = $this->parser->parse("friday");
        $this->assertEquals(new Horde_Date('2006, 8, 18, 12'), $time);

        $time = $this->parser->parse("tue");
        $this->assertEquals(new Horde_Date('2006, 8, 22, 12'), $time);

        $time = $this->parser->parse("5");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 17'), $time);

    time = Chronic.parse("5", :now => Time.local(2006, 8, 16, 3, 0, 0, 0), :ambiguous_time_range => :none)
        $this->assertEquals(new Horde_Date('2006, 8, 16, 5'), $time);

        $time = $this->parser->parse("13:00");
        $this->assertEquals(new Horde_Date('2006, 8, 17, 13'), $time);

        $time = $this->parser->parse("13:45");
        $this->assertEquals(new Horde_Date('2006, 8, 17, 13, 45'), $time);

        $time = $this->parser->parse("november");
        $this->assertEquals(new Horde_Date('2006, 11, 16'), $time);
        */
    }

    public function testParseGuessRR()
    {
        /*
        $time = $this->parser->parse("friday 13:00");
        $this->assertEquals(new Horde_Date('2006, 8, 18, 13'), $time);

        $time = $this->parser->parse("monday 4:00");
        $this->assertEquals(new Horde_Date('2006, 8, 21, 16'), $time);

        $time = $this->parser->parse("sat 4:00", :ambiguous_time_range => :none)
        $this->assertEquals(new Horde_Date('2006, 8, 19, 4'), $time);

        $time = $this->parser->parse("sunday 4:20", :ambiguous_time_range => :none)
        $this->assertEquals(new Horde_Date('2006, 8, 20, 4, 20'), $time);

        $time = $this->parser->parse("4 pm");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 16'), $time);

        $time = $this->parser->parse("4 am", :ambiguous_time_range => :none)
        $this->assertEquals(new Horde_Date('2006, 8, 16, 4'), $time);

        $time = $this->parser->parse("12 pm");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 12'), $time);

        $time = $this->parser->parse("12:01 pm");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 12, 1'), $time);

        $time = $this->parser->parse("12:01 am");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 0, 1'), $time);

        $time = $this->parser->parse("12 am");
        $this->assertEquals(new Horde_Date('2006, 8, 16'), $time);

        $time = $this->parser->parse("4:00 in the morning");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 4'), $time);

        $time = $this->parser->parse("november 4");
        $this->assertEquals(new Horde_Date('2006, 11, 4, 12'), $time);

        $time = $this->parser->parse("aug 24");
        $this->assertEquals(new Horde_Date('2006, 8, 24, 12'), $time);
        */
    }

    public function testParseGuessRRR()
    {
        /*
        $time = $this->parser->parse("friday 1 pm");
        $this->assertEquals(new Horde_Date('2006, 8, 18, 13'), $time);

        $time = $this->parser->parse("friday 11 at night");
        $this->assertEquals(new Horde_Date('2006, 8, 18, 23'), $time);

        $time = $this->parser->parse("friday 11 in the evening");
        $this->assertEquals(new Horde_Date('2006, 8, 18, 23'), $time);

        $time = $this->parser->parse("sunday 6am");
        $this->assertEquals(new Horde_Date('2006, 8, 20, 6'), $time);

        $time = $this->parser->parse("friday evening at 7");
        $this->assertEquals(new Horde_Date('2006, 8, 18, 19'), $time);
        */
    }

    /*
  def test_parse_guess_gr
    # year

        $time = $this->parser->parse("this year");
        $this->assertEquals(new Horde_Date('2006, 10, 24, 12, 30'), $time);

        $time = $this->parser->parse("this year", array('context' => 'past'));
        $this->assertEquals(new Horde_Date('2006, 4, 24, 12, 30'), $time);

    # month

        $time = $this->parser->parse("this month");
        $this->assertEquals(new Horde_Date('2006, 8, 24, 12'), $time);

        $time = $this->parser->parse("this month", array('context' => 'past'));
        $this->assertEquals(new Horde_Date('2006, 8, 8, 12'), $time);

    time = Chronic.parse("next month", :now => Time.local(2006, 11, 15))
        $this->assertEquals(new Horde_Date('2006, 12, 16, 12'), $time);

    # month name

        $time = $this->parser->parse("last november");
        $this->assertEquals(new Horde_Date('2005, 11, 16'), $time);

    # fortnight

        $time = $this->parser->parse("this fortnight");
        $this->assertEquals(new Horde_Date('2006, 8, 21, 19, 30'), $time);

        $time = $this->parser->parse("this fortnight", array('context' => 'past'));
        $this->assertEquals(new Horde_Date('2006, 8, 14, 19'), $time);

    # week

        $time = $this->parser->parse("this week");
        $this->assertEquals(new Horde_Date('2006, 8, 18, 7, 30'), $time);

        $time = $this->parser->parse("this week", array('context' => 'past'));
        $this->assertEquals(new Horde_Date('2006, 8, 14, 19'), $time);

    # weekend

        $time = $this->parser->parse("this weekend");
        $this->assertEquals(new Horde_Date('2006, 8, 20'), $time);

        $time = $this->parser->parse("this weekend", array('context' => 'past'));
        $this->assertEquals(new Horde_Date('2006, 8, 13'), $time);

        $time = $this->parser->parse("last weekend");
        $this->assertEquals(new Horde_Date('2006, 8, 13'), $time);

    # day

        $time = $this->parser->parse("this day");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 19, 30'), $time);

        $time = $this->parser->parse("this day", array('context' => 'past'));
        $this->assertEquals(new Horde_Date('2006, 8, 16, 7'), $time);

        $time = $this->parser->parse("today");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 19, 30'), $time);

        $time = $this->parser->parse("yesterday");
        $this->assertEquals(new Horde_Date('2006, 8, 15, 12'), $time);

        $time = $this->parser->parse("tomorrow");
        $this->assertEquals(new Horde_Date('2006, 8, 17, 12'), $time);

    # day name

        $time = $this->parser->parse("this tuesday");
        $this->assertEquals(new Horde_Date('2006, 8, 22, 12'), $time);

        $time = $this->parser->parse("next tuesday");
        $this->assertEquals(new Horde_Date('2006, 8, 22, 12'), $time);

        $time = $this->parser->parse("last tuesday");
        $this->assertEquals(new Horde_Date('2006, 8, 15, 12'), $time);

        $time = $this->parser->parse("this wed");
        $this->assertEquals(new Horde_Date('2006, 8, 23, 12'), $time);

        $time = $this->parser->parse("next wed");
        $this->assertEquals(new Horde_Date('2006, 8, 23, 12'), $time);

        $time = $this->parser->parse("last wed");
        $this->assertEquals(new Horde_Date('2006, 8, 9, 12'), $time);

    # day portion

        $time = $this->parser->parse("this morning");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 9'), $time);

        $time = $this->parser->parse("tonight");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 22'), $time);

    # minute

        $time = $this->parser->parse("next minute");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 14, 1, 30'), $time);

    # second

        $time = $this->parser->parse("this second");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 14'), $time);

        $time = $this->parser->parse("this second", array('context' => 'past'));
        $this->assertEquals(new Horde_Date('2006, 8, 16, 14'), $time);

        $time = $this->parser->parse("next second");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 14, 0, 1'), $time);

        $time = $this->parser->parse("last second");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 13, 59, 59'), $time);
  end

  def test_parse_guess_grr
        $time = $this->parser->parse("yesterday at 4:00");
        $this->assertEquals(new Horde_Date('2006, 8, 15, 16'), $time);

        $time = $this->parser->parse("today at 9:00");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 9'), $time);

        $time = $this->parser->parse("today at 2100");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 21'), $time);

        $time = $this->parser->parse("this day at 0900");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 9'), $time);

        $time = $this->parser->parse("tomorrow at 0900");
        $this->assertEquals(new Horde_Date('2006, 8, 17, 9'), $time);

        $time = $this->parser->parse("yesterday at 4:00", :ambiguous_time_range => :none)
        $this->assertEquals(new Horde_Date('2006, 8, 15, 4'), $time);

        $time = $this->parser->parse("last friday at 4:00");
        $this->assertEquals(new Horde_Date('2006, 8, 11, 16'), $time);

        $time = $this->parser->parse("next wed 4:00");
        $this->assertEquals(new Horde_Date('2006, 8, 23, 16'), $time);

        $time = $this->parser->parse("yesterday afternoon");
        $this->assertEquals(new Horde_Date('2006, 8, 15, 15'), $time);

        $time = $this->parser->parse("last week tuesday");
        $this->assertEquals(new Horde_Date('2006, 8, 8, 12'), $time);

        $time = $this->parser->parse("tonight at 7");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 19'), $time);

        $time = $this->parser->parse("tonight 7");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 19'), $time);

        $time = $this->parser->parse("7 tonight");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 19'), $time);
  end

  def test_parse_guess_grrr
        $time = $this->parser->parse("today at 6:00pm");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 18'), $time);

        $time = $this->parser->parse("today at 6:00am");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 6'), $time);

        $time = $this->parser->parse("this day 1800");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 18'), $time);

        $time = $this->parser->parse("yesterday at 4:00pm");
        $this->assertEquals(new Horde_Date('2006, 8, 15, 16'), $time);

        $time = $this->parser->parse("tomorrow evening at 7");
        $this->assertEquals(new Horde_Date('2006, 8, 17, 19'), $time);

        $time = $this->parser->parse("tomorrow morning at 5:30");
        $this->assertEquals(new Horde_Date('2006, 8, 17, 5, 30'), $time);

        $time = $this->parser->parse("next monday at 12:01 am");
        $this->assertEquals(new Horde_Date('2006, 8, 21, 00, 1'), $time);

        $time = $this->parser->parse("next monday at 12:01 pm");
        $this->assertEquals(new Horde_Date('2006, 8, 21, 12, 1'), $time);
  end

  def test_parse_guess_rgr
        $time = $this->parser->parse("afternoon yesterday");
        $this->assertEquals(new Horde_Date('2006, 8, 15, 15'), $time);

        $time = $this->parser->parse("tuesday last week");
        $this->assertEquals(new Horde_Date('2006, 8, 8, 12'), $time);
  end

  def test_parse_guess_s_r_p
    # past

        $time = $this->parser->parse("3 years ago");
        $this->assertEquals(new Horde_Date('2003, 8, 16, 14'), $time);

        $time = $this->parser->parse("1 month ago");
        $this->assertEquals(new Horde_Date('2006, 7, 16, 14'), $time);

        $time = $this->parser->parse("1 fortnight ago");
        $this->assertEquals(new Horde_Date('2006, 8, 2, 14'), $time);

        $time = $this->parser->parse("2 fortnights ago");
        $this->assertEquals(new Horde_Date('2006, 7, 19, 14'), $time);

        $time = $this->parser->parse("3 weeks ago");
        $this->assertEquals(new Horde_Date('2006, 7, 26, 14'), $time);

        $time = $this->parser->parse("2 weekends ago");
        $this->assertEquals(new Horde_Date('2006, 8, 5'), $time);

        $time = $this->parser->parse("3 days ago");
        $this->assertEquals(new Horde_Date('2006, 8, 13, 14'), $time);

    #time = parse_now("1 monday ago");
    #assert_equal Time.local(2006, 8, 14, 12'), $time);

        $time = $this->parser->parse("5 mornings ago");
        $this->assertEquals(new Horde_Date('2006, 8, 12, 9'), $time);

        $time = $this->parser->parse("7 hours ago");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 7'), $time);

        $time = $this->parser->parse("3 minutes ago");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 13, 57'), $time);

        $time = $this->parser->parse("20 seconds before now");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 13, 59, 40'), $time);

    # future

        $time = $this->parser->parse("3 years from now");
        $this->assertEquals(new Horde_Date('2009, 8, 16, 14, 0, 0'), $time);

        $time = $this->parser->parse("6 months hence");
        $this->assertEquals(new Horde_Date('2007, 2, 16, 14'), $time);

        $time = $this->parser->parse("3 fortnights hence");
        $this->assertEquals(new Horde_Date('2006, 9, 27, 14'), $time);

        $time = $this->parser->parse("1 week from now");
        $this->assertEquals(new Horde_Date('2006, 8, 23, 14, 0, 0'), $time);

        $time = $this->parser->parse("1 weekend from now");
        $this->assertEquals(new Horde_Date('2006, 8, 19'), $time);

        $time = $this->parser->parse("2 weekends from now");
        $this->assertEquals(new Horde_Date('2006, 8, 26'), $time);

        $time = $this->parser->parse("1 day hence");
        $this->assertEquals(new Horde_Date('2006, 8, 17, 14'), $time);

        $time = $this->parser->parse("5 mornings hence");
        $this->assertEquals(new Horde_Date('2006, 8, 21, 9'), $time);

        $time = $this->parser->parse("1 hour from now");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 15'), $time);

        $time = $this->parser->parse("20 minutes hence");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 14, 20'), $time);

        $time = $this->parser->parse("20 seconds from now");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 14, 0, 20'), $time);

    time = Chronic.parse("2 months ago", :now => Time.parse("2007-03-07 23:30"))
        $this->assertEquals(new Horde_Date('2007, 1, 7, 23, 30'), $time);
  end

  def test_parse_guess_p_s_r
        $time = $this->parser->parse("in 3 hours");
        $this->assertEquals(new Horde_Date('2006, 8, 16, 17'), $time);
  end

  def test_parse_guess_s_r_p_a
    # past

        $time = $this->parser->parse("3 years ago tomorrow");
        $this->assertEquals(new Horde_Date('2003, 8, 17, 12'), $time);

        $time = $this->parser->parse("3 years ago this friday");
        $this->assertEquals(new Horde_Date('2003, 8, 18, 12'), $time);

        $time = $this->parser->parse("3 months ago saturday at 5:00 pm");
        $this->assertEquals(new Horde_Date('2006, 5, 19, 17'), $time);

        $time = $this->parser->parse("2 days from this second");
        $this->assertEquals(new Horde_Date('2006, 8, 18, 14'), $time);

        $time = $this->parser->parse("7 hours before tomorrow at midnight");
        $this->assertEquals(new Horde_Date('2006, 8, 17, 17'), $time);

    # future
  end

  def test_parse_guess_o_r_s_r
        $time = $this->parser->parse("3rd wednesday in november");
        $this->assertEquals(new Horde_Date('2006, 11, 15, 12'), $time);

        $time = $this->parser->parse("10th wednesday in november");
    assert_equal nil, time

    # time = parse_now("3rd wednesday in 2007");
    # assert_equal Time.local(2007, 1, 20, 12'), $time);
  end

  def test_parse_guess_o_r_g_r
        $time = $this->parser->parse("3rd month next year");
        $this->assertEquals(new Horde_Date('2007, 3, 16, 12, 30'), $time);

        $time = $this->parser->parse("3rd thursday this september");
        $this->assertEquals(new Horde_Date('2006, 9, 21, 12'), $time);

        $time = $this->parser->parse("4th day last week");
        $this->assertEquals(new Horde_Date('2006, 8, 9, 12'), $time);
  end

  def test_parse_guess_nonsense
        $time = $this->parser->parse("some stupid nonsense");
    assert_equal nil, time
  end

  def test_parse_span
    span = parse_now("friday", :guess => false)
        $this->assertEquals(new Horde_Date('2006, 8, 18), span.begin
        $this->assertEquals(new Horde_Date('2006, 8, 19), span.end

    span = parse_now("november", :guess => false)
        $this->assertEquals(new Horde_Date('2006, 11), span.begin
        $this->assertEquals(new Horde_Date('2006, 12), span.end

    span = Chronic.parse("weekend" , :now => @time_2006_08_16_14_00_00, :guess => false)
        $this->assertEquals(new Horde_Date('2006, 8, 19), span.begin
        $this->assertEquals(new Horde_Date('2006, 8, 21), span.end
  end

  def test_parse_words
    assert_equal parse_now("33 days from now"), parse_now("thirty-three days from now");
    assert_equal parse_now("2867532 seconds from now"), parse_now("two million eight hundred and sixty seven thousand five hundred and thirty two seconds from now");
    assert_equal parse_now("may 10th"), parse_now("may tenth");
  end

  def test_parse_only_complete_pointers
    assert_equal parse_now("eat pasty buns today at 2pm"), @time_2006_08_16_14_00_00
    assert_equal parse_now("futuristically speaking today at 2pm"), @time_2006_08_16_14_00_00
    assert_equal parse_now("meeting today at 2pm"), @time_2006_08_16_14_00_00
  end

  def test_argument_validation
    assert_raise(Chronic::InvalidArgumentException) do
      time = Chronic.parse("may 27", :foo => :bar)
    end

    assert_raise(Chronic::InvalidArgumentException) do
      time = Chronic.parse("may 27", :context => :bar)
    end
  end

  private
  def parse_now(string, options={})
    Chronic.parse(string, {:now => TIME_2006_08_16_14_00_00 }.merge(options))
  end
    */

}
