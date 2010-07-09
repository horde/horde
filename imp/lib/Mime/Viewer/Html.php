<?php
/**
 * The IMP_Horde_Mime_Viewer_Html class renders out HTML text with an effort
 * to remove potentially malicious code.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Anil Madhavapeddy <anil@recoil.org>
 * @author   Jon Parise <jon@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Horde_Mime_Viewer_Html extends Horde_Mime_Viewer_Html
{
    /**
     * Cached block image.
     *
     * @var string
     */
    public $blockimg = null;

    /**
     * The window target to use for links.
     * Needed for testing purposes.
     *
     * @var string
     */
    public $newwinTarget = null;

    /**
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => true,
        'info' => true,
        'inline' => true,
        'raw' => false
    );

    /**
     * The regular expression to catch any tags and attributes that load
     * external images.
     *
     * @var string
     */
    protected $_img_regex = '/
        # match 1
        (
            # <img> tags
            <img\b[^>]+?src=
            # <input> tags
            |<input\b[^>]+?src=
            # "background" attributes
            |<body\b[^>]+?background=|<td[^>]*background=|<table[^>]*background=
            # "style" attributes; match 2; quotes: match 3
            |(style=\s*("|\')?[^>]*?background(?:-image)?:(?(3)[^"\']|[^>])*?url\s*\()
        )
        # whitespace
        \s*
        # opening quotes, parenthesis; match 4
        ("|\')?
        # the image url; match 5
        ((?(2)
            # matched a "style" attribute
            (?(4)[^"\')>]*|[^\s)>]*)
            # did not match a "style" attribute
            |(?(4)[^"\'>]*|[^\s>]*)
        ))
        # closing quotes
        (?(4)\\4)
        # matched a "style" attribute?
        (?(2)
            # closing parenthesis
            \s*\)
            # remainder of the "style" attribute; match 6
            ((?(3)[^"\'>]*|[^\s>]*)(?(3)\\3))
        )
        /isx';

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _render()
    {
        return array(
            $this->_mimepart->getMimeId() => $this->_IMPrender(false)
        );
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInline()
    {
        /* Non-javascript browsers can't handle IFRAME resizing, so it isn't
         * possible to view inline. */
        if (!$GLOBALS['browser']->hasFeature('javascript')) {
            return array(
                $this->_mimepart->getMimeId() => array(
                    'data' => '',
                    'status' => array(
                        array(
                            'icon' => Horde::img('mime/html.png'),
                            'text' => array(
                                _("This message part contains HTML data, but this data can not be displayed inline."),
                                $this->_params['contents']->linkViewJS($this->_mimepart, 'view_attach', _("View HTML data in new window.")),
                            )
                        )
                    ),
                    'type' => 'text/html; charset=' . Horde_Nls::getCharset()
                )
            );
        }

        $data = $this->_IMPrender(true);

        /* Catch case where using mimp on a javascript browser. */
        if ($_SESSION['imp']['view'] != 'mimp') {
            $uid = 'htmldata_' . uniqid(mt_rand());

            Horde::addScriptFile('imp.js', 'imp');

            $data['js'] = array('IMP.iframeInject("' . $uid . '", ' . Horde_Serialize::serialize($data['data'], Horde_Serialize::JSON, $this->_mimepart->getCharset()) . ')');
            $data['data'] = '<div>' . _("Loading...") . '</div><iframe class="htmlMsgData" id="' . $uid . '" src="javascript:false" frameborder="0" style="display:none"></iframe>';
            $data['type'] = 'text/html; charset=UTF-8';
        }

        return array(
            $this->_mimepart->getMimeId() => $data
        );
    }

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInfo()
    {
        if ($this->canRender('inline') ||
            ($this->_mimepart->getDisposition() == 'attachment')) {
            return array();
        }

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => '',
                'status' => array(
                    array(
                        'icon' => Horde::img('mime/html.png', _("HTML data")),
                        'text' => array(
                            _("This message part contains HTML data, but inline HTML display is disabled."),
                            $this->_params['contents']->linkViewJS($this->_mimepart, 'view_attach', _("View HTML data in new window.")),
                            $this->_params['contents']->linkViewJS($this->_mimepart, 'view_attach', _("Convert HTML data to plain text and view in new window."), array('params' => array('convert_text' => 1)))
                        )
                    )
                ),
                'type' => 'text/html; charset=' . Horde_Nls::getCharset()
            )
        );
    }

    /**
     * Render out the currently set contents.
     *
     * @param boolean $inline  Are we viewing inline?
     *
     * @return array  Two elements: html and status.
     */
    protected function _IMPrender($inline)
    {
        $data = $this->_mimepart->getContents();

        /* Sanitize the HTML. */
        $data = $this->_cleanHTML($data, array(
            'noprefetch' => ($inline && ($_SESSION['imp']['view'] != 'mimp')),
            'phishing' => $inline
        ));
        $status = array($this->_phishingStatus());

        /* We are done processing if in mimp mode, or we are converting to
         * text. */
        if (($_SESSION['imp']['view'] == 'mimp') ||
            (!$inline && Horde_Util::getFormData('convert_text'))) {
            $data = Horde_Text_Filter::filter($data, 'Html2text', array('charset' => Horde_Nls::getCharset(), 'wrap' => false));

            // Filter bad language.
            return array(
                'data' => IMP::filterText($data),
                'status' => array(),
                'type' => 'text/plain; charset=' . Horde_Nls::getCharset()
            );
        }

        /* Search for inlined links that we can display (multipart/related
         * parts). */
        if (isset($this->_params['related_id'])) {
            $cid_replace = array();

            foreach ($this->_params['related_cids'] as $mime_id => $cid) {
                $cid = trim($cid, '<>');
                if ($cid) {
                    $cid_part = $this->_params['contents']->getMIMEPart($mime_id);
                    $cid_replace['cid:' . $cid] = $this->_params['contents']->urlView($cid_part, 'view_attach', array('params' => array('related_data' => 1)));
                }
            }

            if (!empty($cid_replace)) {
                $data = str_replace(array_keys($cid_replace), array_values($cid_replace), $data);
            }
        }

        /* Convert links to open in new windows. First we hide all
         * mailto: links, links that have an "#xyz" anchor and ignore
         * all links that already have a target. */
        $data = $this->openLinksInNewWindow($data);

        /* If displaying inline (in IFRAME), tables with 100% height seems to
         * confuse many browsers re: the iframe internal height. */
        if ($inline) {
            $data = preg_replace('/<table\b([^>]*)\bheight=["\']?100\%["\']?/i', '<table \\1', $data);
        }

        /* Turn mailto: links into our own compose links. */
        if ($inline && $GLOBALS['registry']->hasMethod('mail/compose')) {
            $data = preg_replace_callback('/href\s*=\s*(["\'])?mailto:((?(1)[^\1]*?|[^\s>]+))(?(1)\1|)/i', array($this, '_mailtoCallback'), $data);
        }

        /* Filter bad language. */
        $data = IMP::filterText($data);

        /* Image filtering. */
        if ($inline &&
            $GLOBALS['prefs']->getValue('html_image_replacement') &&
            preg_match($this->_img_regex, $this->_mimepart->getContents()) &&
            !$this->_inAddressBook()) {
            $data = $this->blockImages($data);

            $status[] = array(
                'icon' => Horde::img('mime/image.png'),
                'text' => array(
                    _("Images have been blocked to protect your privacy."),
                    Horde::link('#', '', 'unblockImageLink') . _("Show Images?") . '</a>'
                )
            );
        }

        if ($GLOBALS['prefs']->getValue('emoticons')) {
            $data = Horde_Text_Filter::filter($data, array('emoticons'), array(array('entities' => true)));
        }

        return array(
            'data' => $data,
            'status' => $status,
            'type' => $this->_mimepart->getType(true)
        );
    }

    /**
     * Scans HTML data and alters links to open in a new window.
     * In public function so that it can be tested.
     *
     * @param string $data  Data in.
     *
     * @return string  Altered data.
     */
    public function openLinksInNewWindow($data)
    {
        $target = is_null($this->newwinTarget)
            ? 'target_' . uniqid(mt_rand())
            : $this->newwinTarget;

        return preg_replace(
            array('/<a\b([^>]*\bhref=["\']?(#|mailto:))/i',
                  '/<a\b([^>]*)\btarget=["\']?[^>"\'\s]*["\']?/i',
                  '/<a\b/i',
                  '/<area\b([^>]*\bhref=["\']?(#|mailto:))/i',
                  '/<area\b([^>]*)\btarget=["\']?[^>"\'\s]*["\']?/i',
                  '/<area\b/i',
                  "/\x01/",
                  "/\x02/"),
            array("<\x01\\1",
                  "<\x01\\1target=\"" . $target . "\"",
                  '<a target="' . $target . '"',
                  "<\x02\\1",
                  "<\x02\\1target=\"" . $target . "\"",
                  '<area target="' . $target . '"',
                  'a',
                  'area'),
            $data);
    }

    /**
     * TODO
     */
    protected function _mailtoCallback($m)
    {
        return 'href="' . $GLOBALS['registry']->call('mail/compose', array(Horde_String::convertCharset(html_entity_decode($m[2]), 'ISO-8859-1', Horde_Nls::getCharset()))) . '"';
    }

    /**
     * Block images in HTML data.
     *
     * @param string $data  Data in.
     *
     * @return string  Altered data.
     */
    public function blockImages($data)
    {
        return preg_replace_callback($this->_img_regex, array($this, '_blockImages'), $data);
    }

    /**
     * Called from the image-blocking regexp to construct the new image tags.
     *
     * @param array $matches
     *
     * @return string The new image tag.
     */
    protected function _blockImages($matches)
    {
        if (is_null($this->blockimg)) {
            $this->blockimg = Horde::url(Horde_Themes::img('spacer_red.png'), true, -1);
        }

        return empty($matches[2])
            ? $matches[1] . '"' . $this->blockimg . '" htmlimgblocked="' . rawurlencode(str_replace('&amp;', '&', trim($matches[5], '\'" '))) . '"'
            : trim($matches[1] . "'" . $this->blockimg . '\')' . $matches[6], '\'" ') . '" htmlimgblocked="' . rawurlencode(str_replace('&amp;', '&', trim($matches[5], '\'" '))) . '"';
    }

    /**
     * Determine whether the sender appears in an available addressbook.
     *
     * @return boolean  Does the sender appear in an addressbook?
     */
    protected function _inAddressBook()
    {
        $from = Horde_Mime_Address::bareAddress($this->_params['contents']->getHeaderOb()->getValue('from'));

        if ($GLOBALS['prefs']->getValue('html_image_addrbook') &&
            $GLOBALS['registry']->hasMethod('contacts/getField')) {
            $params = IMP::getAddressbookSearchParams();
            try {
                if ($GLOBALS['registry']->call('contacts/getField', array($from, '__key', $params['sources'], false, true))) {
                    return true;
                }
            } catch (Horde_Exception $e) {}
        }

        /* Check admin defined e-mail list. */
        $safe_addrs = $this->getConfigParam('safe_addrs');
        return (!empty($safe_addrs) && in_array($from, $safe_addrs));
    }

}
