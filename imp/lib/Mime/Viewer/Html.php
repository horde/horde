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
     * Temp array for storing data when parsing the HTML document.
     *
     * @var array
     */
    protected $_tmp = array();

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
                    'type' => 'text/html; charset=' . $GLOBALS['registry']->getCharset()
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
                'type' => 'text/html; charset=' . $GLOBALS['registry']->getCharset()
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
            $data = $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($data, 'Html2text', array('wrap' => false));

            // Filter bad language.
            return array(
                'data' => IMP::filterText($data),
                'status' => array(),
                'type' => 'text/plain; charset=' . $GLOBALS['registry']->getCharset()
            );
        }

        $data = $this->parseHtml($inline, $data)->saveHTML();

        if ($this->_tmp['imgblock']) {
            $status[] = array(
                'icon' => Horde::img('mime/image.png'),
                'text' => array(
                    _("Images have been blocked to protect your privacy."),
                    Horde::link('#', '', 'unblockImageLink') . _("Show Images?") . '</a>'
                )
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

        $filters = array();
        if ($GLOBALS['prefs']->getValue('emoticons')) {
            $filters['emoticons'] = array(
                'entities' => true
            );
        }

        if ($inline) {
            $filters['emails'] = array();
        }

        if (!empty($filters)) {
            $data = $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($data, array_keys($filters), array(array_values($filters)));
        }

        /* Filter bad language. */
        $data = IMP::filterText($data);

        return array(
            'data' => $data,
            'status' => $status,
            'type' => $this->_mimepart->getType(true)
        );
    }

    /**
     */
    public function parseHtml($inline, $data)
    {
        $this->_tmp = array(
            'img' => ($inline && $GLOBALS['prefs']->getValue('html_image_replacement') && !$this->_inAddressBook()),
            'imgblock' => false,
            'inline' => $inline,
            'target' => (is_null($this->newwinTarget) ? 'target_' . uniqid(mt_rand()) : $this->newwinTarget)
        );

        /* Image filtering. */
        if ($this->_tmp['img'] && is_null($this->blockimg)) {
            $this->blockimg = Horde::url(Horde_Themes::img('spacer_red.png'), true, -1);
        }

        $old_error = libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML($data);
        if ($old_error) {
            libxml_use_internal_errors(false);
        }

        $this->_node($doc, $doc);

        return $doc;
    }

    /**
     * Determine whether the sender appears in an available addressbook.
     *
     * @return boolean  Does the sender appear in an addressbook?
     */
    protected function _inAddressBook()
    {
        if (empty($this->_params['contents'])) {
            return false;
        }

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

    /**
     * Process DOM node.
     *
     * @param DOMDocument $doc  Document node.
     * @param DOMElement $node  Element node.
     */
    protected function _node($doc, $node)
    {
        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                if ($child instanceof DOMElement) {
                    switch (strtolower($child->tagName)) {
                    case 'a':
                    case 'area':
                        /* Convert links to open in new windows. Ignore
                         * mailto: links, links that have an "#xyz" anchor,
                         * and links that already have a target. */
                        if (!$child->hasAttribute('target') &&
                            $child->hasAttribute('href')) {
                            $url = parse_url($child->getAttribute('href'));
                            if (empty($url['fragment']) &&
                                ($url['scheme'] != 'mailto:')) {
                                $child->setAttribute('target', $this->_tmp['target']);
                            }
                        }
                        break;

                    case 'img':
                    case 'input':
                        if ($this->_tmp['img'] && $child->hasAttribute('src')) {
                            $child->setAttribute('htmlimgblocked', $child->getAttribute('src'));
                            $child->setAttribute('src', $this->blockimg);
                            $this->_tmp['imgblock'] = true;
                        }
                        break;

                    case 'table':
                        /* If displaying inline (in IFRAME), tables with 100%
                         * height seems to confuse many browsers re: the
                         * iframe internal height. */
                        if ($this->_tmp['inline'] &&
                            $child->hasAttribute('height') &&
                            ($child->getAttribute('height') == '100%')) {
                            $child->removeAttribute('height');
                        }

                        // Fall-through

                    case 'body':
                    case 'td':
                        if ($this->_tmp['img'] &&
                            $child->hasAttribute('background')) {
                            $child->setAttribute('htmlimgblocked', $child->getAttribute('background'));
                            $child->setAttribute('background', $this->blockimg);
                            $this->_tmp['imgblock'] = true;
                        }
                        break;
                    }

                    if ($this->_tmp['img'] && $child->hasAttribute('style')) {
                        $this->_tmp['child'] = $child;
                        $style = preg_replace_callback('/(background(?:-image)?:[^;}]*(?:url\(["\']?))(.*?)((?:["\']?\)))/i', array($this, '_styleCallback'), $child->getAttribute('style'), -1, $matches);
                        if ($matches) {
                            $child->setAttribute('style', $style);
                        }
                    }
                }

                $this->_node($doc, $child);
            }
        }
    }

    /**
     * preg_replace_callback() callback for style/background matching.
     *
     * @param array $matches  The list of matches.
     *
     * @return string  The replacement image string.
     */
    protected function _styleCallback($matches)
    {
        $this->_tmp['child']->setAttribute('htmlimgblocked', $matches[2]);
        return $matches[1] . $this->blockimg . $matches[3];
    }

}
