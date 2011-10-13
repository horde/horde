<?php
class Horde_Kolab_FreeBusy_Stub_MatchDict
extends Horde_Kolab_FreeBusy_Controller_MatchDict
{
    private $_vars;

    public function __construct($vars)
    {
        $this->_vars = $vars;
    }

    public function getMatchDict()
    {
        return new Horde_Support_Array($this->_vars);
    }
}