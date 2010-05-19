<?php
class Horde_Date_Parser_Locale_Pt_Grabber extends Horde_Date_Parser_Locale_Base_Grabber
{
    /**
     * Regex tokens
     */
    public $scanner = array(
        '/\b(passado|[uú]ltimo)\b/' => 'last',
        '/\best[ea]\b/' => 'this',
        '/\bpr[oó]ximo\b/' => 'next',
    );

}
