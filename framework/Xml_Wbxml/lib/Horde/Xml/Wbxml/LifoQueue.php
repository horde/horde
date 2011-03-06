/**
 * From Binary XML Content Format Specification Version 1.3, 25 July 2001
 * found at http://www.wapforum.org
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anthony Mills <amills@pyramid6.com>
 * @package Xml_Wbxml
 */
class Horde_Xml_Wbxml_LifoQueue
{
    protected $_queue = array();

    public function push($obj)
    {
        $this->_queue[] = $obj;
    }

    public function pop()
    {
        if (count($this->_queue)) {
            return array_pop($this->_queue);
        } else {
            return null;
        }
    }

    public function top()
    {
        if ($count = count($this->_queue)) {
            return $this->_queue[$count - 1];
        } else {
            return null;
        }
    }
}
