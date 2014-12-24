<?php
/**
 * Horde_ActiveSync_Imap_MessageBodyData::
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
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Imap_MessageBodyData
{

    /**
     *
     * @var Horde_ActiveSync_Imap_Adapter
     */
    protected $_imap;

    /**
     * @var Horde_ActiveSync_Mime
     */
    protected $_basePart;

    /**
     *
     * @var array
     */
    protected $_options;

    /**
     *
     * @var float
     */
    protected $_version;

    /**
     *
     * @var Horde_Imap_Client_Mailbox
     */
    protected $_mbox;

    /**
     *
     * @var integer
     */
    protected $_uid;

    /**
     *
     * @var string
     */
    protected $_plain;

    /**
     *
     * @var string
     */
    protected $_html;

    /**
     *
     * @var string
     */
    protected $_bodyPart;

    /**
     * Cached validated text/plain data.
     *
     * @var array
     */
    protected $_validatedPlain;

    /**
     * Cached validated text/html data
     *
     * @var array
     */
    protected $_validatedHtml;

    /**
     * Const'r
     *
     * @param array $params  Parameters:
     *     - imap: (Horde_Imap_Client_Base)     The IMAP client.
     *     - mbox: (Horde_Imap_Client_Mailbox)  The mailbox.
     *     - uid: (integer) The message UID.
     *     - mime: (Horde_ActiveSync_Mime)      The MIME object.
     *
     * @param array $options  The options array.
     */
    public function __construct(array $params, array $options)
    {
        stream_filter_register('horde_eol', 'Horde_Stream_Filter_Eol');

        $this->_imap = $params['imap'];
        $this->_basePart = $params['mime'];
        $this->_mbox = $params['mbox'];
        $this->_uid = $params['uid'];
        $this->_options = $options;

        $this->_version = empty($options['protocolversion']) ?
            Horde_ActiveSync::VERSION_TWOFIVE :
            $options['protocolversion'];

        $this->_getParts();
    }

    public function &__get($property)
    {
        switch ($property) {
        case 'plain':
            $body = $this->plainBody();
            return $body;
        case 'html':
            $body = $this->htmlBody();
            return $body;
        case 'bodyPart':
            $body = $this->bodyPartBody();
            return $body;
        default:
            throw new InvalidArgumentException("Unknown property: $property");
        }
    }

    public function __set($property, $value)
    {
        switch ($property) {
        case 'html':
            $this->_html = $value;
            break;
        default:
            throw new InvalidArgumentException("$property can not be set.");
        }
    }

    /**
     * Return the BODYTYPE to return to the client. Takes BODYPREF and available
     * parts into account.
     *
     * @param  boolean $save_bandwith  IF true, saves bandwidth usage by
     *                                 favoring HTML over MIME BODYTYPE if able.
     *
     * @return integer  A Horde_ActiveSync::BODYPREF_TYPE_* constant.
     */
    public function getBodyTypePreference($save_bandwidth = false)
    {
        // Apparently some clients don't send the MIME_SUPPORT field (thus
        // defaulting it to MIME_SUPPORT_NONE), but still request
        // BODYPREF_TYPE_MIME. Failure to do this results in NO data being
        // sent to the client, so we ignore the MIME_SUPPORT requirement and
        // assume it is implied if it is requested in a BODYPREF element.
        $bodyprefs = $this->_options['bodyprefs'];
        if ($save_bandwidth) {
            return !empty($bodyprefs[Horde_ActiveSync::BODYPREF_TYPE_HTML]) && !empty($this->_html)
                ? Horde_ActiveSync::BODYPREF_TYPE_HTML
                : (!empty($bodyprefs[Horde_ActiveSync::BODYPREF_TYPE_MIME])
                    ? Horde_ActiveSync::BODYPREF_TYPE_MIME
                    : Horde_ActiveSync::BODYPREF_TYPE_PLAIN);
        }

        // Prefer high bandwidth, full MIME.
        return !empty($bodyprefs[Horde_ActiveSync::BODYPREF_TYPE_MIME])
            ? Horde_ActiveSync::BODYPREF_TYPE_MIME
            : (!empty($bodyprefs[Horde_ActiveSync::BODYPREF_TYPE_HTML]) && !empty($this->_html)
                ? Horde_ActiveSync::BODYPREF_TYPE_HTML
                : Horde_ActiveSync::BODYPREF_TYPE_PLAIN);
    }

    /**
     * Determine which parts we need, and fetches them from the IMAP client.
     * Takes into account the available parts and the BODYPREF/BODYPARTPREF
     * options.
     */
    protected function _getParts()
    {
        // Look for the parts we need. We try to detect and fetch only the parts
        // we need, while ensuring we have something to return. So, e.g., if we
        // don't have BODYPREF_TYPE_HTML, we only request plain text, but if we
        // can't find plain text but we have a html body, fetch that anyway.
        $text_id = $this->_basePart->findBody('plain');
        $html_id = $this->_basePart->findBody('html');

        // Deduce which part(s) we need to request.
        $want_html_text = $this->_wantHtml();
        $want_plain_text = $this->_wantPlainText($html_id, $want_html_text);

        $want_html_as_plain = false;
        if (!empty($text_id) && $want_plain_text) {
            $text_body_part = $this->_basePart->getPart($text_id);
        } elseif ($want_plain_text && !empty($html_id) &&
                  empty($this->_options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_MIME])) {
            $want_html_text = true;
            $want_html_as_plain = true;
        }
        if (!empty($html_id) && $want_html_text) {
            $html_body_part = $this->_basePart->getPart($html_id);
        }

        // Make sure we have truncation if needed.
        if (empty($this->_options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_PLAIN]) &&
            !empty($this->_options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_HTML]) &&
            $want_plain_text && $want_html_text) {

            // We only have HTML truncation data, requested HTML body but only
            // have plaintext.
            $this->_options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_PLAIN] =
                $this->_options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_HTML];
        }

        // Fetch the data from the IMAP client.
        $data = $this->_fetchData(array('html_id' => $html_id, 'text_id' => $text_id));

        // @todo can we get the text_id from the body part?
        if (!empty($text_id) && $want_plain_text) {
            $this->_plain = $this->_getPlainPart($data, $text_body_part, $text_id);
        }

        if (!empty($html_id) && $want_html_text) {
            $results = $this->_getHtmlPart($data, $html_body_part, $html_id, $want_html_as_plain);
            if (!empty($results['html'])) {
                $this->_html = $results['html'];
            }
            if (!empty($results['plain'])) {
                $this->_plain = $results['plain'];
            }
        }

        if (!empty($this->_options['bodypartprefs'])) {
            $this->_bodyPart = $this->_getBodyPart(
                $data,
                !empty($html_id) ? $html_body_part : $text_body_part,
                !empty($html_id) ? $html_id : $text_id,
                empty($html_id)
            );
        }

    }

    /**
     * Return if we want HTML data.
     *
     * @return boolean  True if HTML data is needed.
     */
    protected function _wantHtml()
    {
        return $this->_version >= Horde_ActiveSync::VERSION_TWELVE &&
            (!empty($this->_options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_HTML]) ||
            !empty($this->_options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_MIME]) ||
            !empty($this->_options['bodypartprefs']));
    }

    /**
     * Return if we want plain text data.
     *
     * @param  string $html_id     The MIME id of any HTML part, if available.
     *                             Used to detect if we need to fetch the plain
     *                             part if we are requesting HTML, but only have
     *                             plain.
     * @param  boolean $want_html  True if the client wants HTML.
     *
     * @return boolean  True if plain data is needed.
     */
    protected function _wantPlainText($html_id, $want_html)
    {
        return $this->_version == Horde_ActiveSync::VERSION_TWOFIVE ||
            empty($this->_options['bodyprefs']) ||
            !empty($this->_options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_PLAIN]) ||
            !empty($this->_options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_RTF]) ||
            !empty($this->_options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_MIME]) ||
            ($want_html && empty($html_id));
    }

    /**
     * Fetch data from the IMAP client.
     *
     * @param  array $params  Parameter array.
     *     - html_id (string)  The MIME id of the HTML part, if any.
     *     - text_id (string)  The MIME id of the plain part, if any.
     *
     * @return Horde_Imap_Client_Data_Fetch  The results.
     */
    protected function _fetchData(array $params)
    {
        $query = new Horde_Imap_Client_Fetch_Query();
        $query_opts = array(
            'decode' => true,
            'peek' => true
        );

        // Get body information
        if ($this->_version >= Horde_ActiveSync::VERSION_TWELVE) {
            if (!empty($params['html_id'])) {
                $query->bodyPartSize($params['html_id']);
                $query->bodyPart($params['html_id'], $query_opts);
            }
            if (!empty($params['text_id'])) {
                $query->bodyPart($params['text_id'], $query_opts);
                $query->bodyPartSize($params['text_id']);
            }
        } else {
            // EAS 2.5 Plaintext body
            $query->bodyPart($params['text_id'], $query_opts);
            $query->bodyPartSize($params['text_id']);
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

        return $data;
    }

    /**
     * Build the data needed for the plain part.
     *
     * @param  Horde_Imap_Client_Data_Fetch $data  The FETCH results.
     * @param  Horde_Mime_Part $text_mime          The plaintext MIME part.
     * @param  string $text_id                     The MIME id for this part on
     *                                             the IMAP server.
     * @return array  The plain part data.
     *     - charset:  (string)   The charset of the text.
     *     - body: (string)       The body text.
     *     - truncated: (boolean) True if text was truncated.
     *     - size: (integer)      The original part size, in bytes.
     */
    protected function _getPlainPart(
        Horde_Imap_Client_Data_Fetch $data, Horde_Mime_Part $text_mime, $text_id)
    {
        $text = $data->getBodyPart($text_id);
        if (!$data->getBodyPartDecode($text_id)) {
            $text_mime->setContents($text);
            $text = $text_mime->getContents();
        }

        $text_size = !is_null($data->getBodyPartSize($text_id))
                ? $data->getBodyPartSize($text_id)
                : strlen($text);

        if (!empty($this->_options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_PLAIN]['truncationsize'])) {
            // EAS >= 12.0 truncation
            $text = Horde_String::substr(
                $text,
                0,
                $this->_options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_PLAIN]['truncationsize'],
                $text_mime->getCharset()
            );
        }

        $truncated = $text_size > strlen($text);
        if ($this->_version >= Horde_ActiveSync::VERSION_TWELVE &&
            $truncated && !empty($this->_options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_PLAIN]['allornone'])) {
            $text = '';
        }
        return array(
            'charset' => $text_mime->getCharset(),
            'body' => $text,
            'truncated' => $truncated,
            'size' => $text_size);
    }

    /**
     * Build the data needed for the html part.
     *
     * @param  Horde_Imap_Client_Data_Fetch $data             FETCH results.
     * @param  Horde_Mime_Part  $html_mime        text/html part.
     * @param  string           $html_id          MIME id.
     * @param  boolean          $convert_to_plain Convert text to plain text
     *                          also? If true, will also return a 'plain' array.
     *
     * @return array  An array containing 'html' and if $convert_to_true is set,
     *                a 'plain' part as well. @see self::_getPlainPart for
     *                structure of each entry.
     */
    protected function _getHtmlPart(
        Horde_Imap_Client_Data_Fetch $data, Horde_Mime_Part $html_mime, $html_id, $convert_to_plain)
    {
        $results = array();
        $html = $data->getBodyPart($html_id);
        if (!$data->getBodyPartDecode($html_id)) {
            $html_mime->setContents($html);
            $html = $html_mime->getContents();
        }
        $charset = $html_mime->getCharset();

        // Size of the original HTML part.
        $html_size = !is_null($data->getBodyPartSize($html_id))
            ? $data->getBodyPartSize($html_id)
            : strlen($html);

        if (!empty($this->_options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_HTML]['truncationsize'])) {
            $html = Horde_String::substr(
                $html,
                0,
                $this->_options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_HTML]['truncationsize'],
                $charset);
        }

        if ($convert_to_plain) {
            $html_plain = Horde_Text_Filter::filter(
                $html, 'Html2text', array('charset' => $charset));

            // Get the new size, since it probably changed.
            $html_plain_size = strlen($html_plain);
            if (!empty($this->_options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_PLAIN]['truncationsize'])) {
                // EAS >= 12.0 truncation
                $html_plain = Horde_String::substr(
                    $html_plain,
                    0,
                    $this->_options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_PLAIN]['truncationsize'],
                    $charset);
            }

            $results['plain'] = array(
                'charset' => $charset,
                'body' => $html_plain,
                'truncated' => $html_plain_size > strlen($html_plain),
                'size' => $html_plain_size
            );
        }

        $truncated = $html_size > strlen($html);
        if ($this->_version >= Horde_ActiveSync::VERSION_TWELVE &&
            !($truncated && !empty($this->_options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_HTML]['allornone']))) {
            $results['html'] = array(
                'charset' => $charset,
                'body' => $html,
                'estimated_size' => $html_size,
                'truncated' => $truncated);
        }

        return $results;
    }

    /**
     * Build the data needed for the BodyPart part.
     *
     * @param  Horde_Imap_Client_Data_Fetch $data  The FETCH results.
     * @param  Horde_Mime_Part $mime  The plaintext MIME part.
     * @param  string $id             The MIME id for this part on the IMAP
     *                                server.
     * @param boolean $to_html        If true, $id is assumed to be a text/plain
     *                                part and is converted to html.
     *
     * @return array  The BodyPart data.
     *     - charset:  (string)   The charset of the text.
     *     - body: (string)       The body text.
     *     - truncated: (boolean) True if text was truncated.
     *     - size: (integer)      The original part size, in bytes.
     */
    protected function _getBodyPart(
        Horde_Imap_Client_Data_Fetch $data, Horde_Mime_Part $mime, $id, $to_html)
    {
        $text = $data->getBodyPart($id);
        if (!$data->getBodyPartDecode($id)) {
            $mime->setContents($text);
            $text = $mime->getContents();
        }

        if ($to_html) {
            $text = Horde_Text_Filter::filter(
                $text, 'Text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO, 'charset' => $mime->getCharset()));
            $size = strlen($text);
        } else {
            $size = !is_null($data->getBodyPartSize($id))
                ? $data->getBodyPartSize($id)
                : strlen($text);
        }

        if (!empty($this->_options['bodypartprefs']['truncationsize'])) {
            $text = Horde_String::substr(
                $text,
                0,
                $this->_options['bodypartprefs']['truncationsize'],
                $mime->getCharset());
        }

        return array(
            'charset' => $mime->getCharset(),
            'body' => $text,
            'truncated' => $size > strlen($text),
            'size' => $size
        );
    }

    /**
     * Return the validated text/plain body data.
     *
     * @return array The validated body data array:
     *     - charset:  (string)   The charset of the text.
     *     - body: (Horde_Stream) The body text in a stream.
     *     - truncated: (boolean) True if text was truncated.
     *     - size: (integer)      The original part size, in bytes.
     */
    public function plainBody()
    {
        if (!empty($this->_plain) && empty($this->_validatedPlain)) {
            $this->_validatedPlain = $this->_validateBodyData($this->_plain);
        }
        if ($this->_validatedPlain) {
            return $this->_validatedPlain;
        }

        return false;
    }

    /**
     * Return the validated text/html body data.
     *
     * @return array The validated body data array:
     *     - charset:  (string)   The charset of the text.
     *     - body: (Horde_Stream) The body text in a stream.
     *     - truncated: (boolean) True if text was truncated.
     *     - size: (integer)      The original part size, in bytes.
     */
    public function htmlBody()
    {
        if (!empty($this->_html) && empty($this->_validatedHtml)) {
            $this->_validatedHtml = $this->_validateBodyData($this->_html);
        }
        if ($this->_validatedHtml) {
            return $this->_validatedHtml;
        }

        return false;
    }

    /**
     * Return the validated BODYPART data.
     *
     * @return array The validated body data array:
     *     - charset:  (string)   The charset of the text.
     *     - body: (Horde_Stream) The body text in a stream.
     *     - truncated: (boolean) True if text was truncated.
     *     - size: (integer)      The original part size, in bytes.
     */
    public function bodyPartBody()
    {
        if (!empty($this->_bodyPart)) {
            return $this->_validateBodyData($this->_bodyPart);
        }

        return false;
    }

    /**
     * Validate the body data to ensure consistent EOL and UTF8 data. Returns
     * body data in a stream object.
     *
     * @param array $data  The body data. @see self::_bodyPartText() for
     *                     structure.
     *
     * @return array  The validated body data array. @see self::_bodyPartText()
     */
    protected function _validateBodyData($data)
    {
        $stream = new Horde_Stream_Temp(array('max_memory' => 1048576));
        $filter_h = stream_filter_append($stream->stream, 'horde_eol', STREAM_FILTER_WRITE);
        $stream->add(Horde_ActiveSync_Utils::ensureUtf8($data['body'], $data['charset']), true);
        stream_filter_remove($filter_h);

        $data['body'] = $stream;

        return $data;
    }

    /**
     * Return the body data in array format. Needed for BC.
     *
     * @return array
     * @todo remove in H6.
     */
    public function toArray()
    {
        $result = array();
        if ($this->plain) {
            $result['plain'] = $this->_plain;
        }
        if ($this->html) {
            $result['html'] = $this->_html;
        }

        return $result;
    }

}