<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   IMP
 */

/**
 * Linked attachment data.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   IMP
 */
class IMP_Compose_LinkedAttachment
{
    /**
     * Attachment data.
     *
     * @var IMP_Compose_Attachment_Storage
     */
    protected $_atc;

    /**
     * Attachment ID (filename in VFS).
     *
     * @var string
     */
    protected $_id;

    /**
     * Owner of the attachment.
     *
     * @var string
     */
    protected $_user;

    /**
     * Constructor.
     *
     * @param string $user  Attachment owner.
     * @param string $id    ID of the attachment.
     *
     * @throws Horde_Vfs_Exception
     * @throws IMP_Exception
     */
    public function __construct($user, $id = null)
    {
        global $conf, $injector;

        /* Sanity checking. */
        if (empty($conf['compose']['link_attachments'])) {
            throw new IMP_Exception('Linked attachments are forbidden.');
        }

        $this->_atc = $injector->getInstance('IMP_Factory_ComposeAtc')->create($user, $id);
        $this->_id = $id;
        $this->_user = $user;
    }

    /**
     * Send data to the browser.
     *
     * @throws IMP_Compose_Exception
     */
    public function sendData()
    {
        global $browser;

        if (!$this->_atc->exists()) {
            throw new IMP_Exception(_("The linked attachment does not exist. It may have been deleted by the original sender or it may have expired."));
        }

        $data = $this->_atc->read();
        fseek($data, 0, SEEK_END);
        $size = ftell($data);
        rewind($data);

        $md = $this->_atc->getMetadata();
        $browser->downloadHeaders($md->filename, $md->type, false, $size);

        while (!feof($data)) {
            echo fread($data, 8192);
        }
        fclose($data);
    }

    /**
     * Delete a linked attachment.
     *
     * @param string $token  The delete token.
     *
     * @return boolean|string  Filename of deleted file, or false if file was
     *                         not deleted.
     */
    public function delete($token)
    {
        if (empty($GLOBALS['conf']['compose']['link_attachments_notify']) ||
            !($dtoken = $this->_getDeleteToken()) ||
            ($dtoken != $token)) {
            return false;
        }

        $md = $this->_atc->getMetadata();

        try {
            $this->_atc->delete();
        } catch (Exception $e) {}

        $this->_atc->saveMetadata();

        return $md->filename;
    }

    /**
     * Convert filename from old (pre-6.1) format.
     *
     * @param string $ts    Timestamp.
     * @param string $file  Filename.
     *
     * @throws IMP_Exception
     */
    public function convert($ts, $file)
    {
        global $injector;

        $vfs = $injector->getInstance('Horde_Core_Factory_Vfs')->create();

        /* Build reproducible ID value from old data. */
        $id = hash('md5', $ts . '|' . $file);

        /* Create new attachment object. */
        $atc = $injector->getInstance('IMP_Factory_ComposeAtc')->create($this->_user, $id);

        $old_path = '.horde/imp/attachments/' . $ts;
        if (!$vfs->exists($old_path, $file)) {
            return;
        }

        try {
            $vfs->rename($old_path, $file, $atc::VFS_LINK_ATTACH_PATH, $id);
        } catch (Exception $e) {
            return;
        }

        $d_id = null;
        $notify = $file . '.notify';

        if ($vfs->exists($old_path, $notify)) {
            try {
                $d_id = $vfs->read($old_path, $notify);
                $vfs->deleteFile($old_path, $notify);
            } catch (Exception $e) {}
        }

        $md = $atc->getMetadata();
        $md->dtoken = $d_id;
        $md->filename = $file;
        $md->time = $ts;
        $md->type = 'application/octet-stream';
        $atc->saveMetadata($md);

        $this->_atc = $atc;
        $this->_id = $id;
    }

    /**
     * Send notification to attachment owner.
     */
    public function sendNotification()
    {
        global $conf, $injector, $registry;

        if (empty($conf['compose']['link_attachments_notify'])) {
            return;
        }

        try {
            $identity = $injector->getInstance('Horde_Core_Factory_Identity')->create($this->_user);
            $address = $identity->getDefaultFromAddress();

            /* Ignore missing addresses, which are returned as <>. */
            if ((strlen($address) < 3) || $this->_getDeleteToken()) {
                return;
            }

            $address_full = $identity->getDefaultFromAddress(true);

            /* Load user prefs to correctly translate gettext strings. */
            if (!$registry->getAuth()) {
                $prefs = $injector->getInstance('Horde_Core_Factory_Prefs')
                    ->create('imp', array('user' => $this->_user));
                $registry->setLanguageEnvironment($prefs->getValue('language'));
            }

            $h = new Horde_Mime_Headers();
            $h->addReceivedHeader(array(
                'dns' => $injector->getInstance('Net_DNS2_Resolver'),
                'server' => $conf['server']['name']
            ));
            $h->addMessageIdHeader();
            $h->addUserAgentHeader();
            $h->addHeader('Date', date('r'));
            $h->addHeader('From', $address_full);
            $h->addHeader('To', $address_full);
            $h->addHeader('Subject', _("Notification: Linked attachment downloaded"));
            $h->addHeader('Auto-Submitted', 'auto-generated');

            $msg = new Horde_Mime_Part();
            $msg->setType('text/plain');
            $msg->setCharset('UTF-8');

            $md = $this->_atc->getMetadata();
            $msg->setContents(Horde_String::wrap(
                _("Your linked attachment has been downloaded by at least one user.") . "\n\n" .
                sprintf(_("Name: %s"), $md->filename) . "\n" .
                sprintf(_("Type: %s"), $md->type) . "\n" .
                sprintf(_("Sent Date: %s"), date('r', $md->time)) . "\n\n" .
                _("Click on the following link to permanently delete the attachment:") . "\n" .
                strval($this->_atc->link_url->add('d', $this->_getDeleteToken(true)))
            ));

            $msg->send($address, $h, $injector->getInstance('Horde_Mail'));
        } catch (Exception $e) {
            Horde::log($e, 'ERR');
        }
    }

    /* Static methods. */

    /**
     * Return UNIX timestamp of linked attachment expiration time.
     *
     * @param boolean $past  If true, determine maximim creation time for
     *                       expiration. If false, determine future expiration
     *                       time.
     *
     * @return integer|null  UNIX timestamp, or null if attachments are not
     *                       pruned.
     */
    static public function keepDate($past = true)
    {
        return ($damk = $GLOBALS['prefs']->getValue('delete_attachments_monthly_keep'))
            ? mktime(0, 0, 0, date('n') + ($past ? ($damk * -1) : ($damk + 1)), 1, date('Y'))
            : null;
    }

    /* Private methods. */

    /**
     * Get/create the delete token.
     *
     * @param boolean $create  Create token if it doesn't exist?
     *
     * @return string  The delete token, or null if it doesn't exist.
     */
    protected function _getDeleteToken($create = false)
    {
        $md = $this->_atc->getMetadata();

        if (is_null($md->dtoken)) {
            if (!$create) {
                return null;
            }

            $md->dtoken = strval(new Horde_Support_Uuid);
            try {
                $this->_atc->saveMetadata($md);
            } catch (Exception $e) {}
        }

        return $md->dtoken;
    }

}
