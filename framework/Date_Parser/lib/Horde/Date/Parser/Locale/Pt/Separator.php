<?php
class Horde_Date_Parser_Locale_Pt_Separator extends Horde_Date_Parser_Locale_Base_Separator
{

    public $atScanner = array(
        '/\b(em|@)\b/' => 'at',
    );

    public $inScanner = array(
        '/\bno\b/' => 'in',
    );

}
