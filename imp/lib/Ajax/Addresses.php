<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Outputs an address list in the form used by the browser-side javascript
 * code.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ajax_Addresses
{
    /**
     * Address object.
     *
     * @var Horde_Mail_Rfc822_Object
     */
    private $_addr;

    /**
     * Address counter for toArray().
     *
     * @var integer
     */
    private $_count;

    /**
     * Constructor.
     *
     * @param Horde_Mail_Rfc822_Object $addr  Address object.
     */
    public function __construct(Horde_Mail_Rfc822_Object $addr)
    {
        $this->_addr = $addr;
    }

    /**
     * Output the address list.
     *
     * @param integer $limit  Limit display to this many addresses. If null,
     *                        shows all addresses.
     *
     * @return array  Output format:
     *   - Group keys: 'a' (list of addresses); 'g' (group name)
     *   - Address keys: 'b' (bare address); 'p' (personal part)
     */
    public function toArray($limit = null)
    {
        $out = array();
        $this->_count = 0;

        foreach ($this->_addr->base_addresses as $ob) {
            if ($limit && ($this->_count > $limit)) {
                $out['limit'] = count($this->_addr);
                break;
            }

            if ($ob instanceof Horde_Mail_Rfc822_Group) {
                $ob->addresses->unique();

                $tmp = new stdClass;
                $tmp->a = array();
                $tmp->g = $ob->groupname;

                foreach ($ob->addresses as $val) {
                    $tmp->a[] = $this->_addAddress($val);
                    if ($limit && ($this->_count > $limit)) {
                        break;
                    }
                }
            } else {
                $tmp = $this->_addAddress($ob);
            }

            $out[] = $tmp;
        }

        return $out;
    }

    /**
     * Create a bare address entry.
     *
     * @param Horde_Mail_Rfc822_Address $addr  Address object.
     *
     * @return object  Address object for use in JS code.
     */
    private function _addAddress(Horde_Mail_Rfc822_Address $addr)
    {
        $tmp = new stdClass;
        if (strlen($b = $addr->bare_address)) {
            $tmp->b = $b;
        }
        if (strlen($p = $addr->personal)) {
            $tmp->p = $p;
        }

        ++$this->_count;

        return $tmp;
    }

}
