<?php
/**
 * Rewrites address information in a mail template.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Filter
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Filter
 */

/**
 * Rewrites address information in a mail template.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category   Kolab
 * @package    Kolab_Filter
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Filter
 */
class Horde_Kolab_Filter_Helper_AddressFilter
extends php_user_filter
{
    public $_previous = '';

    public $_sender;

    public $_recipient;

    public function onCreate()
    {
        $this->_sender = isset($this->params['sender']) ? $this->params['sender'] : '';
        $this->_recipient = isset($this->params['recipient']) ? $this->params['recipient'] : '';
        $this->_previous = '';
    }

    public function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            if (!empty($this->_previous)) {
                $bucket->data = $this->_previous . $bucket->data;
                $this->_previous = '';
            }
            if (!feof($this->stream) && preg_match('/(%[12]\$|%[12]|%)$/', $bucket->data)) {
                $this->_previous .= $bucket->data;
                return PSFS_FEED_ME;
            }
            $consumed += $bucket->datalen;
            if (preg_match('/%([12])\$s/', $bucket->data, $matches)) {
                if ($matches[1] == '1') {
                    $bucket->data = preg_replace('/%1\$s/', $this->_sender, $bucket->data);
                } else {
                    $bucket->data = preg_replace('/%2\$s/', $this->_recipient, $bucket->data);
                }
            }
            $bucket->datalen = strlen($bucket->data);
            stream_bucket_append($out, $bucket);
        }
        if (!empty($this->_previous)) {
            if ($closing) {
                $bucket = stream_bucket_new($this->stream, $this->_previous);
                $bucket->data = $this->_previous;
                $consumed += strlen($this->_previous);
                $this->_previous = '';
                stream_bucket_append($out, $bucket);
            }
        }
        return PSFS_PASS_ON;
    }
}
