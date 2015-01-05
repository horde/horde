<?php
/**
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * This class provides Horde-specific functions for the Horde_Prefs_Identity
 * class.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */
class Horde_Core_Prefs_Identity extends Horde_Prefs_Identity
{
    /** Identity entry containing the expiration time. */
    const EXPIRE = 'confirm_expire';

    /** Expiration (in seconds) of a confirmation request. */
    const EXPIRE_SECS = 86400;

    /**
     * Sends a message to an email address supposed to be added to the
     * identity.
     *
     * A message is send to this address containing a time-sensitive link to
     * confirm that the address really belongs to that user.
     *
     * @param integer $id       The identity's ID.
     * @param string $old_addr  The old From: address.
     *
     * @throws Horde_Mime_Exception
     */
    public function verifyIdentity($id, $old_addr)
    {
        global $injector, $notification, $registry;

        $hash = strval(new Horde_Support_Randomid());

        $pref = $this->_confirmEmail();
        $pref[$hash] = $this->get($id);
        $pref[$hash][self::EXPIRE] = time() + self::EXPIRE_SECS;

        $this->_confirmEmail($pref);

        $new_addr = $this->getValue($this->_prefnames['from_addr'], $id);
        $confirm = Horde::url(
            $registry->getServiceLink('emailconfirm')->add('h', $hash)->setRaw(true),
            true
        );
        $message = sprintf(
            Horde_Core_Translation::t("You have requested to add the email address \"%s\" to the list of your personal email addresses.\n\nGo to the following link to confirm that this is really your address:\n%s\n\nIf you don't know what this message means, you can delete it."),
            $new_addr,
            $confirm
        );

        $msg_headers = new Horde_Mime_Headers();
        $msg_headers->addHeaderOb(Horde_Mime_Headers_MessageId::create());
        $msg_headers->addHeaderOb(Horde_Mime_Headers_UserAgent::create());
        $msg_headers->addHeaderOb(Horde_Mime_Headers_Date::create());
        $msg_headers->addHeader('To', $new_addr);
        $msg_headers->addHeader('From', $old_addr);
        $msg_headers->addHeader('Subject', Horde_Core_Translation::t("Confirm new email address"));

        $body = new Horde_Mime_Part();
        $body->setType('text/plain');
        $body->setContents(Horde_String::wrap($message, 76));
        $body->setCharset('UTF-8');

        $body->send(
            $new_addr,
            $msg_headers,
            $injector->getInstance('Horde_Mail')
        );

        $notification->push(
            sprintf(
                Horde_Core_Translation::t("A message has been sent to \"%s\" to verify that this is really your address. The new email address is activated as soon as you confirm this message."),
                $new_addr
            ),
            'horde.message'
        );
    }

    /**
     * Checks whether an identity confirmation is valid, and adds the
     * validated identity.
     *
     * @param string $hash  The hash of the identity being validated.
     */
    public function confirmIdentity($hash)
    {
        global $notification;

        $confirm = $this->_confirmEmail();
        if (empty($confirm) || !isset($confirm[$hash])) {
            $notification->push(
                Horde_Core_Translation::t("Email address to confirm not found."),
                'horde.message'
            );
            return;
        }

        $identity = $confirm[$hash];
        unset($identity[self::EXPIRE]);

        $id = array_search(
            $identity['id'],
            $this->getAll($this->_prefnames['id'])
        );

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
        $this->_confirmEmail($confirm);

        $notification->push(
            sprintf(
                Horde_Core_Translation::t("The email address %s has been added to your identities. You can close this window now."),
                $verified[$this->_prefnames['from_addr']]
            ),
            'horde.success'
        );
    }

    /**
     * Perform garbage collection on preferences used by identities.
     */
    public function prefsGc()
    {
        /* Clean out expired confirm_email entries. */
        $confirm = $this->_confirmEmail();
        $changed = false;

        foreach ($confirm as $key => $val) {
            if (!isset($val[self::EXPIRE]) || ($val[self::EXPIRE] < time())) {
                unset($confirm[$key]);
                $changed = true;
            }
        }

        if ($changed) {
            $this->_confirmEmail($confirm);
        }
    }

    /**
     * Returns the user's full name.
     *
     * @param integer $ident  The identity to retrieve the name from.
     *
     * @return string  The user's full name, or the user name if it doesn't
     *                 exist.
     */
    public function getName($ident = null)
    {
        global $registry;

        if (!isset($this->_names[$ident]) &&
            !strlen($this->getValue($this->_prefnames['fullname'], $ident))) {
            $this->_names[$ident] = $registry->convertUsername($this->_user, false);
        }

        return parent::getName($ident);
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
        $from = $this->getValue('from_addr', $ident);
        if (strlen($from)) {
            return $from;
        }
        return $this->_user;
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

    /**
     * Manage the storage of the confirm_email preference.
     *
     * @param array $confirm  If set, save this in the pref backend.
     *
     * @return array  Confirm email array.
     */
    protected function _confirmEmail($confirm = null)
    {
        if (is_null($confirm)) {
            return ($pref = @unserialize($this->_prefs->getValue('confirm_email')))
                ? $pref
                : array();
        }

        $this->_prefs->setValue('confirm_email', serialize($confirm));
        return $confirm;
    }

}
