<?php
/**
 * This class provides Horde-specific functions for the Horde_Prefs_Identity
 * class.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
class Horde_Core_Prefs_Identity extends Horde_Prefs_Identity
{
    /**
     * Sends a message to an email address supposed to be added to the
     * identity.
     * A message is send to this address containing a link to confirm that the
     * address really belongs to that user.
     *
     * @param integer $id       The identity's ID.
     * @param string $old_addr  The old From: address.
     *
     * @throws Horde_Mime_Exception
     */
    public function verifyIdentity($id, $old_addr)
    {
        global $conf;

        $hash = strval(new Horde_Support_Randomid());

        if (!($pref = @unserialize($this->_prefs->getValue('confirm_email')))) {
            $pref = array();
        }
        $pref[$hash] = $this->get($id);
        $this->_prefs->setValue('confirm_email', serialize($pref));

        $new_addr = $this->getValue($this->_prefnames['from_addr'], $id);
        $confirm = Horde::getServiceLink('emailconfirm')->add('h', $hash)->setRaw(true);
        $message = sprintf(_("You have requested to add the email address \"%s\" to the list of your personal email addresses.\n\nGo to the following link to confirm that this is really your address:\n%s\n\nIf you don't know what this message means, you can delete it."),
                           $new_addr,
                           $confirm);

        $msg_headers = new Horde_Mime_Headers();
        $msg_headers->addMessageIdHeader();
        $msg_headers->addUserAgentHeader();
        $msg_headers->addHeader('Date', date('r'));
        $msg_headers->addHeader('To', $new_addr);
        $msg_headers->addHeader('From', $old_addr);
        $msg_headers->addHeader('Subject', _("Confirm new email address"));

        $body = new Horde_Mime_Part();
        $body->setType('text/plain');
        $body->setContents(Horde_String::wrap($message, 76, "\n"));
        $body->setCharset('UTF-8');

        $body->send($new_addr, $msg_headers, $GLOBALS['injector']->getInstance('Horde_Mail'));

        $GLOBALS['notification']->push(sprintf(_("A message has been sent to \"%s\" to verify that this is really your address. The new email address is activated as soon as you confirm this message."), $new_addr), 'horde.message');
    }

    /**
     * Checks whether an identity confirmation is valid, and adds the
     * validated identity.
     *
     * @param string $hash  The saved hash of the identity being validated.
     */
    public function confirmIdentity($hash)
    {
        global $notification;

        $confirm = $this->_prefs->getValue('confirm_email');
        if (empty($confirm)) {
            $notification->push(_("There are no email addresses to confirm."), 'horde.message');
            return;
        }

        $confirm = @unserialize($confirm);
        if (empty($confirm)) {
            $notification->push(_("There are no email addresses to confirm."), 'horde.message');
            return;
        } elseif (!isset($confirm[$hash])) {
            $notifcation->push(_("Email addresses to confirm not found."), 'horde.message');
            return;
        }

        $identity = $confirm[$hash];
        $id = array_search($identity['id'], $this->getAll($this->_prefnames['id']));
        if ($id === false) {
            /* Adding a new identity. */
            $verified = array();
            foreach ($identity as $key => $value) {
                if (!$this->_prefs->isLocked($key)) {
                    $verified[$key] = $value;
                }
            }
            $this->add($verified);
        } else {
            /* Updating an existing identity. */
            foreach ($identity as $key => $value) {
                $this->setValue($key, $value, $id);
            }
        }
        $this->save();
        unset($confirm[$hash]);
        $this->_prefs->setValue('confirm_email', serialize($confirm));

        $notification->push(sprintf(_("The email address %s has been added to your identities. You can close this window now."), $verified[$this->_prefnames['from_addr']]), 'horde.success');
    }

    /**
     * Returns the from address based on the chosen identity. If no
     * address can be found it is built from the current user name and
     * the specified maildomain.
     *
     * @param integer $ident  The identity to retrieve the address from.
     *
     * @return string  A valid from address.
     */
    public function getFromAddress($ident = null)
    {
        return $GLOBALS['prefs']->getValue('from_addr');
    }

    /**
     * Returns the identity's id that matches the passed addresses.
     *
     * @param mixed $addresses     Either an array or a single string or a
     *                             comma-separated list of email addresses.
     * @param boolean $search_own  Search for a matching identity in own
     *                             addresses also?
     *
     * @return integer  The id of the first identity that from or alias
     *                  addresses match (one of) the passed addresses or
     *                  null if none matches.
     */
    public function getMatchingIdentity($addresses, $search_own = true)
    {
        return null;
    }

}
