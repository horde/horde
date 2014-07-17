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
 * @package   Imap_Client
 */

/**
 * Query the capabilities of an IMAP server.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 * @since     2.24.0
 *
 * @property-read intger $cmdlength  Allowable command length (in octets).
 */
class Horde_Imap_Client_Data_Capability_Imap
extends Horde_Imap_Client_Data_Capability
{
    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'cmdlength':
            /* RFC 2683 [3.2.1.5] originally recommended that lines should
             * be limited to "approximately 1000 octets". However, servers
             * should allow a command line of at least "8000 octets".
             * RFC 7162 [4] updates the recommendation to 8192 octets.
             * As a compromise, assume all modern IMAP servers handle
             * ~2000 octets and, if CONDSTORE/QRESYNC is supported, assume
             * they can handle ~8000 octets. (Don't need dependency support
             * checks here - the simple presence of CONDSTORE/QRESYNC is
             * enough to trigger.) */
             return (isset($this->_data['CONDSTORE']) || isset($this->_data['QRESYNC']))
                 ? 8000
                 : 2000;
        }
    }

    /**
     */
    public function query($capability, $parameter = null)
    {
        if (parent::query($capability, $parameter)) {
            return true;
        }

        switch (strtoupper($capability)) {
        case 'CONDSTORE':
        case 'ENABLE':
            /* RFC 7162 [3.2.3] - QRESYNC implies CONDSTORE and ENABLE,
             * even if not listed as a capability. */
            return (is_null($parameter) && $this->query('QRESYNC'));
        }

        return false;
    }

}
