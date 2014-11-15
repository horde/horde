<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mime
 */

/**
 * This class represents address fields that may appear multiple times in a
 * message part (i.e. they are independent of each other) (RFC 5322).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mime
 * @since     2.5.0
 */
class Horde_Mime_Headers_AddressesMulti
    extends Horde_Mime_Headers_Element_Multiple
    implements Horde_Mime_Headers_Element_Address
{
    /**
     */
    public function __clone()
    {
        $copy = array();
        foreach ($this->_values as $val) {
            $copy[] = clone $val;
        }
        $this->_values = $copy;
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'full_value':
        case 'value':
            return array_map($this->_values, 'strval');
        }

        return parent::__get($name);
    }

    /**
     */
    public function getAddressList($first = false)
    {
        return $first
            ? reset($this->_values)
            : $this->_values;
    }

    /**
     */
    protected function _setValue($value)
    {
        $rfc822 = new Horde_Mail_Rfc822();
        $this->_values[] = $rfc822->parseAddressList($value);
    }

    /**
     */
    public static function getHandles()
    {
        return array(
            // Mail: RFC 5322 (Address that can appear in multiple headers)
            'resent-to',
            'resent-cc',
            'resent-bcc',
            'resent-from'
        );
    }

    /**
     * @param array $opts  See Horde_Mime_Headers_Addresses#doSendEncode().
     */
    protected function _sendEncode($opts)
    {
        return Horde_Mime_Headers_Addresses::doSendEncode(
            $this->getAddressList(),
            $opts
        );
    }

}
