<?php

class Horde_Kolab_Format_Stub_DateTimeNotEmpty
extends Horde_Kolab_Format_Xml_Type_DateTime
{
    protected $element = 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing';
    protected $value = Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY;
}
