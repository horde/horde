<?php
class Horde_Date_Parser_Locale_Pt_Pointer extends Horde_Date_Parser_Locale_Base_Pointer
{
    public $scanner = array(
        '/\bantes\b/' => 'past',
        '/\b(depois|ap[oó]s|dentro(\s+de)?|daqui(\s+a)?)\b/' => 'future',
    );
}

