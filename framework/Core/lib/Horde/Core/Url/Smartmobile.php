<?php
/**
 * Extend the base URL class to allow for use with the URL parameter scheme
 * used in Horde's smartmobile framework.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category Horde
 * @package  Core
 */

/**
 * Extend the base URL class to allow for use with the URL parameter scheme
 * used in Horde's smartmobile framework.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Core
 */
class Horde_Core_Url_Smartmobile extends Horde_Url
{
    /**
     * Creates the full URL string.
     *
     * @param boolean $raw   Whether to output the URL in the raw URL format
     *                       or HTML-encoded.
     * @param boolean $full  Output the full URL?
     *
     * @return string  The string representation of this object.
     */
    public function toString($raw = false, $full = true)
    {
        if ($this->toStringCallback || !strlen($this->anchor)) {
            return parent::__toString($raw, $full);
        }

        $url = $full
            ? $this->url
            : parse_url($this->url, PHP_URL_PATH);

        if (strlen($this->pathInfo)) {
            $url = rtrim($url, '/');
            $url .= '/' . $this->pathInfo;
        }

        if ($this->anchor) {
            $url .= '#' . ($raw ? $this->anchor : rawurlencode($this->anchor));
        }

        if ($params = $this->_getParameters()) {
            $url .= '?' . implode($raw ? '&' : '&amp;', $params);
        }

        return strval($url);
    }

}
