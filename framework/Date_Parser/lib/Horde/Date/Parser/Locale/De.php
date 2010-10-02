<?php
/**
 */
class Horde_Date_Parser_Locale_De extends Horde_Date_Parser_Locale_Base
{
    /**
     * Clean up the specified input text by stripping unwanted characters,
     * converting idioms to their canonical form, converting number words
     * to numbers (three => 3), and converting ordinal words to numeric
     * ordinals (third => 3rd)
     */
    public function preNormalize($text)
    {
        $text = strtolower($text);
        $text = $this->numericizeNumbers($text);
        $text = preg_replace('/[\'"\.]/', '', $text);
        $text = preg_replace('/([\/\-\,\@])/', ' \1 ', $text);
        $text = preg_replace('/\bheute\b/', 'dieser tag', $text);
        $text = preg_replace('/\bmorgen\b/', 'nächster tag', $text);
        $text = preg_replace('/\bgestern\b/', 'letzter tag', $text);
        $text = preg_replace('/\bmittags?\b/', '12:00', $text);
        $text = preg_replace('/\bmitternachts?\b/', '24:00', $text);
        $text = preg_replace('/\bjetzt\b/', 'diese sekunde', $text);
        $text = preg_replace('/\b(vor|früher)\b/', 'past', $text);
        $text = preg_replace('/\b(?:in|during) the (morning)\b/', '\1', $text);
        $text = preg_replace('/\bam (morgen|nachmittag|abend)\b/', '\1', $text);
        $text = preg_replace('/\in der nacht\b/', 'nachts', $text);
        $text = $this->numericizeOrdinals($text);

        return $text;
    }

    /**
     * Remove tokens that don't fit our definitions and re-orders tokens when
     * necessary.
     *
     * @param array $tokens Array of tagged tokens.
     *
     * @return array  Filtered tagged tokens.
     */
    public function postTokenize($tokens)
    {
        $tokens = parent::postTokenize($tokens);
        // Catch ambiguous constructs like "heute morgen/morgen früh".
        foreach ($tokens as $num => $token) {
            if ($num >= 2 &&
                ($repeater = $token->getTag('repeater')) &&
                $repeater instanceof Horde_Date_Repeater_Day &&
                ($grabber = $tokens[$num - 1]->getTag('grabber')) &&
                $grabber == 'next') {
                $token = new Horde_Date_Parser_Token('');
                $token->tag('repeater_day_portion',
                            new Horde_Date_Repeater_DayPortion('morning'));
                array_splice(
                    $tokens,
                    $num - 1,
                    2,
                    array($token));
                return $tokens;
            }
        }
        return $tokens;
    }
}
