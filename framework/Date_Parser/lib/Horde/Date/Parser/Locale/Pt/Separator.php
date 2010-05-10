<?php
class Horde_Date_Parser_Locale_Pt_Separator extends Horde_Date_Parser_Locale_Base_Separator
{

    public $atScanner = array(
            '/^(em|@)$/' => 'at',
            );

    public $inScanner = array(
            '/^no$/' => 'in',
            );

}
