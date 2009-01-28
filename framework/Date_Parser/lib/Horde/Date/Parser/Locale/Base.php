<?php
class Horde_Date_Parser_Locale_Base
{
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

      def definitions #:nodoc:
        @definitions ||=
      {:time => [Handler.new([:repeater_time, :repeater_day_portion?], nil)],

       :date => [Handler.new([:repeater_day_name, :repeater_month_name, :scalar_day, :repeater_time, :time_zone, :scalar_year], :handle_rdn_rmn_sd_t_tz_sy),
                 Handler.new([:repeater_month_name, :scalar_day, :scalar_year], :handle_rmn_sd_sy),
                 Handler.new([:repeater_month_name, :scalar_day, :scalar_year, :separator_at?, 'time?'], :handle_rmn_sd_sy),
                 Handler.new([:repeater_month_name, :scalar_day, :separator_at?, 'time?'], :handle_rmn_sd),
                 Handler.new([:repeater_month_name, :ordinal_day, :separator_at?, 'time?'], :handle_rmn_od),
                 Handler.new([:repeater_month_name, :scalar_year], :handle_rmn_sy),
                 Handler.new([:scalar_day, :repeater_month_name, :scalar_year, :separator_at?, 'time?'], :handle_sd_rmn_sy),
                 Handler.new([:scalar_month, :separator_slash_or_dash, :scalar_day, :separator_slash_or_dash, :scalar_year, :separator_at?, 'time?'], :handle_sm_sd_sy),
                 Handler.new([:scalar_day, :separator_slash_or_dash, :scalar_month, :separator_slash_or_dash, :scalar_year, :separator_at?, 'time?'], :handle_sd_sm_sy),
                 Handler.new([:scalar_year, :separator_slash_or_dash, :scalar_month, :separator_slash_or_dash, :scalar_day, :separator_at?, 'time?'], :handle_sy_sm_sd),
                 Handler.new([:scalar_month, :separator_slash_or_dash, :scalar_year], :handle_sm_sy)],

       # tonight at 7pm
       :anchor => [Handler.new([:grabber?, :repeater, :separator_at?, :repeater?, :repeater?], :handle_r),
                   Handler.new([:grabber?, :repeater, :repeater, :separator_at?, :repeater?, :repeater?], :handle_r),
                   Handler.new([:repeater, :grabber, :repeater], :handle_r_g_r)],

       # 3 weeks from now, in 2 months
       :arrow => [Handler.new([:scalar, :repeater, :pointer], :handle_s_r_p),
                  Handler.new([:pointer, :scalar, :repeater], :handle_p_s_r),
                  Handler.new([:scalar, :repeater, :pointer, 'anchor'], :handle_s_r_p_a)],

       # 3rd week in march
       :narrow => [Handler.new([:ordinal, :repeater, :separator_in, :repeater], :handle_o_r_s_r),
                   Handler.new([:ordinal, :repeater, :grabber, :repeater], :handle_o_r_g_r)]
      }
    end

    def tokens_to_span(tokens, options) #:nodoc:
      # maybe it's a specific date

      self.definitions[:date].each do |handler|
        if handler.match(tokens, self.definitions)
          puts "-date" if Chronic.debug
          good_tokens = tokens.select { |o| !o.get_tag Separator }
          return self.send(handler.handler_method, good_tokens, options)
        end
      end

      # I guess it's not a specific date, maybe it's just an anchor

      self.definitions[:anchor].each do |handler|
        if handler.match(tokens, self.definitions)
          puts "-anchor" if Chronic.debug
          good_tokens = tokens.select { |o| !o.get_tag Separator }
          return self.send(handler.handler_method, good_tokens, options)
        end
      end

      # not an anchor, perhaps it's an arrow

      self.definitions[:arrow].each do |handler|
        if handler.match(tokens, self.definitions)
          puts "-arrow" if Chronic.debug
          good_tokens = tokens.reject { |o| o.get_tag(SeparatorAt) || o.get_tag(SeparatorSlashOrDash) || o.get_tag(SeparatorComma) }
          return self.send(handler.handler_method, good_tokens, options)
        end
      end

      # not an arrow, let's hope it's a narrow

      self.definitions[:narrow].each do |handler|
        if handler.match(tokens, self.definitions)
          puts "-narrow" if Chronic.debug
          #good_tokens = tokens.select { |o| !o.get_tag Separator }
          return self.send(handler.handler_method, tokens, options)
        end
      end

      # I guess you're out of luck!
      puts "-none" if Chronic.debug
      return nil
    end

    #--------------

    def day_or_time(day_start, time_tokens, options)
      outer_span = Span.new(day_start, day_start + (24 * 60 * 60))

      if !time_tokens.empty?
        @now = outer_span.begin
        time = get_anchor(dealias_and_disambiguate_times(time_tokens, options), options)
        return time
      else
        return outer_span
      end
    end

    #--------------

    def handle_m_d(month, day, time_tokens, options) #:nodoc:
      month.start = @now
      span = month.this(options[:context])

      day_start = Time.local(span.begin.year, span.begin.month, day)

      day_or_time(day_start, time_tokens, options)
    end

    def handle_rmn_sd(tokens, options) #:nodoc:
      handle_m_d(tokens[0].get_tag(RepeaterMonthName), tokens[1].get_tag(ScalarDay).type, tokens[2..tokens.size], options)
    end

    def handle_rmn_od(tokens, options) #:nodoc:
      handle_m_d(tokens[0].get_tag(RepeaterMonthName), tokens[1].get_tag(OrdinalDay).type, tokens[2..tokens.size], options)
    end

    def handle_rmn_sy(tokens, options) #:nodoc:
      month = tokens[0].get_tag(RepeaterMonthName).index
      year = tokens[1].get_tag(ScalarYear).type

      if month == 12
        next_month_year = year + 1
        next_month_month = 1
      else
        next_month_year = year
        next_month_month = month + 1
      end

      begin
        Span.new(Time.local(year, month), Time.local(next_month_year, next_month_month))
      rescue ArgumentError
        nil
      end
    end

    def handle_rdn_rmn_sd_t_tz_sy(tokens, options) #:nodoc:
      month = tokens[1].get_tag(RepeaterMonthName).index
      day = tokens[2].get_tag(ScalarDay).type
      year = tokens[5].get_tag(ScalarYear).type

      begin
        day_start = Time.local(year, month, day)
        day_or_time(day_start, [tokens[3]], options)
      rescue ArgumentError
        nil
      end
    end

    def handle_rmn_sd_sy(tokens, options) #:nodoc:
      month = tokens[0].get_tag(RepeaterMonthName).index
      day = tokens[1].get_tag(ScalarDay).type
      year = tokens[2].get_tag(ScalarYear).type

      time_tokens = tokens.last(tokens.size - 3)

      begin
        day_start = Time.local(year, month, day)
        day_or_time(day_start, time_tokens, options)
      rescue ArgumentError
        nil
      end
    end

    def handle_sd_rmn_sy(tokens, options) #:nodoc:
      new_tokens = [tokens[1], tokens[0], tokens[2]]
      time_tokens = tokens.last(tokens.size - 3)
      self.handle_rmn_sd_sy(new_tokens + time_tokens, options)
    end

    def handle_sm_sd_sy(tokens, options) #:nodoc:
      month = tokens[0].get_tag(ScalarMonth).type
      day = tokens[1].get_tag(ScalarDay).type
      year = tokens[2].get_tag(ScalarYear).type

      time_tokens = tokens.last(tokens.size - 3)

      begin
        day_start = Time.local(year, month, day) #:nodoc:
        day_or_time(day_start, time_tokens, options)
      rescue ArgumentError
        nil
      end
    end

    def handle_sd_sm_sy(tokens, options) #:nodoc:
      new_tokens = [tokens[1], tokens[0], tokens[2]]
      time_tokens = tokens.last(tokens.size - 3)
      self.handle_sm_sd_sy(new_tokens + time_tokens, options)
    end

    def handle_sy_sm_sd(tokens, options) #:nodoc:
      new_tokens = [tokens[1], tokens[2], tokens[0]]
      time_tokens = tokens.last(tokens.size - 3)
      self.handle_sm_sd_sy(new_tokens + time_tokens, options)
    end

    def handle_sm_sy(tokens, options) #:nodoc:
      month = tokens[0].get_tag(ScalarMonth).type
      year = tokens[1].get_tag(ScalarYear).type

      if month == 12
        next_month_year = year + 1
        next_month_month = 1
      else
        next_month_year = year
        next_month_month = month + 1
      end

      begin
        Span.new(Time.local(year, month), Time.local(next_month_year, next_month_month))
      rescue ArgumentError
        nil
      end
    end

    # anchors

    def handle_r(tokens, options) #:nodoc:
      dd_tokens = dealias_and_disambiguate_times(tokens, options)
      self.get_anchor(dd_tokens, options)
    end

    def handle_r_g_r(tokens, options) #:nodoc:
      new_tokens = [tokens[1], tokens[0], tokens[2]]
      self.handle_r(new_tokens, options)
    end

    # arrows

    def handle_srp(tokens, span, options) #:nodoc:
      distance = tokens[0].get_tag(Scalar).type
      repeater = tokens[1].get_tag(Repeater)
      pointer = tokens[2].get_tag(Pointer).type

      repeater.offset(span, distance, pointer)
    end

    def handle_s_r_p(tokens, options) #:nodoc:
      repeater = tokens[1].get_tag(Repeater)

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

      span = self.parse("this second", :guess => false, :now => @now)

      self.handle_srp(tokens, span, options)
    end

    def handle_p_s_r(tokens, options) #:nodoc:
      new_tokens = [tokens[1], tokens[2], tokens[0]]
      self.handle_s_r_p(new_tokens, options)
    end

    def handle_s_r_p_a(tokens, options) #:nodoc:
      anchor_span = get_anchor(tokens[3..tokens.size - 1], options)
      self.handle_srp(tokens, anchor_span, options)
    end

    # narrows

    def handle_orr(tokens, outer_span, options) #:nodoc:
      repeater = tokens[1].get_tag(Repeater)
      repeater.start = outer_span.begin - 1
      ordinal = tokens[0].get_tag(Ordinal).type
      span = nil
      ordinal.times do
        span = repeater.next(:future)
        if span.begin > outer_span.end
          span = nil
          break
        end
      end
      span
    end

    def handle_o_r_s_r(tokens, options) #:nodoc:
      outer_span = get_anchor([tokens[3]], options)
      handle_orr(tokens[0..1], outer_span, options)
    end

    def handle_o_r_g_r(tokens, options) #:nodoc:
      outer_span = get_anchor(tokens[2..3], options)
      handle_orr(tokens[0..1], outer_span, options)
    end

    # support methods

    def get_anchor(tokens, options) #:nodoc:
      grabber = Grabber.new(:this)
      pointer = :future

      repeaters = self.get_repeaters(tokens)
      repeaters.size.times { tokens.pop }

      if tokens.first && tokens.first.get_tag(Grabber)
        grabber = tokens.first.get_tag(Grabber)
        tokens.pop
      end

      head = repeaters.shift
      head.start = @now

      case grabber.type
        when :last
          outer_span = head.next(:past)
        when :this
          if repeaters.size > 0
            outer_span = head.this(:none)
          else
            outer_span = head.this(options[:context])
          end
        when :next
          outer_span = head.next(:future)
        else raise(ChronicPain, "Invalid grabber")
      end

      puts "--#{outer_span}" if Chronic.debug
      anchor = find_within(repeaters, outer_span, pointer)
    end

    def get_repeaters(tokens) #:nodoc:
      repeaters = []
          tokens.each do |token|
            if t = token.get_tag(Repeater)
          repeaters << t
        end
      end
      repeaters.sort.reverse
    end

    # Recursively finds repeaters within other repeaters.
    # Returns a Span representing the innermost time span
    # or nil if no repeater union could be found
    def find_within(tags, span, pointer) #:nodoc:
      puts "--#{span}" if Chronic.debug
      return span if tags.empty?

      head, *rest = tags
      head.start = pointer == :future ? span.begin : span.end
      h = head.this(:none)

      if span.include?(h.begin) || span.include?(h.end)
        return find_within(rest, h, pointer)
      else
        return nil
      end
    end

    def dealias_and_disambiguate_times(tokens, options) #:nodoc:
      # handle aliases of am/pm
      # 5:00 in the morning -> 5:00 am
      # 7:00 in the evening -> 7:00 pm

      day_portion_index = nil
      tokens.each_with_index do |t, i|
        if t.get_tag(RepeaterDayPortion)
          day_portion_index = i
          break
        end
      end

      time_index = nil
      tokens.each_with_index do |t, i|
        if t.get_tag(RepeaterTime)
          time_index = i
          break
        end
      end

      if (day_portion_index && time_index)
        t1 = tokens[day_portion_index]
        t1tag = t1.get_tag(RepeaterDayPortion)

        if [:morning].include?(t1tag.type)
          puts '--morning->am' if Chronic.debug
          t1.untag(RepeaterDayPortion)
          t1.tag(RepeaterDayPortion.new(:am))
        elsif [:afternoon, :evening, :night].include?(t1tag.type)
          puts "--#{t1tag.type}->pm" if Chronic.debug
          t1.untag(RepeaterDayPortion)
          t1.tag(RepeaterDayPortion.new(:pm))
        end
      end

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

      # handle ambiguous times if :ambiguous_time_range is specified
      if options[:ambiguous_time_range] != :none
        ttokens = []
        tokens.each_with_index do |t0, i|
          ttokens << t0
          t1 = tokens[i + 1]
          if t0.get_tag(RepeaterTime) && t0.get_tag(RepeaterTime).type.ambiguous? && (!t1 || !t1.get_tag(RepeaterDayPortion))
            distoken = Token.new('disambiguator')
            distoken.tag(RepeaterDayPortion.new(options[:ambiguous_time_range]))
            ttokens << distoken
          end
        end
        tokens = ttokens
      end

      tokens
    end

  end

}
