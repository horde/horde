<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * Decoding parsing code adapted from rfc822-parser.c (Dovecot 2.2.13)
 *   Original code released under LGPL-2.1
 *   Copyright (c) 2002-2014 Timo Sirainen <tss@iki.fi>
 *
 * @category  Horde
 * @copyright 2002-2014 Timo Sirainen
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mime
 */

/**
 * Decode MIME content parameter data (RFC 2045; 2183; 2231).
 *
 * @author    Timo Sirainen <tss@iki.fi>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2014 Timo Sirainen
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mime
 * @since     2.5.0
 */
class Horde_Mime_ContentParam_Decode extends Horde_Mail_Rfc822
{
    /**
     * Decode content parameter data.
     *
     * @param string $data  Parameter data.
     *
     * @return array  List of parameter key/value combinations.
     */
    public function decode($data)
    {
        $out = array();

        $this->_data = $data;
        $this->_datalen = strlen($data);
        $this->_ptr = 0;

        while ($this->_curr() !== false) {
            $this->_rfc822SkipLwsp();

            $this->_rfc822ParseMimeToken($param);

            if (is_null($param) || ($this->_curr() != '=')) {
                break;
            }

            ++$this->_ptr;
            $this->_rfc822SkipLwsp();

            $value = '';

            if ($this->_curr() == '"') {
                try {
                    $this->_rfc822ParseQuotedString($value);
                } catch (Horde_Mail_Exception $e) {
                    break;
                }
            } else {
                $this->_rfc822ParseMimeToken($value);
                if (is_null($value)) {
                    break;
                }
            }

            $out[$param] = $value;

            $this->_rfc822SkipLwsp();
            if ($this->_curr() != ';') {
                break;
            }

            ++$this->_ptr;
        }

        return $out;
    }

    /**
     */
    protected function _rfc822ParseMimeToken(&$str)
    {
        $length = strspn($this->_data, Horde_Mime_ContentParam::ATEXT_NON_TSPECIAL, $this->_ptr);

        if ($length) {
            $str = substr($this->_data, $this->_ptr, $length);
            $this->_ptr += $length;
            $this->_rfc822SkipLwsp();
        } else {
            $str = null;
        }
    }

}
