<?php
/**
 * Provides common methods shared in all ACL classes (see RFC 2086/4314).
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Imap_Client
 */
class Horde_Imap_Client_Data_AclCommon
{
    /**
     * Normalize virtual rights (see RFC 4314 [2.1.1]).
     */
    protected function _normalize()
    {
        /* Clients conforming to RFC 4314 MUST ignore the virtual ACL_CREATE
         * and ACL_DELETE rights. See RFC 4314 [2.1]. However, we still need
         * to handle these rights when dealing with RFC 2086 servers since
         * we are abstracting out use of ACL_CREATE/ACL_DELETE to their
         * component RFC 4314 rights. */
        if ($this[Horde_Imap_Client::ACL_CREATEMBOX] ||
            $this[Horde_Imap_Client::ACL_DELETEMBOX]) {
            $this[Horde_Imap_Client::ACL_CREATE] = true;
        } elseif ($this[Horde_Imap_Client::ACL_CREATE]) {
            $this[Horde_Imap_Client::ACL_CREATEMBOX] = true;
            $this[Horde_Imap_Client::ACL_DELETEMBOX] = true;
        } else {
            unset($this[Horde_Imap_Client::ACL_CREATE]);
        }

        if ($this[Horde_Imap_Client::ACL_DELETEMBOX] ||
            $this[Horde_Imap_Client::ACL_DELETEMSGS] ||
            $this[Horde_Imap_Client::ACL_EXPUNGE]) {
            $this[Horde_Imap_Client::ACL_DELETE] = true;
        } elseif ($this[Horde_Imap_Client::ACL_DELETE]) {
            $this[Horde_Imap_Client::ACL_DELETEMBOX] = true;
            $this[Horde_Imap_Client::ACL_DELETEMSGS] = true;
            $this[Horde_Imap_Client::ACL_EXPUNGE] = true;
        } else {
            unset($this[Horde_Imap_Client::ACL_DELETE]);
        }
    }

}
