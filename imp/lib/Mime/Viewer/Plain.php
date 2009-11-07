<?php
/**
 * The IMP_Horde_Mime_Viewer_Plain class renders out text/plain MIME parts
 * with URLs made into hyperlinks.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime_Viewer
 */
class IMP_Horde_Mime_Viewer_Plain extends Horde_Mime_Viewer_Plain
{
    /**
     * Cached data.
     *
     * @var array
     */
    static protected $_cache = array();

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInline()
    {
        global $conf, $prefs;

        $mime_id = $this->_mimepart->getMimeId();

        if (isset(self::$_cache[$mime_id])) {
            return null;
        }

        $type = 'text/html; charset=' . Horde_Nls::getCharset();

        // Trim extra whitespace in the text.
        $text = trim($this->_mimepart->getContents());
        if ($text == '') {
            return array(
                $mime_id => array(
                    'data' => '',
                    'status' => array(),
                    'type' => $type
                )
            );
        }

        // Convert to the local charset.
        $text = Horde_String::convertCharset($text, $this->_mimepart->getCharset());

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

        /* Done processing if in mimp mode. */
        if ($_SESSION['imp']['view'] == 'mimp') {
            return array(
                $mime_id => array(
                    'data' => $text,
                    'status' => array(),
                    'type' => $type
                )
            );
        }

        // Build filter stack. Starts with HTML markup and tab expansion.
        $filters = array(
            'text2html' => array(
                'parselevel' => Horde_Text_Filter_Text2html::MICRO,
                'charset' => Horde_Nls::getCharset()
            ),
            'tabs2spaces' => array(),
        );

        // Highlight quoted parts of an email.
        if ($prefs->getValue('highlight_text')) {
            $show = $prefs->getValue('show_quoteblocks');
            $hideBlocks = ($show == 'hidden') ||
                (($show == 'thread') && (basename(Horde::selfUrl()) == 'thread.php'));
            if (!$hideBlocks && in_array($show, array('list', 'listthread'))) {
                $header = $this->_params['contents']->getHeaderOb();
                $imp_ui = new IMP_UI_Message();
                $list_info = $imp_ui->getListInformation($header);
                $hideBlocks = $list_info['exists'];
            }
            $filters['highlightquotes'] = array('hideBlocks' => $hideBlocks, 'outputJS' => false);
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
        $text = Horde_Text_Filter::filter($text, array_keys($filters), array_values($filters));

        // Wordwrap.
        $text = str_replace(array('  ', "\n "), array(' &nbsp;', "\n&nbsp;"), $text);
        if (!strncmp($text, ' ', 1)) {
            $text = '&nbsp;' . substr($text, 1);
        }

        return array(
            $mime_id => array(
                'data' => "<div class=\"fixed leftAlign\">\n" . $text . '</div>',
                'status' => array(),
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
        return (!empty($GLOBALS['conf']['gnupg']['path']) && $GLOBALS['prefs']->getValue('pgp_scan_body')) || $this->getConfigParam('uudecode');
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
        $ret = null;

        if (!empty($GLOBALS['conf']['gnupg']['path']) &&
            $GLOBALS['prefs']->getValue('pgp_scan_body')) {
            $ret = $this->_parsePGP();
        }

        if (is_null($ret) && $this->getConfigParam('uudecode')) {
            $ret = $this->_parseUUencode();
        }

        return $ret;
    }

    /**
     * Scan text for armored PGP blocks and, if they exist, convert the part
     * to the embedded MIME representation.
     *
     * @return mixed  See self::_getEmbeddedMimeParts().
     */
    protected function _parsePGP()
    {
        /* Avoid infinite loop. */
        $imp_pgp = Horde_Crypt::singleton(array('IMP', 'Pgp'));
        $parts = $imp_pgp->parsePGPData($this->_mimepart->getContents());
        if (empty($parts) ||
            ((count($parts) == 1) &&
             ($parts[0]['type'] == Horde_Crypt_Pgp::ARMOR_TEXT))) {
            return null;
        }

        $new_part = new Horde_Mime_Part();
        $new_part->setType('multipart/mixed');
        $charset = $this->_mimepart->getCharset();
        $mime_id = $this->_mimepart->getMimeId();

        while (list(,$val) = each($parts)) {
            switch ($val['type']) {
            case Horde_Crypt_Pgp::ARMOR_TEXT:
                $part = new Horde_Mime_Part();
                $part->setType('text/plain');
                $part->setCharset($charset);
                $part->setContents(implode("\n", $val['data']));
                $new_part->addPart($part);
                break;

            case Horde_Crypt_Pgp::ARMOR_PUBLIC_KEY:
                $part = new Horde_Mime_Part();
                $part->setType('application/pgp-keys');
                $part->setContents(implode("\n", $val['data']));
                $new_part->addPart($part);
                break;

            case Horde_Crypt_Pgp::ARMOR_MESSAGE:
                $part = new Horde_Mime_Part();
                $part->setType('multipart/signed');
                // TODO: add micalg parameter
                $part->setContentTypeParameter('protocol', 'application/pgp-encrypted');

                $part1 = new Horde_Mime_Part();
                $part1->setType('application/pgp-encrypted');
                $part1->setContents("Version: 1\n");

                $part2 = new Horde_Mime_Part();
                $part2->setType('application/octet-stream');
                $part2->setContents($message_encrypt);
                $part2->setDisposition('inline');

                $part->addPart($part1);
                $part->addPart($part2);

                $new_part->addPart($part);
                break;

            case Horde_Crypt_Pgp::ARMOR_SIGNED_MESSAGE:
                if (($sig = current($parts)) &&
                    ($sig['type'] == Horde_Crypt_Pgp::ARMOR_SIGNATURE)) {
                    $part = new Horde_Mime_Part();
                    $part->setType('multipart/signed');
                    // TODO: add micalg parameter
                    $part->setContentTypeParameter('protocol', 'application/pgp-signature');

                    $part1 = new Horde_Mime_Part();
                    $part1->setType('text/plain');
                    $part1->setCharset($charset);

                    $part1_data = implode("\n", $val['data']);
                    $part1->setContents(substr($part1_data, strpos($part1_data, "\n\n") + 2));

                    $part2 = new Horde_Mime_Part();
                    $part2->setType('application/pgp-signature');
                    $part2->setContents(Horde_String::convertCharset(implode("\n", $val['data']) . "\n" . implode("\n", $sig['data']), $charset));
                    // A true pgp-signature part would only contain the
                    // detached signature. However, we need to carry around
                    // the entire armored text to verify correctly. Use a
                    // IMP-specific content-type parameter to clue the PGP
                    // driver into this fact.
                    $part2->setMetadata('imp-pgp-signature', true);

                    $part->addPart($part1);
                    $part->addPart($part2);
                    $new_part->addPart($part);

                    next($parts);
                }
            }
        }

        self::$_cache[$mime_id] = true;

        return $new_part;
    }

    /**
     * Scan text for UUencode data an, if it exists, convert the part to the
     * embedded MIME representation.
     *
     * @return mixed  See self::_getEmbeddedMimeParts().
     */
    protected function _parseUUencode()
    {
        $text = Horde_String::convertCharset($this->_mimepart->getContents(), $this->_mimepart->getCharset());

        $files = Horde_Mime::uudecode($text);
        if (empty($files)) {
            return null;
        }

        $new_part = new Horde_Mime_Part();
        $new_part->setType('multipart/mixed');
        $mime_id = $this->_mimepart->getMimeId();

        $text_part = new Horde_Mime_Part();
        $text_part->setType('text/plain');
        $text_part->setCharset(Horde_Nls::getCharset());
        $text_part->setContents(preg_replace("/begin [0-7]{3} .+\r?\n.+\r?\nend/Us", "\n", $text));
        $new_part->addPart($text_part);

        reset($files);
        while (list(,$file) = each($files)) {
            $uupart = new Horde_Mime_Part();
            $uupart->setType('application/octet-stream');
            $uupart->setContents($file['data']);
            $uupart->setName(strip_tags($file['name']));
            $new_part->addPart($uupart);
        }

        return $new_part;
    }

}
