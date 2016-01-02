<?php
/**
 * Horde_ActiveSync_Mime::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 * @since 2.19.0
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
 * @copyright 2012-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 * @since 2.19.0
 */
class Horde_ActiveSync_Mime
{
    /**
     * The composited mime part.
     *
     * @var Horde_Mime_Part
     */
    protected $_base;

    /**
     * Local cache of hasAttachments data.
     *
     * @var boolean
     */
    protected $_hasAttachments;

    /**
     * Cont'r
     *
     * @param Horde_Mime_Part $mime The mime data.
     */
    public function __construct(Horde_Mime_Part $mime)
    {
        $this->_base = $mime;
    }

    public function __destruct()
    {
        $this->_base = null;
    }

    /**
     * Accessor
     *
     * @param string $property  The property name.
     *
     * @return mixed
     */
    public function __get($property)
    {
        switch ($property) {
        case 'base':
            return $this->_base;
        }
        return $this->_base->property;
    }

    /**
     * Delegate calls to the composed MIME object.
     *
     * @param string $method  The method name.
     * @param array $params   The parameters.
     *
     * @return mixed
     */
    public function __call($method, array $params)
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
     * @todo Pass a single mime part as parameter.
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

        if ($this->_base->getPart($id)->getDisposition() == 'attachment') {
            return true;
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
     * @return mixed  The mime id of an iCalendar part, if present. Otherwise
     *                false.
     */
    public function hasiCalendar()
    {
        if (!$this->hasAttachments()) {
            return false;
        }
        foreach ($this->_base->contentTypeMap() as $id => $type) {
            if ($type == 'text/calendar' || $type == 'application/ics') {
                return $id;
            }
        }

        return false;
    }

    /**
     * Return the S/MIME status of this message (RFC2633)
     *
     * @param Horde_Mime_Part  The part to test. If omitted, uses self::$_base
     *
     * @return boolean  True if message is S/MIME signed, otherwise false.
     */
    public function isSigned(Horde_Mime_Part $mime = null)
    {
        if (empty($mime)) {
            $mime = $this->_base;
        }

        if ($mime->getPrimaryType() == 'multipart') {
            if ($mime->getSubType() == 'signed' && $mime->getContentTypeParameter('protocol') != 'application/pgp-signature') {
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

    /**
     * Return the S/MIME encryption status of this message.
     *
     * @param Horde_Mime_Part  The part to test. If omitted, uses self::$_base
     *
     * @return boolean  True if message is S/MIME encrypted, otherwise false.
     * @since 2.20.0
     * @todo For 3.0, combine into one method with self::isSigned() and return
     *       a bitmask result.
     */
    public function isEncrypted(Horde_Mime_Part $mime = null)
    {
        if (empty($mime)) {
            $mime = $this->_base;
        }

        if ($mime->getType() == 'application/pkcs7-mime' ||
            $mime->getType() == 'application/x-pkcs7-mime') {
            return true;
        }

        // Signed/encrypted part might be lower in the mime structure
        foreach ($mime->getParts() as $part) {
            if ($this->isEncrypted($part)) {
                return true;
            }
        }
    }

    /**
     * Finds the main "body" text part (if any) in a message. "Body" data is the
     * first text part under this part. Considers only body data that should
     * be displayed as the main body on an EAS client. I.e., this ignores any
     * text parts contained withing "attachment" parts such as messages/rfc822
     * attachments.
     *
     * @param string $subtype  Specifically search for this subtype.
     *
     * @return mixed  The MIME ID of the main body part, or null if a body
     *                part is not found.
     */
    public function findBody($subtype = null)
    {
        $this->buildMimeIds();
        $iterator = new Horde_ActiveSync_Mime_Iterator($this->_base, true);
        foreach ($iterator as $val) {
            $id = $val->getMimeId();
            if (($val->getPrimaryType() == 'text') &&
                ((intval($id) === 1) || !$this->getMimeId()) &&
                (is_null($subtype) || ($val->getSubType() == $subtype)) &&
                !$this->isAttachment($id, $val->getType())) {
                return $id;
            }
        }

        return null;
    }

}