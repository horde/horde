<?php
/**
 * The IMP_Horde_Mime_Viewer_Html class renders out HTML text with an effort
 * to remove potentially malicious code.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Jon Parise <jon@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime
 */
class IMP_Horde_Mime_Viewer_Html extends Horde_Mime_Viewer_Html
{
    /**
     * Can this driver render various views?
     *
     * @var boolean
     */
    protected $_capability = array(
        'embedded' => false,
        'forceinline' => false,
        'full' => true,
        'info' => true,
        'inline' => true
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
            <img[^>]+?src=
            # <input> tags
            |<input[^>]+?src=
            # "background" attributes
            |<body[^>]+?background=|<td[^>]*background=|<table[^>]*background=
            # "style" attributes; match 2; quotes: match 3
            |(style=\s*("|\')?[^>]*?background(?:-image)?:(?(3)[^"\']|[^>])*?url\s*\()
        )
        # whitespace
        \s*
        # opening quotes, parenthesis; match 4
        ("|\')?
        # the image url
        (?(2)
            # matched a "style" attribute
            (?(4)[^"\')>]*|[^\s)>]*)
            # did not match a "style" attribute
            |(?(4)[^"\'>]*|[^\s>]*)
        )
        # closing quotes
        (?(4)\\4)
        # matched a "style" attribute?
        (?(2)
            # closing parenthesis
            \s*\)
            # remainder of the "style" attribute; match 5
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
     * URL parameters used by this function:
     * <pre>
     * 'html_iframe_data' - (boolean) If true, output the iframe content only.
     * 'html_view_images' - (boolean) If true, force display of images.
     * </pre>
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInline()
    {
        if (Horde_Util::getFormData('html_iframe_data')) {
            $html = $this->_IMPrender(true);
        } else {
            $data = '';
            $view_part = isset($this->_params['related_id'])
                ? $this->_params['contents']->getMIMEPart($this->_params['related_id'])
                : $this->_mimepart;

            $src = $this->_params['contents']->urlView($view_part, 'view_attach', array('params' => array('html_iframe_data' => 1, 'mode' => IMP_Contents::RENDER_INLINE)));

            /* Check for phishing exploits. */
            $contents = $this->_mimepart->getContents();
            $this->_phishingCheck($contents, true);
            $status = array($this->_phishingStatus());

            /* Only display images if specifically allowed by user. */
            if ($GLOBALS['prefs']->getValue('html_image_replacement') &&
                preg_match($this->_img_regex, $contents)) {
                // Unblock javascript code in js/imp.js
                $data = Horde_Util::bufferOutput(array('Horde', 'addScriptFile'), 'imp.js', 'imp', true);

                $status[] = array(
                    'icon' => Horde::img('mime/image.png'),
                    'text' => array(
                        _("Images have been blocked to protect your privacy."),
                        Horde::link(Horde_Util::addParameter($src, 'html_view_images', 1), '', 'unblockImageLink') . _("Show Images?") . '</a>'
                    )
                );
            }

            $html = array(
                // TODO: Why do we need extra 10 pixels, at least on FF 3.1?
                'data' => '<IFRAME class="htmlMessage" src="' . $src . '" onload="this.setStyle({ height: this.contentWindow.document.height + 10 + \'px\' })" frameborder="0"></iframe>' . $data,
                'status' => $status,
                'type' => 'text/html; charset=' . Horde_Nls::getCharset()
            );
        }

        return array(
            $this->_mimepart->getMimeId() => $html
        );
    }

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInfo()
    {
        if ($this->canRender('inline')) {
            return array();
        }

        $status = array(
            _("This message part contains HTML data, but inline HTML display is disabled."),
            $this->_params['contents']->linkViewJS($this->_mimepart, 'view_attach', _("View HTML data in new window.")),
            $this->_params['contents']->linkViewJS($this->_mimepart, 'view_attach', _("Convert HTML data to plain text and view in new window."), array('params' => array('convert_text' => 1)))
        );

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => '',
                'status' => array(
                    array(
                        'icon' => Horde::img('mime/html.png', _("HTML data")),
                        'text' => $status
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
        $charset = Horde_Nls::getCharset();

        /* Sanitize the HTML. */
        $data = $this->_cleanHTML($data, array('phishing' => $inline));

        /* We are done processing if in mimp mode, or we are converting to
         * text. */
        if (($_SESSION['imp']['view'] == 'mimp') ||
            (!$inline && Horde_Util::getFormData('convert_text'))) {
            $data = Horde_Text_Filter::filter($data, 'html2text');

            // Filter bad language.
            return array(
                'data' => IMP::filterText($data),
                'status' => array(),
                'type' => 'text/plain; charset=' . $charset
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
        $data = preg_replace(
            array('/<a\s([^>]*\s+href=["\']?(#|mailto:))/i',
                  '/<a\s([^>]*)\s+target=["\']?[^>"\'\s]*["\']?/i',
                  '/<a\s/i',
                  '/<area\s([^>]*\s+href=["\']?(#|mailto:))/i',
                  '/<area\s([^>]*)\s+target=["\']?[^>"\'\s]*["\']?/i',
                  '/<area\s/i',
                  "/\x01/",
                  "/\x02/"),
            array("<\x01\\1",
                  "<\x01 \\1 target=\"_blank\"",
                  '<a target="_blank" ',
                  "<\x02\\1",
                  "<\x02 \\1 target=\"_blank\"",
                  '<area target="_blank" ',
                  'a ',
                  'area '),
            $data);

        /* Turn mailto: links into our own compose links. */
        if ($inline && $GLOBALS['registry']->hasMethod('mail/compose')) {
            $data = preg_replace_callback('/href\s*=\s*(["\'])?mailto:((?(1)[^\1]*?|[^\s>]+))(?(1)\1|)/i', array($this, '_mailtoCallback'), $data);
        }

        /* Filter bad language. */
        $data = IMP::filterText($data);

        /* Image filtering. */
        if ($inline &&
            !Horde_Util::getFormData('html_view_images') &&
            $GLOBALS['prefs']->getValue('html_image_replacement') &&
            preg_match($this->_img_regex, $this->_mimepart->getContents()) &&
            (!$GLOBALS['prefs']->getValue('html_image_addrbook') ||
             !$this->_inAddressBook())) {
            $data = preg_replace_callback($this->_img_regex, array($this, '_blockImages'), $data);
        }

        if ($GLOBALS['prefs']->getValue('emoticons')) {
            $data = Horde_Text_Filter::filter($data, array('emoticons'), array(array('emoticons' => true)));
        }

        return array(
            'data' => $data,
            'status' => array(),
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
            $this->_blockimg = Horde::url($GLOBALS['registry']->getImageDir('imp', false) . '/spacer_red.png', false, -1);
        }

        return empty($matches[2])
            ? $matches[1] . '"' . $this->_blockimg . '"'
            : $matches[1] . "'" . $this->_blockimg . '\')' . $matches[5] . '"';
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
