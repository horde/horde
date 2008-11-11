<?php
/**
 * The Horde_Mime_Message:: class provides methods for creating and
 * manipulating MIME email messages.
 *
 * Copyright 1999-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime
 */
class Horde_Mime_Message extends Horde_Mime_Part
{
    /**
     * Create a Horde_Mime_Message object from a Horde_Mime_Part object.
     * This function can be called statically via:
     *    $mime_message = Horde_Mime_Message::convertMimePart();
     *
     * @todo Is this needed?
     *
     * @param Horde_Mime_Part $mime_part  The Horde_Mime_Part object.
     *
     * @return Horde_Mime_Message  The new Horde_Mime_Message object.
     */
    static public function convertMimePart($mime_part)
    {
        if (!$mime_part->getMimeId()) {
            $mime_part->setMimeId(1);
        }

        $mime_message = new Horde_Mime_Message();
        $mime_message->addPart($mime_part);

        return $mime_message;
    }

    /**
     * Sends this message.
     *
     * @param string $email                 The address list to send to.
     * @param Horde_Mime_Headers $headers   The Horde_Mime_Headers object
     *                                      holding this message's headers.
     * @param string $driver                The Mail:: driver to use.
     * @param array $params                 Any parameters necessary for the
     *                                      Mail driver.
     *
     * @return mixed  True on success, PEAR_Error on error.
     */
    public function send($email, $headers, $driver = null, $params = array())
    {
        if (!isset($driver)) {
            $driver = $GLOBALS['conf']['mailer']['type'];
            $params = $GLOBALS['conf']['mailer']['params'];
        }

        require_once 'Mail.php';
        $mailer = Mail::factory($driver, $params);

        /* Add MIME Headers if they don't already exist. */
        if (!$headers->getValue('MIME-Version')) {
            $headers = $this->addMimeHeaders($headers);
        }
        $headerArray = $headers->toArray($this->getCharset());

        /* Does the SMTP backend support 8BITMIME (RFC 1652) or
         * BINARYMIME (RFC 3030) extensions? Requires PEAR's Mail package
         * version 1.2+ and Net_SMTP version 1.3+. */
        if (($driver == 'smtp') && method_exists($mailer, 'getSMTPObject')) {
            $net_smtp = $mailer->getSMTPObject();
            if (!is_a($net_smtp, 'PEAR_Error') &&
                method_exists($net_smtp, 'getServiceExtensions')) {
                $smtp_ext = $net_smtp->getServiceExtensions();
                $message->strict7bit(false);
                $encoding = $message->getTransferEncoding();
                if (($encoding == '8bit') &&
                    isset($smtp_ext['8BITMIME'])) {
                    $mailer->addServiceExtensionParameter('BODY', '8BITMIME');
                } elseif (($encoding == 'binary') &&
                          isset($smtp_ext['BINARYMIME'])) {
                    $mailer->addServiceExtensionParameter('BODY', 'BINARYMIME');
                } else {
                    $message->strict7bit(true);
                    $encoding = $message->getTransferEncoding();
                }
                $headers->addHeader('Content-Transfer-Encoding', $encoding);
            }
        }

        /* Make sure the message has a trailing newline. */
        $msg = $this->toString();
        if (substr($msg, -1) != "\n") {
            $msg .= "\n";
        }

        $result = $mailer->send(Horde_Mime::encodeAddress($email), $headerArray, $msg);

        if (is_a($result, 'PEAR_Error') && ($driver == 'sendmail')) {
            $error = Horde_Mime_Mail::sendmailError($result->getCode());
            if (is_null($error)) {
                $error = $result;
                $userinfo = null;
            } else {
                $userinfo = $result->toString();
            }
            return PEAR::raiseError($error, null, null, null, $userinfo);
        }

        return $result;
    }

    /**
     * Get the list of MIME headers for this part in an array.
     *
     * @return array  The full set of MIME headers.
     */
    public function getHeaderArray()
    {
        /* Per RFC 2045 [4], this MUST appear in the message headers. */
        return parent::header(array('MIME-Version' => '1.0'));
    }

    /**
     * Parse an array of MIME structure information into a Horde_Mime_Message
     * object.
     * This function can be called statically via:
     *    $mime_message = Horde_Mime_Message::parseStructure();
     *
     * @param array $structure  An array of structure information in the
     *                          following format:
     * <pre>
     * MANDATORY:
     *   'type' - (string) The MIME type
     *   'subtype' - (string) The MIME subtype
     *
     * The array MAY contain the following information:
     *   'disposition' - (string) The disposition type of the part (e.g.
     *                   'attachment', 'inline').
     *   'dparameters' - (array) Attribute/value pairs from the part's
     *                   Content-Disposition header.
     *   'language' - (array) A list of body language values.
     *   'location' - (string) The body content URI.
     *
     * Depending on the MIME type of the part, the array will also contain
     * further information. If labeled as [OPTIONAL], the array MAY
     * contain this information, but only if 'noext' is false and the
     * server returned the requested information. Else, the value is not
     * set.
     *
     * multipart/* parts:
     * ==================
     * 'parts' - (array) An array of subparts (follows the same format as
     *           the base structure array).
     * 'parameters' - [OPTIONAL] (array) Attribute/value pairs from the
     *                part's Content-Type header.
     *
     * All other parts:
     * ================
     * 'parameters' - (array) Attribute/value pairs from the part's
     *                Content-Type header.
     * 'id' - (string) The part's Content-ID value.
     * 'description' - (string) The part's Content-Description value.
     * 'encoding' - (string) The part's Content-Transfer-Encoding value.
     * 'size' - (integer) - The part's size in bytes.
     * 'envelope' - [ONLY message/rfc822] (array) See 'envelope' response.
     * 'structure' - [ONLY message/rfc822] (array) See 'structure'
     *               response.
     * 'lines' - [ONLY message/rfc822 and text/*] (integer) The size of
     *           the body in text lines.
     * 'md5' - [OPTIONAL] (string) The part's MD5 value.
     * </pre>
     *
     * @return object  A Horde_Mime_Message object.
     */
    static public function parseStructure($structure)
    {
        $ob = self::_parseStructure($structure, true);
        $ob->buildMimeIds();
        return $ob;
    }

    /**
     * Parse a subpart of a MIME message into a
     * Horde_Mime_Message/Horde_Mime_Part object.
     *
     * @param array $data      Structure information in the format described
     *                         in parseStructure().
     * @param boolean $rfc822  Force the part to be treated as a
     *                         message/rfc822 part.
     *
     * @return mixed  Returns either a Horde_Mime_Message or a Horde_Mime_Part
     *                object, depending on the part's MIME type.
     */
    static protected function _parseStructure($data, $rfc822 = false)
    {
        $type = $data['type'] . '/' . $data['subtype'];

        if ($rfc822 || ($type == 'message/rfc822')) {
            $ob = new Horde_Mime_Message();
        } else {
            $ob = new Horde_Mime_Part();
        }

        $ob->setType($type);

        if (isset($data['encoding'])) {
            $ob->setTransferEncoding($data['encoding']);
        }

        if (isset($data['disposition'])) {
            $ob->setDisposition($data['disposition']);
            if (!empty($data['dparameters'])) {
                foreach ($data['dparameters'] as $key => $val) {
                    /* Disposition parameters are supposed to be encoded via
                     * RFC 2231, but many mailers do RFC 2045 encoding
                     * instead. */
                    // @todo: RFC 2231 decoding
                    $ob->setDispositionParameter($key, Horde_Mime::decode($val));
                }
            }
        }

        if (isset($data['size'])) {
            $ob->setBytes($data['size']);
        }

        if (isset($data['id'])) {
            $ob->setContentId($data['id']);
        }

        if (!empty($data['parameters'])) {
            foreach ($data['parameters'] as $key => $val) {
                /* Content-type parameters are supposed to be encoded via RFC
                 * 2231, but many mailers do RFC 2045 encoding instead. */
                // @todo: RFC 2231 decoding
                $ob->setContentTypeParameter($key, Horde_Mime::decode($val));
            }
        }

        /* Set the default character set. */
        if (($data['subtype'] == 'text') &&
            (String::lower($ob->getCharset()) == 'us-ascii') &&
            isset($GLOBALS['mime_structure']['default_charset'])) {
            /* @todo - switch to using static variable for this. */
            //$ob->setCharset($GLOBALS['mime_structure']['default_charset']);
        }

        if (isset($data['description'])) {
            $ob->setDescription(Horde_Mime::decode($data['description']));
        }

        /* Set the name. */
        if (!$ob->getName()) {
            $ob->setName($ob->getDispositionParameter('filename'));
        }

        // @todo Handle language, location, md5, lines, envelope

        /* Add multipart parts. */
        if (!empty($data['parts'])) {
            reset($data['parts']);
            while (list(,$val) = each($data['parts'])) {
                $ob->addPart(self::_parseStructure($val));
            }
        } elseif (!empty($data['structure'])) {
            $ob->addPart(self::_parseStructure($data['structure']));
        }

        return $ob;
    }

    /**
     * Attempts to build a Horde_Mime_Message object from message text.
     * This function can be called statically via:
     *    $mime_message = Horde_Mime_Message::parseMessage();
     *
     * @param string $text  The text of the MIME message.
     *
     * @return Horde_Mime_Message  A Horde_Mime_Message object, or false on
     *                             error.
     */
    static public function parseMessage($text)
    {
        /* Set up the options for the mimeDecode class. */
        $decode_args = array(
            'include_bodies' => true,
            'decode_bodies' => false,
            'decode_headers' => false
        );

        require_once 'Mail/mimeDecode.php';
        $mimeDecode = new Mail_mimeDecode($text, Horde_Mime_Part::EOL);
        if (!($ob = $mimeDecode->decode($decode_args))) {
            return false;
        }

        return self::parseStructure(self::_convertMimeDecodeData($ob));
    }

    /**
     * Convert the output from Mail_mimeDecode::decode() into a structure that
     * parse() can handle.
     *
     * @param stdClass $ob  The output from Mail_mimeDecode::decode().
     *
     * @return array  An array of structure information.
     */
    static protected function _convertMimeDecodeData($ob)
    {
        /* Primary content-type. */
        if (isset($ob->ctype_primary)) {
            $part = array(
                'type' => strtolower($ob->ctype_primary),
                'subtype' => isset($ob->ctype_secondary) ? strtolower($ob->ctype_secondary) : 'x-unknown'
            );
        } else {
            $part = array(
                'type' => 'application',
                'subtype' => 'octet-stream'
            );
        }

        /* Content transfer encoding. */
        if (isset($ob->headers['content-transfer-encoding'])) {
            $part['encoding'] = strtolower($ob->headers['content-transfer-encoding']);
        }

        /* Content-type and Disposition parameters. */
        $param_types = array(
            'ctype_parameters' => 'parameters',
            'd_parameters' => 'dparameters'
        );

        foreach ($param_types as $param_key => $param_value) {
            if (isset($ob->$param_key)) {
                $part[$param_value] = array();
                foreach ($ob->$param_key as $key => $val) {
                    $part[$param_value][strtolower($key)] = $val;
                }
            }
        }

        /* Content-Description. */
        if (isset($ob->headers['content-description'])) {
            $part['description'] = $ob->headers['content-description'];
        }

        /* Content-Disposition. */
        if (isset($ob->headers['content-disposition'])) {
            $hdr = $ob->headers['content-disposition'];
            $pos = strpos($hdr, ';');
            if ($pos !== false) {
                $hdr = substr($hdr, 0, $pos);
            }
            $part['disposition'] = strtolower($hdr);
        }

        /* Content-ID. */
        if (isset($ob->headers['content-id'])) {
            $part['id'] = $ob->headers['content-id'];
        }

        /* Get file size (if 'body' text is set). */
        if (isset($ob->body) &&
            ($part['type'] != 'message') &&
            ($part['subtype'] != 'rfc822')) {
            /* Mail_mimeDecode puts an extra linebreak at the end of body
             * text. */
            $size = strlen(str_replace(array("\r\n", "\n"), array("\n", "\r\n"), $ob->body)) - 2;
            $part['size'] = ($size < 0) ? 0 : $size;
        }

        /* Process parts also. */
        if (isset($ob->parts)) {
            $part['parts'] = array();
            reset($ob->parts);
            while (list($key,) = each($ob->parts)) {
                $part['parts'][] = self::_convertMimeDecodeData($ob->parts[$key]);
            }
        }

        return $part;
    }

}
