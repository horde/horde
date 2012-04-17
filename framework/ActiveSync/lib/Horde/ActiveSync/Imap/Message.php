<?php
/**
 * Horde_ActiveSync_Imap_Message::
 *
 * PHP 5
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2010-2012 Horde LLC (http://www.horde.org/)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @link      http://pear.horde.org/index.php?package=ActiveSync
 * @package   ActiveSync
 */
/**
 * This class provides all functionality related to parsing and working with
 * a single IMAP email message when using Horde_Imap_Client.
 *
 * Some Mime parsing code taken from Imp_Contents.
 *
 * @copyright 2012 Horde LLC (http://www.horde.org/)
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package  ActiveSync
 */
class Horde_ActiveSync_Imap_Message
{
    /**
     * Message data
     *
     * @var Horde_Imap_Client_Fetch_Data
     */
    protected $_data;

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
     * Constructor
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
        $this->_data = $data;
    }

    /**
     * Return this message's base part headers.
     *
     * @return Horde_Mime_Header  The message headers.
     */
    public function getHeaders()
    {
        return $this->_data->getHeaderText(0, Horde_Imap_Client_Data_Fetch::HEADER_PARSE);
    }

    /**
     * Return nicely formatted text representing the headers to display with
     * in-line forwarded messages.
     *
     * @return string
     */
    public function getForwardHeaders()
    {
        $tmp = array();
        $h = $this->getHeaders();

        if (($ob = $h->getValue('date'))) {
            $tmp[_("Date")] = $ob;
        }

        if (($ob = strval($h->getOb('from')))) {
            $tmp[_("From")] = $ob;
        }

        if (($ob = strval($h->getOb('reply-to')))) {
            $tmp[_("Reply-To")] = $ob;
        }

        if (($ob = $h->getValue('subject'))) {
            $tmp[_("Subject")] = $ob;
        }

        if (($ob = strval($h->getOb('to')))) {
            $tmp[_("To")] = $ob;
        }

        if (($ob = strval($h->getOb('cc')))) {
            $tmp[_("Cc")] = $ob;
        }

        $max = max(array_map(array('Horde_String', 'length'), array_keys($tmp))) + 2;
        $text = '';

        foreach ($tmp as $key => $val) {
            $text .= Horde_String::pad($key . ': ', $max, ' ', STR_PAD_LEFT) . $val . "\n";
        }

        return $text;
    }

    /**
     * Return the full message text.
     *
     * @return mixed  A string or stream resource.
     */
    public function getFullMsg()
    {
        // First see if we already have it.
        if (!$full = $this->_data->getFullMsg()) {
            $query = new Horde_Imap_Client_Fetch_Query();
            $query->fullText();
            $fetch_ret = $this->_imap->fetch(
                $this->_mbox,
                $query,
                array('ids' => new Horde_Imap_Client_Ids(array($this->_uid)))
            );

            $data = array_pop($fetch_ret);
            $full = $data->getFullMsg(true);
        }

        return $full;
    }

    /**
     * Return the message's base Mime part.
     *
     * @return Horde_Mime_Part
     */
    public function getStructure()
    {
        return $this->_message;
    }

    /**
     * Returns the main text body of the message suitable for sending over
     * EAS response.
     *
     * @param array $options  An options array containgin:
     *  - truncation: (integer) Truncate message body to this length.
     *                DEFAULT: none (No truncation).
     *
     *  - bodyprefs: (array)  Bodypref settings
     *               DEFAULT: none (No bodyprefs used).
     *
     *  - mimesupport: (integer)  MIME supported (1) or not (0)
     *                 DEFAULT: none (No MIME support).
     *
     *  - protocolversion: (float)  The EAS protocol we are supporting.
     *                     DEFAULT 2.5
     *
     * @return array  An array of 'plain' and 'html' content.
     */
    public function getMessageBodyData($options = array())
    {
        $version = empty($options['protocolversion']) ? 2.5 : $options['protocolversion'];

        // Find and get the message body parts we will need.
        if ($version >= Horde_ActiveSync::VERSION_TWELVE && !empty($options['bodyprefs'])) {
            $html_id = $this->_message->findBody('html');
            if (!empty($html_id)) {
                $html_body_part = $this->_message->getPart($html_id);
                $html_charset = $html_body_part->getCharset();
            }
        }

        $text_id = $this->_message->findBody();
        if (!empty($text_id)) {
            $text_body_part = $this->_message->getPart($text_id);
            $charset = $text_body_part->getCharset();
        } else {
            $text_body_part = null;
        }

        $query = new Horde_Imap_Client_Fetch_Query();
        if (empty($this->_envelope)) {
            $query->envelope();
        }

        $body_query_opts = array(
            'decode' => true,
            'peek' => true
        );

        // Get HTML body information
        // @TODO: AllorNone
        if ($version >= Horde_ActiveSync::VERSION_TWELVE) {
            $html_query_opts = $body_query_opts;
            if (isset($options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_HTML]['truncationsize'])) {
                $html_query_opts['length'] = $options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_HTML]['truncationsize'];
                $query->bodyPartSize($html_id);
            }
            if (!empty($html_id)) {
                $query->bodyPart($html_id, $html_query_opts);
            } elseif (!empty($text_id)) {
                $query->bodyPart($text_id, $body_query_opts);
                $query->bodyPartSize($text_id);
            }
        } else {
            // Plaintext body
            if (isset($options['truncation']) && $options['truncation'] > 0) {
                $body_query_opts['length'] = $options['truncation'];
            }
            if ((isset($options['truncation']) && $options['truncation'] > 0) ||
                !isset($options['truncation'])) {
                $query->bodyPart($text_id, $body_query_opts);
            }
            $query->bodyPartSize($text_id);
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

        if (!empty($text_id)) {
            $text = $data->getBodyPart($text_id);
            if (!$data->getBodyPartDecode($text_id)) {
                $text_body_part->setContents($data->getBodyPart($text_id));
                $text = $text_body_part->getContents();
            }
            $text_size = $data->getBodyPartSize($text_id);
            $return = array('plain' => array(
                'charset' => $charset,
                'body' => $text,
                'truncated' => $text_size > strlen($text),
                'size' => $text_size));
        }
        if (!empty($html_id)) {
            $html_body_part->setContents($data->getBodyPart($html_id));
            $html = $html_body_part->getContents();
            $html_size = !empty($html_query_opts['length']) ? $data->getBodyPartSize($html_id) : strlen($html);
            $return['html'] = array(
                'charset' => $html_charset,
                'body' => $html,
                'estimated_size' => $size,
                'truncated' => $size > strlen($html));
        }

        return $return;
    }

    /**
     * Return an array of Horde_ActiveSync_Message_Attachment objects for
     * the current message.
     *
     * @param float $version  The EAS protocol version this is for.
     *
     * @return array  An array of Horde_ActiveSync_Message_Attachment objects.
     */
    public function getAttachments($version)
    {
        $ret = array();
        $map = $this->_message->contentTypeMap();
        $headers = $this->getHeaders();
        foreach ($map as $id => $type) {
            if ($this->isAttachment($type)) {
                $mime_part = $this->getMimePart($id, array('nocontents' => true));
                if ($version > Horde_ActiveSync::VERSION_TWOFIVE) {
                    $atc = new Horde_ActiveSync_Message_AirSyncBaseAttachment();
                } else {
                    $atc = new Horde_ActiveSync_Message_Attachment();
                    $atc->attoid = $headers->getValue('content-id');
                }
                $atc->attsize = $mime_part->getBytes();
                $atc->attname = $this->_mbox . ':' . $this->_uid . ':' . $id;
                $atc->displayname = $this->getPartName($mime_part, true);
                $atc->attmethod = Horde_ActiveSync_Message_Attachment::ATT_TYPE_NORMAL;
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
    public function getMimePart($id, array $options = array())
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
                ($viewable = $this->getMimePart($view_id, array('nocontents' => true)))) {
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
        foreach ($to->raw_addresses as $e) {
            $tos[] = $e->bare_address;
            $dtos[] = $e->label;
        }

        return array('to' => $tos, 'displayto' => $dtos);
    }

    public function getCc()
    {
        if (empty($this->_envelope)) {
            $this->_fetchEnvelope();
        }
        $cc = array_pop($this->_envelope->cc->address);
        $a = new Horde_Mail_Rfc822_Address($cc);

        return $a->writeAddress(false);
    }

    public function getReplyTo()
    {
        if (empty($this->_envelope)) {
            $this->_fetchEnvelope();
        }
        $r = array_pop($this->_envelope->reply_to->address);
        $a = new Horde_Mail_Rfc822_Address($r);

        return $a->writeAddress(false);
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

    /**
     * Get a message flag
     *
     * @param string $flag  The flag to search for.
     *
     * @return boolean
     */
    public function getFlag($flag)
    {
        return (array_search($flag, $this->_flags) !== false)
            ? 1
            : 0;
    }

    /**
     * Return this message's content map
     *
     * @return array  The content map, with mime ids as keys and content type
     *                as values.
     */
    public function contentTypeMap()
    {
        return $this->_message->contentTypeMap();
    }

    /**
     * Determines if a MIME type is an attachment.
     * For our purposes, an attachment is any MIME part that can be
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
        case 'text/html':
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
     * Ensure that the envelope is available.
     */
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