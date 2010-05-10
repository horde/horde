<?php
class Horde_Date_Parser_Locale_Pt_Grabber extends Horde_Date_Parser_Locale_Base_Grabber
{
        /**
        * Regex tokens
        */
        public $scanner = array(
            '/(passado|(u|ú)ltimo)\w?/' => 'last',
            '/est(e|a)\w?/' => 'this',
            '/pr(o|ó)ximo\w?/' => 'next',
        );

}

