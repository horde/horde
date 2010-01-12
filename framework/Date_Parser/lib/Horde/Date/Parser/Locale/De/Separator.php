<?php
class Horde_Date_Parser_Locale_De_Separator extends Horde_Date_Parser_Locale_Base_Separator
{

    public $atScanner = array(
        '/^(um|@)$/' => 'at',
    );

}
