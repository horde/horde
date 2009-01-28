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

}
