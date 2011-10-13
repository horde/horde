<?php
/**
 * The IMP_Contents:: class contains all functions related to handling the
 * content and output of mail messages in IMP.
 *
 * Copyright 2002-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Contents
{
    /* Mask entries for getSummary(). */
    const SUMMARY_BYTES = 1;
    const SUMMARY_SIZE = 2;
    const SUMMARY_ICON = 4;
    const SUMMARY_DESCRIP_LINK = 8;
    const SUMMARY_DESCRIP_NOLINK = 16;
    const SUMMARY_DESCRIP_NOLINK_NOHTMLSPECCHARS = 32;
    const SUMMARY_DOWNLOAD = 64;
    const SUMMARY_DOWNLOAD_NOJS = 128;
    const SUMMARY_DOWNLOAD_ZIP = 256;
    const SUMMARY_IMAGE_SAVE = 512;
    const SUMMARY_PRINT = 1024;
    const SUMMARY_PRINT_STUB = 2048;
    const SUMMARY_STRIP_LINK = 4096;
    const SUMMARY_STRIP_STUB = 8192;

    /* Rendering mask entries. */
    const RENDER_FULL = 1;
    const RENDER_INLINE = 2;
    const RENDER_INLINE_DISP_NO = 4;
    const RENDER_INFO = 8;
    const RENDER_INLINE_AUTO = 16;
    const RENDER_RAW = 32;
    const RENDER_RAW_FALLBACK = 64;

    /**
     * Flag to indicate whether the last call to getBodypart() returned
     * decoded data.
     *
     * @var string
     */
    public $lastBodyPartDecode = null;

    /**
     * The IMAP UID of the message.
     *
     * @var integer
     */
    protected $_uid = null;

    /**
     * The mailbox of the current message.
     *
     * @var IMP_Malbox
     */
    protected $_mailbox;

    /**
     * The Horde_Mime_Part object for the message.
     *
     * @var Horde_Mime_Part
     */
    protected $_message;

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
     * Constructor.
     *
     * @param mixed $in  An IMP_Indices or Horde_Mime_Part object.
     *
     * @throws IMP_Exception
     */
    public function __construct($in)
    {
        if ($in instanceof Horde_Mime_Part) {
            $this->_message = $in;
        } else {
            list($this->_mailbox, $this->_uid) = $in->getSingle();

            /* Get the Horde_Mime_Part object for the given UID. */
            $query = new Horde_Imap_Client_Fetch_Query();
            $query->structure();

            try {
                $ret = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->fetch($this->_mailbox, $query, array(
                    'ids' => new Horde_Imap_Client_Ids($this->_uid)
                ));
            } catch (Horde_Imap_Client_Exception $e) {
                throw new IMP_Exception('Error displaying message.');
            }

            $this->_message = $ret[$this->_uid]->getStructure();
        }
    }

    /**
     * Returns the IMAP UID for the current message.
     *
     * @return integer  The message UID.
     */
    public function getUid()
    {
        return $this->_uid;
    }

    /**
     * Returns the IMAP mailbox for the current message.
     *
     * @return IMP_Mailbox  The message mailbox.
     */
    public function getMailbox()
    {
        return $this->_mailbox;
    }

    /**
     * Returns the entire body of the message.
     *
     * @param array $options  Additional options:
     * <pre>
     * 'stream' - (boolean) If true, return a stream.
     *            DEFAULT: No
     * </pre>
     *
     * @return mixed  The text of the part, or a stream resource if 'stream'
     *                is true.
     */
    public function getBody($options = array())
    {
        if (!$this->_mailbox) {
            return $this->_message->toString(array(
                'headers' => true,
                'stream' => !empty($options['stream'])
            ));
        }

        $query = new Horde_Imap_Client_Fetch_Query();
        $query->bodytext(array(
            'peek' => true
        ));

        try {
            $res = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->fetch($this->_mailbox, $query, array(
                'ids' => new Horde_Imap_Client_Ids($this->_uid)
            ));
            return $res[$this->_uid]->getBodyText(0, !empty($options['stream']));
        } catch (Horde_Imap_Client_Exception $e) {
            return '';
        }
    }

    /**
     * Gets the raw text for one section of the message.
     *
     * @param integer $id     The ID of the MIME part.
     * @param array $options  Additional options:
     * <pre>
     * 'decode' - (boolean) Attempt to decode the bodypart on the remote
     *            server. If successful, sets self::$lastBodyPartDecode to
     *            the content-type of the decoded data.
     *            DEFAULT: No
     * 'length' - (integer) If set, only download this many bytes of the
     *            bodypart from the server.
     *            DEFAULT: All data is retrieved.
     * 'mimeheaders' - (boolean) Include the MIME headers also?
     *                 DEFAULT: No
     * 'stream' - (boolean) If true, return a stream.
     *            DEFAULT: No
     * </pre>
     *
     * @return mixed  The text of the part or a stream resource if 'stream'
     *                is true.
     */
    public function getBodyPart($id, $options = array())
    {
        $this->lastBodyPartDecode = null;

        if (empty($id)) {
            return '';
        }

        if (!$this->_mailbox) {
            // TODO: Include MIME headers?
            $ob = $this->getMIMEPart($id, array('nocontents' => true));
            return is_null($ob)
                ? ''
                : $ob->getContents();
        }

        $query = new Horde_Imap_Client_Fetch_Query();
        if (!isset($options['length']) || !empty($options['length'])) {
            $bodypart_params = array(
                'decode' => !empty($options['decode']),
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

        try {
            $res = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->fetch($this->_mailbox, $query, array(
                'ids' => new Horde_Imap_Client_Ids($this->_uid)
            ));

            if (empty($options['mimeheaders'])) {
                $this->lastBodyPartDecode = $res[$this->_uid]->getBodyPartDecode($id);
                return $res[$this->_uid]->getBodyPart($id);
            } elseif (empty($options['stream'])) {
                return $res[$this->_uid]->getMimeHeader($id) . $res[$this->_uid]->getBodyPart($id);
            } else {
                $swrapper = new Horde_Support_CombineStream(array($res[$this->_uid]->getMimeHeader($id, Horde_Imap_Client_Data_Fetch::HEADER_STREAM), $res[$this->_uid]->getBodyPart($id, true)));
                return $swrapper->fopen();
            }
        } catch (Horde_Imap_Client_Exception $e) {
            return empty($options['stream'])
                ? ''
                : fopen('php://temp', 'r+');
        }
    }

    /**
     * Returns the full message text.
     *
     * @param array $options  Additional options:
     * <pre>
     * 'stream' - (boolean) If true, return a stream for bodytext.
     *            DEFAULT: No
     * </pre>
     *
     * @return mixed  The full message text or a stream resource if 'stream'
     *                is true.
     */
    public function fullMessageText($options = array())
    {
        if (!$this->_mailbox) {
            return $this->_message->toString();
        }

        $query = new Horde_Imap_Client_Fetch_Query();
        $query->bodyText(array(
            'peek' => true
        ));
        $query->headerText(array(
            'peek' => true,
        ));

        try {
            $res = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->fetch($this->_mailbox, $query, array(
                'ids' => new Horde_Imap_Client_Ids($this->_uid)
            ));

            if (empty($options['stream'])) {
                return $res[$this->_uid]->getHeaderText(0) . $res[$this->_uid]->getBodyText(0);
            }

            $swrapper = new Horde_Support_CombineStream(array($res[$this->_uid]->getHeaderText(0, Horde_Imap_Client_Data_Fetch::HEADER_STREAM), $res[$this->_uid]->getBodyText(0, true)));
            return $swrapper->fopen();
        } catch (Horde_Imap_Client_Exception $e) {
            return empty($options['stream'])
                ? ''
                : fopen('php://temp', 'r+');
        }
    }

    /**
     * Returns the header object.
     *
     * @param boolean $parse  Parse the headers into a headers object?
     *
     * @return Horde_Mime_Headers|string  Either a Horde_Mime_Headers object
     *                                    (if $parse is true) or the header
     *                                    text (if $parse is false).
     */
    public function getHeaderOb($parse = true)
    {
        if (is_null($this->_message)) {
            return $this->_message->getMIMEHeaders();
        }

        $query = new Horde_Imap_Client_Fetch_Query();
        $query->headerText(array(
            'peek' => true
        ));

        try {
            $res = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->fetch($this->_mailbox, $query, array(
                'ids' => new Horde_Imap_Client_Ids($this->_uid)
            ));
            return $res[$this->_uid]->getHeaderText(0, $parse ? Horde_Imap_Client_Data_Fetch::HEADER_PARSE : 0);
        } catch (Horde_Imap_Client_Exception $e) {
            return $parse
                ? new Horde_Mime_Headers()
                : '';
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
     * <pre>
     * 'length' - (integer) If set, only download this many bytes of the
     *            bodypart from the server.
     *            DEFAULT: All data is retrieved.
     * 'nocontents' - (boolean) If true, don't add the contents to the part
     *                DEFAULT: Contents are added to the part
     * </pre>
     *
     * @return Horde_Mime_Part  The raw MIME part asked for (reference).
     */
    public function getMIMEPart($id, $options = array())
    {
        $this->_buildMessage();

        $part = $this->_message->getPart($id);

        /* Ticket #9201: Treat 'ISO-8859-1' as 'windows-1252'. 1252 has some
         * characters (e.g. euro sign, back quote) not in 8859-1. There
         * shouldn't be any issue doing this since the additional code points
         * in 1252 don't map to anything in 8859-1. */
        if ($part &&
            (strcasecmp($part->getCharset(), 'ISO-8859-1') === 0)) {
            $part->setCharset('windows-1252');
        }

        /* Don't download contents of entire body if ID == 0 (indicating the
         * body of the main multipart message).  I'm pretty sure we never
         * want to download the body of that part here. */
        if (!empty($id) &&
            !is_null($part) &&
            (substr($id, -2) != '.0') &&
            empty($options['nocontents']) &&
            $this->_mailbox &&
            !$part->getContents(array('stream' => true))) {
            $body = $this->getBodyPart($id, array('decode' => true, 'length' => empty($options['length']) ? null : $options['length'], 'stream' => true));
            $part->setContents($body, array('encoding' => $this->lastBodyPartDecode, 'usestream' => true));
        }

        return $part;
    }

    /**
     * Render a MIME Part.
     *
     * @param string $mime_id  The MIME ID to render.
     * @param integer $mode    One of the RENDER_ constants.
     * @param array $options   Additional options:
     * <pre>
     * 'mime_part' - (Horde_Mime_Part) The MIME part to render.
     * 'type' - (string) Use this MIME type instead of the MIME type
     *          identified in the MIME part.
     * </pre>
     *
     * @return array  See Horde_Mime_Viewer_Base::render(). The following
     *                fields may also be present in addition to the fields
     *                defined in Horde_Mime_Viewer_Base:
     *                'attach' - (boolean) Force display of this part as an
     *                           attachment.
     *                'js' - (array) A list of javascript commands to run
     *                       after the content is displayed on screen.
     *                'name' - (string) Contains the MIME name information.
     *                'wrap' - (string) If present, indicates that this
     *                         part, and all child parts, will be wrapped
     *                         in a DIV with the given class name.
     */
    public function renderMIMEPart($mime_id, $mode, $options = array())
    {
        $this->_buildMessage();

        $mime_part = empty($options['mime_part'])
            ? $this->getMIMEPart($mime_id)
            : $options['mime_part'];
        $type = empty($options['type'])
            ? null
            : $options['type'];

        $viewer = $GLOBALS['injector']->getInstance('IMP_Factory_MimeViewer')->create($mime_part, $this, $type);

        switch ($mode) {
        case self::RENDER_FULL:
            $textmode = 'full';
            break;

        case self::RENDER_INLINE:
        case self::RENDER_INLINE_AUTO:
        case self::RENDER_INLINE_DISP_NO:
            $textmode = 'inline';
            $limit = $viewer->getConfigParam('limit_inline_size');

            if ($limit && ($mime_part->getBytes() > $limit)) {
                $data = '';
                $status = array(
                    _("This message part cannot be viewed because it is too large."),
                    sprintf(_("Click %s to download the data."), $this->linkView($mime_part, 'download_attach', _("HERE")))
                );

                if (method_exists($viewer, 'overLimitText')) {
                    $data = $viewer->overLimitText();
                    $status[] = _("The initial portion of this text part is displayed below.");
                }

                return array(
                    $mime_id => array(
                        'data' => $data,
                        'name' => '',
                        'status' => array(
                            array(
                                'icon' => Horde::img('alerts/warning.png', _("Warning")),
                                'text' => $status
                            )
                        ),
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

        if (!is_null($ret[$mime_id]['data']) &&
            ($textmode == 'inline') &&
            !strlen($ret[$mime_id]['data']) &&
            $this->isAttachment($type)) {
            if (empty($ret[$mime_id]['status'])) {
                $ret[$mime_id]['status'] = array(
                    array(
                        'text' => array(
                            _("This part is empty.")
                        )
                    )
                );
            }
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
     * Generate the preview text.
     *
     * @return array  Array with the following keys:
     * <pre>
     * 'cut' - (boolean) Was the preview text cut?
     * 'text' - (string) The preview text.
     * </pre>
     */
    public function generatePreview()
    {
        // For preview generation, don't go through overhead of scanning for
        // embedded parts. Necessary evil, or else very large parts (e.g
        // 5 MB+ text parts) will take ages to scan.
        $oldbuild = $this->_build;
        $this->_build = true;
        $mimeid = $this->findBody();

        if (is_null($mimeid)) {
            $this->_build = $oldbuild;
            return array('cut' => false, 'text' => '');
        }

        $maxlen = empty($GLOBALS['conf']['msgcache']['preview_size'])
            ? $GLOBALS['prefs']->getValue('preview_maxlen')
            : $GLOBALS['conf']['msgcache']['preview_size'];

        // Retrieve 3x the size of $maxlen of bodytext data. This should
        // account for any content-encoding & HTML tags.
        $pmime = $this->getMIMEPart($mimeid, array('length' => $maxlen * 3));

        $ptext = Horde_String::convertCharset($pmime->getContents(), $pmime->getCharset(), 'UTF-8');

        if ($pmime->getType() == 'text/html') {
            $ptext = $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($ptext, 'Html2text');
        }

        $this->_build = $oldbuild;

        if (Horde_String::length($ptext) > $maxlen) {
            return array(
                'cut' => true,
                'text' => Horde_String::truncate($ptext, $maxlen)
            );
        }

        return array(
            'cut' => false,
            'text' => $ptext
        );
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
     *   Output: parts = 'icon'
     *
     * IMP_Contents::SUMMARY_DESCRIP_LINK
     * IMP_Contents::SUMMARY_DESCRIP_NOLINK
     * IMP_Contents::SUMMARY_DESCRIP_NOLINK_NOHTMLSPECCHARS
     *   Output: parts = 'description'
     *
     * IMP_Contents::SUMMARY_DOWNLOAD
     * IMP_Contents::SUMMARY_DOWNLOAD_NOJS
     *   Output: parts = 'download'
     *
     * IMP_Contents::SUMMARY_DOWNLOAD_ZIP
     *   Output: parts = 'download_zip'
     *
     * IMP_Contents::SUMMARY_IMAGE_SAVE
     *   Output: parts = 'img_save'
     *
     * IMP_Contents::SUMMARY_PRINT
     * IMP_Contents::SUMMARY_PRINT_STUB
     *   Output: parts = 'print'
     *
     * IMP_Contents::SUMMARY_STRIP_LINK
     * IMP_Contents::SUMMARY_STRIP_STUB
     *   Output: parts = 'strip'
     * </pre>
     *
     * @return array  An array with the requested information.
     */
    public function getSummary($id, $mask = 0)
    {
        $download_zip = (($mask & self::SUMMARY_DOWNLOAD_ZIP) && Horde_Util::extensionExists('zlib'));
        $param_array = array();

        $this->_buildMessage();

        $part = array(
            'bytes' => null,
            'download' => null,
            'download_zip' => null,
            'id' => $id,
            'img_save' => null,
            'size' => null,
            'strip' => null
        );

        $mime_part = $this->getMIMEPart($id, array('nocontents' => true));
        $mime_type = $mime_part->getType();

        /* If this is an attachment that has no specific MIME type info, see
         * if we can guess a rendering type. */
        if (in_array($mime_type, array('application/octet-stream', 'application/base64'))) {
            $mime_type = Horde_Mime_Magic::filenameToMIME($mime_part->getName());
            if ($mime_type != $mime_part->getType()) {
                $mime_part = clone $mime_part;
                $mime_part->setType($mime_type);
            }
            $param_array['ctype'] = $mime_type;
        }
        $part['type'] = $mime_type;

        /* Is this part an attachment? */
        $is_atc = $this->isAttachment($mime_type);

        /* Get bytes/size information. */
        if (($mask & self::SUMMARY_BYTES) ||
            $download_zip ||
            ($mask & self::SUMMARY_SIZE)) {
            $part['bytes'] = $size = $mime_part->getBytes(true);
            $part['size'] = ($size > 1048576)
                ? sprintf(_("%s MB"), number_format($size / 1048576, 1))
                : sprintf(_("%s KB"), max(round($size / 1024), 1));
        }

        /* Get part's icon. */
        $part['icon'] = ($mask & self::SUMMARY_ICON) ? Horde::img($GLOBALS['injector']->getInstance('Horde_Core_Factory_MimeViewer')->getIcon($mime_type), '', array('title' => $mime_type), '') : null;

        /* Get part's description. */
        $description = $this->getPartName($mime_part, true);

        if ($mask & self::SUMMARY_DESCRIP_LINK) {
            $part['description'] = $this->canDisplay($mime_part, self::RENDER_FULL)
                ? $this->linkViewJS($mime_part, 'view_attach', htmlspecialchars($description), array('jstext' => sprintf(_("View %s"), $description), 'params' => $param_array))
                : htmlspecialchars($description);
        } elseif ($mask & self::SUMMARY_DESCRIP_NOLINK) {
            $part['description'] = htmlspecialchars($description);
        } elseif ($mask & self::SUMMARY_DESCRIP_NOLINK_NOHTMLSPECCHARS) {
            $part['description'] = $description;
        }

        /* Download column. */
        if ($is_atc &&
            (is_null($part['bytes']) || $part['bytes'])) {
            if ($mask & self::SUMMARY_DOWNLOAD) {
                $part['download'] = $this->linkView($mime_part, 'download_attach', '', array('class' => 'iconImg downloadAtc', 'dload' => true, 'jstext' => _("Download")));
            } elseif ($mask & self::SUMMARY_DOWNLOAD_NOJS) {
                $part['download'] = $this->urlView($mime_part, 'download_attach', array('dload' => true));
            }
        }

        /* Display the compressed download link only if size is greater
         * than 200 KB. */
        if ($is_atc &&
            $download_zip &&
            ($part['bytes'] > 204800)) {
            $viewer = $GLOBALS['injector']->getInstance('IMP_Factory_MimeViewer')->create($mime_part, $this, $mime_type);
            if (!$viewer->getMetadata('compressed')) {
                $part['download_zip'] = $this->linkView($mime_part, 'download_attach', null, array('class' => 'iconImg downloadZipAtc', 'dload' => true, 'jstext' => sprintf(_("Download %s in .zip Format"), $description), 'params' => array('zip' => 1)));
            }
        }

        /* Display the image save link if the required registry calls are
         * present. */
        if (($mask & self::SUMMARY_IMAGE_SAVE) &&
            $GLOBALS['registry']->hasMethod('images/selectGalleries') &&
            ($mime_part->getPrimaryType() == 'image')) {
            $part['img_save'] = Horde::link('#', _("Save Image in Gallery"), 'iconImg saveImgAtc', null, Horde::popupJs(Horde::url('saveimage.php'), array('params' => array('mbox' => $this->_mailbox, 'uid' => $this->_uid, 'id' => $id), 'height' => 200, 'width' => 450, 'urlencode' => true)) . 'return false;') . '</a>';
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
        if ((($mask & self::SUMMARY_STRIP_LINK) ||
             ($mask & self::SUMMARY_STRIP_STUB)) &&
            ($id != 0) &&
            (intval($id) != 1) &&
            (strpos($id, '.') === false)) {
            if ($mask & self::SUMMARY_STRIP_LINK) {
                $url = Horde::selfUrl(true)->remove(array('actionID', 'imapid', 'uid'))->add(array('actionID' => 'strip_attachment', 'imapid' => $id, 'uid' => $this->_uid, 'message_token' => $GLOBALS['injector']->getInstance('Horde_Token')->get('imp.impcontents')));
                $part['strip'] = Horde::link($url, _("Strip Attachment"), 'iconImg deleteImg', null, 'return window.confirm(' . Horde_Serialize::serialize(_("Are you sure you wish to PERMANENTLY delete this attachment?"), Horde_Serialize::JSON, 'UTF-8') . ')') . '</a>';
            } else {
                $part['strip'] = Horde::link('#', _("Strip Attachment"), 'iconImg deleteImg stripAtc', null, null, null, null, array('mimeid' => $id)) . '</a>';
            }
        }

        return $part;
    }

    /**
     * Return the URL to the view.php page.
     *
     * @param Horde_Mime_Part $mime_part  The MIME part to view.
     * @param integer $actionID           The actionID to perform.
     * @param array $options              Additional options:
     * <pre>
     * 'dload' - (boolean) Should we generate a download link?
     * 'params' - (array) A list of any additional parameters that need to be
     *            passed to view.php (key => name).
     * </pre>
     *
     * @return string  The URL to view.php.
     */
    public function urlView($mime_part = null, $actionID = 'view_attach',
                            $options = array())
    {
        $params = $this->_urlViewParams($mime_part, $actionID, isset($options['params']) ? $options['params'] : array());

        return empty($options['dload'])
            ? Horde::url('view.php', true)->add($params)
            : Horde::downloadUrl($mime_part->getName(true), $params);
    }

    /**
     * Generates the necessary URL parameters for the view.php page.
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

        if ($this->_mailbox) {
            $params['uid'] = $this->_uid;
            $params['mailbox'] = $this->_mailbox->form_to;
        }

        return $params;
    }

    /**
     * Generate a link to the view.php page.
     *
     * @param Horde_Mime_Part $mime_part  The MIME part to view.
     * @param integer $actionID           The actionID value.
     * @param string $text                The ESCAPED (!) link text.
     * @param array $options              Additional parameters:
     * <pre>
     * 'class' - (string) The CSS class to use.
     * 'dload' - (boolean) Should we generate a download link?
     * 'jstext' - (string) The JS text to use.
     * 'params' - (array) A list of any additional parameters that need to be
     *            passed to view.php.
     * </pre>
     *
     * @return string  A HTML href link to view.php.
     */
    public function linkView($mime_part, $actionID, $text, $options = array())
    {
        $options = array_merge(array(
            'class' => null,
            'jstext' => $text,
            'params' => array()
        ), $options);

        return Horde::link($this->urlView($mime_part, $actionID, $options), $options['jstext'], $options['class'], empty($options['dload']) ? null : 'view_' . hash('md5', $mime_part->getMIMEId() . $this->_mailbox . $this->_uid)) . $text . '</a>';
    }

    /**
     * Generate a javascript link to the view.php page.
     *
     * @param Horde_Mime_Part $mime_part  The MIME part to view.
     * @param string $actionID            The actionID to perform.
     * @param string $text                The ESCAPED (!) link text.
     * @param array $options              Additional options:
     * <pre>
     * 'css' - (string) The CSS class to use.
     * 'jstext' - (string) The javascript link text.
     * 'onload' - (string) A JS function to run when popup window is
     *            fully loaded.
     * 'params' - (array) A list of any additional parameters that need to be
     *            passed to view.php. (key = name)
     * 'widget' - (boolean) If true use Horde::widget() to generate,
     *            Horde::link() otherwise.
     * </pre>
     *
     * @return string  A HTML href link to view.php.
     */
    public function linkViewJS($mime_part, $actionID, $text,
                               $options = array())
    {
        if (empty($options['params'])) {
            $options['params'] = array();
        }

        if (empty($options['jstext'])) {
            $options['jstext'] = sprintf(_("View %s"), $mime_part->getDescription(true));
        }

        $url = Horde::popupJs(Horde::url('view.php'), array('menu' => true, 'onload' => empty($options['onload']) ? '' : $options['onload'], 'params' => $this->_urlViewParams($mime_part, $actionID, isset($options['params']) ? $options['params'] : array()), 'urlencode' => true));

        return empty($options['widget'])
            ? Horde::link('#', $options['jstext'], empty($options['css']) ? null : $options['css'], null, $url) . $text . '</a>'
            : Horde::widget('#', $options['jstext'], empty($options['css']) ? null : $options['css'], null, $url, $text);
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

        foreach ($parts as $id) {
            if (!is_null($last_id) &&
                (strpos($id, $last_id) === 0)) {
                continue;
            }

            $last_id = null;

            $mime_part = $this->getMIMEPart($id, array('nocontents' => true));
            $viewer = $GLOBALS['injector']->getInstance('IMP_Factory_MimeViewer')->create($mime_part, $this);
            if ($viewer->embeddedMimeParts()) {
                $mime_part = $this->getMIMEPart($id);
                $viewer->setMIMEPart($mime_part);
                $new_part = $viewer->getEmbeddedMimeParts();
                if (!is_null($new_part)) {
                    $this->_embedded[] = $id;
                    $mime_part->addPart($new_part);
                    $mime_part->buildMimeIds($id);
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
     * @param mixed $id      The MIME part or a MIME ID string.
     * @param integer $mask  One of the RENDER_ constants.
     * @param string $type   The type to use (overrides the MIME ID if $id is
     *                       a MIME part).
     *
     * @return integer  The RENDER_ constant of the allowable display.
     */
    public function canDisplay($part, $mask, $type = null)
    {
        if (!is_object($part)) {
            $part = $this->getMIMEPart($part, array('nocontents' => true));
        }
        $viewer = $GLOBALS['injector']->getInstance('IMP_Factory_MimeViewer')->create($part, $this, $type);

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
     * @return Horde_Tree  A tree instance representing the MIME part tree.
     * @throws Horde_Tree_Exception
     */
    public function getTree($renderer = 'Horde_Core_Tree_Html')
    {
        $tree = Horde_Tree::factory('mime-' . $this->_uid, $renderer);
        $this->_addTreeNodes($tree, $this->_message);
        return $tree;
    }

    /**
     * Adds MIME parts to the tree instance.
     *
     * @param Horde_Tree tree        A tree instance.
     * @param Horde_Mime_Part $part  The MIME part to add to the tree,
     *                               including its sub-parts.
     * @param string $parent         The parent part's MIME id.
     */
    protected function _addTreeNodes($tree, $part, $parent = null)
    {
        $viewer = $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_MimeViewer');
        $mimeid = $part->getMimeId();

        $line = $mimeid;
        if ($description = $part->getDescription(true)) {
            $line .= ' ' . $description;
        }
        $line .= ' [' . $part->getType(true) . ']';
        $tree->addNode($mimeid, $parent, $line);
        $tree->addNodeParams(
            $mimeid,
            array('icon' => $viewer->getIcon($part->getType())));

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
                    (strpos($val, 'multipart/') === false)) {
                    $part = $this->getMIMEPart($key);
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
            if (Horde_Mime::isChild($val, $mime_id)) {
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
        $id = Horde_Mime::mimeIdArithmetic($id, 'up');

        while (!is_null($id)) {
            if (($part = $this->getMIMEPart($id, array('nocontents' => true))) &&
                ($part->getType() == $type)) {
                return $part;
            }
            $id = Horde_Mime::mimeIdArithmetic($id, 'up');
        }

        return null;
    }

    /**
     * Return the descriptive part label, making sure it is not empty.
     *
     * @param Horde_Mime_Part $part            The MIME Part object.
     * @param boolean         $useDescription  Use description? If false, uses
     *                                         name.
     *
     * @return string  The part label (non-empty).
     */
    public function getPartName(Horde_Mime_Part $part, $useDescription = false)
    {
        $name = $useDescription
            ? $part->getDescription(true)
            : $part->getName(true);

        if ($name) {
            return $name;
        }

        $ptype = $part->getPrimaryType();
        switch ($ptype) {
        case 'multipart':
            if (($part->getSubType() == 'related') &&
                ($view_id = $part->getMetaData('viewable_part')) &&
                ($viewable = $this->getMIMEPart($view_id, array('nocontents' => true)))) {
                return $this->getPartName($viewable, $useDescription);
            }
            /* Fall-through. */

        case 'application':
            return sprintf(_("%s part"), ucfirst($part->getSubType()));

        default:
            return sprintf(_("%s part"), ucfirst($ptype));
        }
    }

    /**
     * Generate inline message display.
     *
     * @param array $options  Options:
     *   - display_mask: (integer) The mask of display view type to render
     *                   inline (DEFAULT: RENDER_INLINE_AUTO).
     *   - mask: (integer) The mask needed for a getSummary() call.
     *   - no_inline_all: (boolean) If true, only display first inline part.
     *                    Subsequent inline parts will be treated as
     *                    attachments.
     *   - part_info_display: (array) The list of summary fields to display.
     *   - show_parts: (string) The value of the 'parts_display' pref.
     *
     * @return array  An array with the following keys:
     *   - atc_parts: (array) The list of attachment MIME IDs.
     *   - display_ids: (array) The list of display MIME IDs.
     *   - js_onload: (array) A list of javascript code to run onload.
     *   - msgtext: (string) The rendered HTML code.
     */
    public function getInlineOutput(array $options = array())
    {
        $atc_parts = $display_ids = $js_onload = $wrap_ids = array();
        $msgtext = array();
        $parts_list = $this->getContentTypeMap();
        $text_out = '';

        $contents_mask = isset($options['mask'])
            ? $options['mask']
            : 0;
        $display_mask = isset($options['display_mask'])
            ? $options['display_mask']
            : IMP_Contents::RENDER_INLINE_AUTO;
        $no_inline_all = !empty($options['no_inline_all']);
        $part_info_display = isset($options['part_info_display'])
            ? $options['part_info_display']
            : array();
        $show_parts = isset($options['show_parts'])
            ? $options['show_parts']
            : $GLOBALS['prefs']->getValue('parts_display');

        foreach ($parts_list as $mime_id => $mime_type) {
            if (isset($display_ids[$mime_id]) ||
                isset($atc_parts[$mime_id])) {
                continue;
            }

            if (!($render_mode = $this->canDisplay($mime_id, $display_mask))) {
                if ($this->isAttachment($mime_type)) {
                    if ($show_parts == 'atc') {
                        $atc_parts[$mime_id] = 1;
                    }

                    if ($contents_mask && empty($info['nosummary'])) {
                        $msgtext[$mime_id] = array(
                            'text' => $this->_formatSummary($mime_id, $contents_mask, $part_info_display, true)
                        );
                    }
                }
                continue;
            }

            $render_part = $this->renderMIMEPart($mime_id, $render_mode);
            if (($show_parts == 'atc') &&
                $this->isAttachment($mime_type) &&
                (empty($render_part) ||
                 !($render_mode & IMP_Contents::RENDER_INLINE))) {
                $atc_parts[$mime_id] = 1;
            }

            if (empty($render_part)) {
                if ($contents_mask &&
                    empty($info['nosummary']) &&
                    $this->isAttachment($mime_type)) {
                    $msgtext[$mime_id] = array(
                        'text' => $this->_formatSummary($mime_id, $contents_mask, $part_info_display, true)
                    );
                }
                continue;
            }

            reset($render_part);
            while (list($id, $info) = each($render_part)) {
                $display_ids[$id] = 1;

                if (empty($info)) {
                    continue;
                }

                if ($no_inline_all === 1) {
                    $atc_parts[$id] = 1;
                    continue;
                }

                $part_text = '';

                if (empty($info['attach'])) {
                    if ($contents_mask) {
                        if (empty($info['nosummary'])) {
                            $part_text .= $this->_formatSummary($id, $contents_mask, $part_info_display);
                        }

                        $part_text .= $this->formatStatusMsg($info['status']) .
                            '<div class="mimePartData">' . $info['data'] . '</div>';
                    } else {
                        if ($part_text && !empty($options['sep'])) {
                            $part_text .= $options['sep'];
                        }
                        $part_text .= $info['data'];
                    }
                } else {
                    if ($show_parts == 'atc') {
                        $atc_parts[$id] = 1;
                    }

                    if ($contents_mask && empty($info['nosummary'])) {
                        $part_text .= $this->_formatSummary($id, $contents_mask, $part_info_display, true);
                    }
                }

                $msgtext[$id] = array(
                    'text' => $part_text,
                    'wrap' => empty($info['wrap']) ? null : $info['wrap']
                );

                if (isset($info['js'])) {
                    $js_onload = array_merge($js_onload, $info['js']);
                }

                if ($no_inline_all) {
                    $no_inline_all = 1;
                }
            }
        }

        if (!empty($msgtext)) {
            uksort($msgtext, 'strnatcmp');
        }

        reset($msgtext);
        while (list($id, $part) = each($msgtext)) {
            while (count($wrap_ids) &&
                   !Horde_Mime::isChild(end($wrap_ids), $id)) {
                array_pop($wrap_ids);
                $text_out .= '</div>';
            }

            if (!empty($part['wrap'])) {
                $text_out .= '<div class="' . $part['wrap'] . '">';
                $wrap_ids[] = $id;
            }

            $text_out .= $part['text'];
        }

        $text_out .= str_repeat('</div>', count($wrap_ids));

        if (!strlen($text_out)) {
            $text_out = $this->formatStatusMsg(array(array('text' => array(_("There are no parts that can be shown inline.")))));
        }

        $atc_parts = ($show_parts == 'all')
            ? array_keys($display_ids)
            : array_keys($atc_parts);

        return array(
            'atc_parts' => $atc_parts,
            'display_ids' => array_keys($display_ids),
            'js_onload' => $js_onload,
            'msgtext' => $text_out
        );
    }

    /**
     * Prints out a MIME status message (in HTML).
     *
     * @param array $data  An array of information (as returned from
                           Horde_Mime_Viewer_Base::render()).
     *
     * @return string  The formatted status message string.
     */
    public function formatStatusMsg($data)
    {
        $out = '';

        foreach ($data as $val) {
            if (empty($val)) {
                continue;
            }

            $out .= '<div><table class="mimeStatusMessageTable"' . (isset($val['id']) ? (' id="' . $val['id'] . '" ') : '') . '>';

            /* If no image, simply print out the message. */
            if (empty($val['icon'])) {
                foreach ($val['text'] as $val) {
                    $out .= '<tr><td>' . $val . '</td></tr>';
                }
            } else {
                $out .= '<tr><td class="mimeStatusIcon">' . $val['icon'] . '</td><td><table>';
                foreach ($val['text'] as $val) {
                    $out .= '<tr><td>' . $val . '</td></tr>';
                }
                $out .= '</table></td></tr>';
            }

            $out .= '</table></div>';
        }

        return $out
            ? '<div class="mimeStatusMessage">' . $out . '</div>'
            : '';
    }

    /**
     * Prints out a MIME summary (in HTML).
     *
     * @param string $id      The MIME ID.
     * @param integer $mask   A bitmask indicating what summary information to
     *                        return.
     * @param array $display  The fields to display (in this order).
     * @param boolean $atc    Is this an attachment?
     *
     * @return string  The formatted summary string.
     */
    protected function _formatSummary($id, $mask, $display, $atc = false)
    {
        $summary = $this->getSummary($id, $mask);
        $tmp_summary = array();

        foreach ($display as $val) {
            if (isset($summary[$val])) {
                switch ($val) {
                case 'description':
                    $summary[$val] = '<span class="mimePartInfoDescrip">' . $summary[$val] . '</span>';
                    break;

                case 'size':
                    $summary[$val] = '<span class="mimePartInfoSize">(' . $summary[$val] . ')</span>';
                    break;
                }
                $tmp_summary[] = $summary[$val];
            }
        }

        return '<div class="mimePartInfo' .
            ($atc ? ' mimePartInfoAtc' : '') .
            '"><div>' .
            implode(' ', $tmp_summary) .
            '</div></div>';
    }


}
