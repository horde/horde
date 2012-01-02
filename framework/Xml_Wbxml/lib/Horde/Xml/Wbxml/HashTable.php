<?php
/**
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
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
