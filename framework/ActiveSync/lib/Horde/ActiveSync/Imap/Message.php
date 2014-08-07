<?php
/**
 * Horde_ActiveSync_Imap_Message::
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
 * This class provides all functionality related to parsing and working with
 * a single IMAP email message when using Horde_Imap_Client.
 *
 * Some Mime parsing code taken from Imp_Contents.
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
class Horde_ActiveSync_Imap_Message
{
    /**
     * Message data.
     *
     * @var Horde_Imap_Client_Fetch_Data
     */
    protected $_data;

    /**
     * Message structure.
     *
     * @var Horde_Mime_Part
     */
    protected $_message;

    /**
     * The imap client.
     *
     * @var Horde_Imap_Client_Base
     */
    protected $_imap;

    /**
     * The envelope data.
     *
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
     * Flag to indicate if this message contains attachments.
     *
     * @var boolean
     */
    protected $_hasAttachments = null;

    /**
     * Constructor
     *
     * @param Horde_Imap_Client_Base $imap        The imap client object.
     * @param Horde_Imap_Client_Mailbox $mbox     The mailbox object.
     * @param Horde_Imap_Client_Data_Fetch $data  The data returned from a FETCH
     *                                            must contain at least uid,
     *                                            structure and flags.
     */
    public function __construct(
        Horde_Imap_Client_Base $imap,
        Horde_Imap_Client_Mailbox $mbox,
        Horde_Imap_Client_Data_Fetch $data)
    {
        $this->_imap = $imap;
        $this->_message = $data->getStructure();
        $this->_uid = $data->getUid();
        $this->_flags = $data->getFlags();
        $this->_mbox = $mbox;
        $this->_data = $data;
        $this->_envelope = $data->getEnvelope();

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
            $tmp[Horde_ActiveSync_Translation::t('Date')] = $ob;
        }

        if (($ob = strval($h->getOb('from')))) {
            $tmp[Horde_ActiveSync_Translation::t('From')] = $ob;
        }

        if (($ob = strval($h->getOb('reply-to')))) {
            $tmp[Horde_ActiveSync_Translation::t('Reply-To')] = $ob;
        }

        if (($ob = $h->getValue('subject'))) {
            $tmp[Horde_ActiveSync_Translation::t('Subject')] = $ob;
        }

        if (($ob = strval($h->getOb('to')))) {
            $tmp[Horde_ActiveSync_Translation::t('To')] = $ob;
        }

        if (($ob = strval($h->getOb('cc')))) {
            $tmp[Horde_ActiveSync_Translation::t('Cc')] = $ob;
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
     * @param boolean $stream  Return data as a stream?
     *
     * @return mixed  A string or stream resource.
     * @throws Horde_ActiveSync_Exception
     */
    public function getFullMsg($stream = false)
    {
        // First see if we already have it.
        if (!$full = $this->_data->getFullMsg()) {
            $query = new Horde_Imap_Client_Fetch_Query();
            $query->fullText(array('peek' => true));
            try {
                $fetch_ret = $this->_imap->fetch(
                    $this->_mbox,
                    $query,
                    array('ids' => new Horde_Imap_Client_Ids(array($this->_uid)))
                );
            } catch (Horde_Imap_Exception $e) {
                throw new Horde_ActiveSync_Exception($e);
            }
            $data = $fetch_ret[$this->_uid];
            $full = $data->getFullMsg($stream);
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
     *  - bodyprefs: (array)  Bodypref settings
     *               DEFAULT: none (No bodyprefs used).
     *  - mimesupport: (integer)  Indicates if MIME is supported or not.
     *                  Possible values: 0 - Not supported 1 - Only S/MIME or
     *                  2 - All MIME.
     *                  DEFAULT: 0 (No MIME support)
     *  - protocolversion: (float)  The EAS protocol we are supporting.
     *                     DEFAULT 2.5
     *
     * @return array  An array of one or both of 'plain' and 'html' content.
     *
     * @throws Horde_ActiveSync_Exception, Horde_Exception_NotFound
     */
    public function getMessageBodyData(array $options = array())
    {
        $version = empty($options['protocolversion']) ?
            Horde_ActiveSync::VERSION_TWOFIVE :
            $options['protocolversion'];

        // Look for the parts we need. We try to detect and fetch only the parts
        // we need, while ensuring we have something to return. So, e.g., if we
        // don't have BODYPREF_TYPE_HTML, we only request plain text, but if we
        // can't find plain text but we have a html body, fetch that anyway.
        $text_id = $this->_message->findBody('plain');
        $html_id = $this->_message->findBody('html');

        // Deduce which part(s) we need to request.
        $want_html_text = $version >= Horde_ActiveSync::VERSION_TWELVE &&
            (!empty($options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_HTML]) ||
            !empty($options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_MIME]));

        $want_plain_text = $version == Horde_ActiveSync::VERSION_TWOFIVE ||
            empty($options['bodyprefs']) ||
            !empty($options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_PLAIN]) ||
            !empty($options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_RTF]) ||
            !empty($options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_MIME]) ||
            ($want_html_text && empty($html_id));

        $want_html_as_plain = false;
        if (!empty($text_id) && $want_plain_text) {
            $text_body_part = $this->_message->getPart($text_id);
            $charset = $text_body_part->getCharset();
        } elseif ($want_plain_text && !empty($html_id) &&
                  empty($options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_MIME])) {
            $want_html_text = true;
            $want_html_as_plain = true;
        }
        if (!empty($html_id) && $want_html_text) {
            $html_body_part = $this->_message->getPart($html_id);
            $html_charset = $html_body_part->getCharset();
        }

        // Sanity check the truncation stuff
        if (empty($options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_PLAIN]) &&
            !empty($options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_HTML]) &&
            $want_plain_text && $want_html_text) {

            // We only have HTML truncation data, requested HTML body but only
            // have plaintext.
            $options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_PLAIN] = $options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_HTML];
        }

        $query = new Horde_Imap_Client_Fetch_Query();
        $query_opts = array(
            'decode' => true,
            'peek' => true
        );

        // Get body information
        if ($version >= Horde_ActiveSync::VERSION_TWELVE) {
            if (!empty($html_id)) {
                $query->bodyPartSize($html_id);
                $query->bodyPart($html_id, $query_opts);
            }
            if (!empty($text_id)) {
                $query->bodyPart($text_id, $query_opts);
                $query->bodyPartSize($text_id);
            }
        } else {
            // EAS 2.5 Plaintext body
            $query->bodyPart($text_id, $query_opts);
            $query->bodyPartSize($text_id);
        }
        try {
            $fetch_ret = $this->_imap->fetch(
                $this->_mbox,
                $query,
                array('ids' => new Horde_Imap_Client_Ids(array($this->_uid)))
            );
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
        if (!$data = $fetch_ret->first()) {
            throw new Horde_Exception_NotFound(
                sprintf('Could not load message %s from server.', $this->_uid));
        }

        $return = array();
        if (!empty($text_id) && $want_plain_text) {
            $text = $data->getBodyPart($text_id);
            if (!$data->getBodyPartDecode($text_id)) {
                $text_body_part->setContents($text);
                $text = $text_body_part->getContents();
            }

            $text_size = !is_null($data->getBodyPartSize($text_id))
                    ? $data->getBodyPartSize($text_id)
                    : Horde_String::length($text);

            if (!empty($options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_PLAIN]['truncationsize'])) {
                // EAS >= 12.0 truncation
                $text = Horde_String::substr($text, 0, $options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_PLAIN]['truncationsize'], $charset);
            }

            $truncated = $text_size > Horde_String::length($text);
            if ($version >= Horde_ActiveSync::VERSION_TWELVE &&
                $truncated && !empty($options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_PLAIN]['allornone'])) {
                $text = '';
            }
            $return ['plain'] = array(
                'charset' => $charset,
                'body' => $text,
                'truncated' => $truncated,
                'size' => $text_size);
        }

        if (!empty($html_id) && $want_html_text) {
            $html = $data->getBodyPart($html_id);
            if (!$data->getBodyPartDecode($html_id)) {
                $html_body_part->setContents($html);
                $html = $html_body_part->getContents();
            }

            // Size of the original HTML part.
            $html_size = !is_null($data->getBodyPartSize($html_id))
                ? $data->getBodyPartSize($html_id)
                : Horde_String::length($html);

            if (!empty($options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_HTML]['truncationsize'])) {
                $html = Horde_String::substr(
                    $html,
                    0,
                    $options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_HTML]['truncationsize'],
                    $html_charset);
            } elseif ($want_html_as_plain) {
                $html = Horde_Text_Filter::filter(
                    $html, 'Html2text', array('charset' => $html_charset));

                // Get the new size, since it probably changed.
                $html_size = Horde_String::length($html);
                if (!empty($options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_PLAIN]['truncationsize'])) {
                    // EAS >= 12.0 truncation
                    $html = Horde_String::substr(
                        $html,
                        0,
                        $options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_PLAIN]['truncationsize'],
                        $html_charset);
                }
            }

            // Was the part truncated?
            $truncated = $html_size > Horde_String::length($html);

            if ($want_html_as_plain) {
                $return['plain'] = array(
                    'charset' => $html_charset,
                    'body' => $html,
                    'truncated' => $truncated,
                    'size' => $html_size
                );
            }

            if ($version >= Horde_ActiveSync::VERSION_TWELVE &&
                !($truncated && !empty($options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_HTML]['allornone']))) {
                $return['html'] = array(
                    'charset' => $html_charset,
                    'body' => $html,
                    'estimated_size' => $html_size,
                    'truncated' => $truncated);
            }
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
        foreach ($map as $id => $type) {
            if ($this->isAttachment($id, $type)) {
                if ($type != 'application/ms-tnef' || (!$mime_part = $this->_decodeTnefData($id))) {
                    $mime_part = $this->getMimePart($id, array('nocontents' => true));
                }
                $ret[] = $this->_buildEasAttachmentFromMime($id, $mime_part, $version);
            }
        }

        return $ret;
    }

    /**
     * Build an appropriate attachment object from the given mime part.
     *
     * @param integer $id                  The mime id for the part
     * @param Horde_Mime_Part  $mime_part  The mime part.
     * @param float $version               The EAS version.
     *
     * @return Horde_ActiveSync_Message_AirSyncBaseAttachment |
     *         Horde_ActiveSync_Message_Attachment
     */
    protected function _buildEasAttachmentFromMime($id, Horde_Mime_Part $mime_part, $version)
    {
        if ($version > Horde_ActiveSync::VERSION_TWOFIVE) {
            $atc = Horde_ActiveSync::messageFactory('AirSyncBaseAttachment');
            $atc->contentid = $mime_part->getContentId();
            $atc->isinline = $mime_part->getDisposition() == 'inline';
        } else {
            $atc = Horde_ActiveSync::messageFactory('Attachment');
            $atc->attoid = $mime_part->getContentId();
        }
        $atc->attsize = intval($mime_part->getBytes(true));
        $atc->attname = $this->_mbox . ':' . $this->_uid . ':' . $id;
        $atc->displayname = Horde_String::convertCharset(
            $this->getPartName($mime_part, true),
            $this->_message->getHeaderCharset(),
            'UTF-8',
            true);
        $atc->attmethod = in_array($mime_part->getType(), array('message/rfc822', 'message/disposition-notification'))
            ? Horde_ActiveSync_Message_AirSyncBaseAttachment::ATT_TYPE_EMBEDDED
            : Horde_ActiveSync_Message_AirSyncBaseAttachment::ATT_TYPE_NORMAL;

        return $atc;
    }

    /**
     * Convert a TNEF attachment into a multipart/mixed part.
     *
     * @param  integer|Horde_Mime_part $data  Either a mime part id or a
     *                                        Horde_Mime_Part object containing
     *                                        the TNEF attachment.
     *
     * @return Horde_Mime_Part  The multipart/mixed MIME part containing any
     *                          attachment data we can decode.
     */
    protected function _decodeTnefData($data)
    {
        $wrapper = new Horde_Mime_Part();
        $wrapper->setType('multipart/mixed');

        if (!($data instanceof Horde_Mime_Part)) {
            $mime_part = $this->getMimePart($data);
        } else {
            $mime_part = $data;
        }
        $tnef_parser = Horde_Compress::factory('Tnef');
        try {
            $tnef_data = $tnef_parser->decompress($mime_part->getContents());
        } catch (Horde_Compress_Exception $e) {
            return false;
        }
        if (!count($tnef_data)) {
            return false;
        }

        reset($tnef_data);
        while (list(,$data) = each($tnef_data)) {
            $tmp_part = new Horde_Mime_Part();
            $tmp_part->setName($data['name']);
            $tmp_part->setDescription($data['name']);
            $tmp_part->setContents($data['stream']);

            $type = $data['type'] . '/' . $data['subtype'];
            if (in_array($type, array('application/octet-stream', 'application/base64'))) {
                $type = Horde_Mime_Magic::filenameToMIME($data['name']);
            }
            $tmp_part->setType($type);
            $wrapper->addPart($tmp_part);
        }

        return $wrapper;
    }

    /**
     * Return an array of mime parts for each message attachment.
     *
     * @return array An array of Horde_Mime_Part objects.
     */
    public function getAttachmentsMimeParts()
    {
        $mime_parts = array();
        $map = $this->_message->contentTypeMap();
        foreach ($map as $id => $type) {
            if ($this->isAttachment($id, $type)) {
                $mpart = $this->getMimePart($id);
                // Work around PHP bug 65776
                if ($mpart->getPrimaryType() == 'text') {
                    $mpart->setContents(
                        $mpart->replaceEOL(
                            $mpart->getContents(array('stream' => true)), Horde_Mime_Part::RFC_EOL, true)
                    );
                }
                if ($mpart->getType() == 'text/calendar') {
                    $mpart->setDisposition('inline');
                }
                if ($mpart->getType() != 'application/ms-tnef' ||
                    ($mpart->getType() == 'application/ms-tnef' && !$part = $this->_decodeTnefData($mpart))) {
                    $part = $mpart;
                }
                $mime_parts[] = $part;
            }
        }

        return $mime_parts;
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
            return Horde_ActiveSync_Translation::t('Audio part');

        case 'image':
            return Horde_ActiveSync_Translation::t('Image part');

        case 'message':
        case Horde_Mime_Part::UNKNOWN:
            return Horde_ActiveSync_Translation::t('Message part');

        case 'multipart':
            return Horde_ActiveSync_Translation::t('Multipart part');

        case 'text':
            return Horde_ActiveSync_Translation::t('Text part');

        case 'video':
            return Horde_ActiveSync_Translation::t('Video part');

        default:
            // Attempt to translate this type, if possible. Odds are that
            // it won't appear in the dictionary though.
            return sprintf(Horde_ActiveSync_Translation::t('%s part'), _(Horde_String::ucfirst($ptype)));
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
        $options = array_merge(
            array(
                'decode' => false,
                'mimeheaders' => false,
                'stream' => false),
            $options);
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
            return $fetch_res[$this->_uid]->getBodyPart($id, $options['stream']);
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
        $to = $this->_envelope->to;
        $dtos = $tos = array();
        foreach ($to->raw_addresses as $e) {
            $tos[] = $e->bare_address;
            $dtos[] = $e->label;
        }

        return array('to' => $tos, 'displayto' => $dtos);
    }

    /**
     * Return the CC addresses for this message.
     *
     * @return string  The Cc address string.
     */
    public function getCc()
    {
        $cc = new Horde_Mail_Rfc822_List($this->_envelope->cc->addresses);
        return $cc->writeAddress();
    }

    /**
     * Return the ReplyTo Address
     *
     * @return string
     */
    public function getReplyTo()
    {
        $r = $this->_envelope->reply_to->addresses;
        $a = new Horde_Mail_Rfc822_Address(current($r));

        return $a->writeAddress(false);
    }

    /**
     * Return the message's From: address.
     *
     * @return string  The From address of this message.
     */
    public function getFromAddress()
    {
        $from = $this->_envelope->from->addresses;
        $a = new Horde_Mail_Rfc822_Address(current($from));

        return $a->writeAddress(false);
    }

    /**
     * Return the message subject.
     *
     * @return string  The subject.
     */
    public function getSubject()
    {
        return $this->_envelope->subject;
    }

    /**
     * Return the message date.
     *
     * @return Horde_Date  The messages's envelope date.
     */
    public function getDate()
    {
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
     * Return all message flags.
     *
     * @return array  An array of message flags.
     * @since 2.17.0
     */
    public function getFlags()
    {
        return $this->_flags;
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
     * @param string $id         The MIME Id for the part we are checking.
     * @param string $mime_type  The MIME type.
     *
     * @return boolean  True if an attachment.
     */
    public function isAttachment($id, $mime_type)
    {
        switch ($mime_type) {
        case 'text/plain':
            if (!($this->_message->findBody('plain') == $id)) {
                return true;
            }
            return false;
        case 'text/html':
            if (!($this->_message->findBody('html') == $id)) {
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
        foreach ($this->contentTypeMap() as $id => $type) {
            if ($type == 'text/calendar') {
                return $this->getMimePart($id);
            }
        }

        return false;
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

        foreach ($this->contentTypeMap() as $id => $type) {
            if ($this->isAttachment($id, $type)) {
                $this->_hasAttachments = true;
                return true;
            }
        }

        $this->_hasAttachments = false;
        return false;
    }

    /**
     * Return the S/MIME status of this message (RFC2633)
     *
     * @param Horde_Mime_Part $message  A mime part to check. If omitted, use
     *                                  $this->_message.
     *
     * @return boolean True if message is S/MIME signed or encrypted,
     *                 false otherwise.
     */
    public function isSigned($message = null)
    {
        if (empty($message)) {
            $message = $this->_message;
        }
        if ($message->getType() == 'application/pkcs7-mime' ||
            $message->getType() == 'application/x-pkcs7-mime') {
            return true;
        }

        if ($message->getPrimaryType() == 'multipart') {
            if ($message->getSubType() == 'signed') {
                return true;
            }

            // Signed/encrypted part might be lower in the mime structure
            foreach ($message->getParts() as $part) {
                if ($this->isSigned($part)) {
                    return true;
                }
            }
        }

        return false;
    }

}
