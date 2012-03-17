<?php
/**
 * This class provides all functionality related to parsing and working with
 * a single IMAP email message when using Horde_Imap_Client.
 *
 * @copyright 2012 Horde LLC (http://www.horde.org/)
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package Core
 */

class Horde_ActiveSync_Imap_Message
{

    /**
     * Message structure
     *
     * @var Horde_Mime_Part
     */
    protected $_message;

    /**
     * The imap client
     *
     * @var Horde_Imap_Client_Base
     */
    protected $_imap;

    /**
     * @var Horde_Imap_Client_Data_Envelope
     */
    protected $_envelope;

    /**
     * Cache if the last body part was encoded or not.
     *
     * @var boolean
     */
    protected $_lastBodyPartDecode = null;

    /**
     *
     * @param Horde_Imap_Client_Base $imap        The imap client object.
     * @param Horde_Imap_Client_Mailbox $mbox     The mailbox object.
     * @param Horde_Imap_Client_Data_Fetch $data  The data returned from a FETCH
     *                                            must contain at least uid,
     *                                            structure and flags.
     */
    public function __construct($imap, $mailbox, $data)
    {
        $this->_imap = $imap;
        $this->_message = $data->getStructure();
        $this->_uid = $data->getUid();
        $this->_flags = $data->getFlags();
        $this->_mbox = $mailbox;
    }

    /**
     * Returns the main text body of the message.
     *
     * @param array $options  An options array containgin:
     *  - truncation: (integer) Truncate message body to this length.
     *              DEFAULT: No truncation.
     *
     * @return array  An array with 'text' and 'charset' keys.
     */
    public function getMessageBody($options = array())
    {
        // Find and get the message body
        $id = $this->_message->findBody();
        if (is_null($id)) {
            return array('text' => '', 'charset' => 'UTF-8');
        }
        $body = $this->_message->getPart($id);
        $charset = $body->getCharset();

        $query = new Horde_Imap_Client_Fetch_Query();
        if (empty($this->_envelope)) {
            $query->envelope();
        }

        $body_query_opts = array(
            'decode' => true,
            'peek' => true
        );

        // Figure out if we need the body, and if so, how to truncate it.
        if (isset($options['truncation']) && $options['truncation'] > 0) {
            $body_query_opts['length'] = $options['truncation'];
        }
        if ((isset($options['truncation']) && $options['truncation'] > 0) ||
            !isset($options['truncation'])) {
            $query->bodyPart($id, $body_query_opts);
        }

        try {
            $fetch_ret = $this->_imap->fetch(
                $this->_mbox,
                $query,
                array('ids' => new Horde_Imap_Client_Ids(array($this->_uid)))
            );
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Exception($e);
        }
        $data = $fetch_ret[$this->_uid];

        // Save the envelope for later, if we asked for it.
        if (empty($this->_envelope)) {
            $this->_envelope = $data->getEnvelope();
        }

        // Get only the plaintext part
        $text = $data->getBodyPart($id);
        if (!$data->getBodyPartDecode($id)) {
            $body->setContents($data->getBodyPart($id));
            $text = $body->getContents();
        }

        return array('text' => $text, 'charset' => $charset);
    }

    /**
     * Return an array of Horde_ActiveSync_Message_Attachment objects for
     * the current message.
     *
     * @return array  An array of Horde_ActiveSync_Message_Attachment objects.
     */
    public function getAttachments()
    {
        $ret = array();
        $map = $this->_message->contentTypeMap();
        foreach ($map as $id => $type) {
            if ($this->isAttachment($type)) {
                $mime_part = $this->getMIMEPart($id, array('nocontents' => true));
                $atc = new Horde_ActiveSync_Message_Attachment();
                $atc->attsize = $mime_part->getBytes();
                $atc->attname = $this->_mbox . ':' . $this->_uid . ':' . $id;
                $atc->displayname = $this->getPartName($mime_part, true);
                $atc->attmethod = Horde_ActiveSync_Message_Attachment::ATT_TYPE_NORMAL;
                $atc->attoid = ''; // content-id header?
                $ret[] = $atc;
            }
        }

        return $ret;
    }

    /**
     * Fetch a part of a MIME message.
     *
     * @param integer $id     The MIME index of the part requested.
     * @param array $options  Additional options:
     *   - length: (integer) If set, only download this many bytes of the
     *             bodypart from the server.
     *             DEFAULT: All data is retrieved.
     *   - nocontents: (boolean) If true, don't add the contents to the part
     *                 DEFAULT: Contents are added to the part
     *
     * @return Horde_Mime_Part  The raw MIME part asked for (reference).
     */
    public function getMIMEPart($id, array $options = array())
    {
        $part = $this->_message->getPart($id);
        if ($part &&
            (strcasecmp($part->getCharset(), 'ISO-8859-1') === 0)) {
            $part->setCharset('windows-1252');
        }

        if (!empty($id) &&
            !is_null($part) &&
            substr($id, -2) != '.0' &&
            empty($options['nocontents']) &&
            !$part->getContents(array('stream' => true))) {

            $body = $this->getBodyPart(
                $id,
                array(
                    'decode' => true,
                    'length' => empty($options['length']) ? null : $options['length'],
                    'stream' => true)
            );

            $part->setContents($body, array('encoding' => $this->_lastBodyPartDecode, 'usestream' => true));
        }

        return $part;
    }

    /**
     * Return the descriptive part label, making sure it is not empty.
     *
     * @param Horde_Mime_Part $part  The MIME Part object.
     * @param boolean $use_descrip   Use description? If false, uses name.
     *
     * @return string  The part label (non-empty).
     */
    public function getPartName(Horde_Mime_Part $part, $use_descrip = false)
    {
        $name = $use_descrip
            ? $part->getDescription(true)
            : $part->getName(true);

        if ($name) {
            return $name;
        }

        switch ($ptype = $part->getPrimaryType()) {
        case 'multipart':
            if (($part->getSubType() == 'related') &&
                ($view_id = $part->getMetaData('viewable_part')) &&
                ($viewable = $this->getMIMEPart($view_id, array('nocontents' => true)))) {
                return $this->getPartName($viewable, $use_descrip);
            }
            /* Fall-through. */

        case 'application':
        case 'model':
            $ptype = $part->getSubType();
            break;
        }

        switch ($ptype) {
        case 'audio':
            return _("Audio part");

        case 'image':
            return _("Image part");

        case 'message':
        case Horde_Mime_Part::UNKNOWN:
            return _("Message part");

        case 'multipart':
            return _("Multipart part");

        case 'text':
            return _("Text part");

        case 'video':
            return _("Video part");

        default:
            // Attempt to translate this type, if possible. Odds are that
            // it won't appear in the dictionary though.
            return sprintf(_("%s part"), _(Horde_String::ucfirst($ptype)));
        }
    }

    /**
     * Gets the raw text for one section of the message.
     *
     * @param integer $id     The ID of the MIME part.
     * @param array $options  Additional options:
     *   - decode: (boolean) Attempt to decode the bodypart on the remote
     *             server. If successful, sets self::$_lastBodyPartDecode to
     *             the content-type of the decoded data.
     *             DEFAULT: No
     *   - length: (integer) If set, only download this many bytes of the
     *             bodypart from the server.
     *             DEFAULT: All data is retrieved.
     *   - mimeheaders: (boolean) Include the MIME headers also?
     *                  DEFAULT: No
     *   - stream: (boolean) If true, return a stream.
     *             DEFAULT: No
     *
     * @return mixed  The text of the part or a stream resource if 'stream'
     *                is true.
     */
    public function getBodyPart($id, $options)
    {
        $this->_lastBodyPartDecode = null;
        $query = new Horde_Imap_Client_Fetch_Query();
        if (!isset($options['length']) || !empty($options['length'])) {
            $bodypart_params = array(
                'decode' => true,
                'peek' => true
            );

            if (isset($options['length'])) {
                $bodypart_params['start'] = 0;
                $bodypart_params['length'] = $options['length'];
            }

            $query->bodyPart($id, $bodypart_params);
        }

        if (!empty($options['mimeheaders'])) {
            $query->mimeHeader($id, array(
                'peek' => true
            ));
        }

        $fetch_res = $this->_imap->fetch(
            $this->_mbox,
            $query,
            array('ids' => new Horde_Imap_Client_Ids(array($this->_uid)))
        );

        if (empty($options['mimeheaders'])) {
            $this->_lastBodyPartDecode = $fetch_res[$this->_uid]->getBodyPartDecode($id);
            return $fetch_res[$this->_uid]->getBodyPart($id);
        } elseif (empty($options['stream'])) {
            return $fetch_res[$this->_uid]->getMimeHeader($id) . $fetch_res[$this->_uid]->getBodyPart($id);
        } else {
            $swrapper = new Horde_Support_CombineSream(
                array(
                    $fetch_res[$this->_uid]->getMimeHeader($id, Horde_Imap_Client_Data_Fetch::HEADER_STREAM),
                    $fetch_res[$this->_uid]->getBodyPart($id, true))
            );

            return $swrapper->fopen();
        }
    }

    /**
     * Return the To addresses from this message.
     *
     * @return array  An array containing arrays of 'to' and 'displayto'
     *                addresses.
     */
    public function getToAddresses()
    {
        if (empty($this->_envelope)) {
            $this->_fetchEnvelope();
        }

        $to = $this->_envelope->to;
        $dtos = $tos = array();
        $rfc822 = new Horde_Mail_Rfc822();
        foreach ($to->addresses as $e) {
            $tos[] = $e->bare_address;
            $dtos[] = $e->personal;
        }

        return array('to' => $tos, 'displayto' => $dtos);
    }

    /**
     * Return the message's From: address.
     *
     * @return string  The From address of this message.
     */
    public function getFromAddress()
    {
        if (empty($this->_envelope)) {
            $this->_fetchEnvelope();
        }
        $from = array_pop($this->_envelope->from->addresses);
        $a = new Horde_Mail_Rfc822_Address($from);

        return $a->writeAddress(false);
    }

    /**
     * Return the message subject.
     *
     * @return string  The subject.
     */
    public function getSubject()
    {
        if (empty($this->_envelope)) {
            $this->_fetchEnvelope();
        }

        return $this->_envelope->subject;
    }

    /**
     * Return the message date.
     *
     * @return Horde_Date  The messages's envelope date.
     */
    public function getDate()
    {
        if (empty($this->_envelope)) {
            $this->_fetchEnvelope();
        }

        return new Horde_Date((string)$this->_envelope->date);
    }

    public function getFlag($flag)
    {
        return (array_search($flag, $this->_flags) !== false)
            ? 1
            : 0;
    }

    public function getMap()
    {
        $parts = $this->_message->contentTypeMap();
        foreach ($parts as $id => $type) {
            $part = $this->_message->getPart($id);
            if ($part &&
                (strcasecmp($part->getCharset(), 'ISO-8859-1') === 0)) {
                $part->setCharset('windows-1252');
            }

            var_dump($part->getBytes());
            //var_dump($part);
        }
    }

    /**
     * Determines if a MIME type is an attachment.
     * For IMP's purposes, an attachment is any MIME part that can be
     * downloaded by itself (i.e. all the data needed to view the part is
     * contained within the download data).
     *
     * @param string $mime_part  The MIME type.
     *
     * @return boolean  True if an attachment.
     */
    public function isAttachment($mime_type)
    {
        switch ($mime_type) {
        case 'text/plain':
        case 'application/ms-tnef':
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

    protected function _fetchEnvelope()
    {
        $query = new Horde_Imap_Client_Fetch_Query();
        $query->envelope();
        try {
            $fetch_ret = $this->_imap->fetch(
                $this->_mbox,
                $query,
                array('ids' => new Horde_Imap_Client_Ids(array($this->_uid)))
            );
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Exception($e);
        }
        $this->_envelope = $fetch_ret[$this->_uid]->getEnvelope();
    }
}