<?php
class Horde_Date_Parser_Locale_Base
{
    public $definitions = array();

    /**
    # Parses a string containing a natural language date or time. If the parser
    # can find a date or time, either a Time or Chronic::Span will be returned
    # (depending on the value of <tt>:guess</tt>). If no date or time can be found,
    # +nil+ will be returned.
    #
    # Options are:
    #
    # [<tt>:context</tt>]
    #     <tt>:past</tt> or <tt>:future</tt> (defaults to <tt>:future</tt>)
    #
    #     If your string represents a birthday, you can set <tt>:context</tt> to <tt>:past</tt>
    #     and if an ambiguous string is given, it will assume it is in the
    #     past. Specify <tt>:future</tt> or omit to set a future context.
    #
    # [<tt>:now</tt>]
    #     Time (defaults to Time.now)
    #
    #     By setting <tt>:now</tt> to a Time, all computations will be based off
    #     of that time instead of Time.now
    #
    # [<tt>:guess</tt>]
    #     +true+ or +false+ (defaults to +true+)
    #
    #     By default, the parser will guess a single point in time for the
    #     given date or time. If you'd rather have the entire time span returned,
    #     set <tt>:guess</tt> to +false+ and a Chronic::Span will be returned.
    #
    # [<tt>:ambiguousTimeRange</tt>]
    #     Integer or <tt>:none</tt> (defaults to <tt>6</tt> (6am-6pm))
    #
    #     If an Integer is given, ambiguous times (like 5:00) will be
    #     assumed to be within the range of that time in the AM to that time
    #     in the PM. For example, if you set it to <tt>7</tt>, then the parser will
    #     look for the time between 7am and 7pm. In the case of 5:00, it would
    #     assume that means 5:00pm. If <tt>:none</tt> is given, no assumption
    #     will be made, and the first matching instance of that time will
    #     be used.
    */
    public function parse($text, $specifiedOptions = array())
    {
        // get options and set defaults if necessary
        $defaultOptions = array(
            'context' => 'future',
            'now' => new Horde_Date,
            'guess' => true,
            'ambiguousTimeRange' => 6,
        );
        $options = array_merge($defaultOptions, $specifiedOptions);

        // ensure the specified options are valid
        foreach (array_keys($specifiedOptions) as $key) {
            if (!isset($defaultOptions[$key])) {
                throw new InvalidArgumentException("$key is not a valid option key");
            }
        }

        if (!in_array($options['context'], array('past', 'future', 'none'))) {
            throw new InvalidArgumentException("Invalid value " . $options['context'] . " for 'context' specified. Valid values are 'past', 'future', and 'none'");
        }

        // store now for later =)
        $this->now = $options['now'];

        // put the text into a normal format to ease scanning
        $text = $this->preNormalize($text);

        // get base tokens for each word
        $tokens = $this->baseTokenize($text);

        // scan the tokens with each token scanner
        foreach (array('Repeater') as $tokenizer) {
            $tokenizer = $this->componentFactory($tokenizer);
            $tokens = $tokenizer->scan($tokens, $options);
        }

        foreach (array('Grabber', 'Pointer', 'Scalar', 'Ordinal', 'Separator', 'Timezone') as $tokenizer) {
            $tokenizer = $this->componentFactory($tokenizer);
            $tokens = $tokenizer->scan($tokens);
        }

        // strip any non-tagged tokens
        $tokens = array_filter($tokens, create_function('$t', 'return $t->tagged();'));

        if (Horde_Date_Parser::$debug) {
            echo "+---------------------------------------------------\n";
            echo "| " + implode(', ', $tokens) . "\n";
            echo "+---------------------------------------------------\n";
        }

        // do the heavy lifting
        $span = $this->tokensToSpan($tokens, $options);

        // guess a time within a span if required
        if ($options['guess']) {
            return $this->guess($span);
        } else {
            return $span;
        }
    }

    /**
    # Clean up the specified input text by stripping unwanted characters,
    # converting idioms to their canonical form, converting number words
    # to numbers (three => 3), and converting ordinal words to numeric
    # ordinals (third => 3rd)
    */
    public function preNormalize($text)
    {
        $normalizedText = strtolower($text);
        $normalizedText = $this->numericizeNumbers($normalizedText);
        $normalizedText = preg_replace('/[\'"\.]/', '', $normalizedText);
        $normalizedText = preg_replace('/([\/\-\,\@])/', ' \1 ', $normalizedText);
        $normalizedText = preg_replace('/\btoday\b/', 'this day', $normalizedText);
        $normalizedText = preg_replace('/\btomm?orr?ow\b/', 'next day', $normalizedText);
        $normalizedText = preg_replace('/\byesterday\b/', 'last day', $normalizedText);
        $normalizedText = preg_replace('/\bnoon\b/', '12:00', $normalizedText);
        $normalizedText = preg_replace('/\bmidnight\b/', '24:00', $normalizedText);
        $normalizedText = preg_replace('/\bbefore now\b/', 'past', $normalizedText);
        $normalizedText = preg_replace('/\bnow\b/', 'this second', $normalizedText);
        $normalizedText = preg_replace('/\b(ago|before)\b/', 'past', $normalizedText);
        $normalizedText = preg_replace('/\bthis past\b/', 'last', $normalizedText);
        $normalizedText = preg_replace('/\bthis last\b/', 'last', $normalizedText);
        $normalizedText = preg_replace('/\b(?:in|during) the (morning)\b/', '\1', $normalizedText);
        $normalizedText = preg_replace('/\b(?:in the|during the|at) (afternoon|evening|night)\b/', '\1', $normalizedText);
        $normalizedText = preg_replace('/\btonight\b/', 'this night', $normalizedText);
        $normalizedText = preg_replace('/(?=\w)([ap]m|oclock)\b/', ' \1', $normalizedText);
        $normalizedText = preg_replace('/\b(hence|after|from)\b/', 'future', $normalizedText);;
        $normalizedText = $this->numericizeOrdinals($normalizedText);
    }

    /**
     * Convert number words to numbers (three => 3)
     */
    public function numericizeNumbers($text)
    {
        return Horde_Support_Numerizer::numerize($normalizedText, array('locale' => $this->locale));
    }

    /**
     * Convert ordinal words to numeric ordinals (third => 3rd)
     */
    public function numericizeOrdinals($text)
    {
        return $text;
    }

    /**
     * Split the text on spaces and convert each word into a Token
     */
    public function baseTokenize($text)
    {
        return array_map(create_function('$w', 'return new Horde_Date_Parser_Token($w);'), preg_split('/\s+/', $text));
    }

    /**
     * Guess a specific time within the given span
     */
    public function guess($span)
    {
        if (empty($span)) {
            return null;
        }
        if ($span->width > 1) {
            return $span->begin + ($span->width() / 2);
        } else {
            return $span->begin;
        }
    }

    public function initDefinitions()
    {
        if ($this->definitions) { return; }

        $this->definitions = array(
            'time' => array(
                new Horde_Date_Parser_Handler(array('repeater_time', 'repeater_day_portion?'), null),
            ),

            'date' => array(
                new Horde_Date_Parser_Handler(array('repeater_day_name', 'repeater_month_name', 'scalar_day', 'repeater_time', 'time_zone', 'scalar_year'), 'handle_rdn_rmn_sd_t_tz_sy'),
                new Horde_Date_Parser_Handler(array('repeater_month_name', 'scalar_day', 'scalar_year'), 'handle_rmn_sd_sy'),
                new Horde_Date_Parser_Handler(array('repeater_month_name', 'scalar_day', 'scalar_year', 'separator_at?', 'time?'), 'handle_rmn_sd_sy'),
                new Horde_Date_Parser_Handler(array('repeater_month_name', 'scalar_day', 'separator_at?', 'time?'), 'handle_rmn_sd'),
                new Horde_Date_Parser_Handler(array('repeater_month_name', 'ordinal_day', 'separator_at?', 'time?'), 'handle_rmn_od'),
                new Horde_Date_Parser_Handler(array('repeater_month_name', 'scalar_year'), 'handle_rmn_sy'),
                new Horde_Date_Parser_Handler(array('scalar_day', 'repeater_month_name', 'scalar_year', 'separator_at?', 'time?'), 'handle_sd_rmn_sy'),
                new Horde_Date_Parser_Handler(array('scalar_month', 'separator_slash_or_dash', 'scalar_day', 'separator_slash_or_dash', 'scalar_year', 'separator_at?', 'time?'), 'handle_sm_sd_sy'),
                new Horde_Date_Parser_Handler(array('scalar_day', 'separator_slash_or_dash', 'scalar_month', 'separator_slash_or_dash', 'scalar_year', 'separator_at?', 'time?'), 'handle_sd_sm_sy'),
                new Horde_Date_Parser_Handler(array('scalar_year', 'separator_slash_or_dash', 'scalar_month', 'separator_slash_or_dash', 'scalar_day', 'separator_at?', 'time?'), 'handle_sy_sm_sd'),
                new Horde_Date_Parser_Handler(array('scalar_month', 'separator_slash_or_dash', 'scalar_year'), 'handle_sm_sy'),
            ),

            // tonight at 7pm
            'anchor' => array(
                new Horde_Date_Parser_Handler(array('grabber?', 'repeater', 'separator_at?', 'repeater?', 'repeater?'), 'handle_r'),
                new Horde_Date_Parser_Handler(array('grabber?', 'repeater', 'repeater', 'separator_at?', 'repeater?', 'repeater?'), 'handle_r'),
                new Horde_Date_Parser_Handler(array('repeater', 'grabber', 'repeater'), 'handle_r_g_r'),
            ),

            // 3 weeks from now, in 2 months
            'arrow' => array(
                new Horde_Date_Parser_Handler(array('scalar', 'repeater', 'pointer'), 'handle_s_r_p'),
                new Horde_Date_Parser_Handler(array('pointer', 'scalar', 'repeater'), 'handle_p_s_r'),
                new Horde_Date_Parser_Handler(array('scalar', 'repeater', 'pointer', 'anchor'), 'handle_s_r_p_a'),
            ),

            // 3rd week in march
            'narrow' => array(
                new Horde_Date_Parser_Handler(array('ordinal', 'repeater', 'separator_in', 'repeater'), 'handle_o_r_s_r'),
                new Horde_Date_Parser_Handler(array('ordinal', 'repeater', 'grabber', 'repeater'), 'handle_o_r_g_r'),
            ),
        );
    }

    public function tokensToSpans($tokens, $options)
    {
        $this->initDefinitions();

        // maybe it's a specific date
        foreach ($this->definitions['date'] as $handler) {
            if ($handler->match($tokens, $this->definitions)) {
                if (Horde_Date_Parser::$debug) { echo "-date\n"; }
                $goodTokens = array_filter($tokens, create_function('$o', 'return !$o->getTag("Separator");'));
                return call_user_func(array($this, $handler->handlerMethod), $goodTokens, $options);
            }
        }

        // I guess it's not a specific date, maybe it's just an anchor
        foreach ($this->definitions['anchor'] as $handler) {
            if ($handler->match($tokens, $this->definitions)) {
                if (Horde_Date_Parser::$debug) { echo "-anchor\n"; }
                $goodTokens = array_filter($tokens, create_function('$o', 'return !$o->getTag("Separator");'));
                return call_user_func(array($this, $handler->handlerMethod), $goodTokens, $options);
            }
        }

        // not an anchor, perhaps it's an arrow
        foreach ($this->definitions['arrow'] as $handler) {
            if ($handler->match($tokens, $this->definitions)) {
                if (Horde_Date_Parser::$debug) { echo "-arrow\n"; }
                $goodTokens = array_filter($tokens, create_function('$o', 'return !$o->getTag("SeparatorAt") && !$o->getTag("SeparatorSlashOrDash") && !$o->getTag("SeparatorComma");'));
                return call_user_func(array($this, $handler->handlerMethod), $goodTokens, $options);
            }
        }

        // not an arrow, let's hope it's a narrow
        foreach ($this->definitions['narrow'] as $handler) {
            if ($handler->match($tokens, $this->definitions)) {
                if (Horde_Date_Parser::$debug) { echo "-narrow\n"; }
                //good_tokens = tokens.select { |o| !o.get_tag Separator }
                return call_user_func(array($this, $handler->handlerMethod), $tokens, $options);
            }
        }

        // I guess you're out of luck!
        if (Horde_Date_Parser::$debug) { echo "-none\n"; }
        return null;
    }


    public function dayOrTime($dayStart, $timeTokens, $options)
    {
        $outerSpan = new Horde_Date_Span($dayStart, $dayStart + (24 * 60 * 60));

        if (!empty($timeTokens)) {
            $this->now = $outerSpan->begin;
            return $this->getAnchor($this->dealiasAndDisambiguateTimes($timeTokens, $options), $options);
        } else {
            return $outerSpan;
        }
    }


    public function handle_m_d($month, $day, $timeTokens, $options)
    {
        $month->start = $this->now;
        $span = $month->this($options['context']);

        $dayStart = new Horde_Date(array('year' => $span->begin->year, 'month' => $span->begin->month, 'day' => $day));
        return $this->dayOrTime($dayStart, $timeTokens, $options);
    }

    public function handle_rmn_sd($tokens, $options)
    {
        return $this->handle_m_d($tokens[0]->getTag('RepeaterMonthName'), $tokens[1]->getTag('ScalarDay')->type, array_slice($tokens, 2), $options);
    }

    public function handle_rmn_od($tokens, $options)
    {
        return $this->handle_m_d($tokens[0]->getTag('RepeaterMonthName'), $tokens[1]->getTag('OrdinalDay')->type, array_slice($tokens, 2), $options);
    }

    public function handle_rmn_sy($tokens, $options)
    {
        $month = $tokens[0]->getTag('RepeaterMonthName')->index;
        $year = $tokens[1]->getTag('ScalarYear')->type;

        try {
            return new Horde_Date_Span(new Horde_Date(array('year' => $year, 'month' => $month)), new Horde_Date(array('year' => $year, 'month' => $month + 1)));
        } catch (Exception $e) {
            return null;
        }
    }

    public function handle_rdn_rmn_sd_t_tz_sy($tokens, $options)
    {
        $month = $tokens[1]->getTag('RepeaterMonthName')->index;
        $day = $tokens[2]->getTag('ScalarDay')->type;
        $year = $tokens[5]->getTag('ScalarYear')->type;

        try {
            $dayStart = new Horde_Date(array('year' => $year, 'month' => $month, 'day' => $day));
            return $this->dayOrTime($daystart, array($tokens[3]), $options);
        } catch (Exception $e) {
            return null;
        }
    }

    public function handle_rmn_sd_sy($tokens, $options)
    {
        $month = $tokens[0]->getTag('RepeaterMonthName')->index;
        $day = $tokens[1]->getTag('ScalarDay')->type;
        $year = $tokens[2]->getTag('ScalarYear')->type;

        $timeTokens = array_slice($tokens, 3);

        try {
            $dayStart = new Horde_Date(array('year' => $year, 'month' => $month, 'day' => $day));
            return $this->dayOrTime($dayStart, $timeTokens, $options);
        } catch (Exception $e) {
            return null;
        }
    }

    public function handle_sd_rmn_sy($tokens, $options)
    {
        $newTokens = array($tokens[1], $tokens[0], $tokens[2]);
        $timeTokens = array_slice($tokens, 3);
        return $this->handle_rmn_sd_sy($newTokens + $timeTokens, $options);
    }

    public function handle_sm_sd_sy($tokens, $options)
    {
        $month = $tokens[0]->getTag('ScalarMonth')->type;
        $day = $tokens[1]->getTag('ScalarDay')->type;
        $year = $tokens[2]->getTag('ScalarYear')->type;

        $timeTokens = array_slice($tokens, 3);

        try {
            $dayStart = new Horde_Date(array('year' => $year, 'month' => $month, 'day' => $day));
            return $this->dayOrTime($dayStart, $timeTokens, $options);
        } catch (Exception $e) {
            return null;
        }
    }

    public function handle_sd_sm_sy($tokens, $options)
    {
        $newTokens = array($tokens[1], $tokens[0], $tokens[2]);
        $timeTokens = array_slice($tokens, 3);
        return $this->handle_sm_sd_sy($newTokens + $timeTokens, $options);
    }

    public function handle_sy_sm_sd($tokens, $options)
    {
        $newTokens = array($tokens[1], $tokens[2], $tokens[0]);
        $timeTokens = array_slice($tokens, 3);
        return $this->handle_sm_sd_sy($newTokens + $timeTokens, $options);
    }

    public function handle_sm_sy($tokens, $options)
    {
        $month = $tokens[0]->getTag('ScalarMonth')->type;
        $year = $tokens[1]->getTag('ScalarYear')->type;

        try {
            return new Horde_Date_Span(new Horde_Date(array('year' => $year, 'month' => $month)), new Horde_Date(array('year' => $year, 'month' => $month = 1)));
        } catch (Exception $e) {
            return null;
        }
    }


    /*##########################################################################
    # Anchors
    ##########################################################################*/

    public function handle_r($tokens, $options)
    {
        $ddTokens = $this->dealiasAndDisambiguateTimes($tokens, $options);
        return $this->getAnchor($ddTokens, $options);
    }

    public function handle_r_g_r($tokens, $options)
    {
        $newTokens = array($tokens[1], $tokens[0], $tokens[2]);
        return $this->handle_r($newTokens, $options);
    }


    /*##########################################################################
    # Arrows
    ##########################################################################*/

    public function handle_srp($tokens, $span, $options)
    {
        $distance = $tokens[0]->getTag('Scalar')->type;
        $repeater = $tokens[1]->getTag('Repeater');
        $pointer = $tokens[2]->getTag('Pointer')->type;

        return $repeater->offset($span, $distance, $pointer);
    }

    public function handle_s_r_p($tokens, $options)
    {
        $repeater = $tokens[1]->getTag('Repeater');

        /*
          # span =
          # case true
          # when [RepeaterYear, RepeaterSeason, RepeaterSeasonName, RepeaterMonth, RepeaterMonthName, RepeaterFortnight, RepeaterWeek].include?(repeater.class)
          #   self.parse("this hour", :guess => false, :now => @now)
          # when [RepeaterWeekend, RepeaterDay, RepeaterDayName, RepeaterDayPortion, RepeaterHour].include?(repeater.class)
          #   self.parse("this minute", :guess => false, :now => @now)
          # when [RepeaterMinute, RepeaterSecond].include?(repeater.class)
          #   self.parse("this second", :guess => false, :now => @now)
          # else
          #   raise(ChronicPain, "Invalid repeater: #{repeater.class}")
          # end
        */

        $span = $this->parse('this second', array('guess' => false, 'now' => $this->now));
        return $this->handle_srp($tokens, $span, $options);
    }

    public function handle_p_s_r($tokens, $options)
    {
        $newTokens = array($tokens[1], $tokens[2], $tokens[0]);
        return $this->handle_s_r_p($newTokens, $options);
    }

    public function handle_s_r_p_a($tokens, $options)
    {
        $anchorSpan = $this->getAnchor(array_slice($tokens, 3), $options);
        return $this->handle_srp($tokens, $anchorSpan, $options);
    }


    /*##########################################################################
    # Narrows
    ##########################################################################*/

    public function handle_orr($tokens, $outerSpan, $options)
    {
        $repeater = $tokens[1]->getTag('Repeater');
        $repeater->start = $outerSpan->begin - 1;
        $ordinal = $tokens[0]->getTag('Ordinal')->type;
        $span = null;

        for ($i = 0; $i < $ordinal; $i++) {
            $span = $repeater->next('future');
            if ($span->begin > $outerSpan->end) {
                $span = null;
                break;
            }
        }
        return $span;
    }

    public function handle_o_r_s_r($tokens, $options)
    {
        $outerSpan = $this->getAnchor(array($tokens[3]), $options);
        return $this->handle_orr(array($tokens[0], $tokens[1]), $outerSpan, $options);
    }

    public function handle_o_r_g_r($tokens, $options)
    {
        $outerSpan = $this->getAnchor(array($tokens[2], $tokens[3]), $options);
        return $this->handle_orr(array($tokens[0], $tokens[1]), $outerSpan, $options);
    }


    /*##########################################################################
    # Support Methods
    ##########################################################################*/

    public function getAnchor($tokens, $options)
    {
        $grabber = $this->componentFactory('Grabber', array('this'));
        $pointer = 'future';

        $repeaters = $this->getRepeaters($tokens);
        for ($i = 0, $size = count($repeaters); $i < $size; $i++) {
            array_pop($tokens);
        }

        if (count($tokens) && $tokens[0]->getTag('Grabber')) {
            $grabber = $tokens[0]->getTag('Grabber');
            array_pop($tokens);
        }

        $head = array_shift($repeaters);
        $head->start = $this->now;

        switch ($grabber->type) {
        case 'last':
            $outerSpan = $head->next('past');
            break;

        case 'this':
            if (count($repeaters)) {
                $outerSpan = $head->this('none');
            } else {
                $outerSpan = $head->this($options['context']);
            }
            break;

        case 'next':
            $outerSpan = $head->next('future');
            break;

        default:
            throw new Horde_Date_Parser_Exception('Invalid grabber ' . $grabber->type);
        }

        if (Horde_Date_Parser::$debug) { echo "--$outerSpan\n"; }
        return $this->findWithin($repeaters, $outerSpan, $pointer);
    }

    public function getRepeaters($tokens)
    {
        $repeaters = array();
        foreach ($tokens as $token) {
            if ($t = $token->getTag('Repeater')) {
                $repeaters[] = $t;
            }
        }

        rsort($repeaters);
        return $repeaters;
    }

    /**
     * Recursively finds repeaters within other repeaters.  Returns a Span
     * representing the innermost time span or null if no repeater union could
     * be found
     */
    public function findWithin($tags, $span, $pointer)
    {
        if (Horde_Date_Parser::$debug) { echo "--$span\n"; }
        if (empty($tags)) { return $span; }

        $head = array_shift($tags);
        $rest = $tags;
        $head->start = ($pointer == 'future') ? $span->begin : $span->end;
        $h = $head->this('none');

        if ($span->include($h->begin) || $span->include($h->end)) {
            return $this->findWithin($rest, $h, $pointer);
        } else {
            return null;
        }
    }

    /**
     * handle aliases of am/pm
     * 5:00 in the morning -> 5:00 am
     * 7:00 in the evening -> 7:00 pm
     */
    public function dealiasAndDisambiguateTimes($tokens, $options)
    {
        $dayPortionIndex = null;
        foreach ($tokens as $i => $t) {
            if ($t->getTag('RepeaterDayPortion')) {
                $dayPortionIndex = $i;
                break;
            }
        }

        $timeIndex = null;
        foreach ($tokens as $i => $t) {
            if ($t->getTag('RepeaterTime')) {
                $timeIndex = $i;
                break;
            }
        }

        if ($dayPortionIndex && $timeIndex) {
            $t1 = $tokens[$dayPortionIndex];
            $t1tag = $t1->getTag('RepeaterDayPortion');

            if ($t1tag->type == 'morning') {
                if (Horde_Date_Parser::$debug) { echo "--morning->am\n"; }
                $t1->untag('RepeaterDayPortion');
                $t1->tag(new Horde_Date_Parser_Locale_Base_RepeaterDayPortion('am'));
            } elseif (in_array($t1tag->type, array('afternoon', 'evening', 'night'))) {
                if (Horde_Date_Parser::$debug) { echo "--{$t1tag->type}->pm\n"; }
                $t1->untag('RepeaterDayPortion');
                $t1->tag(new Horde_Date_Parser_Locale_Base_RepeaterDayPortion('pm'));
            }
        }

        /*
          # tokens.each_with_index do |t0, i|
          #   t1 = tokens[i + 1]
          #   if t1 && (t1tag = t1.get_tag(RepeaterDayPortion)) && t0.get_tag(RepeaterTime)
          #     if [:morning].include?(t1tag.type)
          #       puts '--morning->am' if Chronic.debug
          #       t1.untag(RepeaterDayPortion)
          #       t1.tag(RepeaterDayPortion.new(:am))
          #     elsif [:afternoon, :evening, :night].include?(t1tag.type)
          #       puts "--#{t1tag.type}->pm" if Chronic.debug
          #       t1.untag(RepeaterDayPortion)
          #       t1.tag(RepeaterDayPortion.new(:pm))
          #     end
          #   end
          # end
        */

        // handle ambiguous times if :ambiguousTimeRange is specified
        if ($options['ambiguousTimeRange'] != 'none') {
            $ttokens = array();
            foreach ($tokens as $i => $t0) {
                $ttokens[] = $t0;
                $t1 = isset($tokens[$i + 1]) ? $tokens[$i + 1] : null;
                if ($t0->getTag('RepeaterTime') && $t0->getTag('RepeaterTime')->type->ambiguous() && (!$t1 || !$t1->getTag('RepeaterDayPortion'))) {
                    $distoken = new Horde_Date_Parser_Token('disambiguator');
                    $distoken->tag(new Horde_Date_Parser_Locale_Base_RepeaterDayPortion($options['ambiguousTimeRange']));
                    $ttokens[] = $distoken;
                }
            }

            $tokens = $ttokens;
        }

        return $tokens;
    }

}
