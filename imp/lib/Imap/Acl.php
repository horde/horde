<?php
/**
 * Contains functions related to managing IMAP Access Control Lists.
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chris Hastie <imp@oak-wood.co.uk>
 * @package IMP
 */
class IMP_Imap_Acl
{
    /**
     * Singleton instance.
     *
     * @var IMP_Imap_Acl
     */
    static protected $_instance = null;

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
     * Attempts to return a reference to a concrete object instance.
     * It will only create a new instance if no instance currently exists.
     *
     * @return IMP_Imap_Acl  The created concrete instance.
     * @throws Horde_Exception
     */
    static public function singleton()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Constructor.
     *
     * @throws Horde_Exception
     */
    protected function __construct()
    {
        if ($_SESSION['imp']['protocol'] != 'imap') {
            throw new Horde_Exception(_("ACL requires an IMAP server."));
        }

        $capability = $GLOBALS['imp_imap']->ob()->queryCapability('ACL');
        if (!$capability) {
            throw new Horde_Exception(_("IMAP server does not support ACLs."));
        }

        $rfc4314 = $GLOBALS['imp_imap']->ob()->queryCapability('RIGHTS');

        $this->_protected = array($GLOBALS['imp_imap']->ob()->getParam('username'));

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

        if ($rfc4314) {
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
     * @return array  A hash containing information on the ACL.
     * @throws Horde_Exception
     */
    public function getACL($mbox)
    {
        try {
            return $GLOBALS['imp_imap']->ob()->getACL($mbox);
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Exception(_("Could not retrieve ACL"));
        }
    }

    /**
     * Edits an ACL on the server.
     *
     * @param string $mbox  The mailbox on which to edit the ACL.
     * @param string $user  The user to grant rights to.
     * @param array $acl    The keys of which are the rights to be granted
     *                      (see RFC 2086).
     *
     * @throws Horde_Exception
     */
    public function editACL($mbox, $user, $acl)
    {
        try {
            $GLOBALS['imp_imap']->ob()->setACL($mbox, $user, array('rights' => $acl));
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Exception(sprintf(_("Couldn't give user \"%s\" the following rights for the folder \"%s\": %s"), $user, $mbox, implode('', $acl)));
        }
    }

    /**
     * Can a user edit the ACL for this mailbox?
     *
     * @param string $mbox  The mailbox name.
     * @param string $user  A user name.
     *
     * @return boolean  True if $user has 'a' right
     */
    public function canEdit($mbox, $user)
    {
        try {
            $rights = $GLOBALS['imp_imap']->ob()->listACLRights($mbox, $user);
            foreach ($rights as $val) {
                if (strpos($val, 'a') !== false) {
                    return true;
                }
            }
            return false;
        } catch (Horde_Imap_Client_Exception $e) {
            return false;
        }
    }

    /**
     * TODO
     */
    public function getRights()
    {
        return $this->_rightsList;
    }

    /**
     * TODO
     */
    public function getProtected()
    {
        return $this->_protected;
    }

}
