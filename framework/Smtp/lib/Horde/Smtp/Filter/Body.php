<?php
/**
 * Copyright 2014-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Smtp
 */

/**
 * Stream filter to determine whether body needs to be sent as 8-bit
 * (STD 71/RFC 6152) or binary (RFC 3030).
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014-2016 Horde LLC
 * @deprecated Use Horde_Mime_Filter_Encoding instead.
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Smtp
 * @since      1.7.0
 */
class Horde_Smtp_Filter_Body extends php_user_filter
{
    /**
     * Non CR/LF characters.
     *
     * @var integer
     */
    protected $_crlf = 0;

    /**
     * @see stream_filter_register()
     */
    public function onCreate()
    {
        $this->params->body = false;

        return true;
    }

    /**
     * @see stream_filter_register()
     */
    public function filter($in, $out, &$consumed, $closing)
    {
        $skip = ($this->params->body !== false);

        while ($bucket = stream_bucket_make_writeable($in)) {
            if (!$skip) {
                $len = $bucket->datalen;
                $str = $bucket->data;

                for ($i = 0; $i < $len; ++$i) {
                    $chr = ord($str[$i]);

                    switch ($chr) {
                    case 0:
                        /* Only binary data can have NULLs. */
                        $this->params->body = 'binary';
                        $skip = true;
                        break 2;

                    case 10: // LF
                    case 13: // CR
                        $this->_crlf = 0;
                        break;

                    default:
                        /* RFC 2045 [2.8]: 8bit data must be less than 998
                         * characters in length. Otherwise, we are looking at
                         * binary. */
                        if (++$this->_crlf > 998) {
                            $this->params->body = 'binary';
                            $skip = true;
                            break 2;
                        } else if ($chr > 127) {
                            $this->params->body = '8bit';
                        }
                        break;
                    }
                }
            }

            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }

}
