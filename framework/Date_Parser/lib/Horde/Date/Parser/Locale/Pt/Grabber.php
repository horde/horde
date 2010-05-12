<?php
class Horde_Date_Parser_Locale_Pt_Grabber extends Horde_Date_Parser_Locale_Base_Grabber
{
        /**
        * Regex tokens
        */
        public $scanner = array(

            '/\b(passado|(u|ú)ltimo)\b/' => 'last',
            '/\best(e|a)\b/' => 'this',
            '/\bpr(o|ó)ximo\b/' => 'next',

        );

}

