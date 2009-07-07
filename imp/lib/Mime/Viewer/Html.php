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
            <img[^>]+src=
            # <input> tags
            |<input[^>]*src=
            # "background" attributes
            |<body[^>]*background=|<td[^>]*background=|<table[^>]*background=
            # "style" attributes; match 2; quotes: match 3
            |(style=\s*("|\')?[^>]*background(?:-image)?:(?(3)[^"\']|[^>])*?url\s*\()
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
        $render = $this->_IMPrender(false);

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $render['html'],
                'status' => $render['status'],
                'type' => $this->_mimepart->getType(true)
            )
        );
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInline()
    {
        $render = $this->_IMPrender(true);

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $render['html'],
                'status' => $render['status'],
                'type' => 'text/html; charset=' . NLS::getCharset()
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
        $charset = NLS::getCharset();
        $msg_charset = $this->_mimepart->getCharset();

        if ($inline) {
            $data = Horde_String::convertCharset($data, $msg_charset);
            $msg_charset = $charset;
        }

        /* Sanitize the HTML. */
        $cleanhtml = $this->_cleanHTML($data, $inline, $msg_charset);
        $data = $cleanhtml['html'];

        /* We are done processing if in mimp mode. */
        if ($_SESSION['imp']['view'] == 'mimp') {
            $data = Horde_Text_Filter::filter($data, 'html2text');

            // Filter bad language.
            return array('html' => IMP::filterText($data), 'status' => array());
        }

        /* Reset absolutely positioned elements. */
        if ($inline) {
            $data = preg_replace('/(style\s*=\s*)(["\'])?([^>"\']*)position\s*:\s*absolute([^>"\']*)\2/i', '$1"$3$4"', $data);
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

        if ($inline) {
            /* Put div around message. */
            $data = '<div class="htmlMessage">' . $data . '</div>';
        }

        /* Only display images if specifically allowed by user. */
        if ($inline &&
            !IMP::$printMode &&
            $GLOBALS['prefs']->getValue('html_image_replacement') &&
            preg_match($this->_img_regex, $data)) {
            /* Make sure the URL parameters are correct for the current
             * message. */
            $url = Horde_Util::removeParameter(IMP::selfUrl(), array('actionID', 'index'));
            $url = Horde_Util::addParameter($url, 'index', $this->_params['contents']->getIndex());

            $view_img = Horde_Util::getFormData('view_html_images');
            $addr_check = ($GLOBALS['prefs']->getValue('html_image_addrbook') && $this->_inAddressBook());

            if (!$view_img && !$addr_check) {
                $data .= Horde_Util::bufferOutput(array('Horde', 'addScriptFile'), 'prototype.js', 'horde', true) .
                    Horde_Util::bufferOutput(array('Horde', 'addScriptFile'), 'imp.js', 'imp', true);

                // Unblock javascript code in js/src/imp.js
                $cleanhtml['status'][] = array(
                    'icon' => Horde::img('mime/image.png'),
                    'text' => array(
                        Horde_String::convertCharset(_("Images have been blocked to protect your privacy."), $charset, $msg_charset),
                        Horde::link(Horde_Util::addParameter($url, 'view_html_images', 1), '', 'unblockImageLink') . Horde_String::convertCharset(_("Show Images?"), $charset, $msg_charset) . '</a>'
                    )
                );

                $data = preg_replace_callback($this->_img_regex, array($this, '_blockImages'), $data);
            }
        }

        if ($GLOBALS['prefs']->getValue('emoticons')) {
            $data = Horde_Text_Filter::filter($data, array('emoticons'), array(array('emoticons' => true)));
        }

        return array(
            'html' => $data,
            'status' => $cleanhtml['status']
        );
    }

    /**
     * TODO
     */
    protected function _mailtoCallback($m)
    {
        return 'href="' . $GLOBALS['registry']->call('mail/compose', array(Horde_String::convertCharset(html_entity_decode($m[2]), 'ISO-8859-1', NLS::getCharset()))) . '"';
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
            ? $matches[1] . '"' . $this->_blockimg . '" blocked="' . rawurlencode(str_replace('&amp;', '&', trim($matches[5], '\'" '))) . '"'
            : $matches[1] . "'" . $this->_blockimg . '\')' . $matches[6] . '" blocked="' . rawurlencode(str_replace('&amp;', '&', trim($matches[5], '\'" ')));
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
        $res = $GLOBALS['registry']->call('contacts/getField', array(Horde_Mime_Address::bareAddress($headers->getValue('from')), '__key', $params['sources'], false, true));
        return is_a($res, 'PEAR_Error') ? false : count($res);
    }
}
