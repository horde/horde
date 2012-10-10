<?php
/**
 * This class renders out HTML text with an effort to remove potentially
 * malicious code.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Anil Madhavapeddy <anil@recoil.org>
 * @author   Jon Parise <jon@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Mime_Viewer_Html extends Horde_Mime_Viewer_Html
{
    const CSS_BG_PREG = '/(background(?:-image)?:[^;\}]*(?:url\(["\']?))(.*?)((?:["\']?\)))/i';

    /**
     * Temp array for storing data when parsing the HTML document.
     *
     * @var array
     */
    protected $_imptmp = array();

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
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
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
     * @return array  See parent::render().
     */
    protected function _renderInline()
    {
        $data = $this->_IMPrender(true);

        switch ($GLOBALS['registry']->getView()) {
        case Horde_Registry::VIEW_MINIMAL:
            $data['status'] = new IMP_Mime_Status(array(
                _("This message part contains HTML data, but this data can not be displayed inline."),
                $this->getConfigParam('imp_contents')->linkView($this->_mimepart, 'view_attach', _("View HTML data in new window."))
            ));
            break;

        default:
            $uid = strval(new Horde_Support_Randomid());

            $GLOBALS['page_output']->addScriptPackage('IMP_Script_Package_Imp');

            $data['js'] = array('IMP_JS.iframeInject("' . $uid . '", ' . Horde_Serialize::serialize($data['data'], Horde_Serialize::JSON, $this->_mimepart->getCharset()) . ')');
            $data['data'] = '<div>' . _("Loading...") . '</div><iframe class="htmlMsgData" id="' . $uid . '" src="javascript:false" frameborder="0" style="display:none"></iframe>';
            $data['type'] = 'text/html; charset=UTF-8';
            break;
        }

        return array(
            $this->_mimepart->getMimeId() => $data
        );
    }

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderInfo()
    {
        if ($this->canRender('inline') ||
            ($this->_mimepart->getDisposition() == 'attachment')) {
            return array();
        }

        $status = new IMP_Mime_Status(array(
            _("This message part contains HTML data, but inline HTML display is disabled."),
            $this->getConfigParam('imp_contents')->linkViewJS($this->_mimepart, 'view_attach', _("View HTML data in new window.")),
            $this->getConfigParam('imp_contents')->linkViewJS($this->_mimepart, 'view_attach', _("Convert HTML data to plain text and view in new window."), array('params' => array('convert_text' => 1)))
        ));
        $status->icon('mime/html.png');

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => '',
                'status' => $status,
                'type' => 'text/html; charset=' . $this->getConfigParam('charset')
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
        global $injector, $prefs, $registry;

        $data = $this->_mimepart->getContents();

        $contents = $this->getConfigParam('imp_contents');
        $convert_text = ($registry->getView() == $registry::VIEW_MINIMAL) ||
                        $injector->getInstance('Horde_Variables')->convert_text;

        /* Don't do IMP DOM processing if in mimp mode or converting to
         * text. */
        if (!$inline || $convert_text) {
            $this->_imptmp = array();
        } else {
            $filters = array();
            if ($prefs->getValue('emoticons')) {
//                $filters['emoticons'] = array(
//                    'entities' => true
//                );
            }

            if ($inline) {
                $imgview = new IMP_Ui_Imageview();
                $blockimg = !$imgview->showInlineImage($contents) &&
                            ($registry->getView() != $registry::VIEW_SMARTMOBILE);
//                $filters['emails'] = array(
//                    'callback' => array($this, 'emailsCallback')
//                );
            } else {
                $blockimg = false;
            }

            $this->_imptmp = array(
                'blockimg' => null,
                'cid' => null,
                'cid_used' => array(),
                'cssblock' => false,
                'filters' => $filters,
                'img' => $blockimg,
                'imgblock' => false,
                'inline' => $inline,
                'style' => array(),
                'target' => strval(new Horde_Support_Randomid())
            );

            /* Image filtering. */
            if ($blockimg) {
                $this->_imptmp['blockimg'] = strval(Horde_Themes::img('spacer_red.png'));
            }
        }

        /* Search for inlined data that we can display (multipart/related
         * parts) - see RFC 2392. */
        if ($related_part = $contents->findMimeType($this->_mimepart->getMimeId(), 'multipart/related')) {
            $this->_imptmp['cid'] = $related_part->getMetadata('related_ob');
        }

        /* Sanitize the HTML. */
        $data = $this->_cleanHTML($data, array(
            'noprefetch' => ($inline && ($registry->getView() != Horde_Registry::VIEW_MINIMAL)),
            'phishing' => $inline
        ));

        if (!empty($this->_imptmp['style'])) {
            $this->_processDomDocument($data->dom);
        }

        $data = $data->returnHtml();

        $status = array();
        if ($this->_phishWarn) {
            $status[] = new IMP_Mime_Status(array(
                sprintf(_("%s: This message may not be from whom it claims to be."), _("WARNING")),
                _("Beware of following any links in it or of providing the sender with any personal information."),
                _("The links that caused this warning have this background color:") . ' <span style="' . $this->_phishCss . '">' . _("EXAMPLE LINK") . '</span>'
            ));
        }

        /* We are done processing if in mimp mode, or we are converting to
         * text. */
        if ($convert_text) {
            $data = $this->_textFilter($data, 'Html2text', array(
                'wrap' => false
            ));

            // Filter bad language.
            return array(
                'data' => IMP::filterText($data),
                'type' => 'text/plain; charset=' . $this->getConfigParam('charset')
            );
        }

        if ($inline && $this->_imptmp['imgblock']) {
            $tmp = new IMP_Mime_Status(array(
                _("Images have been blocked in this message part."),
                Horde::link('#', '', 'unblockImageLink', '', '', '', '', array(
                    'mailbox' => $contents->getMailbox()->form_to,
                    'uid' => $contents->getUid()
                )) . _("Show Images?") . '</a>'
            ));
            $tmp->icon('mime/image.png');
            $status[] = $tmp;
        } elseif ($inline && $this->_imptmp['cssblock']) {
            /* This is a bit less intuitive for end users, so hide within
             * image blocking if possible. */
            $tmp = new IMP_Mime_Status(array(
                _("Message styling has been suppressed in this message part since the style data lives on a remote server."),
                Horde::link('#', '', 'unblockImageLink') . _("Load Styling?") . '</a>'
            ));
            $tmp->icon('mime/image.png');
            $status[] = $tmp;
        }

        /* Filter bad language. */
        $data = IMP::filterText($data);

        /* Add used CID information. */
        if ($inline && !empty($this->_imptmp['cid'])) {
            $related_part->setMetadata('related_cids_used', $this->_imptmp['cid_used']);
        }

        return array(
            'data' => $data,
            'status' => $status,
            'type' => $this->_mimepart->getType(true)
        );
    }

    /**
     * Process emails text filter callback.
     *
     * @param array $args   List of arguments to pass to the compose script.
     * @param array $extra  Hash of extra, non-standard arguments to pass to
     *                      compose script.
     *
     * @return Horde_Url  The link to the message composition script.
     */
    public function emailsCallback($args, $extra)
    {
        return IMP::composeLink($args, $extra, true);
    }

    /**
     */
    protected function _node($doc, $node)
    {
        parent::_node($doc, $node);

        if (empty($this->_imptmp) || !($node instanceof DOMElement)) {
            if (!empty($this->_imptmp['filters']) &&
                ($node instanceof DOMText) &&
                ($node->length > 1)) {
                $node->replaceData(0, $node->length, $this->_textFilter($node->wholeText, array_keys($this->_imptmp['filters']), array_values($this->_imptmp['filters'])));
            }
            return;
        }

        $tag = Horde_String::lower($node->tagName);

        switch ($tag) {
        case 'a':
        case 'area':
            /* Convert links to open in new windows. Ignore mailto: links and
             * links that already have a target. */
            if ($node->hasAttribute('href')) {
                $url = parse_url($node->getAttribute('href'));
                if (isset($url['scheme']) && ($url['scheme'] == 'mailto')) {
                    /* We don't include HordePopup in IFRAME, so need to use
                     * 'simple' links. */
                    $node->setAttribute('href', IMP::composeLink($node->getAttribute('href'), array(), true));
                    $node->removeAttribute('target');
                } elseif (!empty($this->_imptmp['inline']) &&
                          isset($url['fragment']) &&
                          empty($url['path']) &&
                          $GLOBALS['browser']->isBrowser('mozilla')) {
                    /* See Bug #8695: internal anchors are broken in
                     * Mozilla. */
                    $node->removeAttribute('href');
                } elseif (!$node->hasAttribute('target') ||
                          Horde_String::lower($node->getAttribute('target')) == '_self') {
                    $node->setAttribute('target', $this->_imptmp['target']);
                }
            }
            break;

        case 'img':
        case 'input':
            if ($node->hasAttribute('src')) {
                $val = $node->getAttribute('src');

                /* Multipart/related. */
                if (($tag == 'img') && ($id = $this->_cidSearch($val))) {
                    $val = $this->getConfigParam('imp_contents')->urlView(null, 'view_attach', array('params' => array(
                        'ctype' => 'image/*',
                        'id' => $id,
                        'imp_img_view' => 'data'
                    )));
                    $node->setAttribute('src', $val);
                }

                /* Block images.*/
                if (!empty($this->_imptmp['img'])) {
                    $url = new Horde_Url($val);
                    $url->setScheme();
                    $node->setAttribute('htmlimgblocked', $url);
                    $node->setAttribute('src', $this->_imptmp['blockimg']);
                    $this->_imptmp['imgblock'] = true;
                }
            }
            break;

        case 'link':
            /* Block all link tags that reference foreign URLs, other than
             * CSS. There's no inherently wrong with linking to a foreign
             * CSS file other than privacy concerns. Therefore, block
             * linking until requested by the user. */
            $delete_link = true;

            switch (Horde_String::lower($node->getAttribute('type'))) {
            case 'text/css':
                if ($node->hasAttribute('href')) {
                    $tmp = $node->getAttribute('href');

                    if ($id = $this->_cidSearch($tmp, false)) {
                        $this->_imptmp['style'][] = $this->getConfigParam('imp_contents')->getMIMEPart($id)->getContents();
                    } else {
                        $node->setAttribute('htmlcssblocked', $node->getAttribute('href'));
                        $node->removeAttribute('href');
                        $this->_imptmp['cssblock'] = true;
                        $delete_link = false;
                    }
                }
                break;
            }

            if ($delete_link &&
                $node->hasAttribute('href') &&
                $node->parentNode) {
                $node->parentNode->removeChild($node);
            }
            break;

        case 'style':
            switch (Horde_String::lower($node->getAttribute('type'))) {
            case 'text/css':
                $this->_imptmp['style'][] = $node->nodeValue;
                $node->parentNode->removeChild($node);
                break;
            }
            break;

        case 'table':
            /* If displaying inline (in IFRAME), tables with 100% height seems
             * to confuse many browsers re: the IFRAME internal height. */
            if (!empty($this->_imptmp['inline']) &&
                $node->hasAttribute('height') &&
                ($node->getAttribute('height') == '100%')) {
                $node->removeAttribute('height');
            }

            // Fall-through

        case 'body':
        case 'td':
            if ($node->hasAttribute('background')) {
                $val = $node->getAttribute('background');

                /* Multipart/related. */
                if ($id = $this->_cidSearch($val)) {
                    $val = $this->getConfigParam('imp_contents')->urlView(null, 'view_attach', array('params' => array(
                        'id' => $id,
                        'imp_img_view' => 'data'
                    )));
                    $node->setAttribute('background', $val);
                }

                /* Block images.*/
                if (!empty($this->_imptmp['img'])) {
                    $node->setAttribute('htmlimgblocked', $val);
                    $node->setAttribute('background', $this->_imptmp['blockimg']);
                    $this->_imptmp['imgblock'] = true;
                }
            }
            break;
        }

        $remove = array();
        foreach ($node->attributes as $val) {
            /* Catch random mailto: strings in attributes that will cause
             * problems with e-mail linking. */
            if (stripos($val->value, 'mailto:') === 0) {
                $remove[] = $val->name;
            }
        }

        foreach ($remove as $val) {
            $node->removeAttribute($val);
        }

        if ($node->hasAttribute('style')) {
            if (strpos($node->getAttribute('style'), 'content:') !== false) {
                // TODO: Figure out way to unblock?
                $node->removeAttribute('style');
            } elseif (!empty($this->_imptmp['img']) ||
                      !empty($this->_imptmp['cid'])) {
                $this->_imptmp['node'] = $node;
                $style = preg_replace_callback(self::CSS_BG_PREG, array($this, '_styleCallback'), $node->getAttribute('style'), -1, $matches);
                if ($matches) {
                    $node->setAttribute('style', $style);
                }
            }
        }
    }

    /**
     */
    protected function _processDomDocument($doc)
    {
        /* Sanitize and optimize style tags. */
        try {
            // Csstidy may not be available.
            $style = $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter(implode("\n", $this->_imptmp['style']), 'csstidy', array(
                'ob' => true,
                'preserve_css' => false
            ));
        } catch (Horde_Exception $e) {
            return;
        }

        $blocked = array();
        foreach ($style->import as $val) {
            $blocked[] = '@import "' . $val . '";';
        }
        $style->import = array();

        $style_blocked = clone $style;
        $was_blocked = false;

        foreach ($style->css as $key => $val) {
            foreach ($val as $key2 => $val2) {
                foreach ($val2 as $key3 => $val3) {
                    foreach ($val3['p'] as $key4 => $val4) {
                        if (preg_match('/^\s*url\(["\']?.*?["\']?\)/i', $val4)) {
                            $was_blocked = true;
                            unset($style->css[$key][$key2]);
                            break 3;
                        }
                    }
                }
                unset($style_blocked->css[$key][$key2]);
            }
        }

        $css_text = $style->print->plain();

        if ($was_blocked) {
            $blocked[] = $style_blocked->print->plain();
        }

        if ($css_text || !empty($blocked)) {
            /* Gets the HEAD element or creates one if it doesn't exist. */
            $head = $doc->getElementsByTagName('head');
            if ($head->length) {
                $headelt = $head->item(0);
            } else {
                $headelt = $doc->createElement('head');
                $doc->appendChild($headelt);
            }
        }

        if ($css_text) {
            $style_elt = $doc->createElement('style', $css_text);
            $style_elt->setAttribute('type', 'text/css');
            $headelt->appendChild($style_elt);
        }

        /* Store all the blocked CSS in a bogus style element in the HTML
         * output - then we simply need to change the type attribute to
         * text/css, and the browser should load the definitions on-demand. */
        if (!empty($blocked)) {
            $block_elt = $doc->createElement('style', implode('', $blocked));
            $block_elt->setAttribute('type', 'text/x-imp-cssblocked');
            $headelt->appendChild($block_elt);
        }
    }

    /**
     * preg_replace_callback() callback for style/background matching of
     * images.
     *
     * @param array $matches  The list of matches.
     *
     * @return string  The replacement image string.
     */
    protected function _styleCallback($matches)
    {
        if ($id = $this->_cidSearch($matches[2])) {
            $replace = $this->getConfigParam('imp_contents')->urlView(null, 'view_attach', array('params' => array(
                'id' => $id,
                'imp_img_view' => 'data'
            )));
        } else {
            $this->_imptmp['node']->setAttribute('htmlimgblocked', $matches[2]);
            $this->_imptmp['imgblock'] = true;
            $replace = $this->_imptmp['blockimg'];
        }
        return $matches[1] . $replace . $matches[3];
    }

    /**
     * Search for a CID in a related part.
     *
     * @param string $cid    The CID to query.
     * @param boolean $save  Save as a CID used?
     *
     * @return string  The MIME ID of the part, or null if not found.
     */
    protected function _cidSearch($cid, $save = true)
    {
        if (empty($this->_imptmp['cid']) ||
            (strpos($cid, 'cid:') !== 0) ||
            !($id = $this->_imptmp['cid']->cidSearch(substr($cid, 4)))) {
            return null;
        }

        if ($save) {
            $this->_imptmp['cid_used'][] = $id;
        }

        return $id;
    }

}
