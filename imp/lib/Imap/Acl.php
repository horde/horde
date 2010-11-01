<?php
/**
 * Contains functions related to managing IMAP Access Control Lists.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Chris Hastie <imp@oak-wood.co.uk>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Imap_Acl
{
    /**
     * Hash containing the list of possible rights and a human readable
     * description of each.
     *
     * @var array
     */
    protected $_rightsList;

    /**
     * Array containing user names that cannot have their access rights
     * changed.
     *
     * @var boolean
     */
    protected $_protected;

    /**
     * Constructor.
     *
     * @throws IMP_Exception
     */
    public function __construct()
    {
        if ($GLOBALS['session']['imp:protocol'] != 'imap') {
            throw new IMP_Exception(_("ACL requires an IMAP server."));
        }

        if (!$GLOBALS['session']['imp:imap_acl']) {
            throw new IMP_Exception(_("ACLs not configured for this server."));
        }

        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Imap')->create();
        if (!$imp_imap->queryCapability('ACL')) {
            throw new IMP_Exception(_("IMAP server does not support ACLs."));
        }

        $this->_protected = array($imp_imap->getParam('username'));

        $this->_rightsList = array(
            'l' => array(
                'desc' => _("List - user can see the folder"),
                'title' => _("List")
            ),
            'r' => array(
                'desc' => _("Read messages"),
                'title' => _("Read")
            ),
            's' => array(
                'desc' => _("Mark with Seen/Unseen flags"),
                'title' => _("Mark (Seen)")
            ),
            'w' => array(
                'desc' => _("Mark with other flags (e.g. Important/Answered)"),
                'title' => _("Mark (Other)")
            ),
            'i' => array(
                'desc' => _("Insert messages"),
                'title' => _("Insert")
            ),
            'p' => array(
                'desc' => _("Post to this folder (not enforced by IMAP)"),
                'title' => _("Post")
            ),
            'a' => array(
                'desc' => _("Administer - set permissions for other users"),
                'title' => _("Administer")
            )
        );

        if ($imp_imap->queryCapability('RIGHTS')) {
            // RFC 4314 compliant rights
            $this->_rightsList = array_merge($this->_rightsList, array(
                'k' => array(
                    'desc' => _("Create sub folders"),
                    'title' => _("Create Folders")
                ),
                'x' => array(
                    'desc' => _("Delete sub folders"),
                    'title' => _("Delete Folders")
                ),
                't' => array(
                    'desc' => _("Delete messages"),
                    'title' => _("Delete")
                ),
                'e' => array(
                    'desc' => _("Purge messages"),
                    'title' => _("Purge")
                )
            ));
        } else {
            // RFC 2086 compliant rights
            $this->_rightsList = array_merge($this->_rightsList, array(
                'c' => array(
                    'desc' =>_("Create sub folders"),
                    'title' => _("Create Folder")
                ),
                'd' => array(
                    'desc' => _("Delete and purge messages"),
                    'title' => _("Delete/Purge")
                )
            ));
        }
    }

    /**
     * Attempts to retrieve the existing ACL for a mailbox from the server.
     *
     * @param string $mbox  The mailbox to get the ACL for.
     *
     * @return array  See Horde_Imap_Client_Base::getACL().
     * @throws IMP_Exception
     */
    public function getACL($mbox)
    {
        try {
            return $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Imap')->create()->getACL($mbox);
        } catch (Horde_Imap_Client_Exception $e) {
            throw new IMP_Exception(_("Could not retrieve ACL"));
        }
    }

    /**
     * Edits an ACL on the server.
     *
     * @param string $mbox  The mailbox on which to edit the ACL.
     * @param string $user  The user to grant rights to.
     * @param array $acl    The rights to be granted.
     *
     * @throws IMP_Exception
     */
    public function editACL($mbox, $user, $acl)
    {
        try {
            $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Imap')->create()->setACL($mbox, $user, array('remove' => empty($acl), 'rights' => implode('', $acl)));
        } catch (Horde_Imap_Client_Exception $e) {
            throw new IMP_Exception(sprintf(_("Couldn't give user \"%s\" the following rights for the folder \"%s\": %s"), $user, $mbox, implode('', $acl)));
        }
    }

    /**
     * Can a user edit the ACL for this mailbox?
     *
     * @param string $mbox  The mailbox name.
     * @param string $user  A user name.
     *
     * @return boolean  True if $user has 'a' right.
     */
    public function canEdit($mbox, $user)
    {
        try {
            $rights = $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Imap')->create()->listACLRights($mbox, $user);
            $rights = array_merge($rights['required'], $rights['optional']);
            foreach ($rights as $val) {
                if (strpos($val, 'a') !== false) {
                    return true;
                }
            }
        } catch (Horde_Imap_Client_Exception $e) {}

        return false;
    }

    /**
     * Return list of rights available on the server.
     *
     * @return array  Rights list.
     */
    public function getRights()
    {
        return $this->_rightsList;
    }

    /**
     * Returns list of protected users.
     *
     * @return array  List of protected users.
     */
    public function getProtected()
    {
        return $this->_protected;
    }

}
