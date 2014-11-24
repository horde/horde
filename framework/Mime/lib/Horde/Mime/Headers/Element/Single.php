<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mime
 */

/**
 * Interface representing a single named header element that can only appear
 * once in a message part.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mime
 * @since     2.5.0
 *
 * @property-read string $full_value  Full header value.
 * @property-read string $value  Header value.
 */
class Horde_Mime_Headers_Element_Single
extends Horde_Mime_Headers_Element
{
    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'full_value':
        case 'value':
            return reset($this->_values);
        }

        return parent::__get($name);
    }

    /**
     */
    public function __toString()
    {
        return $this->value;
    }

    /**
     */
    protected function _setValue($value)
    {
        if ($value instanceof Horde_Mime_Headers_Element) {
            $value = $value->value;
        } elseif (is_array($value)) {
            $value = reset($value);
        }

        $this->_values = array(
            $this->_sanityCheck(Horde_Mime::decode($value))
        );
    }

    /**
     */
    public static function getHandles()
    {
        return array(
            // Mail: RFC 3798
            'disposition-notification-options',
            'original-recipient',
            // MIME: RFC 1864
            'content-md5',
            // MIME: RFC 2045
            'content-transfer-encoding',
            'content-id',
            'content-description',
            // MIME: RFC 2110
            'content-base',
            // MIME: RFC 2424
            'content-duration',
            // MIME: RFC 2557
            'content-location',
            // MIME: RFC 2912 [3]
            'content-features',
            // MIME: RFC 3282
            'content-language',
            // MIME: RFC 3297
            'content-alternative',
            // Lists: RFC 2369
            'list-help',
            'list-unsubscribe',
            'list-subscribe',
            'list-owner',
            'list-post',
            'list-archive',
            // Lists: RFC 2919
            'list-id',
            // Importance: See, e.g., RFC 4356 [2.1.3.3.1]
            'importance',
            // OTHER: X-Priority
            // See: http://kb.mozillazine.org/Emulate_Microsoft_email_clients
            'x-priority'
        );
    }

}
