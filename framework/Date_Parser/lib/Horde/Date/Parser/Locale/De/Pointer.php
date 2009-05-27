<?php
class Horde_Date_Parser_Locale_De_Pointer extends Horde_Date_Parser_Locale_Base_Pointer
{
    public $scanner = array(
        '/\bvor\b/' => 'past',
        '/\bin\b/' => 'future',
    );

}
