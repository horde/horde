<?php
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Contains all functions related to handling the content and output of mail
 * messages in IMP.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Contents
{
    /* Mask entries for getSummary(). */
    const SUMMARY_BYTES = 1;
    const SUMMARY_SIZE = 2;
    const SUMMARY_ICON = 4;
    const SUMMARY_ICON_RAW = 16384;
    const SUMMARY_DESCRIP = 8;
    const SUMMARY_DESCRIP_LINK = 16;
    const SUMMARY_DOWNLOAD = 32;
    const SUMMARY_IMAGE_SAVE = 64;
    const SUMMARY_PRINT = 128;
    const SUMMARY_PRINT_STUB = 256;
    const SUMMARY_STRIP = 512;

    /* Rendering mask entries. */
    const RENDER_FULL = 1;
    const RENDER_INLINE = 2;
    const RENDER_INLINE_DISP_NO = 4;
    const RENDER_INFO = 8;
    const RENDER_INLINE_AUTO = 16;
    const RENDER_RAW = 32;
    const RENDER_RAW_FALLBACK = 64;

    /* Header return type for getHeader(). */
    const HEADER_OB = 1;
    const HEADER_TEXT = 2;
    const HEADER_STREAM = 3;

    /**
     * Have we scanned for embedded parts?
     *
     * @var boolean
     */
    protected $_build = false;

    /**
     * The list of MIME IDs that consist of embedded data.
     *
     * @var array
     */
    protected $_embedded = array();

    /**
     * Message header.
     *
     * @var mixed
     */
    protected $_header;

    /**
     * The index of the current message.
     *
     * @var IMP_Indices_Mailbox
     */
    protected $_indices;

    /**
     * The Horde_Mime_Part object for the message.
     *
     * @var Horde_Mime_Part
     */
    protected $_message;

    /**
     * Cached data for the MIME Viewer objects.
     *
     * @var object
     */
    protected $_viewcache;

    /**
     * Constructor.
     *
     * @param mixed $in  An IMP_Indices_Mailbox or Horde_Mime_Part object.
     *
     * @throws IMP_Exception
     */
    public function __construct($in)
    {
        if ($in instanceof Horde_Mime_Part) {
            $this->_message = $in;
        } else {
            $this->_indices = $in;

            /* Get the Horde_Mime_Part object for the given UID. */
            $query = new Horde_Imap_Client_Fetch_Query();
            $query->structure();

            if (!($ret = $this->_fetchData($query))) {
                $e = new IMP_Exception(_("Error displaying message: message does not exist on server."));
                $e->setLogLevel('NOTICE');
                throw $e;
            }

            $this->_message = $ret->getStructure();
        }
    }

    /**
     * String representation of object.
     *
     * @return string  The indices string.
     */
    public function __toString()
    {
        return strval($this->getIndicesOb());
    }

    /**
     * Returns the IMAP UID for the current message.
     *
     * @return integer  The message UID.
     */
    public function getUid()
    {
        list(,$uid) = $this->_indices->getSingle();
        return $uid;
    }

    /**
     * Returns the IMAP mailbox for the current message.
     *
     * @return IMP_Mailbox  The message mailbox.
     */
    public function getMailbox()
    {
        list($mbox,) = $this->_indices->getSingle();
        return $mbox;
    }

    /**
     * Return an IMP_Indices object for the current message.
     *
     * @return IMP_Indices  An indices object.
     */
    public function getIndicesOb()
    {
        return $this->_indices;
    }

    /**
     * Returns the entire body of the message.
     *
     * @param array $options  Additional options:
     *   - stream: (boolean) If true, return a stream.
     *             DEFAULT: No
     *
     * @return mixed  The text of the part, or a stream resource if 'stream'
     *                is true.
     */
    public function getBody($options = array())
    {
        if (!$this->_indices) {
            return $this->_message->toString(array(
                'headers' => true,
                'stream' => !empty($options['stream'])
            ));
        }

        $query = new Horde_Imap_Client_Fetch_Query();
        $query->bodytext(array(
            'peek' => true
        ));

        return ($res = $this->_fetchData($query))
            ? $res->getBodyText(0, !empty($options['stream']))
            : '';
    }

    /**
     * Gets the raw text for one section of the message.
     *
     * @param integer $id     The ID of the MIME part.
     * @param array $options  Additional options:
     *   - decode: (boolean) Attempt to decode the bodypart on the remote
     *             server.
     *             DEFAULT: No
     *   - length: (integer) If set, only download this many bytes of the
     *             bodypart from the server.
     *             DEFAULT: All data is retrieved.
     *   - mimeheaders: (boolean) Include the MIME headers also?
     *                  DEFAULT: No
     *   - stream: (boolean) If true, return a stream.
     *             DEFAULT: No
     *
     * @return object  Object with the following properties:
     *   - data: (mixed) The text of the part or a stream resource if 'stream'
     *           option is true.
     *   - decode: (string) If 'decode' option is true, and bodypart decoded
     *             on server, the content-type of the decoded data.
     */
    public function getBodyPart($id, $options = array())
    {
        $ret = new stdClass;
        $ret->data = '';
        $ret->decode = null;

        if (empty($id)) {
            return $ret;
        }

        if (!$this->_indices || $this->isEmbedded($id)) {
            if (empty($options['mimeheaders']) ||
                in_array($id, $this->_embedded)) {
                $ob = $this->getMimePart($id, array('nocontents' => true));

                if (empty($options['stream'])) {
                    if (!is_null($ob)) {
                        $ret->data = $ob->getContents();
                    }
                } else {
                    $ret->data = is_null($ob)
                        ? fopen('php://temp', 'r+')
                        : $ob->getContents(array('stream' => true));
                }

                return $ret;
            }

            $base_id = new Horde_Mime_Id($id);
            while (!in_array($base_id->id, $this->_embedded, true)) {
                $base_id->id = $base_id->idArithmetic($base_id::ID_UP);
                if (is_null($base_id->id)) {
                    return $ret;
                }
            }

            $body = '';
            $part = $this->getMimePart(
                $base_id->id,
                array('nocontents' => true)
            );

            if ($part) {
                $txt = $part->addMimeHeaders()->toString() .
                    "\n" .
                    $part->getContents();

                try {
                    $body = Horde_Mime_Part::getRawPartText($txt, 'header', '1') .
                        "\n\n" .
                        Horde_Mime_Part::getRawPartText($txt, 'body', '1');
                } catch (Horde_Mime_Exception $e) {}
            }

            if (empty($options['stream'])) {
                $ret->data = $body;
                return $ret;
            }

            $ret->data = fopen('php://temp', 'r+');
            if (strlen($body)) {
                fwrite($ret->data, $body);
                fseek($ret->data, 0);
            }
            return $ret;
        }

        $query = new Horde_Imap_Client_Fetch_Query();
        if (substr($id, -2) === '.0') {
            $rfc822 = true;
            $id = substr($id, 0, -2);
        } else {
            $rfc822 = false;
        }

        if (!isset($options['length']) || !empty($options['length'])) {
            $bodypart_params = array(
                'decode' => !empty($options['decode']),
                'peek' => true
            );

            if (isset($options['length'])) {
                $bodypart_params['start'] = 0;
                $bodypart_params['length'] = $options['length'];
            }

            if ($rfc822) {
                $bodypart_params['id'] = $id;
                $query->bodyText($bodypart_params);
            } else {
                $query->bodyPart($id, $bodypart_params);
            }
        }

        if (!empty($options['mimeheaders'])) {
            if ($rfc822) {
                $query->headerText(array(
                    'id' => $id,
                    'peek' => true
                ));
            } else {
                $query->mimeHeader($id, array(
                    'peek' => true
                ));
            }
        }

        if ($res = $this->_fetchData($query)) {
            try {
                if (empty($options['mimeheaders'])) {
                    $ret->decode = $res->getBodyPartDecode($id);
                    $ret->data = $rfc822
                        ? $res->getBodyText($id, !empty($options['stream']))
                        : $res->getBodyPart($id, !empty($options['stream']));
                    return $ret;
                } elseif (empty($options['stream'])) {
                    $ret->data = $rfc822
                        ? ($res->getHeaderText($id) . $res->getBodyText($id))
                        : ($res->getMimeHeader($id) . $res->getBodyPart($id));
                    return $ret;
                }

                if ($rfc822) {
                    $data = array(
                        $res->getHeaderText($id, Horde_Imap_Client_Data_Fetch::HEADER_STREAM),
                        $res->getBodyText($id, true)
                    );
                } else {
                    $data = array(
                        $res->getMimeHeader($id, Horde_Imap_Client_Data_Fetch::HEADER_STREAM),
                        $res->getBodyPart($id, true)
                    );
                }

                $ret->data = Horde_Stream_Wrapper_Combine::getStream($data);
                return $ret;
            } catch (Horde_Exception $e) {}
        }

        if (!empty($options['stream'])) {
            $ret->data = fopen('php://temp', 'r+');
        }

        return $ret;
    }

    /**
     * Returns the full message text.
     *
     * @param array $options  Additional options:
     *   - stream: (boolean) If true, return a stream for bodytext.
     *             DEFAULT: No
     *
     * @return mixed  The full message text or a stream resource if 'stream'
     *                is true.
     */
    public function fullMessageText($options = array())
    {
        if (!$this->_indices) {
            return $this->_message->toString();
        }

        $query = new Horde_Imap_Client_Fetch_Query();
        $query->bodyText(array(
            'peek' => true
        ));

        if ($res = $this->_fetchData($query)) {
            try {
                if (empty($options['stream'])) {
                    return $this->getHeader(self::HEADER_TEXT) . $res->getBodyText(0);
                }

                return Horde_Stream_Wrapper_Combine::getStream(array(
                    $this->getHeader(self::HEADER_STREAM),
                    $res->getBodyText(0, true)
                ));
            } catch (Horde_Exception $e) {}
        }

        return empty($options['stream'])
            ? ''
            : fopen('php://temp', 'r+');
    }

    /**
     * Returns base header information.
     *
     * @param integer $type  Return type (HEADER_* constant).
     *
     * @return mixed  Either a Horde_Mime_Headers object (HEADER_OB), header
     *                text (HEADER_TEXT), or a stream resource (HEADER_STREAM).
     */
    public function getHeader($type = self::HEADER_OB)
    {
        return $this->_getHeader($type, false);
    }

    /**
     * Returns base header information and marks the message as seen.
     *
     * @param integer $type  See getHeader().
     *
     * @return mixed  See getHeader().
     */
    public function getHeaderAndMarkAsSeen($type = self::HEADER_OB)
    {
        $mbox = $this->getMailbox();

        if ($mbox->readonly) {
            $seen = false;
        } else {
            $seen = true;

            if (isset($this->_header)) {
                try {
                    $imp_imap = $mbox->imp_imap;
                    $imp_imap->store($mbox, array(
                        'add' => array(
                            Horde_Imap_Client::FLAG_SEEN
                        ),
                        'ids' => $imp_imap->getIdsOb($this->getUid())
                    ));
                } catch (Exception $e) {}
            }
        }

        return $this->_getHeader($type, $seen);
    }

    /**
     * Returns base header information.
     *
     * @param integer $type  See getHeader().
     * @param boolean $seen  Mark message as seen?
     *
     * @return mixed  See getHeader().
     */
    protected function _getHeader($type, $seen)
    {
        if (!isset($this->_header)) {
            if (!$this->_indices) {
                $this->_header = $this->_message->addMimeHeaders();
            } else {
                $query = new Horde_Imap_Client_Fetch_Query();
                $query->headerText(array(
                    'peek' => !$seen
                ));

                $this->_header = ($res = $this->_fetchData($query))
                    ? $res
                    : new Horde_Imap_Client_Data_Fetch();
            }
        }

        switch ($type) {
        case self::HEADER_OB:
            return $this->_indices
                ? $this->_header->getHeaderText(0, Horde_Imap_Client_Data_Fetch::HEADER_PARSE)
                : $this->_header;

        case self::HEADER_TEXT:
            return $this->_indices
                ? $this->_header->getHeaderText()
                : $this->_header->toString();

        case self::HEADER_STREAM:
            if ($this->_indices) {
                return $this->_header->getHeaderText(0, Horde_Imap_Client_Data_Fetch::HEADER_STREAM);
            }

            $stream = new Horde_Support_StringStream($this->_header->toString());
            $stream->fopen();
            return $stream;
        }
    }

    /**
     * Returns the Horde_Mime_Part object.
     *
     * @return Horde_Mime_Part  A Horde_Mime_Part object.
     */
    public function getMIMEMessage()
    {
        return $this->_message;
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
     * @return Horde_Mime_Part  The raw MIME part asked for. If not found,
     *                          returns null.
     */
    public function getMimePart($id, $options = array())
    {
        $this->_buildMessage();

        if (!$part = $this->_message->getPart($id)) {
            return null;
        }

        /* Ticket #9201: Treat 'ISO-8859-1' as 'windows-1252'. 1252 has some
         * characters (e.g. euro sign, back quote) not in 8859-1. There
         * shouldn't be any issue doing this since the additional code points
         * in 1252 don't map to anything in 8859-1. */
        if (strcasecmp($part->getCharset(), 'ISO-8859-1') === 0) {
            $part->setCharset('windows-1252');
        }

        /* Don't download contents of entire body if ID == 0 (indicating the
         * body of the main multipart message).  I'm pretty sure we never
         * want to download the body of that part here. */
        if (!empty($id) &&
            (substr($id, -2) != '.0') &&
            empty($options['nocontents']) &&
            $this->_indices &&
            !$part->getContents(array('stream' => true))) {
            $body = $this->getBodyPart($id, array(
                'decode' => true,
                'length' => empty($options['length']) ? null : $options['length'],
                'stream' => true
            ));
            $part->setContents($body->data, array(
                'encoding' => $body->decode,
                'usestream' => true
            ));
        }

        return $part;
    }

    /**
     * Render a MIME Part.
     *
     * @param string $mime_id  The MIME ID to render.
     * @param integer $mode    One of the RENDER_ constants.
     * @param array $options   Additional options:
     *   - autodetect: (boolean) Attempt to auto-detect MIME type?
     *   - mime_part: (Horde_Mime_Part) The MIME part to render.
     *   - type: (string) Use this MIME type instead of the MIME type
     *           identified in the MIME part.
     *
     * @return array  See Horde_Mime_Viewer_Base::render(). The following
     *                fields may also be present in addition to the fields
     *                defined in Horde_Mime_Viewer_Base:
     *   - attach: (boolean) Force display of this part as an attachment.
     *   - js: (array) A list of javascript commands to run after the content
     *         is displayed on screen.
     *   - name: (string) Contains the MIME name information.
     *   - wrap: (string) If present, indicates that this part, and all child
     *           parts, will be wrapped in a DIV with the given class name.
     */
    public function renderMIMEPart($mime_id, $mode, array $options = array())
    {
        $this->_buildMessage();

        $mime_part = empty($options['mime_part'])
            ? $this->getMimePart($mime_id)
            : $options['mime_part'];
        if (!$mime_part) {
            return array($mime_id => null);
        }

        if (!empty($options['autodetect']) &&
            ($tempfile = Horde::getTempFile()) &&
            ($fp = fopen($tempfile, 'w')) &&
            !is_null($contents = $mime_part->getContents(array('stream' => true)))) {
            rewind($contents);
            while (!feof($contents)) {
                fwrite($fp, fread($contents, 65536));
            }
            fclose($fp);

            $options['type'] = Horde_Mime_Magic::analyzeFile($tempfile, empty($GLOBALS['conf']['mime']['magic_db']) ? null : $GLOBALS['conf']['mime']['magic_db']);
        }

        $type = empty($options['type'])
            ? null
            : $options['type'];

        $viewer = $GLOBALS['injector']->getInstance('IMP_Factory_MimeViewer')->create($mime_part, array('contents' => $this, 'type' => $type));

        switch ($mode) {
        case self::RENDER_INLINE:
        case self::RENDER_INLINE_AUTO:
        case self::RENDER_INLINE_DISP_NO:
            $textmode = 'inline';
            $limit = $viewer->getConfigParam('limit_inline_size');

            if ($limit && ($mime_part->getBytes() > $limit)) {
                $data = '';
                $status = new IMP_Mime_Status(
                    $mime_part,
                    array(
                        _("This message part cannot be viewed because it is too large."),
                        $this->linkView($mime_part, 'download_attach', _("Click to download the data."))
                    )
                );
                $status->icon('alerts/warning.png', _("Warning"));

                if (method_exists($viewer, 'overLimitText')) {
                    $data = $viewer->overLimitText();
                    $status->addText(_("The initial portion of this text part is displayed below."));
                }

                return array(
                    $mime_id => array(
                        'data' => $data,
                        'name' => '',
                        'status' => $status,
                        'type' => 'text/html; charset=' . 'UTF-8'
                    )
                );
            }
            break;

        case self::RENDER_INFO:
            $textmode = 'info';
            break;

        case self::RENDER_RAW:
            $textmode = 'raw';
            break;

        case self::RENDER_RAW_FALLBACK:
            $textmode = $viewer->canRender('raw')
                ? 'raw'
                : 'full';
            break;

        case self::RENDER_FULL:
        default:
            $textmode = 'full';
            break;
        }

        $ret = $viewer->render($textmode);

        if (empty($ret)) {
            return ($mode == self::RENDER_INLINE_AUTO)
                ? $this->renderMIMEPart($mime_id, self::RENDER_INFO, $options)
                : array();
        }

        if (!empty($ret[$mime_id]) && !isset($ret[$mime_id]['name'])) {
            $ret[$mime_id]['name'] = $mime_part->getName(true);
        }

        /* Don't show empty parts. */
        if (($textmode == 'inline') &&
            !is_null($ret[$mime_id]['data']) &&
            !strlen($ret[$mime_id]['data']) &&
            !isset($ret[$mime_id]['status'])) {
            $ret[$mime_id] = null;
        }

        return $ret;
    }

    /**
     * Finds the main "body" text part (if any) in a message.
     * "Body" data is the first text part in the base MIME part.
     *
     * @param string $subtype  Specifically search for this subtype.
     *
     * @return string  The MIME ID of the main body part.
     */
    public function findBody($subtype = null)
    {
        $this->_buildMessage();
        return $this->_message->findBody($subtype);
    }

    /**
     * Get summary info for a MIME ID.
     *
     * @param string $id     The MIME ID.
     * @param integer $mask  A bitmask indicating what information to return:
     * <pre>
     * Always output:
     *   'type' = MIME type
     *
     * IMP_Contents::SUMMARY_BYTES
     *   Output: parts = 'bytes'
     *
     * IMP_Contents::SUMMARY_SIZE
     *   Output: parts = 'size'
     *
     * IMP_Contents::SUMMARY_ICON
     * IMP_Contents::SUMMARY_ICON_RAW
     *   Output: parts = 'icon'
     *
     * IMP_Contents::SUMMARY_DESCRIP
     *   Output: parts = 'description_raw'
     *
     * IMP_Contents::SUMMARY_DESCRIP_LINK
     *   Output: parts = 'description'
     *
     * IMP_Contents::SUMMARY_DOWNLOAD
     *   Output: parts = 'download', 'download_url'
     *
     * IMP_Contents::SUMMARY_IMAGE_SAVE
     *   Output: parts = 'img_save'
     *
     * IMP_Contents::SUMMARY_PRINT
     * IMP_Contents::SUMMARY_PRINT_STUB
     *   Output: parts = 'print'
     *
     * IMP_Contents::SUMMARY_STRIP
     *   Output: parts = 'strip'
     * </pre>
     *
     * @return array  An array with the requested information.
     */
    public function getSummary($id, $mask = 0)
    {
        $autodetect_link = false;
        $param_array = array();

        $this->_buildMessage();

        $part = array(
            'bytes' => null,
            'download' => null,
            'download_url' => null,
            'id' => $id,
            'img_save' => null,
            'size' => null,
            'strip' => null
        );

        $mime_part = $this->getMimePart($id, array('nocontents' => true));
        if (!$mime_part) {
            return $part;
        }

        $mime_type = $mime_part->getType();

        /* If this is an attachment that has no specific MIME type info, see
         * if we can guess a rendering type. */
        if (in_array($mime_type, array('application/octet-stream', 'application/base64'))) {
            $mime_type = Horde_Mime_Magic::filenameToMIME($mime_part->getName());
            if ($mime_type == $mime_part->getType()) {
                $autodetect_link = true;
            } else {
                $mime_part = clone $mime_part;
                $mime_part->setType($mime_type);
                $param_array['ctype'] = $mime_type;
            }
        }
        $part['type'] = $mime_type;

        /* Is this part an attachment? */
        $is_atc = $this->isAttachment($mime_type);

        /* Get bytes/size information. */
        if (($mask & self::SUMMARY_BYTES) ||
            ($mask & self::SUMMARY_SIZE)) {
            $part['bytes'] = $size = $mime_part->getBytes();
            $part['size'] = ($size > 1048576)
                ? sprintf(_("%s MB"), IMP::numberFormat($size / 1048576, 1))
                : sprintf(_("%s KB"), max(round($size / 1024), 1));
        }

        /* Get part's icon. */
        if (($mask & self::SUMMARY_ICON) ||
            ($mask & self::SUMMARY_ICON_RAW)) {
            $part['icon'] = $GLOBALS['injector']->getInstance('IMP_Factory_MimeViewer')->getIcon($mime_type);
            if ($mask & self::SUMMARY_ICON) {
                $part['icon'] = Horde_Themes_Image::tag($part['icon'], array(
                    'attr' => array(
                        'title' => $mime_type
                    )
                ));
            }
        } else {
            $part['icon'] = null;
        }

        /* Get part's description. */
        $description = $this->getPartName($mime_part, true);

        if ($mask & self::SUMMARY_DESCRIP_LINK) {
            if (($can_d = $this->canDisplay($mime_part, self::RENDER_FULL)) ||
                $autodetect_link) {
                $part['description'] = $this->linkViewJS($mime_part, 'view_attach', htmlspecialchars($description), array('jstext' => sprintf(_("View %s"), $description), 'params' => array_filter(array_merge($param_array, array(
                    'autodetect' => !$can_d
                )))));
            } else {
                $part['description'] = htmlspecialchars($description);
            }
        }
        if ($mask & self::SUMMARY_DESCRIP) {
            $part['description_raw'] = $description;
        }

        /* Download column. */
        if ($is_atc && ($mask & self::SUMMARY_DOWNLOAD)) {
            $part['download'] = $this->linkView($mime_part, 'download_attach', '', array('class' => 'iconImg downloadAtc', 'jstext' => _("Download")));
            $part['download_url'] = $this->urlView($mime_part, 'download_attach');
        }

        /* Display the image save link if the required registry calls are
         * present. */
        if (($mask & self::SUMMARY_IMAGE_SAVE) &&
            $GLOBALS['registry']->hasMethod('images/selectGalleries') &&
            ($mime_part->getPrimaryType() == 'image')) {
            $part['img_save'] = Horde::link('#', _("Save Image in Gallery"), 'iconImg saveImgAtc', null, Horde::popupJs(IMP_Basic_Saveimage::url(), array('params' => array('muid' => strval($this->getIndicesOb()), 'id' => $id), 'height' => 200, 'width' => 450, 'urlencode' => true)) . 'return false;') . '</a>';
        }

        /* Add print link? */
        if ((($mask & self::SUMMARY_PRINT) ||
             ($mask & self::SUMMARY_PRINT_STUB)) &&
            $this->canDisplay($id, self::RENDER_FULL)) {
            $part['print'] = ($mask & self::SUMMARY_PRINT)
                ? $this->linkViewJS($mime_part, 'print_attach', '', array('css' => 'iconImg printAtc', 'jstext' => _("Print"), 'onload' => 'IMP_JS.printWindow', 'params' => $param_array))
                : Horde::link('#', _("Print"), 'iconImg printAtc', null, null, null, null, array('mimeid' => $id)) . '</a>';
        }

        /* Strip Attachment? Allow stripping of base parts other than the
         * base multipart and the base text (body) part. */
        if (($mask & self::SUMMARY_STRIP) &&
            ($id != 0) &&
            (intval($id) != 1) &&
            (strpos($id, '.') === false)) {
            $part['strip'] = Horde::link(
                Horde::selfUrlParams()->add(array(
                    'actionID' => 'strip_attachment',
                    'imapid' => $id,
                    'muid' => strval($this->getIndicesOb()),
                    'token' => $GLOBALS['session']->getToken()
                )),
                _("Strip Attachment"),
                'iconImg deleteImg stripAtc',
                null,
                null,
                null,
                null,
                array('mimeid' => $id)
            ) . '</a>';
        }

        return $part;
    }

    /**
     * Return the URL to the download/view page.
     *
     * @param Horde_Mime_Part $mime_part  The MIME part to view.
     * @param integer $actionID           The actionID to perform.
     * @param array $options              Additional options:
     *   - params: (array) A list of any additional parameters that need to be
     *             passed to the download/view page (key => name).
     *
     * @return Horde_Url  The URL to the download/view page.
     */
    public function urlView($mime_part = null, $actionID = 'view_attach',
                            array $options = array())
    {
        $params = $this->_urlViewParams($mime_part, $actionID, isset($options['params']) ? $options['params'] : array());

        return (strpos($actionID, 'download_') === 0)
            ? IMP_Contents_View::downloadUrl($mime_part->getName(true), $params)
            : Horde::url('view.php', true)->add($params);
    }

    /**
     * Generates the necessary URL parameters for the download/view page.
     *
     * @param Horde_Mime_Part $mime_part  The MIME part to view.
     * @param integer $actionID           The actionID to perform.
     * @param array $params               Additional parameters to pass.
     *
     * @return array  The array of parameters.
     */
    protected function _urlViewParams($mime_part, $actionID, $params)
    {
        /* Add the necessary local parameters. */
        $params = array_merge($params, array(
            'actionID' => $actionID,
            'id' => isset($params['id']) ? $params['id'] : $mime_part->getMIMEId()
        ));

        if ($this->_indices) {
            $params['muid'] = strval($this->getIndicesOb());
        }

        return IMP_Contents_View::addToken($params);
    }

    /**
     * Generate a link to the download/view page.
     *
     * @param Horde_Mime_Part $mime_part  The MIME part to view.
     * @param integer $actionID           The actionID value.
     * @param string $text                The ESCAPED (!) link text.
     * @param array $options              Additional parameters:
     *   - class: (string) The CSS class to use.
     *   - jstext: (string) The JS text to use.
     *   - params: (array) A list of any additional parameters that need to be
     *             passed to the download/view page.
     *
     * @return string  A HTML href link to the download/view page.
     */
    public function linkView($mime_part, $actionID, $text, $options = array())
    {
        $options = array_merge(array(
            'class' => null,
            'jstext' => $text,
            'params' => array()
        ), $options);

        return Horde::link(
            $this->urlView($mime_part, $actionID, $options),
            $options['jstext'],
            $options['class'],
            ($actionID == 'download_attach') ? null : strval(new Horde_Support_Randomid())
        ) . $text . '</a>';
    }

    /**
     * Generate a javascript link to the download/view page.
     *
     * @param Horde_Mime_Part $mime_part  The MIME part to view.
     * @param string $actionID            The actionID to perform.
     * @param string $text                The ESCAPED (!) link text.
     * @param array $options              Additional options:
     *   - css: (string) The CSS class to use.
     *   - jstext: (string) The javascript link text.
     *   - onload: (string) A JS function to run when popup window is
     *             fully loaded.
     *   - params: (array) A list of any additional parameters that need to be
     *             passed to download/view page. (key = name)
     *   - widget: (boolean) If true use Horde::widget() to generate,
     *             Horde::link() otherwise.
     *
     * @return string  A HTML href link to the download/view page.
     */
    public function linkViewJS($mime_part, $actionID, $text,
                               $options = array())
    {
        if (empty($options['params'])) {
            $options['params'] = array();
        }

        if (empty($options['jstext'])) {
            $options['jstext'] = ($description = $mime_part->getDescription(true))
                ? sprintf(_("View %s"), $description)
                : null;
        }

        $url = Horde::popupJs(Horde::url('view.php'), array(
            'menu' => true,
            'onload' => empty($options['onload']) ? 'IMP_JS.resizePopup' : $options['onload'],
            'params' => $this->_urlViewParams($mime_part, $actionID, isset($options['params']) ? $options['params'] : array()),
            'urlencode' => true
        ));

        return empty($options['widget'])
            ? Horde::link('#', $options['jstext'], empty($options['css']) ? null : $options['css'], null, $url) . $text . '</a>'
            : Horde::widget(array('url' => '#', 'class' => empty($options['css']) ? null : $options['css'], 'onclick' => $url, 'title' => $text));
    }

    /**
     * Determines if a MIME type is an attachment.
     * For IMP's purposes, an attachment is any MIME part that can be
     * downloaded by itself (i.e. all the data needed to view the part is
     * contained within the download data).
     *
     * @param string $mime_type  The MIME type.
     *
     * @return boolean  True if an attachment.
     */
    public function isAttachment($mime_type)
    {
        switch ($mime_type) {
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

    /**
     * Builds the "virtual" Horde_Mime_Part object by checking for embedded
     * parts.
     *
     * @param array $parts  The parts list to process.
     */
    protected function _buildMessage($parts = null)
    {
        global $injector;

        if (is_null($parts)) {
            if ($this->_build) {
                return;
            }
            $this->_build = true;
            $parts = array_keys($this->_message->contentTypeMap());
            $first_id = reset($parts);
        } else {
            $first_id = null;
        }

        $last_id = null;
        $to_process = array();

        $mv_factory = $injector->getInstance('IMP_Factory_MimeViewer');

        foreach ($parts as $id) {
            if (!is_null($last_id) &&
                (strpos($id, $last_id) === 0)) {
                continue;
            }

            $last_id = null;

            $mime_part = $this->getMimePart($id, array('nocontents' => true));
            if (!$mime_part) {
                continue;
            }

            $viewer = $mv_factory->create(
                $mime_part,
                array('contents' => $this)
            );

            if ($viewer->embeddedMimeParts() &&
                ($mime_part = $this->getMimePart($id))) {
                $viewer->setMIMEPart($mime_part);
                $new_part = $viewer->getEmbeddedMimeParts();
                if (!is_null($new_part)) {
                    $mime_part->addPart($new_part);
                    $mime_part->buildMimeIds($id);
                    $this->_embedded[] = $new_part->getMimeId();
                    $to_process = array_merge($to_process, array_keys($new_part->contentTypeMap()));
                    $last_id = $id;
                }
            }
        }

        if (!empty($to_process)) {
            $this->_buildMessage($to_process);
        }
    }

    /**
     * Can this MIME part be displayed in the given mode?
     *
     * @param mixed $part    The MIME part or a MIME ID string.
     * @param integer $mask  One of the RENDER_ constants.
     * @param string $type   The type to use (overrides the MIME ID if $id is
     *                       a MIME part).
     *
     * @return integer  The RENDER_ constant of the allowable display.
     */
    public function canDisplay($part, $mask, $type = null)
    {
        if (!is_object($part) &&
            !($part = $this->getMimePart($part, array('nocontents' => true)))) {
            return 0;
        }
        $viewer = $GLOBALS['injector']->getInstance('IMP_Factory_MimeViewer')->create($part, array('contents' => $this, 'type' => $type));

        if ($mask & self::RENDER_INLINE_AUTO) {
            $mask |= self::RENDER_INLINE | self::RENDER_INFO;
        }

        if (($mask & self::RENDER_RAW) && $viewer->canRender('raw')) {
            return self::RENDER_RAW;
        }

        if (($mask & self::RENDER_FULL) && $viewer->canRender('full')) {
            return self::RENDER_FULL;
        }

        if ($mask & self::RENDER_INLINE) {
            if ($viewer->canRender('inline')) {
                return self::RENDER_INLINE;
            }
        } elseif (($mask & self::RENDER_INLINE_DISP_NO) &&
                  $viewer->canRender('inline')) {
            return self::RENDER_INLINE_DISP_NO;
        }

        if (($mask & self::RENDER_INFO) && $viewer->canRender('info')) {
            return self::RENDER_INFO;
        }

        return 0;
    }

    /**
     * Returns the Content-Type map for the entire message, regenerating
     * embedded parts if needed.
     *
     * @return array  See Horde_Mime_Part::contentTypeMap().
     */
    public function getContentTypeMap()
    {
        $this->_buildMessage();
        return $this->_message->contentTypeMap();
    }

    /**
     * Returns the MIME part tree of the message.
     *
     * @param string $renderer  Either the tree renderer driver or a full
     *                          class name to use.
     *
     * @return Horde_Tree_Renderer_Base  A tree instance representing the MIME parts.
     * @throws Horde_Tree_Exception
     */
    public function getTree($renderer = 'Horde_Core_Tree_Renderer_Html')
    {
        $tree = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Tree')->create('mime-' . $this->getUid(), $renderer, array(
            'nosession' => true
        ));
        $this->_addTreeNodes($tree, $this->_message);
        return $tree;
    }

    /**
     * Adds MIME parts to the tree instance.
     *
     * @param Horde_Tree_Renderer_Base tree   A tree instance.
     * @param Horde_Mime_Part $part           The MIME part to add to the
     *                                        tree, including its sub-parts.
     * @param string $parent                  The parent part's MIME id.
     */
    protected function _addTreeNodes($tree, $part, $parent = null)
    {
        $mimeid = $part->getMimeId();

        $summary_mask = self::SUMMARY_ICON_RAW | self::SUMMARY_DESCRIP_LINK | self::SUMMARY_SIZE | self::SUMMARY_DOWNLOAD;
        if ($GLOBALS['prefs']->getValue('strip_attachments')) {
            $summary_mask += self::SUMMARY_STRIP;
        }

        $summary = $this->getSummary($mimeid, $summary_mask);

        $tree->addNode(array(
            'id' => $mimeid,
            'parent' => $parent,
            'label' => sprintf(
                '%s (%s) %s %s',
                $summary['description'],
                $summary['size'],
                $summary['download'],
                $summary['strip']
            ),
            'params' => array(
                'class' => 'partsTreeDiv',
                'icon' => $summary['icon']
            )
        ));

        foreach ($part->getParts() as $part) {
            $this->_addTreeNodes($tree, $part, $mimeid);
        }
    }

    /**
     * Get download all list.
     *
     * @return array  An array of downloadable parts.
     */
    public function downloadAllList()
    {
        $ret = array();

        foreach ($this->getContentTypeMap() as $key => $val) {
            if ($this->isAttachment($val)) {
                $ret[] = $key;
            }
        }

        return $ret;
    }

    /**
     * Injects body contents into the base Horde_Mime_part object.
     *
     * @param array $ignore  A list of MIME IDs to ignore.
     *
     * @return Horde_Mime_Part  The part with body contents set.
     */
    public function buildMessageContents($ignore = array())
    {
        $message = $this->_message;
        $curr_ignore = null;

        foreach ($message->contentTypeMap() as $key => $val) {
            if (is_null($curr_ignore) && in_array($key, $ignore)) {
                $curr_ignore = $key . '.';
            } elseif (is_null($curr_ignore) ||
                      (strpos($key, $curr_ignore) === false)) {
                $curr_ignore = null;
                if (($key != 0) &&
                    ($val != 'message/rfc822') &&
                    (strpos($val, 'multipart/') === false) &&
                    ($part = $this->getMimePart($key))) {
                    $message->alterPart($key, $part);
                }
            }
        }

        return $message;
    }

    /**
     * Determines if a given MIME part ID is a part of embedded data.
     *
     * @param string $mime_id  The MIME ID.
     *
     * @return boolean  True if the MIME ID is part of embedded data.
     */
    public function isEmbedded($mime_id)
    {
        foreach ($this->_embedded as $val) {
            if (($mime_id == $val) ||
                (($id_ob = new Horde_Mime_Id($val)) &&
                 $id_ob->isChild($mime_id))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find a MIME type in parent parts.
     *
     * @param string $id    The MIME ID to begin the search at.
     * @param string $type  The MIME type to search for.
     *
     * @return mixed  Either the requested MIME part, or null if not found.
     */
    public function findMimeType($id, $type)
    {
        $id_ob = new Horde_Mime_Id($id);

        while (($id_ob->id = $id_ob->idArithmetic($id_ob::ID_UP)) !== null) {
            if (($part = $this->getMimePart($id_ob->id, array('nocontents' => true))) &&
                ($part->getType() == $type)) {
                return $part;
            }
        }

        return null;
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
            return _("Audio");

        case 'image':
            return _("Image");

        case 'message':
        case '':
        case Horde_Mime_Part::UNKNOWN:
            return _("Message");

        case 'multipart':
            return _("Multipart");

        case 'text':
            return _("Text");

        case 'video':
            return _("Video");

        default:
            // Attempt to translate this type, if possible. Odds are that
            // it won't appear in the dictionary though.
            return _(Horde_String::ucfirst($ptype));
        }
    }

    /**
     * Get FETCH data from IMAP server for this message.
     *
     * @param Horde_Imap_Client_Fetch_Query $query  Search query.
     *
     * @return Horde_Imap_Client_Data_Fetch  Fetch data for the message.
     */
    protected function _fetchData(Horde_Imap_Client_Fetch_Query $query)
    {
        try {
            $mbox = $this->getMailbox();
            $imp_imap = $mbox->imp_imap;
            return $imp_imap->fetch($mbox, $query, array(
                'ids' => $imp_imap->getIdsOb($this->getUid())
            ))->first();
        } catch (Horde_Imap_Client_Exception $e) {
            return new Horde_Imap_Client_Data_Fetch();
        }
    }

    /**
     * Return the view cache object for this message.
     *
     * @return object  View object.
     */
    public function getViewCache()
    {
        if (!isset($this->_viewcache)) {
            $this->_viewcache = new stdClass;
        }

        return $this->_viewcache;
    }

    /**
     * Returns mailing list information for the message.
     *
     * @return array  An array with 2 elements:
     * <pre>
     *   - exists: (boolean) True if this is a mailing list message.
     *   - reply_list: (string) If non-null, the e-mail address to use to post
     *                 to the list.
     * </pre>
     */
    public function getListInformation()
    {
        global $injector;

        $headers = $this->getHeader();
        $lh = $injector->getInstance('Horde_ListHeaders');
        $ret = array(
            'exists' => false,
            'reply_list' => null
        );

        if ($lh->listHeadersExist($headers)) {
            $ret['exists'] = true;

            /* See if the List-Post header provides an e-mail address for the
             * list. */
            if ($val = $headers['List-Post']) {
                foreach ($lh->parse('list-post', $val->value) as $val2) {
                    if ($val2 instanceof Horde_ListHeaders_NoPost) {
                        break;
                    } elseif (stripos($val2->url, 'mailto:') === 0) {
                        $ret['reply_list'] = substr($val2->url, 7);
                        break;
                    }
                }
            }
        }

        return $ret;
    }

}
