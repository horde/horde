<?php
class Horde_Date_Parser_Locale_Pt_Timezone extends Horde_Date_Parser_Locale_Base_Timezone
{

    public $scanner = array(
        '/((e(s|d)t|c(s|d)t|m(s|d)t|p(s|d)t)|((gmt)?\s*(\+|\-)\s*\d\d\d\d?)|gmt|utc)/i' => 'tz',
    );

}
