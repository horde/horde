<?php
/**
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anthony Mills <amills@pyramid6.com>
 * @package Xml_Wbxml
 */
class Horde_Xml_Wbxml_HashTable
{
    protected $_h;

    public function set($k, $v)
    {
        $this->_h[$k] = $v;
    }

    public function get($k)
    {
        return isset($this->_h[$k]) ? $this->_h[$k] : null;
    }
}
