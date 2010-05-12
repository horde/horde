<?php
class Horde_Date_Parser_Locale_Pt_Pointer extends Horde_Date_Parser_Locale_Base_Pointer
{
    public $scanner = array(
        '/\bantes\b/' => 'past',
        '/\b(depois|ap(o|ó)s)\b/' => 'future',
        /* '/\bdentro\b/' => 'future',   */
        '/\b(dentro(\sde)?|daqui\sa)\b/' => 'future',
     );
}

