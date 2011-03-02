<?php
/**
 * Contains functions related to managing IMAP Access Control Lists.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Chris Hastie <imp@oak-wood.co.uk>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Imap_Acl
{
    /**
     * Constructor.
     *
     * @throws IMP_Exception
     */
    public function __construct()
    {
        if ($GLOBALS['session']->get('imp', 'protocol') != 'imap') {
            throw new IMP_Exception(_("ACL requires an IMAP server."));
        }

        if (!$GLOBALS['session']->get('imp', 'imap_acl')) {
            throw new IMP_Exception(_("ACLs not configured for this server."));
        }

        if (!$GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->queryCapability('ACL')) {
            throw new IMP_Exception(_("IMAP server does not support ACLs."));
        }
    }

    /**
     * Retrieve the existing ACLs for a mailbox from the server.
     *
     * @param IMP_Mailbox $mbox  The mailbox to get the ACL for.
     *
     * @return array  See Horde_Imap_Client_Base::getACL().
     * @throws IMP_Exception
     */
    public function getACL(IMP_Mailbox $mbox)
    {
        try {
            return $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->getACL($mbox);
        } catch (Horde_Imap_Client_Exception $e) {
            throw new IMP_Exception(_("Could not retrieve ACL"));
        }
    }

    /**
     * Adds rights to an ACL on the server.
     *
     * @param IMP_Mailbox $mbox  The mailbox on which to edit the ACL.
     * @param string $user       The user to grant rights to.
     * @param string $rights     The rights to add.
     *
     * @throws IMP_Exception
     */
    public function addRights(IMP_Mailbox $mbox, $user, $rights)
    {
        try {
            $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->setACL($mbox, $user, array(
                'rights' => $rights
            ));
        } catch (Horde_Imap_Client_Exception $e) {
            throw new IMP_Exception(sprintf(_("Couldn't give user \"%s\" these rights for the mailbox \"%s\": %s"), $user, $mbox, $rights));
        }
    }

    /**
     * Removes rights to an ACL on the server.
     *
     * @param IMP_Mailbox $mbox  The mailbox on which to edit the ACL.
     * @param string $user       The user to remove rights from.
     * @param array $rights      The rights to remove.  If empty, removes the
     *                           entire ACL.
     *
     * @throws IMP_Exception
     */
    public function removeRights(IMP_Mailbox $mbox, $user, $rights)
    {
        try {
            $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->setACL($mbox, $user, array(
                'remove' => true,
                'rights' => $rights
            ));
        } catch (Horde_Imap_Client_Exception $e) {
            throw new IMP_Exception(sprintf(_("Couldn't remove from user \"%s\" these rights for the mailbox \"%s\": %s"), $user, $mbox, $rights));
        }
    }

    /**
     * Can the current user edit the ACL for this mailbox?
     *
     * @param IMP_Mailbox $mbox  The mailbox name.
     *
     * @return boolean  True if the current user has administrative rights.
     */
    public function canEdit(IMP_Mailbox $mbox)
    {
        $rights = $this->getRightsMbox($mbox, $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->getParam('username'));
        return $rights[Horde_Imap_Client::ACL_ADMINISTER];
    }

    /**
     * Return master list of ACL rights.
     *
     * @return array  A list of ACL rights.
     */
    public function getRights()
    {
        return $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->allAclRights();
    }

    /**
     * Return list of rights available on the server.
     *
     * @param IMP_Mailbox $mbox  The mailbox name.
     * @param string $user       The ACL identifier (user) to query.
     *
     * @return Horde_Imap_Client_Data_AclRights  An ACL rights object.
     */
    public function getRightsMbox(IMP_Mailbox $mbox, $user)
    {
        try {
            return $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->listACLRights($mbox, $user);
        } catch (Horde_Imap_Client_Exception $e) {
            return new Horde_Imap_Client_Data_AclRights(array(), $this->getRights());
        }
    }

}
