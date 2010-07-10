<?php
class Horde_Date_Parser_Locale_Pt_Timezone extends Horde_Date_Parser_Locale_Base_Timezone
{

    public $scanner = array(
        '/((E[SD]T|C[SD]T|M[SD]T|P[SD]T)|((GMT)?\s*[+-]\s*\d{3,4}?)|GMT|UTC)/i' => 'tz',		// não pode ter modificadores, vai dar erro se usado
    );

}
