<?php
/**
 * The IMP_Horde_Mime_Viewer_plain class renders out text/plain MIME parts
 * with URLs made into hyperlinks.
 *
 * Copyright 1999-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime_Viewer
 */
class IMP_Horde_Mime_Viewer_plain extends Horde_Mime_Viewer_plain
{
    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInline()
    {
        global $conf, $prefs;

        // Trim extra whitespace in the text.
        $text = rtrim($this->_mimepart->getContents());
        if ($text == '') {
            return array(
                $this->_mimepart->getMimeId() => array(
                    'data' => '',
                    'status' => array(),
                    'type' => 'text/html; charset=' . NLS::getCharset()
                )
            );
        }

        // Convert to the local charset.
        $text = String::convertCharset($text, $this->_mimepart->getCharset());


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

        // Build filter stack. Starts with HTML markup and tab expansion.
        require_once 'Horde/Text/Filter.php';
        $filters = array(
            'text2html' => array(
                'parselevel' => TEXT_HTML_MICRO,
                'charset' => NLS::getCharset()
            ),
            'tabs2spaces' => array()
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
            $filters['highlightquotes'] = array('hideBlocks' => $hideBlocks);
        }

        // Highlight simple markup of an email.
        if ($prefs->getValue('highlight_simple_markup')) {
            $filters['simplemarkup'] = array();
        }

        // Dim signatures.
        if ($prefs->getValue('dim_signature')) {
            $filters['dimsignature'] = array();
        }

        // Filter bad language.
        if ($prefs->getValue('filtering')) {
            $filters['words'] = array(
                'words_file' => $conf['msgsettings']['filtering']['words'],
                'replacement' => $conf['msgsettings']['filtering']['replacement']
            );
        }

        if ($prefs->getValue('emoticons')) {
            $filters['emoticons'] = array('entities' => true);
        }

        // Run filters.
        $text = Text_Filter::filter($text, array_keys($filters), array_values($filters));

        // Wordwrap.
        $text = str_replace(array('  ', "\n "), array(' &nbsp;', "\n&nbsp;"), $text);
        if (!strncmp($text, ' ', 1)) {
            $text = '&nbsp;' . substr($text, 1);
        }

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => '<div class="fixed leftAlign">' . "\n" . $text . '</div>',
                'status' => array(),
                'type' => 'text/html; charset=' . NLS::getCharset()
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
        return (!empty($GLOBALS['conf']['utils']['gnupg']) && $GLOBALS['prefs']->getValue('pgp_scan_body')) || $this->getConfigParam('uudecode');
    }

    /**
     * If this MIME part can contain embedded MIME parts, and those embedded
     * MIME parts exist, return a list of MIME parts that contain the embedded
     * MIME part information.
     *
     * @return mixed  An array of Horde_Mime_Part objects, with the key as
     *                the ID, or null if no embedded MIME parts exist.
     */
    public function getEmbeddedMimeParts()
    {
        $ret = null;

        if (!empty($GLOBALS['conf']['utils']['gnupg']) &&
            $GLOBALS['prefs']->getValue('pgp_scan_body')) {
            $ret = $this->_parsePGP();
        }

        if (is_null($ret) && $this->getConfigParam('uudecode')) {
            $ret = $this->_parseUUencode();
        }

        return $ret;
    }

    /*
     */
    protected function _parsePGP()
    {
        /* Avoid infinite loop. */
        $imp_pgp = &Horde_Crypt::singleton(array('imp', 'pgp'));
        $parts = $imp_pgp->parsePGPData($this->_mimepart->getContents());
        if (empty($parts) ||
            ((count($parts) == 1) &&
             ($parts[0]['type'] == Horde_Crypt_pgp::ARMOR_TEXT))) {
            return null;
        }

        $new_part = new Horde_Mime_Part();
        $new_part->setType('multipart/mixed');
        $charset = $this->_mimepart->getCharset();
        $mime_id = $this->_mimepart->getMimeId();

        while (list(,$val) = each($parts)) {
            switch ($val['type']) {
            case Horde_Crypt_pgp::ARMOR_TEXT:
                $part = new Horde_Mime_Part();
                $part->setType('text/plain');
                $part->setCharset($charset);
                $part->setContents(implode("\n", $val['data']));
                $new_part->addPart($part);
                break;

            case Horde_Crypt_pgp::ARMOR_PUBLIC_KEY:
                $part = new Horde_Mime_Part();
                $part->setType('application/pgp-keys');
                $part->setContents(implode("\n", $val['data']));
                $new_part->addPart($part);
                break;

            case Horde_Crypt_pgp::ARMOR_MESSAGE:
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

            case Horde_Crypt_pgp::ARMOR_SIGNED_MESSAGE:
                if (($sig = current($parts)) &&
                    ($sig['type'] == Horde_Crypt_pgp::ARMOR_SIGNATURE)) {
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
                    $part2->setType('application/x-imp-pgp-signature');
                    $part2->setContents(String::convertCharset(implode("\n", $val['data']) . "\n" . implode("\n", $sig['data']), $charset));

                    $part->addPart($part1);
                    $part->addPart($part2);
                    $new_part->addPart($part);

                    next($parts);
                }
            }
        }

        $new_part->setMimeId($mime_id);

        return array($mime_id => $new_part);
    }

    protected function _parseUUencode()
    {
        $text = String::convertCharset($this->_mimepart->getContents(), $this->_mimepart->getCharset());

        /* Don't want to use convert_uudecode() here as there may be multiple
         * files residing in the text. */
        require_once 'Mail/mimeDecode.php';
        $files = &Mail_mimeDecode::uudecode($text);
        if (empty($files)) {
            return null;
        }

        $new_part = new Horde_Mime_Part();
        $new_part->setType('multipart/mixed');
        $mime_id = $this->_mimepart->getMimeId();

        $text_part = new Horde_Mime_Part();
        $text_part->setType('text/plain');
        $text_part->setCharset(NLS::getCharset());
        $text_part->setContents(preg_replace("/begin ([0-7]{3}) (.+)\r?\n(.+)\r?\nend/Us", "\n", $text));
        $new_part->addPart($text_part);

        reset($files);
        while (list(,$file) = each($files)) {
            $uupart = new Horde_Mime_Part();
            $uupart->setType('application/octet-stream');
            $uupart->setContents($file['filedata']);
            $uupart->setName(strip_tags($file['filename']));
            $new_part->addPart($uupart);
        }

        $new_part->setMimeId($mime_id);

        return array($mime_id => $new_part);
    }
}
