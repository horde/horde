<?php
class Horde_Date_Parser_Locale_De_Timezone extends Horde_Date_Parser_Locale_Base_Timezone
{
    public $scanner = array(
        '/MES?[ZT]/i' => 'tz',
    );

}
