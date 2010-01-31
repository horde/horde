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
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Jon Parise <jon@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Horde_Mime_Viewer_Html extends Horde_Mime_Viewer_Html
{
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
     * Cached block image.
     *
     * @var string
     */
    protected $_blockimg = null;

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
            ((?(3)[^"\'>]*|[^\s>]*))
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

            $data['js'] = array('IMP.iframeInject("' . $uid . '", ' . Horde_Serialize::serialize($data['data'], Horde_Serialize::JSON, $this->_mimepart->getCharset()) . ')');
            $data['data'] = '<DIV>' . _("Loading...") . '</DIV><IFRAME class="htmlMsgData" id="' . $uid . '" src="javascript:false" frameborder="0" style="display:none"></IFRAME>' .
                Horde_Util::bufferOutput(array('Horde', 'addScriptFile'), 'imp.js', 'imp');
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
        $data = $this->_cleanHTML($data, array('phishing' => $inline));
        $status = array($this->_phishingStatus());

        /* We are done processing if in mimp mode, or we are converting to
         * text. */
        if (($_SESSION['imp']['view'] == 'mimp') ||
            (!$inline && Horde_Util::getFormData('convert_text'))) {
            $data = Horde_Text_Filter::filter($data, 'html2text', array('charset' => Horde_Nls::getCharset()));

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
        $target = 'target_' . uniqid(mt_rand());
        $data = preg_replace(
            array('/<a\b([^>]*\s+href=["\']?(#|mailto:))/i',
                  '/<a\b([^>]*)\s+target=["\']?[^>"\'\s]*["\']?/i',
                  '/<a\s/i',
                  '/<area\b([^>]*\s+href=["\']?(#|mailto:))/i',
                  '/<area\b([^>]*)\s+target=["\']?[^>"\'\s]*["\']?/i',
                  '/<area\s/i',
                  "/\x01/",
                  "/\x02/"),
            array("<\x01\\1",
                  "<\x01 \\1 target=\"" . $target . "\"",
                  '<a target="' . $target . '" ',
                  "<\x02\\1",
                  "<\x02 \\1 target=\"" . $target . "\"",
                  '<area target="' . $target . '" ',
                  'a ',
                  'area '),
            $data);

        /* If displaying inline (in IFRAME), tables with 100% height seems to
         * confuse many browsers re: the iframe internal height. */
        if ($inline) {
            $data = preg_replace('/<table\b([^>]*)\s+height=["\']?100\%["\']?/i', '<table \\1', $data);
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
            (!$GLOBALS['prefs']->getValue('html_image_addrbook') ||
             !$this->_inAddressBook())) {
            $data = preg_replace_callback($this->_img_regex, array($this, '_blockImages'), $data);

            $status[] = array(
                'icon' => Horde::img('mime/image.png', null, null, $GLOBALS['registry']->getImageDir('horde')),
                'text' => array(
                    _("Images have been blocked to protect your privacy."),
                    Horde::link('#', '', 'unblockImageLink') . _("Show Images?") . '</a>'
                )
            );
        }

        if ($GLOBALS['prefs']->getValue('emoticons')) {
            $data = Horde_Text_Filter::filter($data, array('emoticons'), array(array('emoticons' => true)));
        }

        return array(
            'data' => $data,
            'status' => $status,
            'type' => $this->_mimepart->getType(true)
        );
    }

    /**
     * TODO
     */
    protected function _mailtoCallback($m)
    {
        return 'href="' . $GLOBALS['registry']->call('mail/compose', array(Horde_String::convertCharset(html_entity_decode($m[2]), 'ISO-8859-1', Horde_Nls::getCharset()))) . '"';
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
        if (is_null($this->_blockimg)) {
            $this->_blockimg = Horde::url($GLOBALS['registry']->getImageDir('imp', false) . '/spacer_red.png', true, -1);
        }

        return empty($matches[2])
            ? $matches[1] . '"' . $this->_blockimg . '" htmlimgblocked="' . rawurlencode(str_replace('&amp;', '&', trim($matches[5], '\'" '))) . '"'
            : $matches[1] . "'" . $this->_blockimg . '\')' . $matches[6] . '" htmlimgblocked="' . rawurlencode(str_replace('&amp;', '&', trim($matches[5], '\'" '))) . '"';
    }

    /**
     * Determine whether the sender appears in an available addressbook.
     *
     * @return boolean  Does the sender appear in an addressbook?
     */
    protected function _inAddressBook()
    {
        /* If we don't have a contacts provider available, give up. */
        if (!$GLOBALS['registry']->hasMethod('contacts/getField')) {
            return false;
        }

        $params = IMP_Compose::getAddressSearchParams();
        $headers = $this->_params['contents']->getHeaderOb();

        /* Try to get back a result from the search. */
        try {
            $res = $GLOBALS['registry']->call('contacts/getField', array(Horde_Mime_Address::bareAddress($headers->getValue('from')), '__key', $params['sources'], false, true));
            return count($res);
        } catch (Horde_Exception $e) {
            return false;
        }
    }

}
