<?php
class Horde_Date_Parser_Locale_Base_OrdinalDay extends Horde_Date_Parser_Locale_Base_Ordinal
{
    public function __toString()
    {
        return parent::__toString() . '-day-' . $this->type;
    }

}
