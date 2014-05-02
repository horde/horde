<?php
/**
 * Copyright 1999-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 1999-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Renderer of HTML data, attempting to remove malicious code.
 *
 * @author    Anil Madhavapeddy <anil@recoil.org>
 * @author    Jon Parise <jon@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 1999-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Mime_Viewer_Html extends Horde_Mime_Viewer_Html
{
    /** CSS background regex. */
    const CSS_BG_PREG = '/(background(?:-image)?:[^;\}]*(?:url\(["\']?))(.*?)((?:["\']?\)))/i';

    /** Blocked attributes. */
    const CSSBLOCK = 'htmlcssblocked';
    const IMGBLOCK = 'htmlimgblocked';
    const SRCSETBLOCK = 'htmlimgblocked_srcset';

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
        global $page_output, $registry;

        $data = $this->_IMPrender(true);

        switch ($view = $registry->getView()) {
        case $registry::VIEW_MINIMAL:
            $data['status'] = new IMP_Mime_Status(array(
                _("This message part contains HTML data, but this data can not be displayed inline."),
                $this->getConfigParam('imp_contents')->linkView($this->_mimepart, 'view_attach', _("View HTML data in new window."))
            ));
            break;

        default:
            $uid = strval(new Horde_Support_Randomid());

            $page_output->addScriptPackage('IMP_Script_Package_Imp');

            $data['js'] = array(
                'IMP_JS.iframeInject("' . $uid . '", ' . json_encode($data['data']) . ')'
            );

            if ($view == $registry::VIEW_SMARTMOBILE) {
                $data['js'][] = '$("#imp-message-body a[href=\'#unblock-image\']").button()';
            }

            $data['data'] = '<div>' . _("Loading...") . '</div><iframe class="htmlMsgData" id="' . $uid . '" src="javascript:false" frameborder="0" style="display:none;height:auto;"></iframe>';
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
        global $injector, $registry;

        $data = $this->_mimepart->getContents();
        $view = $registry->getView();

        $contents = $this->getConfigParam('imp_contents');
        $convert_text = ($view == $registry::VIEW_MINIMAL) ||
                        $injector->getInstance('Horde_Variables')->convert_text;

        /* Don't do IMP DOM processing if in mimp mode or converting to
         * text. */
        $this->_imptmp = array();
        if ($inline && !$convert_text) {
            $this->_imptmp += array(
                'cid' => null,
                'cid_used' => array(),
                'cssblock' => false,
                'cssbroken' => false,
                'imgblock' => false,
                'inline' => $inline,
                'style' => array()
            );
        }

        /* Search for inlined data that we can display (multipart/related
         * parts) - see RFC 2392. */
        if ($related_part = $contents->findMimeType($this->_mimepart->getMimeId(), 'multipart/related')) {
            $this->_imptmp['cid'] = $related_part->getMetadata('related_ob');
        }

        /* Sanitize the HTML. */
        $data = $this->_cleanHTML($data, array(
            'noprefetch' => ($inline && ($view != Horde_Registry::VIEW_MINIMAL)),
            'phishing' => $inline
        ));

        if (!empty($this->_imptmp['style'])) {
            $this->_processDomDocument($data->dom);
        }

        if ($inline) {
            $charset = 'UTF-8';
            $data = $data->returnHtml(array(
                'charset' => $charset,
                'metacharset' => true
            ));
        } else {
            $charset = $this->_mimepart->getCharset();
            $data = $data->returnHtml();
        }

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
                'type' => 'text/plain; charset=' . $charset
            );
        }

        if ($inline) {
            switch ($view) {
            case $registry::VIEW_SMARTMOBILE:
                if ($this->_imptmp['imgblock']) {
                    $tmp_txt = _("Show images...");
                } elseif ($this->_imptmp['cssblock']) {
                    $tmp_txt = _("Load message styling...");
                } else {
                    $tmp_txt = null;
                }

                if (!is_null($tmp_txt)) {
                    $tmp = new IMP_Mime_Status(array(
                        '<a href="#unblock-image" data-role="button" data-theme="e">' . $tmp_txt . '</a>'
                    ));
                    $tmp->views = array($view);
                    $status[] = $tmp;
                }
                break;

            default:
                $class = 'unblockImageLink';
                if (!$injector->getInstance('IMP_Prefs_Special_ImageReplacement')->canAddToSafeAddrList() ||
                    $injector->getInstance('IMP_Identity')->hasAddress($contents->getHeader()->getOb('from'))) {
                    $class .= ' noUnblockImageAdd';
                }

                if ($this->_imptmp['imgblock']) {
                    $tmp = new IMP_Mime_Status(array(
                        _("Images have been blocked in this message part."),
                        Horde::link('#', '', $class, '', '', '', '', array(
                            'muid' => strval($contents->getIndicesOb())
                        )) . _("Show Images?") . '</a>'
                    ));
                    $tmp->icon('mime/image.png');
                    $status[] = $tmp;
                } elseif ($this->_imptmp['cssblock']) {
                    /* This is a bit less intuitive for end users, so hide
                     * within image blocking if possible. */
                    $tmp = new IMP_Mime_Status(array(
                        _("Message styling has been suppressed in this message part since the style data lives on a remote server."),
                        Horde::link('#', '', $class) . _("Load Styling?") . '</a>'
                    ));
                    $tmp->icon('mime/image.png');
                    $status[] = $tmp;
                }

                if ($this->_imptmp['cssbroken']) {
                    $tmp = new IMP_Mime_Status(array(
                        _("This message contains corrupt styling data so the message contents may not appear correctly below."),
                        $contents->linkViewJS($this->_mimepart, 'view_attach', _("Click to view HTML data in new window; it is possible this will allow you to view the message correctly."))
                    ));
                    $tmp->icon('mime/image.png');
                    $status[] = $tmp;
                }
                break;
            }
        }

        /* Add used CID information. */
        if ($inline && !empty($this->_imptmp['cid'])) {
            $related_part->setMetadata('related_cids_used', $this->_imptmp['cid_used']);
        }

        return array(
            'data' => $data,
            'status' => $status,
            'type' => 'text/html; charset=' . $charset
        );
    }

    /**
     */
    protected function _node($doc, $node)
    {
        parent::_node($doc, $node);

        if (empty($this->_imptmp) || !($node instanceof DOMElement)) {
            if (($node instanceof DOMText) && ($node->length > 1)) {
                /* Filter bad language. */
                $text = IMP::filterText($node->data);
                if ($node->data != $text) {
                    $node->replaceData(0, $node->length, $text);
                }
            }
            return;
        }

        $tag = Horde_String::lower($node->tagName);

        /* Remove 'height' styles from HTML messages, because it can break
         * sizing of IFRAME. */
        foreach ($node->attributes as $key => $val) {
            if ($key == 'style') {
                /* Do simplistic style parsing here. */
                $parts = array_filter(explode(';', $val->value));
                foreach ($parts as $k2 => $v2) {
                    if (preg_match("/^\s*height:\s*/i", $v2)) {
                        unset($parts[$k2]);
                    }
                }
                $val->value = implode(';', $parts);
            }
        }

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
                    $clink = new IMP_Compose_Link($node->getAttribute('href'));
                    $node->setAttribute('href', $clink->link(true));
                    $node->removeAttribute('target');
                } elseif (!empty($this->_imptmp['inline']) &&
                          isset($url['fragment']) &&
                          empty($url['path']) &&
                          $GLOBALS['browser']->isBrowser('mozilla')) {
                    /* See Bug #8695: internal anchors are broken in
                     * Mozilla. */
                    $node->removeAttribute('href');
                } else {
                    $node->setAttribute('target', strval(new Horde_Support_Randomid()));
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
                if ($this->_imgBlock()) {
                    if (Horde_Url_Data::isData($val)) {
                        $url = new Horde_Url_Data($val);
                    } else {
                        $url = new Horde_Url($val);
                        $url->setScheme();
                    }
                    $node->setAttribute(self::IMGBLOCK, $url);
                    $node->setAttribute('src', $this->_imgBlockImg());
                    $this->_imptmp['imgblock'] = true;
                }
            }

            /* IMG only */
            if (($tag == 'img') &&
                $this->_imgBlock() &&
                $node->hasAttribute('srcset')) {
                $node->setAttribute(self::SRCSETBLOCK, $node->getAttribute('srcset'));
                $node->setAttribute('srcset', '');
                $this->_imptmp['imgblock'] = true;
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
                    } elseif ($this->_imgBlock()) {
                        $node->setAttribute(self::CSSBLOCK, $node->getAttribute('href'));
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
                $this->_imptmp['style'][] = str_replace(
                    array('<!--', '-->'),
                    '',
                    $node->nodeValue
                );
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
                if ($this->_imgBlock()) {
                    $node->setAttribute(self::IMGBLOCK, $val);
                    $node->setAttribute('background', $this->_imgBlockImg());
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
            } elseif (!empty($this->_imptmp['cid']) || $this->_imgBlock()) {
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
        try {
            $css = new Horde_Css_Parser(implode("\n", $this->_imptmp['style']));
        } catch (Exception $e) {
            /* If your CSS sucks and we can't parse it, tough. Ignore it
             * and inform the user. */
            $this->_imptmp['cssbroken'] = true;
            return;
        }
        $blocked = clone $css;

        /* Go through and remove questionable rules from styles first. */
        $css_text = $this->_parseCss($css, false);

        /* Now go through blocked object and do the opposite: only keep the
         * questionable rules. */
        $blocked_text = $this->_parseCss($blocked, true);

        if (strlen($css_text) || strlen($blocked_text)) {
            /* Gets the HEAD element or creates one if it doesn't exist. */
            $head = $doc->getElementsByTagName('head');
            if ($head->length) {
                $headelt = $head->item(0);
            } else {
                $headelt = $doc->createElement('head');
                $doc->appendChild($headelt);
            }
        } else {
            $headelt = $doc->createElement('head');
            $doc->appendChild($headelt);
        }

        if (strlen($css_text)) {
            $style_elt = $doc->createElement('style', $css_text);
            $style_elt->setAttribute('type', 'text/css');
            $headelt->appendChild($style_elt);
        }

        /* Store all the blocked CSS in a bogus style element in the HTML
         * output - then we simply need to change the type attribute to
         * text/css, and the browser should load the definitions on-demand. */
        if (strlen($blocked_text)) {
            $block_elt = $doc->createElement('style', $blocked_text);
            $block_elt->setAttribute('type', 'text/x-imp-cssblocked');
            $headelt->appendChild($block_elt);
        }
    }

    /**
     */
    protected function _parseCss($css, $blocked)
    {
        foreach ($css->doc->getContents() as $val) {
            if ($val instanceof Sabberworm\CSS\RuleSet\RuleSet) {
                foreach ($val->getRules() as $val2) {
                    $item = $val2->getValue();

                    if ($item instanceof Sabberworm\CSS\Value\URL) {
                        if (!$blocked) {
                            $val->removeRule($val2);
                        }
                    } elseif ($item instanceof Sabberworm\CSS\Value\RuleValueList) {
                        $components = $item->getListComponents();
                        foreach ($components as $key3 => $val3) {
                            if ($val3 instanceof Sabberworm\CSS\Value\URL) {
                                if (!$blocked) {
                                    unset($components[$key3]);
                                }
                            } elseif ($blocked) {
                                unset($components[$key3]);
                            }
                        }
                        $item->setListComponents($components);
                    } elseif ($blocked) {
                        $val->removeRule($val2);
                    }
                }
            } elseif ($val instanceof Sabberworm\CSS\Property\Import) {
                if (!$blocked) {
                    $css->doc->remove($val);
                }
            } elseif ($blocked) {
                $css->doc->remove($val);
            }
        }

        return $css->compress();
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
            $this->_imptmp['node']->setAttribute(self::IMGBLOCK, $matches[2]);
            $this->_imptmp['imgblock'] = true;
            $replace = $this->_imgBlockImg();
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

    /**
     * Are we blocking images?
     *
     * @return boolean  True if blocking images.
     */
    protected function _imgBlock()
    {
        global $injector;

        /* Done on demand, since we potentially save a contacts API call if
         * not needed/used in a message. */
        if (!isset($this->_imptmp['img'])) {
            $this->_imptmp['img'] =
                ($this->_imptmp['inline'] &&
                !$injector->getInstance('IMP_Images')->showInlineImage($this->getConfigParam('imp_contents')));
        }

        return $this->_imptmp['img'];
    }

    /**
     * The HTML image source to use for blocked images.
     *
     * @return string  The HTML image source.
     */
    protected function _imgBlockImg()
    {
        if (!isset($this->_imptmp['blockimg'])) {
            $this->_imptmp['blockimg'] = strval(Horde_Themes::img('spacer_red.png'));
        }

        return $this->_imptmp['blockimg'];
    }

}
