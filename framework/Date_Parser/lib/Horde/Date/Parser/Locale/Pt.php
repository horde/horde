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
     * Parses a string containing a natural language date or time. If the
     * parser can find a date or time, either a Horde_Date or Horde_Date_Span
     * will be returned (depending on the value of <tt>:return</tt>). If no
     * date or time can be found, +nil+ will be returned.
     *
     * Options are:
     *
     * [<tt>:context</tt>]
     *     <tt>:past</tt> or <tt>:future</tt> (defaults to <tt>:future</tt>)
     *
     *     If your string represents a birthday, you can set <tt>:context</tt>
     *     to <tt>:past</tt> and if an ambiguous string is given, it will
     *     assume it is in the past. Specify <tt>:future</tt> or omit to set a
     *     future context.
     *
     * [<tt>:now</tt>]
     *     Time (defaults to time())
     *
     *     By setting <tt>:now</tt> to a Horde_Date, all computations will be
     *     based off of that time instead of time().
     *
     * [<tt>:return</tt>]
     *     'result', 'span', or 'date' (defaults to 'date')
     *
     *     By default, the parser will guess a single point in time for the
     *     given date or time. If you'd rather have the entire time span
     *     returned, set <tt>:return</tt> to 'span' and a Horde_Date_Span will
     *     be returned.  If you want the entire result, including tokens (for
     *     retrieving the text that was or was not tagged, for example), set
     *     <tt>:return</tt> to 'result' and you will get a result object.
     *
     * [<tt>:ambiguousTimeRange</tt>]
     *     Integer or <tt>:none</tt> (defaults to <tt>6</tt> (6am-6pm))
     *
     *     If an Integer is given, ambiguous times (like 5:00) will be assumed
     *     to be within the range of that time in the AM to that time in the
     *     PM. For example, if you set it to <tt>7</tt>, then the parser will
     *     look for the time between 7am and 7pm. In the case of 5:00, it would
     *     assume that means 5:00pm. If <tt>:none</tt> is given, no assumption
     *     will be made, and the first matching instance of that time will be
     *     used.
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

        // Remove tokens we know we don't want - for example, if the first
        // token is a separator, drop it.
        $taggedTokens = $this->postTokenize($taggedTokens);

        // do the heavy lifting
        $span = $this->tokensToSpan($taggedTokens, $options);

        // generate the result and return it, the span, or a guessed time
        // within the span
        $result = new Horde_Date_Parser_Result($span, $tokens);
        switch ($options['return']) {
        case 'result':
            return $result;
        case 'span':
            return $result->span;
        case 'date':
            return $result->guess();
        }
    }

    /**
     * Replaces special characters with non-special equivalents.
     *
     * Source: http://pt2.php.net/manual/en/function.chr.php#93291
     */
	public function normalize_special_characters($str)
	{
	    // Quotes cleanup
	    $str = str_replace(
            array(
                chr(ord("`")),
                chr(ord("´")),
                chr(ord("„")),
                chr(ord("`")),
                chr(ord("´")),
                chr(ord("“")),
                chr(ord("”")),
                chr(ord("´")),
            ),
            array(
                "'",
                "'",
                ",",
                "'",
                "'",
                "\"",
                "\"",
                "'",
            ),
           $str
        );

	    $unwanted_array = array(
            'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A',
            'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C',
            'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I',
            'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
            'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U',
            'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a',
            'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i',
            'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
            'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u',
            'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y'
        );
	    $str = strtr($str, $unwanted_array);

	    // Bullets, dashes, and trademarks
	    $str = str_replace(
            array(
                chr(149), # bullet •
                chr(150), # en dash
                chr(151), # em dash
                chr(153), # trademark
                chr(169), # copyright mark
                chr(174), # registration mark
            ),
            array(
                '&#8226;',
                '&ndash;',
                '&mdash;',
                '&#8482;',
                '&copy;', 
                '&reg;',
            ),
            $str
        );

	    return $str;
	}


    /**
     * Clean up the specified input text by stripping unwanted characters,
     * converting idioms to their canonical form, converting number words to
     * numbers (three => 3), and converting ordinal words to numeric ordinals
     * (third => 3rd)
    */
    public function preNormalize($text)
    {
		// fix email parser
		$text = preg_replace('/\b([_a-z0-9-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,4})\b/', '', $text);

		$text = strtolower($text);
        $text = $this->numericizeNumbers($text);
		// fix url parser
		$text = preg_replace('/(?:(?:https?|ftp):\/\/)/', '', $text);

		// composed sentences
        $text = preg_replace(
            array(
                '/\bsegunda[ \-]feira\b/',
                '/\bterca[ \-]feira\b/',
                '/\bquarta[ \-]feira\b/',
                '/\bquinta[ \-]feira\b/',
                '/\bsexta[ \-]feira\b/', 
                '/[\'"\.]/',
                '/([\/\-\,\@])/',
                '/\bhoje\b/',
                '/\bamanh[aã]\b/',
                '/\bontem\b/',
                '/\bfim de semana\b/',
                '/\bmeio\s+dia\b/',
                '/\bmeia\s+noite\b/',
                '/\b(antes|anterior)\b/',
                '/\b(agora|j[aá])\b/',
                '/\b[uú]ltim[oa]\b/',
                '/\b(?:de|na|durante\s+a|logo(?:\s[aà]|de))\s+(manh[aã]|madrugada)\b/',
                '/\b(?:de|[àa]|durante\s+a|logo(?:\s[aà]|de))\s+tarde\b/',
                '/\b((?:de|[àa]|durante\s+a|logo(?:\s[aà]))\s+noite|(?:ao)\s+anoitecer)\b/',
            ),
            array(
                'segunda',
                'terca',
                'quarta',
                'quinta',
                'sexta',
                '',
                ' $1 ',
                'this day',
                'next day',
                'last day',
                'fds',
                '12:00',
                '24:00',
                'past',
                'this second',
                'last',
                'morning',
                'afternoon',
                'this night',
            ),
            $text
        );
        
		$text = preg_replace_callback(
			'/\b([0-1]?[0-9]|2[0-3])(:|,|.)?([0-5][0-9])?\s?(horas?)\b/',
			function($matches) {
                $minute = ($matches[3] != '')
                    ? str_pad($matches[3], 2 , '0', STR_PAD_LEFT)
                    : '00';
                $hour = $matches[1];
                return $hour . ':' . $minute . ' oclock';
            },
            $text
        );

        $text = preg_replace(
            array(
                '/\b(horas?|h|hrs?)\b/',
                '/\b(depois|ap[oó]s)\b/',
                '/\bdia\b/',
            ),
            array(
                ' oclock',
                'future',
                '', // broke parser: redundant, ignore and read from number day
            ),
            $text
        );

        // $text = $this->numericizeNumbers($text);

		return $text;
    }

    /**
     * Convert ordinal words to numeric ordinals (third => 3rd)
     */
    public function numericizeOrdinals($text)
    {
		/*
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
		*/
        return $text;
    }
    public function initDefinitions()
    {
        if ($this->definitions) { return; }

        $this->definitions = array(
            'time' => array(
//                new Horde_Date_Parser_Handler(array(':repeater_time', ':repeater_day_portion?'), null),
				new Horde_Date_Parser_Handler(array(':separator_at?', ':repeater_time', ':repeater_day_portion?'), null),
				new Horde_Date_Parser_Handler(array(':separator_at?', ':time', ':repeater_time'), null),			// ás 10 horas
/*
                new Horde_Date_Parser_Handler(array(':repeater_day_portion?', ':repeater_time' ), null),
                new Horde_Date_Parser_Handler(array(':separator_at?', ':repeater_time' ), null),
                new Horde_Date_Parser_Handler(array(':repeater_time', ':separator_at?', ':repeater_day_portion?'), null),
*/
            ),

            'date' => array(
                new Horde_Date_Parser_Handler(array(':repeater_day_name', ':repeater_month_name', ':scalar_day', ':repeater_time', ':timezone', ':scalar_year'), 'handle_rdn_rmn_sd_t_tz_sy'),
                new Horde_Date_Parser_Handler(array(':repeater_month_name', ':scalar_day', ':scalar_year'), 'handle_rmn_sd_sy'),
                new Horde_Date_Parser_Handler(array(':repeater_month_name', ':scalar_day', ':scalar_year', ':separator_at?', 'time?'), 'handle_rmn_sd_sy'),
                new Horde_Date_Parser_Handler(array(':repeater_month_name', ':scalar_day', ':separator_at?', 'time?'), 'handle_rmn_sd'),
                new Horde_Date_Parser_Handler(array(':repeater_month_name', ':ordinal_day', ':separator_at?', 'time?'), 'handle_rmn_od'),
                new Horde_Date_Parser_Handler(array(':repeater_month_name', ':scalar_year'), 'handle_rmn_sy'),
                new Horde_Date_Parser_Handler(array(':scalar_day', ':repeater_month_name', ':scalar_year', ':separator_at?', 'time?'), 'handle_sd_rmn_sy'),
                new Horde_Date_Parser_Handler(array(':scalar_month', ':separator_slash_or_dash', ':scalar_day', ':separator_slash_or_dash', ':scalar_year', ':separator_at?', 'time?'), 'handle_sm_sd_sy'),
                new Horde_Date_Parser_Handler(array(':scalar_day', ':separator_slash_or_dash', ':scalar_month', ':separator_slash_or_dash', ':scalar_year', ':separator_at?', 'time?'), 'handle_sd_sm_sy'),
                new Horde_Date_Parser_Handler(array(':scalar_year', ':separator_slash_or_dash', ':scalar_month', ':separator_slash_or_dash', ':scalar_day', ':separator_at?', 'time?'), 'handle_sy_sm_sd'),
                new Horde_Date_Parser_Handler(array(':scalar_month', ':separator_slash_or_dash', ':scalar_year'), 'handle_sm_sy'),
                new Horde_Date_Parser_Handler(array(':scalar_day', ':separator_at?', ':repeater_month_name', ':separator_at?', ':scalar_year', ':separator_at?', 'time?'), 'handle_sd_rmn_sy'),
				/*
				new Horde_Date_Parser_Handler(array(':scalar_day',  ':separator_at?', ':repeater_month_name', ':separator_at?', 'time?'), 'handle_sd_rmn'),
				new Horde_Date_Parser_Handler(array(':ordinal_day',  ':separator_at?', ':repeater_month_name', ':separator_at?', 'time?'), 'handle_od_rmn'),
                new Horde_Date_Parser_Handler(array(':repeater_day_name',  ':separator_at?', ':time?'), 'handle_rdn'),
				new Horde_Date_Parser_Handler(array(':scalar_day',  ':separator_at?', ':scalar_month', ':separator_at?', ':scalar_year?', 'time?'), 'handle_sd_sm_sy'),
				new Horde_Date_Parser_Handler(array(':scalar_day',  ':separator_at?', ':repeater_month_name', ':separator_at?', ':scalar_year', ':separator_at?', 'time?'), 'handle_sd_rmn_sy'),
				*/
                new Horde_Date_Parser_Handler(array(':scalar_day', ':separator_slash_or_dash', ':scalar_month', ':separator_slash_or_dash', ':scalar_year', ':separator_at?', 'time?'), 'handle_sd_sm_sy'),
				new Horde_Date_Parser_Handler(array(':scalar_year', ':separator_slash_or_dash', ':scalar_month', ':separator_slash_or_dash', ':scalar_day', ':separator_at?', 'time?'), 'handle_sy_sm_sd'),
				new Horde_Date_Parser_Handler(array(':scalar_month', ':separator_slash_or_dash', ':scalar_year'), 'handle_sm_sy'),
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

    public function handle_rdn($tokens, $options)
    {
        try {
            return $this->dayOrTime(
                new Horde_Date(time()), array($tokens[0]), $options
            );
        } catch (Exception $e) {
            return null;
        }
    }

	// JPC
    public function handle_sd_rmn($tokens, $options)
    {
		return $this->handle_m_d(
            $tokens[1]->getTag('repeater_month_name'),
            $tokens[0]->getTag('scalar_day'),
            array_slice($tokens, 2),
            $options
        );
	}

    public function handle_od_rmn($tokens, $options)
	{
	    return $this->handle_m_d(
            $tokens[1]->getTag('repeater_month_name'),
            $tokens[0]->getTag('ordinal_day'),
            array_slice($tokens, 2),
            $options
        );
	}
}
