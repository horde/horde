<?php
/**
 * Contains functions related to managing IMAP Access Control Lists.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Chris Hastie <imp@oak-wood.co.uk>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
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
        if (!$GLOBALS['session']->get('imp', 'imap_acl')) {
            throw new IMP_Exception(_("ACLs not configured for this server."));
        }

        if (!$GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->queryCapability('ACL')) {
            throw new IMP_Exception(_("Server does not support ACLs."));
        }
    }

    /**
     * Retrieve the existing ACLs for a mailbox from the server.
     *
     * @param IMP_Mailbox $mbox  The mailbox to get the ACL for.
     * @param boolean $user      Return only the current user's rights?
     *
     * @return array  If $user is false, see Horde_Imap_Client_Base::getACL().
     *                If $user is true, see
     *                Horde_Imap_Client_Base::getMyACLRights().
     * @throws IMP_Exception
     */
    public function getACL(IMP_Mailbox $mbox, $user = false)
    {
        try {
            $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();
            return $user
                ? $imp_imap->getMyACLRights($mbox)
                : $imp_imap->getACL($mbox);
        } catch (IMP_Imap_Exception $e) {
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
        if (!strlen($rights)) {
            return;
        }

        try {
            $GLOBALS['injector']->getInstance('IMP_Factory_Imap')
                ->create()
                ->setACL($mbox, $user, array('rights' => $rights, 'action' => 'add'));
        } catch (IMP_Imap_Exception $e) {
            throw new IMP_Exception(sprintf(_("Could not add rights for user \"%s\" for the mailbox \"%s\"."), $user, $mbox));
        }
    }

    /**
     * Removes rights to an ACL on the server.
     *
     * @param IMP_Mailbox $mbox  The mailbox on which to edit the ACL.
     * @param string $user       The user to remove rights from.
     * @param string $rights     The rights to remove.  If empty, removes the
     *                           entire ACL.
     *
     * @throws IMP_Exception
     */
    public function removeRights(IMP_Mailbox $mbox, $user, $rights)
    {
        try {
            $GLOBALS['injector']->getInstance('IMP_Factory_Imap')
                ->create()
                ->setACL($mbox, $user, array('rights' => $rights, 'action' => 'remove'));
        } catch (IMP_Imap_Exception $e) {
            throw new IMP_Exception(sprintf(_("Could not remove rights for user \"%s\" for the mailbox \"%s\"."), $user, $mbox));
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
     * @return array  A list of ACL rights. Keys are the right identifiers,
     *                values are arrays containing two entries: 'desc' and
     *                'title'.
     */
    public function getRights()
    {
        return array(
            Horde_Imap_Client::ACL_LOOKUP => array(
                'desc' => _("User can see the mailbox"),
                'title' => _("List")
            ),
            Horde_Imap_Client::ACL_READ => array(
                'desc' => _("Read messages"),
                'title' => _("Read")
            ),
            Horde_Imap_Client::ACL_SEEN => array(
                'desc' => _("Mark with Seen/Unseen flags"),
                'title' => _("Mark (Seen)")
            ),
            Horde_Imap_Client::ACL_WRITE => array(
                'desc' => _("Mark with other flags (e.g. Important/Answered)"),
                'title' => _("Mark (Other)")
            ),
            Horde_Imap_Client::ACL_INSERT => array(
                'desc' => _("Insert messages"),
                'title' => _("Insert")
            ),
            Horde_Imap_Client::ACL_POST => array(
                'desc' => _("Post to this mailbox (not enforced by IMAP)"),
                'title' => _("Post")
            ),
            Horde_Imap_Client::ACL_ADMINISTER => array(
                'desc' => _("Set permissions for other users"),
                'title' => _("Administer")
            ),
            Horde_Imap_Client::ACL_CREATEMBOX => array(
                'desc' => _("Create subfolders and rename mailbox"),
                'title' => _("Create Subfolders/Rename Mailbox")
            ),
            Horde_Imap_Client::ACL_DELETEMBOX => array(
                'desc' => _("Delete and rename mailbox"),
                'title' => _("Delete/Rename Mailbox")
            ),
            Horde_Imap_Client::ACL_DELETEMSGS => array(
                'desc' => _("Delete messages"),
                'title' => _("Delete")
            ),
            Horde_Imap_Client::ACL_EXPUNGE => array(
                'desc' => _("Purge messages"),
                'title' => _("Purge")
            )
        );
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
        } catch (IMP_Imap_Exception $e) {
            return new Horde_Imap_Client_Data_AclRights(array(), array_keys($this->getRights()));
        }
    }

}
