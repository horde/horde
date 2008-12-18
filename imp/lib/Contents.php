<?php
/**
 * The IMP_Contents:: class contains all functions related to handling the
 * content and output of mail messages in IMP.
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
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
    const SUMMARY_DOWNLOAD_ZIP = 128;
    const SUMMARY_IMAGE_SAVE = 256;
    const SUMMARY_STRIP_LINK = 512;

    /* Rendering mask entries. */
    const RENDER_FULL = 1;
    const RENDER_INLINE = 2;
    const RENDER_INLINE_DISP_NO = 4;
    const RENDER_INFO = 8;
    const RENDER_INLINE_AUTO = 16;

    /**
     * The IMAP index of the message.
     *
     * @var integer
     */
    protected $_index = null;

    /**
     * The mailbox of the current message.
     *
     * @var string
     */
    protected $_mailbox = null;

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
     * The status cache.
     * NOT CURRENTLY USED
     *
     * @var array
     */
    protected $_statuscache = array();

    /**
     * Attempts to return a reference to a concrete IMP_Contents instance.
     * If an IMP_Contents object is currently stored in the local cache,
     * recreate that object.  Else, create a new instance.
     * Ensures that only one IMP_Contents instance for any given message is
     * available at any one time.
     *
     * This method must be invoked as:
     *   $imp_contents = &IMP_Contents::singleton($in);
     *
     * @param mixed $in  Either an index string (see IMP_Contents::singleton()
     *                   for the format) or a Horde_Mime_Part object.
     *
     * @return IMP_Contents  The IMP_Contents object or null.
     */
    static public function &singleton($in)
    {
        static $instance = array();

        $sig = is_a($in, 'Horde_Mime_Part')
            ? md5(serialize($in))
            : $in;

        if (empty($instance[$sig])) {
            $instance[$sig] = new IMP_Contents($in);
        }

        return $instance[$sig];
    }

    /**
     * Constructor.
     *
     * @param mixed $in  Either an index string (see IMP_Contents::singleton()
     *                   for the format) or a Horde_Mime_Part object.
     */
    function __construct($in)
    {
        if (is_a($in, 'Horde_Mime_Part')) {
            $this->_message = $in;
        } else {
            list($this->_index, $this->_mailbox) = explode(IMP::IDX_SEP, $in);

            /* Get the Horde_Mime_Part object for the given index. */
            try {
                $ret = $GLOBALS['imp_imap']->ob->fetch($this->_mailbox, array(
                    Horde_Imap_Client::FETCH_STRUCTURE => array('parse' => true)
                ), array('ids' => array($this->_index)));
                $this->_message = $ret[$this->_index]['structure'];
            } catch (Horde_Imap_Client_Exception $e) {
                return PEAR::raiseError('Error displaying message.');
            }
        }
    }

    /**
     * Returns the IMAP index for the current message.
     *
     * @return integer  The message index.
     */
    public function getIndex()
    {
        return $this->_index;
    }

    /**
     * Returns the IMAP mailbox for the current message.
     *
     * @return string  The message mailbox.
     */
    public function getMailbox()
    {
        return $this->_mailbox;
    }

    /**
     * Returns the entire body of the message.
     *
     * @return string  The text of the body of the message.
     */
    public function getBody()
    {
        if (is_null($this->_mailbox)) {
            return $this->_message->toString();
        }

        try {
            $res = $GLOBALS['imp_imap']->ob->fetch($this->_mailbox, array(
                Horde_Imap_Client::FETCH_BODYTEXT => array(array('peek' => true))
            ), array('ids' => array($this->_index)));
            return $res[$this->_index]['bodytext'][0];
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
     * 'mimeheaders' - (boolean) Include the MIME headers also?
     *                 DEFAULT: No
     * </pre>
     *
     * @return string  The text of the part.
     */
    public function getBodyPart($id, $options = array())
    {
        if (is_null($this->_mailbox)) {
            // TODO: Include MIME headers?
            $ob = $this->getMIMEPart($id, array('nocontents' => true));
            return is_null($ob)
                ? ''
                : $ob->getContents();
        }

        $query = array(
            Horde_Imap_Client::FETCH_BODYPART => array(array('id' => $id, 'peek' => true))
        );
        if (!empty($options['mimeheaders'])) {
            $query[Horde_Imap_Client::FETCH_MIMEHEADER] = array(array('id' => $id, 'peek' => true));
        }

        try {
            $res = $GLOBALS['imp_imap']->ob->fetch($this->_mailbox, $query, array('ids' => array($this->_index)));
            return empty($options['mimeheaders'])
                ? $res[$this->_index]['bodypart'][$id]
                : $res[$this->_index]['mimeheader'][$id] . $res[$this->_index]['bodypart'][$id];
        } catch (Horde_Imap_Client_Exception $e) {
            return '';
        }
    }

    /**
     * Returns the full message text.
     *
     * @return string  The full message text.
     */
    public function fullMessageText()
    {
        if (is_null($this->_mailbox)) {
            return $this->_message->toString(true);
        }

        try {
            $res = $GLOBALS['imp_imap']->ob->fetch($this->_mailbox, array(
                Horde_Imap_Client::FETCH_HEADERTEXT => array(array('peek' => true)),
                Horde_Imap_Client::FETCH_BODYTEXT => array(array('peek' => true))
            ), array('ids' => array($this->_index)));
            return $res[$this->_index]['headertext'][0] . $res[$this->_index]['bodytext'][0];
        } catch (Horde_Imap_Client_Exception $e) {
            return '';
        }
    }

    /**
     * Returns the header object.
     *
     * @return Horde_Mime_Headers  The Horde_Mime_Headers object.
     */
    public function getHeaderOb()
    {
        if (is_null($this->_message)) {
            return $this->_message->getMIMEHeaders();
        }

        try {
            $res = $GLOBALS['imp_imap']->ob->fetch($this->_mailbox, array(
                Horde_Imap_Client::FETCH_HEADERTEXT => array(array('parse' => true, 'peek' => true))
            ), array('ids' => array($this->_index)));
            return $res[$this->_index]['headertext'][0];
        } catch (Horde_Imap_Client_Exception $e) {
            return new Horde_Mime_Headers();
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
     * 'nocontents' - (boolean) TODO
     *              DEFAULT: TODO
     * 'nodecode' - (boolean) TODO
     *            DEFAULT: TODO
     * </pre>
     *
     * @return Horde_Mime_Part  The raw MIME part asked for (reference).
     */
    public function &getMIMEPart($id, $options = array())
    {
        $this->_buildMessage();

        $part = $this->_message->getPart($id);

        if (!is_null($part) &&
            empty($options['nocontents']) &&
            !is_null($this->_mailbox) &&
            !$part->getContents()) {
            $contents = $this->getBodyPart($id);
            if (($part->getPrimaryType() == 'text') &&
                (String::upper($part->getCharset()) == 'US-ASCII') &&
                Horde_Mime::is8bit($contents)) {
                $contents = String::convertCharset($contents, 'US-ASCII');
            }
            $part->setContents($contents);

            if (empty($options['nodecode'])) {
                $part->transferDecodeContents();
            }
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
     * 'params' - (array) Additional params to set.
     * 'type' - (string) Use this MIME type instead of the MIME type
     *          identified in the MIME part.
     * </pre>
     *
     * @return array  See Horde_Mime_Viewer_Driver::render(). Additionally,
     *                a entry in the base array labeled 'name' will be present
     *                which contains the MIME name information.
     */
    public function renderMIMEPart($mime_id, $mode, $options = array())
    {
        $this->_buildMessage();

        $mime_part = empty($options['mime_part'])
            ? $this->getMIMEPart($mime_id)
            : $options['mime_part'];
        $viewer = Horde_Mime_Viewer::factory($mime_part, empty($options['type']) ? null : $options['type']);
        $viewer->setParams(array('contents' => &$this));
        if (!empty($options['params'])) {
            $viewer->setParams($options['params']);
        }

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
                return array(
                    $mime_id => array(
                        'data' => '',
                        'name' => '',
                        'status' => array(
                            array(
                                'text' => array(
                                    _("This message part cannot be viewed because it is too large."),
                                    sprintf(_("Click %s to download the data."), $this->linkView($mime_part, 'download_attach', _("HERE")))
                                )
                            )
                        ),
                        'type' => 'text/html; charset=' . NLS::getCharset()
                    )
                );
            }
            break;

        case self::RENDER_INFO:
            $textmode = 'info';
            break;
        }

        $ret = $viewer->render($textmode);

        if (empty($ret)) {
            return ($mode == self::RENDER_INLINE_AUTO)
                ? $this->renderMIMEPart($mime_id, self::RENDER_INLINE_INFO, $options)
                : array();
        }

        if (!empty($ret[$mime_id]) && !isset($ret[$mime_id]['name'])) {
            $ret[$mime_id]['name'] = $mime_part->getName(true);
        }

        if (isset($this->_statuscache[$mime_id])) {
            $ret[$mime_id]['status'] = array_merge($this->_statuscache[$mime_id], $ret[$mime_id]['status']);
        }

        /* If this is a text/* part, AND the browser does not support UTF-8,
         * give the user a link to open the part in a new window with the
         * correct character set. */
        if (($mode != 'full') && ($mime_part->getPrimaryType() == 'text')) {
            $default_charset = String::upper(NLS::getCharset());
            if ($default_charset !== 'UTF-8') {
                $charset_upper = String::upper($mime_part->getCharset());
                if (($charset_upper != 'US-ASCII') &&
                    ($charset_upper != $default_charset)) {
                    $ret['status'][] = array(
                        'text' => array(
                            sprintf(_("This message was written in a character set (%s) other than your own."), htmlspecialchars($charset_upper)),
                            sprintf(_("If it is not displayed correctly, %s to open it in a new window."), $this->linkViewJS($mime_part, 'view_attach', _("click here")))
                        )
                    );
                }
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
        if (($mimeid = $this->findBody()) === null) {
            return array('cut' => false, 'text' => '');
        }

        $pmime = $this->getMIMEPart($mimeid);
        $ptext = $pmime->getContents();
        $ptext = String::convertCharset($ptext, $pmime->getCharset());
        if ($pmime->getType() == 'text/html') {
            require_once 'Horde/Text/Filter.php';
            $ptext = Text_Filter::filter($ptext, 'html2text',
                                         array('charset' => NLS::getCharset()));
        }

        $maxlen = empty($GLOBALS['conf']['msgcache']['preview_size'])
            ? $GLOBALS['prefs']->getValue('preview_maxlen')
            : $GLOBALS['conf']['msgcache']['preview_size'];

        if (String::length($ptext) > $maxlen) {
            $ptext = String::substr($ptext, 0, $maxlen) . ' ...';
            $cut = true;
        } else {
            $cut = false;
        }

        return array('cut' => $cut, 'text' => $ptext);
    }

    /**
     * Get summary info for a MIME ID.
     *
     * @param string $id     The MIME ID.
     * @param integer $mask  A bitmask indicating what information to return:
     * <pre>
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
     *   Output: parts = 'download'
     *
     * IMP_Contents::SUMMARY_DOWNLOAD_ZIP
     *   Output: parts = 'download_zip'
     *
     * IMP_Contents::SUMMARY_IMAGE_SAVE
     *   Output: parts = 'img_save'
     *
     * IMP_Contents::SUMMARY_STRIP_LINK
     *   Output: parts = 'strip'
     * </pre>
     *
     * @return array  An array with the requested information.
     */
    public function getSummary($id, $mask = 0)
    {
        $download_zip = (($mask & self::SUMMARY_DOWNLOAD_ZIP) && Util::extensionExists('zlib'));
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
            $param_array['ctype'] = $mime_type;
        }
        $part['type'] = $mime_type;

        /* Is this part an attachment? */
        $is_atc = $this->isAttachment($mime_type);

        /* Get bytes/size information. */
        if (($mask & self::SUMMARY_BYTES) ||
            $download_zip ||
            ($mask & self::SUMMARY_SIZE)) {
            $part['bytes'] = $mime_part->getBytes();

            if ($part['bytes'] &&
                ($mime_part->getCurrentEncoding() == 'base64')) {
                /* From RFC 2045 [6.8]: "...the encoded data are consistently
                 * only about 33 percent larger than the unencoded data."
                 * Thus, adding 33% to the byte size is a good estimate for
                 * our purposes. */
                $size = number_format(max((($part['bytes'] * 0.75) / 1024), 1));
            } else {
                $size = $mime_part->getSize(true);
            }
            $part['size'] = ($size > 1024)
                ? sprintf(_("%s MB"), number_format(max(($size / 1024), 1)))
                : sprintf(_("%s KB"), $size);
        }

        /* Get part's icon. */
        $part['icon'] = ($mask & self::SUMMARY_ICON) ? Horde::img(Horde_Mime_Viewer::getIcon($mime_type), '', array('title' => $mime_type), '') : null;

        /* Get part's description. */
        $description = $mime_part->getDescription(true);
        if (empty($description)) {
            $description = _("unnamed");
        }

        if ($mask & self::SUMMARY_DESCRIP_LINK) {
            $part['description'] = $this->canDisplay($id, self::RENDER_FULL)
                ? $this->linkViewJS($mime_part, 'view_attach', htmlspecialchars($description), array('jstext' => sprintf(_("View %s"), $description), 'params' => $param_array))
                : htmlspecialchars($description);
        } elseif ($mask & self::SUMMARY_DESCRIP_NOLINK) {
            $part['description'] = htmlspecialchars($description);
        } elseif ($mask & self::SUMMARY_DESCRIP_NOLINK_NOHTMLSPECCHARS) {
            $part['description'] = $description;
        }

        /* Download column. */
        if ($is_atc &&
            ($mask & self::SUMMARY_DOWNLOAD) &&
            (is_null($part['bytes']) || $part['bytes'])) {
            $part['download'] = $this->linkView($mime_part, 'download_attach', '', array('class' => 'downloadAtc', 'dload' => true, 'jstext' => _("Download")));
        }

        /* Display the compressed download link only if size is greater
         * than 200 KB. */
        if ($is_atc &&
            $download_zip &&
            ($part['bytes'] > 204800) &&
            !in_array($mime_type, array('application/zip', 'application/x-zip-compressed'))) {
            $part['download_zip'] = $this->linkView($mime_part, 'download_attach', null, array('class' => 'downloadZipAtc', 'dload' => true, 'jstext' => sprintf(_("Download %s in .zip Format"), $mime_part->getDescription(true)), 'params' => array('zip' => 1)));
        }

        /* Display the image save link if the required registry calls are
         * present. */
        if (($mask && self::SUMMARY_IMAGE_SAVE) &&
            $GLOBALS['registry']->hasMethod('images/selectGalleries') &&
            ($mime_part->getPrimaryType() == 'image')) {
            $part['img_save'] = Horde::link('#', _("Save Image in Gallery"), 'saveImgAtc', null, IMP::popupIMPString('saveimage.php', array('index' => ($this->_index . IMP::IDX_SEP . $this->_mailbox), 'id' => $id), 450, 200) . "return false;") . '</a>';
        }

        /* Strip the Attachment? */
        if (($mask & self::SUMMARY_STRIP_LINK) &&
            !$this->isParent($id, 'message/rfc822')) {
            $url = Util::removeParameter(Horde::selfUrl(true), array('actionID', 'imapid', 'index'));
            $url = Util::addParameter($url, array('actionID' => 'strip_attachment', 'imapid' => $id, 'index' => $this->_index, 'message_token' => IMP::getRequestToken('imp.impcontents')));
            $part['strip'] = Horde::link($url, _("Strip Attachment"), 'stripAtc', null, "return window.confirm('" . addslashes(_("Are you sure you wish to PERMANENTLY delete this attachment?")) . "');") . '</a>';
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
     *            passed to view.php (key = name).
     * </pre>
     *
     * @return string  The URL to view.php.
     */
    public function urlView($mime_part, $actionID, $options = array())
    {
        $params = $this->_urlViewParams($mime_part, $actionID, isset($options['params']) ? $options['params'] : array());

        return empty($options['dload'])
            ? Util::addParameter(Horde::applicationUrl('view.php'), $params)
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

        if (!is_null($this->_mailbox)) {
            $params['index'] = $this->_index;
            $params['mailbox'] = $this->_mailbox;
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
     *              passed to view.php.
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

        return Horde::link($this->urlView($mime_part, $actionID, $options), $options['jstext'], $options['class'], empty($options['dload']) ? null : 'view_' . md5($mime_part->getMIMEId() . $this->_mailbox . $this->_index)) . $text . '</a>';
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

        $url = IMP::popupIMPString('view.php', $this->_urlViewParams($mime_part, $actionID, isset($options['params']) ? $options['params'] : array())) . 'return false;';

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
            $viewer = Horde_Mime_Viewer::factory($mime_part);
            if ($viewer->embeddedMimeParts()) {
                $mime_part = $this->getMIMEPart($id);
                $viewer->setMIMEPart($mime_part);
                $viewer->setParams(array('contents' => &$this));
                $new_parts = $viewer->getEmbeddedMimeParts();
                if (!is_null($new_parts)) {
                    foreach (array_keys($new_parts) as $key) {
                        if ($first_id === $key) {
                            $this->_message = $new_parts[$key];
                            $this->_build = false;
                            return $this->_buildMessage();
                        }

                        $this->_message->alterPart($key, $new_parts[$key]);
                        $to_process = array_merge($to_process, array_keys($new_parts[$key]->contentTypeMap()));
                    }
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
     * @return boolean  True if the part can be displayed.
     */
    public function canDisplay($part, $mask, $type = null)
    {
        if (!is_object($part)) {
            $part = $this->getMIMEPart($part, array('nocontents' => true));
        }
        $viewer = Horde_Mime_Viewer::factory($part, $type);

        if ($mask & self::RENDER_INLINE_AUTO) {
            $mask |= self::RENDER_INLINE | self::RENDER_INFO;
        }

        if (($mask & self::RENDER_FULL) && $viewer->canRender('full')) {
            return self::RENDER_FULL;
        }

        $inline = null;
        if (($mask & self::RENDER_INLINE) &&
            ($inline = $viewer->canRender('inline'))) {
            return self::RENDER_INLINE;
        }

        if (($mask & self::RENDER_INLINE_DISP_NO) &&
            (($inline === true) ||
             (is_null($inline) && $viewer->canRender('inline')))) {
            return self::RENDER_INLINE_DISP_NO;
        }

        if (($mask & self::RENDER_INFO) && $viewer->canRender('info')) {
            return self::RENDER_INFO;
        }

        return 0;
    }

    /**
     * Given a MIME ID, determines if the given MIME type is a parent.
     *
     * @param string $id    The MIME ID string.
     * @param string $type  The MIME type to search for.
     *
     * @return boolean  True if the MIME type is a parent.
     */
    public function isParent($id, $type)
    {
        $cmap = $this->getContentTypeMap();
        while (($id = Horde_Mime::mimeIdArithmetic($id, 'up')) !== null) {
            return isset($cmap[$id]) && ($cmap[$id] == $type);
        }
        return false;
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
     * Sets additional status information for a part.
     * NOT CURRENTLY USED
     *
     * @param string $id    The MIME ID
     * @param array $entry  The status entry.
     */
    public function setStatusCache($id, $entry)
    {
        $this->_statuscache[$id][] = $entry;
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
}
