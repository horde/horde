<?php 
/**
 */

class Horde_Date_Parser_Locale_Pt extends Horde_Date_Parser_Locale_Base
{
    public $definitions = array();
    public $args = array();
    public $now;

    public function __construct($args)
    {
        $this->args = $args;
    }

    /**
    # Parses a string containing a natural language date or time. If the parser
    # can find a date or time, either a Horde_Date or Horde_Date_Span will be returned
    # (depending on the value of <tt>:return</tt>). If no date or time can be found,
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
    #     Time (defaults to time())
    #
    #     By setting <tt>:now</tt> to a Horde_Date, all computations will be based off
    #     of that time instead of time().
    #
    # [<tt>:return</tt>]
    #     'result', 'span', or 'date' (defaults to 'date')
    #
    #     By default, the parser will guess a single point in time for the
    #     given date or time. If you'd rather have the entire time span returned,
    #     set <tt>:return</tt> to 'span' and a Horde_Date_Span will be returned.
    #     If you want the entire result, including tokens (for retrieving the text
    #     that was or was not tagged, for example), set <tt>:return</tt> to 'result'
    #     and you will get a result object.
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
            'now' => new Horde_Date(time()),
            'return' => 'date',
            'ambiguousTimeRange' => 6,
        );
        $options = array_merge($defaultOptions, $this->args, $specifiedOptions);

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


		$text = $this->normalize_special_characters($text);

        // put the text into a normal format to ease scanning
        $text = $this->preNormalize($text);

        // get base tokens for each word
        $tokens = $this->preTokenize($text);

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
        $taggedTokens = array_values(array_filter($tokens, create_function('$t', 'return $t->tagged();')));

        // Remove tokens we know we don't want - for example, if the first token
        // is a separator, drop it.
        $taggedTokens = $this->postTokenize($taggedTokens);

        // do the heavy lifting
        $span = $this->tokensToSpan($taggedTokens, $options);

        // generate the result and return it, the span, or a guessed time within the span
        $result = new Horde_Date_Parser_Result($span, $tokens);
        switch ($options['return']) {
        case 'result':
            return $result;
        case 'span':
            return $result->span;
        case 'date':
            return $result->guess();
		// TODO: return end date (force) like all day event = 0-24
        }
    }

    public function componentFactory($component, $args = null)
    {
        $locale = isset($this->args['locale']) ? $this->args['locale'] : null;

        if ($locale && strtolower($locale) != 'base') {
            $locale = str_replace(' ', '_', ucwords(str_replace('_', ' ', strtolower($locale))));
            $class = 'Horde_Date_Parser_Locale_' . $locale . '_' . $component;
            if (class_exists($class)) {
                return new $class($args);
            }

            $language = array_shift(explode('_', $locale));
            if ($language != $locale) {
                $class = 'Horde_Date_Parser_Locale_' . $language . '_' . $component;
                if (class_exists($class)) {
                    return new $class($args);
                }
            }
       }

        $class = 'Horde_Date_Parser_Locale_Base_' . $component;
        return new $class($args);
    }

    /**
	Replaces special characters with non-special equivalents
	source: http://pt2.php.net/manual/en/function.chr.php#93291
    */
	public function normalize_special_characters( $str )
	{
	    # Quotes cleanup
	    $str = ereg_replace( chr(ord("`")), "'", $str );        # `
	    $str = ereg_replace( chr(ord("´")), "'", $str );        # ´
	    $str = ereg_replace( chr(ord("„")), ",", $str );        # „
	    $str = ereg_replace( chr(ord("`")), "'", $str );        # `
	    $str = ereg_replace( chr(ord("´")), "'", $str );        # ´
	    $str = ereg_replace( chr(ord("“")), "\"", $str );        # “
	    $str = ereg_replace( chr(ord("”")), "\"", $str );        # ”
	    $str = ereg_replace( chr(ord("´")), "'", $str );        # ´

	    $unwanted_array = array(    'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
		                        'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
		                        'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
		                        'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
		                        'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
	    $str = strtr( $str, $unwanted_array );

	    # Bullets, dashes, and trademarks
	    $str = ereg_replace( chr(149), "&#8226;", $str );    # bullet •
	    $str = ereg_replace( chr(150), "&ndash;", $str );    # en dash
	    $str = ereg_replace( chr(151), "&mdash;", $str );    # em dash
	    $str = ereg_replace( chr(153), "&#8482;", $str );    # trademark
	    $str = ereg_replace( chr(169), "&copy;", $str );    # copyright mark
	    $str = ereg_replace( chr(174), "&reg;", $str );        # registration mark

	    return $str;
	}


    /**
    # Clean up the specified input text by stripping unwanted characters,
    # converting idioms to their canonical form, converting number words
    # to numbers (three => 3), and converting ordinal words to numeric
    # ordinals (third => 3rd)
    */
    public function preNormalize($text)
    {
        $text = strtolower($text);
        $text = $this->numericizeNumbers($text);
        $text = preg_replace('/[\'"\.]/', '', $text);
        $text = preg_replace('/([\/\-\,\@])/', ' $1 ', $text);
        $text = preg_replace('/\bhoje\b/', 'this day', $text);
        $text = preg_replace('/\bamanh[aã]\b/', 'next day', $text);
		$text = preg_replace('/\bontem\b/', 'last day', $text);
        $text = preg_replace('/\bmeio\s+dia\b/', '12:00', $text);
        $text = preg_replace('/\bmeia\s+noite\b/', '24:00', $text);
        $text = preg_replace('/\b(antes|anterior)\b/', 'past', $text);
        $text = preg_replace('/\b(agora|j[aá])\b/', 'this second', $text);
        $text = preg_replace('/\b[uú]ltim[oa]\b/', 'last', $text);
        $text = preg_replace('/\b(?:de|na|durante\s+a|logo(?:\s[aà]|de))\s+(manh[aã]|madrugada)\b/', 'morning', $text);
        $text = preg_replace('/\b(?:de|[àa]|durante\s+a|logo(?:\s[aà]|de))\s+tarde\b/', 'afternoon', $text);
        $text = preg_replace('/\b((?:de|[àa]|durante\s+a|logo(?:\s[aà]))\s+noite|(?:ao)\s+anoitecer)\b/', 'this night', $text);
        $text = preg_replace('/\b(horas?|h|hrs?)\b/', ' oclock', $text);
        $text = preg_replace('/\b(depois|ap[oó]s)\b/', 'future', $text);
        // $text = $this->numericizeNumbers($text);

		return $text;
    }

    /**
     * Convert number words to numbers (three => 3)
     */
    public function numericizeNumbers($text)
    {
		return Horde_Support_Numerizer::numerize($text, $this->args);
		// return $text;
	}

    /**
     * Convert ordinal words to numeric ordinals (third => 3rd)
     */
    public function numericizeOrdinals($text)
    {
        $text = preg_replace('/^d[eé]cim[oa]\s+primeir[oa]$/', '11º', $text);
        $text = preg_replace('/^d[eé]cim[oa]\s+segund[oa]$/', '12º', $text);
        $text = preg_replace('/^d[eé]cim[oa]\s+terceir[oa]$/', '13º', $text);
        $text = preg_replace('/^d[eé]cim[oa]\s+quart[oa]$/', '14º', $text);
        $text = preg_replace('/^d[eé]cim[oa]\s+quint[oa]$/', '15º', $text);
        $text = preg_replace('/^d[eé]cim[oa]\s+sext[oa]$/', '16º', $text);
        $text = preg_replace('/^d[eé]cim[oa]\s+s[eé]tim[oa]$/', '17º', $text);
        $text = preg_replace('/^d[eé]cim[oa]\s+oit[aá]v[oa]$/', '18º', $text);
        $text = preg_replace('/^d[eé]cim[oa]\s+^non[oa]$/', '19º', $text);
		$text = preg_replace('/^primeir[oa]$/', '1º', $text);
        $text = preg_replace('/^segund[oa]$/', '2º', $text);
        $text = preg_replace('/^terceir[oa]$/', '3º', $text);
        $text = preg_replace('/^quart[oa]$/', '4º', $text);
        $text = preg_replace('/^quint[oa]$/', '5º', $text);
		$text = preg_replace('/^sext[oa]$/', '6º', $text);
        $text = preg_replace('/^s[eé]tim[oa]$/', '7º', $text);
        $text = preg_replace('/^oit[aá]v[oa]$/', '8º', $text);
        $text = preg_replace('/^non[oa]$/', '9º', $text);
        $text = preg_replace('/^d[eé]cim[oa]$/', '10º', $text);
	    // and so one....
        return $text;

    }

    /**
     * Split the text on spaces and convert each word into a Token.
     *
     * @param string $text  Text to tokenize
     *
     * @return array  Array of Horde_Date_Parser_Tokens.
     */
    public function preTokenize($text)
    {
        return array_map(create_function('$w', 'return new Horde_Date_Parser_Token($w);'), preg_split('/\s+/', $text));
    }

    /**
     * Remove tokens that don't fit our definitions.
     *
     * @param array $tokens Array of tagged tokens.
     *
     * @return array  Filtered tagged tokens.
     */
    public function postTokenize($tokens)
    {
        if (!count($tokens)) { return $tokens; }

        // First rule: if the first token is a separator, remove it from the
        // list of tokens we consider in tokensToSpan().
        $first = clone($tokens[0]);
        $first->untag('separator_at');
        $first->untag('separator_comma');
        $first->untag('separator_in');
        $first->untag('separator_slash_or_dash');
        if (!$first->tagged()) {
            array_shift($tokens);
        }

        return $tokens;
    }

    public function initDefinitions()
    {
        if ($this->definitions) { return; }

        $this->definitions = array(
            'time' => array(
                new Horde_Date_Parser_Handler(array(':repeater_time', ':repeater_day_portion?'), null),
                new Horde_Date_Parser_Handler(array(':repeater_day_portion?', ':repeater_time' ), null),
                new Horde_Date_Parser_Handler(array(':separator_at?', ':repeater_time' ), null),
                new Horde_Date_Parser_Handler(array(':repeater_time', ':separator_at?', ':repeater_day_portion?'), null),
				
            ),

            'date' => array(
                new Horde_Date_Parser_Handler(array(':repeater_day_name', ':repeater_month_name', ':scalar_day', ':repeater_time', ':timezone', ':scalar_year'), 'handle_rdn_rmn_sd_t_tz_sy'),
                new Horde_Date_Parser_Handler(array(':repeater_month_name', ':scalar_day', ':scalar_year'), 'handle_rmn_sd_sy'),
                new Horde_Date_Parser_Handler(array(':repeater_month_name', ':scalar_day', ':scalar_year', ':separator_as?', 'time?'), 'handle_rmn_sd_sy'),
                new Horde_Date_Parser_Handler(array(':repeater_month_name', ':scalar_day', ':separator_as?', 'time?'), 'handle_rmn_sd'),
                new Horde_Date_Parser_Handler(array(':repeater_month_name', ':ordinal_day', ':separator_as?', 'time?'), 'handle_rmn_od'),
                new Horde_Date_Parser_Handler(array(':repeater_month_name', ':scalar_year'), 'handle_rmn_sy'),
                new Horde_Date_Parser_Handler(array(':scalar_day', ':repeater_month_name', ':scalar_year', ':separator_at?', 'time?'), 'handle_sd_rmn_sy'),
                new Horde_Date_Parser_Handler(array(':scalar_month', ':separator_slash_or_dash', ':scalar_day', ':separator_slash_or_dash', ':scalar_year', ':separator_at?', 'time?'), 'handle_sm_sd_sy'),
                new Horde_Date_Parser_Handler(array(':scalar_day', ':separator_slash_or_dash', ':scalar_month', ':separator_slash_or_dash', ':scalar_year', ':separator_at?', 'time?'), 'handle_sd_sm_sy'),
                new Horde_Date_Parser_Handler(array(':scalar_year', ':separator_slash_or_dash', ':scalar_month', ':separator_slash_or_dash', ':scalar_day', ':separator_at?', 'time?'), 'handle_sy_sm_sd'),
                new Horde_Date_Parser_Handler(array(':scalar_month', ':separator_slash_or_dash', ':scalar_year'), 'handle_sm_sy'),
                new Horde_Date_Parser_Handler(array(':scalar_day', ':separator_at?', ':repeater_month_name', ':separator_at?', ':scalar_year', ':separator_at?', 'time?'), 'handle_sd_rmn_sy'),
                new Horde_Date_Parser_Handler(array(':repeater_day_name',  ':separator_at?', ':time?'), 'handle_rdn'),
				new Horde_Date_Parser_Handler(array(':scalar_day',  ':separator_at?', ':scalar_month', ':separator_at?', ':scalar_year?', 'time?'), 'handle_sd_sm_sy'),
				new Horde_Date_Parser_Handler(array(':scalar_day',  ':separator_at?', ':repeater_month_name', ':separator_at?', ':scalar_year', ':separator_at?', 'time?'), 'handle_sd_rmn_sy'), 
                new Horde_Date_Parser_Handler(array(':scalar_day',  ':separator_at?', ':repeater_month_name', ':separator_at?', 'time?'), 'handle_sd_rmn'),
            ),

            // tonight at 7pm
            'anchor' => array(
                new Horde_Date_Parser_Handler(array(':grabber?', ':repeater', ':separator_at?', ':repeater?', ':repeater?'), 'handle_r'),
                new Horde_Date_Parser_Handler(array(':grabber?', ':repeater', ':repeater', ':separator_at?', ':repeater?', ':repeater?'), 'handle_r'),
                new Horde_Date_Parser_Handler(array(':repeater', ':grabber', ':repeater'), 'handle_r_g_r'),
            ),

            // 3 weeks from now, in 2 months
            'arrow' => array(
                new Horde_Date_Parser_Handler(array(':scalar', ':repeater', ':pointer'), 'handle_s_r_p'),
                new Horde_Date_Parser_Handler(array(':pointer', ':scalar', ':repeater'), 'handle_p_s_r'),
                new Horde_Date_Parser_Handler(array(':scalar', ':repeater', ':pointer', 'anchor'), 'handle_s_r_p_a'),
            ),

            // 3rd week in march
            'narrow' => array(
                new Horde_Date_Parser_Handler(array(':ordinal', ':repeater', ':separator_in', ':repeater'), 'handle_o_r_s_r'),
                new Horde_Date_Parser_Handler(array(':ordinal', ':repeater', ':grabber', ':repeater'), 'handle_o_r_g_r'),
            ),
        );
    }

    public function tokensToSpan($tokens, $options)
    {
        $this->initDefinitions();

        // maybe it's a specific date
        foreach ($this->definitions['date'] as $handler) {
            if ($handler->match($tokens, $this->definitions)) {
                $goodTokens = array_values(array_filter($tokens, create_function('$o', 'return !$o->getTag("separator");')));
                $this->debug($handler->handlerMethod, $goodTokens, $options);
                return call_user_func(array($this, $handler->handlerMethod), $goodTokens, $options);
            }
        }

        // I guess it's not a specific date, maybe it's just an anchor
        foreach ($this->definitions['anchor'] as $handler) {
            if ($handler->match($tokens, $this->definitions)) {
                $goodTokens = array_values(array_filter($tokens, create_function('$o', 'return !$o->getTag("separator");')));
                $this->debug($handler->handlerMethod, $goodTokens, $options);
                return call_user_func(array($this, $handler->handlerMethod), $goodTokens, $options);
            }
        }

        // not an anchor, perhaps it's an arrow
        foreach ($this->definitions['arrow'] as $handler) {
            if ($handler->match($tokens, $this->definitions)) {
                $goodTokens = array_values(array_filter($tokens, create_function('$o', 'return !$o->getTag("separator_at") && !$o->getTag("separator_slash_or_dash") && !$o->getTag("separator_comma");')));
                $this->debug($handler->handlerMethod, $goodTokens, $options);
                return call_user_func(array($this, $handler->handlerMethod), $goodTokens, $options);
            }
        }

        // not an arrow, let's hope it's a narrow
        foreach ($this->definitions['narrow'] as $handler) {
            if ($handler->match($tokens, $this->definitions)) {
                //good_tokens = tokens.select { |o| !o.get_tag Separator }
                $this->debug($handler->handlerMethod, $tokens, $options);
                return call_user_func(array($this, $handler->handlerMethod), $tokens, $options);
            }
        }

        return null;
    }

    public function dayOrTime($dayStart, $timeTokens, $options)
    {
        $outerSpan = new Horde_Date_Span($dayStart, $dayStart->add(array('day' => 1)));

        if (!empty($timeTokens)) {
            $this->now = $outerSpan->begin;
            return $this->getAnchor($this->dealiasAndDisambiguateTimes($timeTokens, $options), $options);
        } else {
            return $outerSpan;
        }
    }


    public function handle_m_d($month, $day, $timeTokens, $options)
    {
        $month->now = $this->now;
        $span = $month->this($options['context']);

        $dayStart = new Horde_Date($span->begin->year, $span->begin->month, $day);
        return $this->dayOrTime($dayStart, $timeTokens, $options);
    }

    public function handle_rmn_sd($tokens, $options)
    {
        return $this->handle_m_d($tokens[0]->getTag('repeater_month_name'), $tokens[1]->getTag('scalar_day'), array_slice($tokens, 2), $options);	// mês primeiro (dia/ano)
    }

    public function handle_rmn_od($tokens, $options)
    {
        return $this->handle_m_d($tokens[0]->getTag('repeater_month_name'), $tokens[1]->getTag('ordinal_day'), array_slice($tokens, 2), $options);
    }

    public function handle_rmn_sy($tokens, $options)
    {
        $month = $tokens[0]->getTag('repeater_month_name')->index();
        $year = $tokens[1]->getTag('scalar_year');

        try {
            return new Horde_Date_Span(new Horde_Date($year, $month, 1), new Horde_Date($year, $month + 1, 1));
        } catch (Exception $e) {
            return null;
        }
    }

    public function handle_rdn_rmn_sd_t_tz_sy($tokens, $options)
    {
        $month = $tokens[1]->getTag('repeater_month_name')->index();
        $day = $tokens[2]->getTag('scalar_day');
        $year = $tokens[5]->getTag('scalar_year');

        try {
            $dayStart = new Horde_Date($year, $month, $day);
            return $this->dayOrTime($dayStart, array($tokens[3]), $options);
        } catch (Exception $e) {
            return null;
        }
    }

    public function handle_rmn_sd_sy($tokens, $options)
    {
        $month = $tokens[0]->getTag('repeater_month_name')->index();
        $day = $tokens[1]->getTag('scalar_day');
        $year = $tokens[2]->getTag('scalar_year');

        $timeTokens = array_slice($tokens, 3);

        try {
            $dayStart = new Horde_Date($year, $month, $day);
            return $this->dayOrTime($dayStart, $timeTokens, $options);
        } catch (Exception $e) {
            return null;
        }
    }

    public function handle_sd_rmn_sy($tokens, $options)
    {
        $newTokens = array($tokens[1], $tokens[0], $tokens[2]);
        $timeTokens = array_slice($tokens, 3);
        return $this->handle_rmn_sd_sy(array_merge($newTokens, $timeTokens), $options);
    }


    public function handle_sd_rmn($tokens, $options)
    {
		return $this->handle_m_d($tokens[1]->getTag('repeater_month_name'), $tokens[0]->getTag('scalar_day'), array_slice($tokens, 2), $options);
	}

    public function handle_sm_sd_sy($tokens, $options)
    {
        $month = $tokens[0]->getTag('scalar_month');
        $day = $tokens[1]->getTag('scalar_day');
        $year = $tokens[2]->getTag('scalar_year');

        $timeTokens = array_slice($tokens, 3);

        try {
            $dayStart = new Horde_Date($year, $month, $day);
            return $this->dayOrTime($dayStart, $timeTokens, $options);
        } catch (Exception $e) {
            return null;
        }
    }

    public function handle_sd_sm_sy($tokens, $options)
    {
        $newTokens = array($tokens[1], $tokens[0], $tokens[2]);
        $timeTokens = array_slice($tokens, 3);
        return $this->handle_sm_sd_sy(array_merge($newTokens, $timeTokens), $options);
    }

    public function handle_sy_sm_sd($tokens, $options)
    {
        $newTokens = array($tokens[1], $tokens[2], $tokens[0]);
        $timeTokens = array_slice($tokens, 3);
        return $this->handle_sm_sd_sy(array_merge($newTokens, $timeTokens), $options);
    }

    public function handle_sm_sy($tokens, $options)
    {
        $month = $tokens[0]->getTag('scalar_month');
        $year = $tokens[1]->getTag('scalar_year');

        try {
            return new Horde_Date_Span(new Horde_Date($year, $month, 1), new Horde_Date($year, $month + 1, 1));
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
        $distance = $tokens[0]->getTag('scalar');
        $repeater = $tokens[1]->getTag('repeater');
        $pointer = $tokens[2]->getTag('pointer');

        return $repeater->offset($span, $distance, $pointer);
    }

    public function handle_s_r_p($tokens, $options)
    {
        $span = new Horde_Date_Span($this->now, $this->now->add(1));
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
        $repeater = $tokens[1]->getTag('repeater');
        $repeater->now = $outerSpan->begin->sub(1);
        $ordinal = $tokens[0]->getTag('ordinal');
        $span = null;

        for ($i = 0; $i < $ordinal; $i++) {
            $span = $repeater->next('future');
            if ($span->begin->after($outerSpan->end)) {
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
    # Logging Methods
    ##########################################################################*/

    public function debug($method, $args)
    {
        $args = func_get_args();
        $method = array_shift($args);
        // echo "$method\n";
    }


    /*##########################################################################
    # Support Methods
    ##########################################################################*/

    public function getAnchor($tokens, $options)
    {
        $grabber = 'this';
        $pointer = 'future';

        $repeaters = $this->getRepeaters($tokens);
        for ($i = 0, $size = count($repeaters); $i < $size; $i++) {
            array_pop($tokens);
        }

        if (count($tokens) && $tokens[0]->getTag('grabber')) {
            $grabber = $tokens[0]->getTag('grabber');
            array_pop($tokens);
        }

        $head = array_shift($repeaters);
        $head->now = $this->now;

        switch ($grabber) {
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
            throw new Horde_Date_Parser_Exception('Invalid grabber ' . $grabber);
        }

        return $this->findWithin($repeaters, $outerSpan, $pointer);
    }

    public function getRepeaters($tokens)
    {
        $repeaters = array();
        foreach ($tokens as $token) {
            if ($t = $token->getTag('repeater')) {
                $repeaters[] = $t;
            }
        }

        // Return repeaters in order from widest (years) to smallest (seconds)
        usort($repeaters, create_function('$a, $b', 'return $b->width() > $a->width();'));
        return $repeaters;
    }

    /**
     * Recursively finds repeaters within other repeaters.  Returns a Span
     * representing the innermost time span or null if no repeater union could
     * be found
     */
    public function findWithin($tags, $span, $pointer)
    {
        if (empty($tags)) { return $span; }

        $head = array_shift($tags);
        $rest = $tags;
        $head->now = ($pointer == 'future') ? $span->begin : $span->end;
        $h = $head->this('none');

        if ($span->includes($h->begin) || $span->includes($h->end)) {
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
            if ($t->getTag('repeater_day_portion')) {
                $dayPortionIndex = $i;
                break;
            }
        }

        $timeIndex = null;
        foreach ($tokens as $i => $t) {
            if ($t->getTag('repeater_time')) {
                $timeIndex = $i;
                break;
            }
        }

        if ($dayPortionIndex !== null && $timeIndex !== null) {
            $t1 = $tokens[$dayPortionIndex];
            $t1tag = $t1->getTag('repeater_day_portion');

            if ($t1tag->type == 'morning') {
                $t1->untag('repeater_day_portion');
                $t1->tag('repeater_day_portion', new Horde_Date_Repeater_DayPortion('am'));
            } elseif (in_array($t1tag->type, array('afternoon', 'evening', 'night'))) {
                $t1->untag('repeater_day_portion');
                $t1->tag('repeater_day_portion', new Horde_Date_Repeater_DayPortion('pm'));
            }
        }

        // handle ambiguous times if ambiguousTimeRange is specified
        if (!isset($options['ambiguousTimeRange']) || $options['ambiguousTimeRange'] != 'none') {
            $ttokens = array();
            foreach ($tokens as $i => $t0) {
                $ttokens[] = $t0;
                $t1 = isset($tokens[$i + 1]) ? $tokens[$i + 1] : null;
                if ($t0->getTag('repeater_time') && $t0->getTag('repeater_time')->ambiguous && (!$t1 || !$t1->getTag('repeater_day_portion'))) {
                    $distoken = new Horde_Date_Parser_Token('disambiguator');
                    $distoken->tag('repeater_day_portion', new Horde_Date_Repeater_DayPortion($options['ambiguousTimeRange']));
                    $ttokens[] = $distoken;
                }
            }

            $tokens = $ttokens;
        }

        return $tokens;
    }

}
