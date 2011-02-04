<?php
/**
 * Mnemo_Driver:: defines an API for implementing storage backends for Mnemo.
 *
 * Copyright 2001-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Jon Parise <jon@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Mnemo
 */
abstract class Mnemo_Driver
{
    /**
     * Array holding the current memo list.  Each array entry is a hash
     * describing a memo.  The array is indexed numerically by memo ID.
     *
     * @var array
     */
    protected $_memos = array();

    /**
     * String containing the current notepad name.
     *
     * @var string
     */
    protected $_notepad = '';

    /**
     * Crypting processor.
     *
     * @var Horde_Crypt_pgp
     */
    protected $_pgp;


    /**
     * Lists memos based on the given criteria. All memos will be
     * returned by default.
     *
     * @return array    Returns a list of the requested memos.
     */
    public function listMemos()
    {
        return $this->_memos;
    }

    /**
     * Update the description (short summary) of a memo.
     *
     * @param integer $memo_id  The memo to update.
     */
    public function getMemoDescription($body)
    {
        if (!strstr($body, "\n") && Horde_String::length($body) <= 64) {
            return trim($body);
        }

        $lines = explode("\n", $body);
        if (!is_array($lines)) {
            return trim(Horde_String::substr($body, 0, 64));
        }

        // Move to a line with more than spaces.
        $i = 0;
        while (isset($lines[$i]) && !preg_match('|[^\s]|', $lines[$i])) {
            $i++;
        }
        if (Horde_String::length($lines[$i]) <= 64) {
            return trim($lines[$i]);
        } else {
            return trim(Horde_String::substr($lines[$i], 0, 64));
        }
    }

    /**
     * Loads the PGP encryption driver.
     *
     * @TODO: Inject *into* driver from the factory binder
     */
    protected function _loadPGP()
    {
        if (empty($GLOBALS['conf']['utils']['gnupg'])) {
            throw new Mnemo_Exception(_("Encryption support has not been configured, please contact your administrator."));
        }

        $this->_pgp = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Crypt')->create('pgp', array(
            'program' => $GLOBALS['conf']['utils']['gnupg']
        ));
    }

    /**
     * Encrypts a note.
     *
     * @param string $note        The note text.
     * @param string $passphrase  The passphrase to encrypt the note with.
     *
     * @return string  The encrypted text.
     */
    protected function _encrypt($note, $passphrase)
    {
        $this->_loadPGP();
        return $this->_pgp->encrypt($note, array('type' => 'message', 'symmetric' => true, 'passphrase' => $passphrase));
    }

    /**
     * Decrypts a note.
     *
     * @param string $note        The encrypted note text.
     * @param string $passphrase  The passphrase to decrypt the note with.
     *
     * @return string  The decrypted text.
     * @throws Mnemo_Exception
     */
    protected function _decrypt($note, $passphrase)
    {
        $this->_loadPGP();

        try {
            return $this->_pgp->decrypt($note, array('type' => 'message', 'passphrase' => $passphrase));
        } catch (Horde_Crypt_Exception $e) {
            throw new Mnemo_Exception($e->getMessage(), Mnemo::ERR_DECRYPT);
        }
    }

    /**
     * Returns whether note encryption is supported.
     *
     * Checks if PGP support could be loaded, if it supports symmetric
     * encryption, and if we have a secure connection.
     *
     * @return boolean  Whether encryption is suppoted.
     */
    public function encryptionSupported()
    {
        try {
            $this->_loadPGP();
        } catch (Mnemo_Exception $e) {
        }
        return (is_callable(array($this->_pgp, 'encryptedSymmetrically')) &&
                Horde::isConnectionSecure());
    }

    /**
     * Export this memo in iCalendar format.
     *
     * @param array  memo      The memo (hash array) to export
     * @param Horde_Icalendar  A Horde_Icalendar object that acts as container.
     *
     * @return Horde_Icalendar_Vnote  object for this event.
     */
    public function toiCalendar($memo, $calendar)
    {
        global $prefs;

        $vnote = Horde_Icalendar::newComponent('vnote', $calendar);

        $vnote->setAttribute('UID', $memo['uid']);
        $vnote->setAttribute('BODY', $memo['body']);
        $vnote->setAttribute('SUMMARY', $this->getMemoDescription($memo['body']));

        if (!empty($memo['category'])) {
            $vnote->setAttribute('CATEGORIES', $memo['category']);
        }

        /* Get the note's history. */
        $history = $GLOBALS['injector']->getInstance('Horde_History');
        $log = $history->getHistory('mnemo:' . $memo['memolist_id'] . ':' . $memo['uid']);
        if ($log) {
            foreach ($log->getData() as $entry) {
                switch ($entry['action']) {
                case 'add':
                    $created = $entry['ts'];
                    break;

                case 'modify':
                    $modified = $entry['ts'];
                    break;
                }
            }
        }

        if (!empty($created)) {
            $vnote->setAttribute('DCREATED', $created);
        }
        if (!empty($modified)) {
            $vnote->setAttribute('LAST-MODIFIED', $modified);
        }

        return $vnote;
    }

    /**
     * Create a memo (hash array) from a Horde_Icalendar_Vnote object.
     *
     * @param Horde_Icalendar_Vnote $vnote  The iCalendar data to update from.
     *
     * @return array  Memo (hash array) created from the vNote.
     */
    public function fromiCalendar(Horde_Icalendar_Vnote $vNote)
    {
        $memo = array();

        try {
            $body = $vNote->getAttribute('BODY');
        } catch (Horde_Icalendar_Exception $e) {
        }
        if (!is_array($body)) {
            $memo['body'] = $body;
        } else {
            $memo['body'] = '';
        }

        $memo['desc'] = $this->getMemoDescription($memo['body']);

        try {
            $cat = $vNote->getAttribute('CATEGORIES');
        } catch (Horde_Icalendar_Exception $e) {
        }
        if (!is_array($cat)) {
            $memo['category'] = $cat;
        }

        return $memo;
    }

    /**
     * Retrieves notes from the database.
     *
     * @thows Mnemo_Exception
     */
    abstract public function retrieve();
}
