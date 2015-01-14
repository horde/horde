<?php
/**
 * Copyright 1999-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 1999-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Renderer for text/plain MIME parts with URLs made into hyperlinks.
 *
 * @author    Anil Madhavapeddy <anil@recoil.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 1999-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Mime_Viewer_Plain extends Horde_Mime_Viewer_Plain
{
    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _render()
    {
        $data = $this->_impRender(false);
        $item = reset($data);
        Horde::startBuffer();
        $GLOBALS['page_output']->includeStylesheetFiles();
        $item['data'] = '<html><head>' . Horde::endBuffer() . '</head><body>' . $item['data'] . '</body></html>';
        $data[key($data)] = $item;
        return $data;
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderInline()
    {
        return $this->_impRender(true);
    }

    /**
     * Render the object.
     *
     * @param boolean $inline  Viewing inline?
     *
     * @return array  See parent::render().
     */
    protected function _impRender($inline)
    {
        global $injector, $prefs, $registry;

        $contents = $this->getConfigParam('imp_contents');

        $cache = $contents->getViewCache();
        $mime_id = $this->_mimepart->getMimeId();

        if (isset($cache->plain[$mime_id])) {
            return array($mime_id => null);
        }

        // Trim extra whitespace in the text.
        $charset = $this->_mimepart->getCharset();
        $text = trim($this->_mimepart->getContents());
        if ($text == '') {
            return array(
                $mime_id => array(
                    'data' => '',
                    'type' => 'text/html; charset=' . $charset
                )
            );
        }

        // Convert to the local charset.
        if ($inline) {
            $text = Horde_String::convertCharset($text, $charset, 'UTF-8');
            $charset = $this->getConfigParam('charset');
        }
        $type = 'text/html; charset=' . $charset;

        // Check for 'flowed' text data.
        if ($this->_mimepart->getContentTypeParameter('format') == 'flowed') {
            $text = $this->_formatFlowed($text, $this->_mimepart->getContentTypeParameter('delsp'));
        } else {
            /* A "From" located at the beginning of a line in the body text
             * will be escaped with a '>' by the IMAP server.  Remove this
             * escape character or else the line will display as being
             * quoted. Flowed conversion would have already taken care of this
             * for us. */
            $text = preg_replace('/(\n+)> ?From(\s+)/', "$1From$2", $text);
        }

        $text = IMP::filterText($text);

        // Build filter stack. Starts with HTML markup and tab expansion.
        $filters = array(
            'text2html' => array(
                'charset' => $charset,
                'parselevel' => $inline ? Horde_Text_Filter_Text2html::MICRO : Horde_Text_Filter_Text2html::MICRO_LINKURL
            ),
            'tabs2spaces' => array(),
        );

        // Highlight quoted parts of an email.
        if ($prefs->getValue('highlight_text')) {
            $hideBlocks = $js_blocks = false;
            if ($registry->getView() !== $registry::VIEW_SMARTMOBILE) {
                $js_blocks = $inline;
                if ($inline) {
                    $show = $prefs->getValue('show_quoteblocks');
                    $hideBlocks = (($show == 'hidden') ||
                                   (($show == 'thread') && ($injector->getInstance('Horde_Variables')->page == 'thread')));
                    if (!$hideBlocks &&
                        in_array($show, array('list', 'listthread'))) {
                        $list_info = $contents->getListInformation();
                        $hideBlocks = $list_info['exists'];
                    }
                }
            }

            if ($js_blocks) {
                $filters['highlightquotes'] = array(
                    'hideBlocks' => $hideBlocks,
                    'noJS' => ($registry->getView() == Horde_Registry::VIEW_DYNAMIC)
                );
            } else {
                $filters['Horde_Text_Filter_Highlightquotes'] = array(
                    'hideBlocks' => $hideBlocks
                );
            }
        }

        // Highlight simple markup of an email.
        if ($prefs->getValue('highlight_simple_markup')) {
            $filters['simplemarkup'] = array();
        }

        // Dim signatures.
        if ($prefs->getValue('dim_signature')) {
            $filters['dimsignature'] = array();
        }

        if ($prefs->getValue('emoticons')) {
            $filters['emoticons'] = array('entities' => true);
        }

        // Run filters.
        $status = array();
        $text = $this->_textFilter($text, array_keys($filters), array_values($filters));

        if (strlen($text)) {
            // Wordwrap.
            $text = str_replace(array('  ', "\n "), array(' &nbsp;', "\n&nbsp;"), $text);
            if (!strncmp($text, ' ', 1)) {
                $text = '&nbsp;' . substr($text, 1);
            }
        } else {
            $error = new IMP_Mime_Status_RenderIssue(
                $this->_mimepart,
                array(
                    _("Cannot display message text."),
                    _("The message part may contain incorrect character set information preventing correct display.")
                )
            );
            $error->action(IMP_Mime_Status::ERROR);
            $status[] = $error;
        }

        return array(
            $mime_id => array(
                'data' => "<div class=\"fixed leftAlign\">\n" . $text . '</div>',
                'status' => $status,
                'type' => $type
            )
        );
    }

    /**
     * Does this MIME part possibly contain embedded MIME parts?
     *
     * @return boolean  True if this driver supports parsing embedded MIME
     *                  parts.
     */
    public function embeddedMimeParts()
    {
        return ($this->getConfigParam('pgp_inline') ||
                $this->getConfigParam('uudecode'));
    }

    /**
     * If this MIME part can contain embedded MIME part(s), and those part(s)
     * exist, return a representation of that data.
     *
     * @return mixed  A Horde_Mime_Part object representing the embedded data.
     *                Returns null if no embedded MIME part(s) exist.
     */
    protected function _getEmbeddedMimeParts()
    {
        $ret = $this->getConfigParam('pgp_inline')
            ? $this->_parsePGP()
            : null;

        return (is_null($ret) && $this->getConfigParam('uudecode'))
            ? $this->_parseUUencode()
            : $ret;
    }

    /**
     * Scan text for inline, armored PGP blocks and, if they exist, convert
     * the part to the embedded MIME representation.
     *
     * @return mixed  See self::_getEmbeddedMimeParts().
     */
    protected function _parsePGP()
    {
        $part = $GLOBALS['injector']->getInstance('Horde_Crypt_Pgp_Parse')->parseToPart(
            new Horde_Stream_Existing(array(
                'stream' => $this->_mimepart->getContents(array('stream' => true))
            )),
            $this->_mimepart->getCharset()
        );

        if (!is_null($part)) {
            $cache = $this->getConfigParam('imp_contents')->getViewCache();
            $cache->plain[$this->_mimepart->getMimeId()] = true;
        }

        return $part;
    }

    /**
     * Scan text for UUencode data an, if it exists, convert the part to the
     * embedded MIME representation.
     *
     * @return mixed  See self::_getEmbeddedMimeParts().
     */
    protected function _parseUUencode()
    {
        $text = Horde_String::convertCharset($this->_mimepart->getContents(), $this->_mimepart->getCharset(), 'UTF-8');

        $files = new Horde_Mime_Uudecode($text);
        if (!count($files)) {
            return null;
        }

        $new_part = new Horde_Mime_Part();
        $new_part->setType('multipart/mixed');

        $text_part = new Horde_Mime_Part();
        $text_part->setType('text/plain');
        $text_part->setCharset($this->getConfigParam('charset'));
        $text_part->setContents(preg_replace("/begin [0-7]{3} .+\r?\n.+\r?\nend/Us", "\n", $text));
        $new_part->addPart($text_part);

        foreach ($files as $file) {
            $uupart = new Horde_Mime_Part();
            $uupart->setType('application/octet-stream');
            $uupart->setContents($file['data']);
            $uupart->setName(strip_tags($file['name']));
            $new_part->addPart($uupart);
        }

        return $new_part;
    }

    /**
     * Output to use if text size is over the limit.
     * See IMP_Contents::renderMIMEPart().
     *
     * @return string  The text to display inline.
     */
    public function overLimitText()
    {
        $stream = $this->_mimepart->getContents(array('stream' => true));
        rewind($stream);

        // Escape text
        $filters = array(
            'text2html' => array(
                'parselevel' => Horde_Text_Filter_Text2html::MICRO
            ),
            'tabs2spaces' => array(),
        );

        return '<div class="fixed">' .
            $this->_textFilter(Horde_String::convertCharset(fread($stream, 1024), $this->_mimepart->getCharset(), 'UTF-8'), array_keys($filters), array_values($filters)) .
            ' [...]</div>';
    }

}
