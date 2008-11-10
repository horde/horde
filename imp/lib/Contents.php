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
     * The Horde_Mime_Message object for the message.
     *
     * @var Horde_Mime_Message
     */
    protected $_message;

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
     *                   for the format) or a Horde_Mime_Message object.
     *
     * @return IMP_Contents  The IMP_Contents object or null.
     */
    static public function &singleton($in)
    {
        static $instance = array();

        if (is_a($in, 'Horde_Mime_Message')) {
            $sig = md5(serialize($in));
        } else {
            $sig = $in;
        }

        if (empty($instance[$sig])) {
            $instance[$sig] = new IMP_Contents($in);
        }

        return $instance[$sig];
    }

    /**
     * Constructor.
     *
     * @param mixed $in  Either an index string (see IMP_Contents::singleton()
     *                   for the format) or a Horde_Mime_Message object.
     */
    function __construct($in)
    {
        if (is_a($in, 'Horde_Mime_Message')) {
            $this->_message = $in;
        } else {
            list($this->_index, $this->_mailbox) = explode(IMP::IDX_SEP, $in);

            /* Get the Horde_Mime_Message object for the given index. */
            try {
                $ret = $GLOBALS['imp_imap']->ob->fetch($this->_mailbox, array(
                    Horde_Imap_Client::FETCH_STRUCTURE => array('parse' => true)
                ), array('ids' => array($this->_index)));
                $this->_message = $ret[$this->_index]['structure'];
            } catch (Horde_Imap_Client_Exception $e) {
                $GLOBALS['imp_imap']->logException($e);
                return PEAR::raiseError('Error displaying message.');
            }
        }
    }

    /**
     * Returns the IMAP index for the current message.
     *
     * @return integer  The message index.
     */
    public function getMessageIndex()
    {
        return $this->_index;
    }

    /**
     * Returns the IMAP mailbox for the current message.
     *
     * @return string  The message mailbox.
     */
    public function getMessageMailbox()
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
            $GLOBALS['imp_imap']->logException($e);
            return '';
        }
    }

    /**
     * Gets the raw text for one section of the message.
     *
     * @param integer $id  The ID of the MIME part.
     *
     * @return string  The text of the part.
     */
    public function getBodyPart($id)
    {
        if (is_null($this->_mailbox)) {
            $ob = $this->getMIMEPart($id, array('nocontents' => true, 'nodecode' => true));
            return is_null($ob)
                ? ''
                : $ob->getContents();
        }

        try {
            $res = $GLOBALS['imp_imap']->ob->fetch($this->_mailbox, array(
                Horde_Imap_Client::FETCH_BODYPART => array(array('id' => $id, 'peek' => true))
            ), array('ids' => array($this->_index)));
            return $res[$this->_index]['bodypart'][$id];
        } catch (Horde_Imap_Client_Exception $e) {
            $GLOBALS['imp_imap']->logException($e);
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
            return $res[$this->_index]['headertext'][0] . "\r\n\r\n" . $res[$this->_index]['bodytext'][0];
        } catch (Horde_Imap_Client_Exception $e) {
            $GLOBALS['imp_imap']->logException($e);
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
            $GLOBALS['imp_imap']->logException($e);
            return new Horde_Mime_Headers();
        }
    }

    /**
     * Returns the Horde_Mime_Message object.
     *
     * @return Horde_Mime_Message  A Horde_Mime_Message object.
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
        $part = $this->_message->getPart($id);

        if (!is_null($part)) {
            if (empty($options['nocontents']) &&
                !is_null($this->_mailbox) &&
                !$part->getContents()) {
                $contents = $this->getBodyPart($id);
                if (($part->getPrimaryType() == 'text') &&
                    (String::upper($part->getCharset()) == 'US-ASCII') &&
                    Horde_Mime::is8bit($contents)) {
                    $contents = String::convertCharset($contents, 'US-ASCII');
                }
                $part->setContents($contents);
            }
            if (empty($options['nodecode'])) {
                $part->transferDecodeContents();
            }
        }

        return $part;
    }

    /**
     * Render a MIME Part.
     *
     * @param string $mime_id
     * @param array $options
     *
     * @return
     */
    public function renderMIMEPart($mime_id, $options = array())
    {
        $mime_part = $this->getMIMEPart($mime_id);
        $viewer = Horde_Mime_Viewer::factory(empty($options['type']) ? $mime_part->getType() : $options['type']);
        $viewer->setMIMEPart($mime_part);
        $viewer->setParams(array('contents' => &$this));

        switch($options['format']) {
        case 'inline':
            $data = $viewer->renderInline();
            break;

        case 'info':
            $data = $viewer->renderInfo();
            break;

        case 'full':
            $data = $viewer->render();
            return array('data' => $data['data'], 'name' => $mime_part->getName(true), 'type' => $data['type']);
            break;
        }

        return array('data' => $data, 'name' => $mime_part->getName(true), 'type' => null);
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
        foreach ($this->_message->contentTypeMap() as $key => $val) {
            if ((strpos($val, 'text/') === 0) &&
                (intval($key) == 1) &&
                (is_null($subtype) || (substr($val, 5) == $subtype))) {
                return $key;
            }
        }

        return null;
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
     * Get message summary info.
     *
     * @param array $options  Additional options:
     * <pre>
     * 'show_links' - (boolean)
     * 'strip' - (boolean)
     * </pre>
     *
     * @return array  The following fields:
     * <pre>
     * 'description' - (string) The part description.
     * 'icon' - (string) The icon.
     * 'type' - (string) The MIME type.
     * </pre>
     */
    public function getSummary($options = array())
    {
        $msg = array(
            'download_all' => array(),
            'has_download_link' => false,
            'has_img_save' => false,
            'has_strip' => false,
            'has_zip' => false,
        );
        $ret = array();
        $slinks = !empty($options['show_links']);

        foreach ($this->_message->contentTypeMap() as $mime_id => $mime_type) {
            if ($slinks &&
                in_array($mime_type, array('application/octet-stream', 'application/base64'))) {
                $mime_type = Horde_Mime_Magic::filenameToMIME($mime_part->getName());
                $param_array['ctype'] = $mime_type;
            } else {
                $param_array = array();
            }

            $mime_part = $this->getMIMEPart($mime_id, array('nocontents' => true, 'nodecode' => true));

            $bytes = $mime_part->getBytes();
            $icon = Horde::img(Horde_Mime_Viewer::getIcon($mime_type), '', array('title' => $mime_type));

            $description = $mime_part->getDescription(true);
            if (empty($description)) {
                $description = _("unnamed");
            }

            if ($slinks) {
                $descrip = $this->linkViewJS($mime_part, 'view_attach', htmlspecialchars($description), array('jstext' => sprintf(_("View %s [%s]"), $description, $mime_type), 'params' => $param_array));
            } else {
                $descrip = htmlspecialchars($description);
            }

            if (!empty($bytes) &&
                ($mime_part->getCurrentEncoding() == 'base64')) {
                /* From RFC 2045 [6.8]: "...the encoded data are consistently
                 * only about 33 percent larger than the unencoded data." */
                $size = number_format(max((($bytes * 0.75) / 1024), 1));
            } else {
                $size = $mime_part->getSize(true);
            }
            $size = ($size > 1024)
                ? sprintf(_("%s MB"), number_format(max(($size / 1024), 1)))
                : sprintf(_("%s KB"), $size);

            /* Download column. */
            if ($slinks && $bytes) {
                $download_link = $this->linkView($mime_part, 'download_attach', '', array('class' => 'download', 'dload' => true, 'jstext' => sprintf(_("Download %s"), $description)));
                $msg['has_download_link'] = true;
            } else {
                $download_link = null;
            }

            /* Display the compressed download link only if size is greater
             * than 200 KB. */
            if ($slinks &&
                ($mime_part->getBytes() > 204800) &&
                Util::extensionExists('zlib') &&
                !in_array($mime_type, array('application/zip', 'application/x-zip-compressed'))) {
                $zip = $this->linkView($mime_part, 'download_attach', Horde::img('compressed.png', _("Download in .zip Format"), null, $GLOBALS['registry']->getImageDir('horde') . '/mime'), array('dload' => true, 'jstext' => sprintf(_("Download %s in .zip Format"), $mime_part->getDescription(true)), 'params' => array('zip' => 1)));
                $msg['has_zip'] = true;
            } else {
                $zip = null;
            }

            /* Display the image save link if the required registry calls are
             * present. */
            if ($slinks &&
                ($mime_part->getPrimaryType() == 'image') &&
                $GLOBALS['registry']->hasMethod('images/selectGalleries') &&
                ($image_app = $GLOBALS['registry']->hasMethod('images/saveImage'))) {
                if (!$msg['has_img_save']) {
                    Horde::addScriptFile('prototype.js', 'horde', true);
                    Horde::addScriptFile('popup.js', 'imp', true);
                    $msg['has_img_save'] = true;
                }
                $img_save = Horde::link('#', _("Save Image in Gallery"), null, null, IMP::popupIMPString('saveimage.php', array('index' => ($this->_index . IMP::IDX_SEP . $this->_mailbox), 'id' => $mime_id), 450, 200) . "return false;") . '<img src="' . $GLOBALS['registry']->get('icon', $image_app) . '" alt="' . _("Save Image in Gallery") . '" title="' . _("Save Image in Gallery") . '" /></a>';
            } else {
                $img_save = null;
            }

            /* Strip the Attachment? */
            if ($slinks && !empty($options['strip'])) {
                // TODO: No stripping of RFC822 part.
                $url = Util::removeParameter(Horde::selfUrl(true), array('actionID', 'imapid', 'index'));
                $url = Util::addParameter($url, array('actionID' => 'strip_attachment', 'imapid' => $mime_id, 'index' => $this->_index, 'message_token' => $options['message_token']));
                $strip = Horde::link($url, _("Strip Attachment"), null, null, "return window.confirm('" . addslashes(_("Are you sure you wish to PERMANENTLY delete this attachment?")) . "');") . Horde::img('delete.png', _("Strip Attachment"), null, $GLOBALS['registry']->getImageDir('horde')) . '</a>';
                $msg['has_strip'] = true;
            } else {
                $strip = null;
            }

            if ($download = $this->isDownloadable($mime_part)) {
                $msg['download_all'][] = $mime_id;
            }

            $ret[$mime_id] = array(
                'description' => $descrip,
                'download' => $download,
                'download_link' => $download_link,
                'icon' => $icon,
                'id' => $mime_id,
                'img_save' => $img_save,
                'size' => $size,
                'strip' => $strip,
                'type' => $mime_type,
                'zip' => $zip
            );
        }

        return array('message' => $msg, 'parts' => $ret);
    }

    /**
     * Get the viewable inline parts.
     *
     * @return array  TODO
     */
    public function getInlineParts()
    {
        $ret = array();
        $last_id = null;

        foreach ($this->_message->contentTypeMap() as $mime_id => $mime_type) {
            if (!is_null($last_id) &&
                (($last_id === 0) ||
                 (strpos($mime_id, $last_id) === 0))) {
                continue;
            }

            $last_id = null;
            $viewer = Horde_Mime_Viewer::factory($mime_type);

            if ($viewer->canDisplayInline()) {
                $mime_part = $this->getMIMEPart($mime_id, array('nocontents' => true, 'nodecode' => true));
                if ($mime_part->getDisposition() == 'inline') {
                    $res = $this->renderMIMEPart($mime_id, array('format' => 'inline'));
                    $ret[$mime_id] = $res['data'];
                    $last_id = $mime_id;
                }
            }

            if (is_null($last_id) && $viewer->canDisplayInfo()) {
                $res = $this->renderMIMEPart($mime_id, array('format' => 'info'));
                $ret[$mime_id] = $res['data'];
                $last_id = $mime_id;
            }
        }

        return $ret;
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
        /* Add the necessary local parameters. */
        $params = array_merge(isset($options['params']) ? $options['params'] : array(), array(
            'actionID' => $actionID,
            'id' => isset($options['params']['id']) ? $options['params']['id'] : $mime_part->getMIMEId()
        ));

        if (!is_null($this->_mailbox)) {
            $params['index'] = $this->_index;
            $params['mailbox'] = $this->_mailbox;
        }

        return empty($options['dload'])
            ? Util::addParameter(Horde::applicationUrl('view.php'), $params)
            : Horde::downloadUrl($mime_part->getName(true), $params);
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
    function linkView($mime_part, $actionID, $text, $options = array())
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

        $url = IMP::popupIMPString($this->urlView($mime_part, $actionID, $options));

        return empty($options['widget'])
            ? Horde::link('#', $options['jstext'], empty($options['css']) ? null : $options['css'], null, $url) . $text . '</a>'
            : Horde::widget('#', $options['jstext'], empty($options['css']) ? null : $options['css'], null, $url, $text);
    }

    /**
     * Determines if a MIME part is downloadable.
     *
     * @param Horde_Mime_Part $mime_part  The MIME part object.
     *
     * @return boolean  True if downloadable.
     */
    public function isDownloadable($mime_part)
    {
        $type = $mime_part->getType();
        $ptype = $mime_part->getPrimaryType();

        if ($ptype == 'message') {
            return ($type == 'message/rfc822');
        }

        return ((($mime_part->getDisposition() == 'attachment') ||
                 $mime_part->getContentTypeParameter('name')) &&
                ($ptype != 'multipart') &&
                ($type != 'application/applefile') &&
                ($type != 'application/x-pkcs7-signature') &&
                ($type != 'application/pkcs7-signature'));
    }
}
