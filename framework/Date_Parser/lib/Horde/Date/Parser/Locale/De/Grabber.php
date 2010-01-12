<?php
class Horde_Date_Parser_Locale_De_Grabber extends Horde_Date_Parser_Locale_Base_Grabber
{
    /**
     * Regex tokens
     */
    public $scanner = array(
        '/letzte\w?/' => 'last',
        '/diese\w?/' => 'this',
        '/nächste\w?/' => 'next',
    );

}
