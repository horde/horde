<?php
class Horde_Date_Parser_Locale_Pt_Pointer extends Horde_Date_Parser_Locale_Base_Pointer
{
    public $scanner = array(
        '/\antes\b/' => 'past',
        '/\(depois|ap(o|ó)s)?\b/' => 'future',
        '/\dentro?\b/' => 'future',
     );
}

