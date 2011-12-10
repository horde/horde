<?php

class Horde_Kolab_Format_Stub_DateTimeDefault
extends Horde_Kolab_Format_Xml_Type_DateTime
{
    protected $element = 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing';
    protected $value = Horde_Kolab_Format_Xml::VALUE_DEFAULT;
    protected $default;

    public function __construct($factory)
    {
        $this->default = array('date' => new DateTime());
        parent::__construct($factory);
    }
}
