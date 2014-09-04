<?php
/**
 * Horde_ActiveSync_Mime::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * This class provides some base functionality for dealing with MIME objects in
 * the context of ActiveSync requests.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Mime
{

    protected $_base;

    protected $_hasAttachments;


    public function __construct(Horde_Mime_Part $mime)
    {
        $this->_base = $mime;
    }

    public function __get($property)
    {
        switch ($property) {
        case 'base':
            return $this->_base;
        }
        return $this->_base->property;
    }

    public function __call($method, $params)
    {
        if (is_callable(array($this->_base, $method))) {
            return call_user_func_array(array($this->_base, $method), $params);
        }

        throw new InvalidArgumentException();
    }

    /**
     * Return the hasAttachments flag
     *
     * @return boolean
     */
    public function hasAttachments()
    {
        if (isset($this->_hasAttachments)) {
            return $this->_hasAttachments;
        }

        foreach ($this->_base->contentTypeMap() as $id => $type) {
            if ($this->isAttachment($id, $type)) {
                $this->_hasAttachments = true;
                return true;
            }
        }
        $this->_hasAttachments = false;

        return false;
    }

    /**
     * Determines if a MIME type is an attachment.
     * For our purposes, an attachment is any MIME part that can be
     * downloaded by itself (i.e. all the data needed to view the part is
     * contained within the download data).
     *
     * @param string $id         The MIME Id for the part we are checking.
     * @param string $mime_type  The MIME type.
     *
     * @return boolean  True if an attachment.
     */
    public function isAttachment($id, $mime_type)
    {
        switch ($mime_type) {
        case 'text/plain':
            if (!($this->_base->findBody('plain') == $id)) {
                return true;
            }
            return false;
        case 'text/html':
            if (!($this->_base->findBody('html') == $id)) {
                return true;
            }
            return false;
        case 'application/pkcs7-signature':
        case 'application/x-pkcs7-signature':
            return false;
        }

        list($ptype,) = explode('/', $mime_type, 2);

        switch ($ptype) {
        case 'message':
            return in_array($mime_type, array('message/rfc822', 'message/disposition-notification'));

        case 'multipart':
            return false;

        default:
            return true;
        }
    }

    /**
     * Return the MIME part of the iCalendar attachment, if available.
     *
     * @return mixed  The mime part, if present, false otherwise.
     */
    public function hasiCalendar()
    {
        if (!$this->hasAttachments()) {
            return false;
        }
        foreach ($this->_base->contentTypeMap() as $id => $type) {
            if ($type == 'text/calendar') {
                return $this->_base->getMimePart($id);
            }
        }

        return false;
    }

    /**
     * Return the S/MIME status of this message (RFC2633)
     *
     * @return boolean True if message is S/MIME signed or encrypted,
     *                 false otherwise.
     */
    public function isSigned(Horde_Mime_Part $mime = null)
    {
        if (empty($mime)) {
            $mime = $this->_base;
        }

        if ($mime->getType() == 'application/pkcs7-mime' ||
            $mime->getType() == 'application/x-pkcs7-mime') {
            return true;
        }

        if ($mime->getPrimaryType() == 'multipart') {
            if ($mime->getSubType() == 'signed') {
                return true;
            }

            // Signed/encrypted part might be lower in the mime structure
            foreach ($mime->getParts() as $part) {
                if ($this->isSigned($part)) {
                    return true;
                }
            }
        }

        return false;
    }

}