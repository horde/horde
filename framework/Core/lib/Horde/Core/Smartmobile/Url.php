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
class Horde_Core_Smartmobile_Url extends Horde_Url
{
    /**
     * The URL used as the base for the smartmobile anchor.
     *
     * @var Horde_Url
     */
    protected $_baseUrl;

    /**
     * Constructor.
     *
     * @param Horde_Url $url   The basic URL.
     * @param boolean $raw     Whether to output the URL in the raw URL format
     *                         or HTML-encoded.
     */
    public function __construct($url = null, $raw = null)
    {
        if (is_null($url)) {
            $url = new Horde_Url();
        }
        if (!($url instanceof Horde_Url)) {
            throw new InvalidArgumentException('First argument to Horde_Core_Smartmobile_Url constructor must be a Horde_Url object');
        }
        $this->_baseUrl = $url;
        parent::__construct('', $raw);
    }

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
            $baseUrl = $this->_baseUrl->copy();
            $baseUrl->parameters = array_merge($baseUrl->parameters,
                                               $this->parameters);
            if (strlen($this->pathInfo)) {
                $baseUrl->pathInfo = $this->pathInfo;
            }
            return $baseUrl->toString($raw, $full);
        }

        $url = $this->_baseUrl->toString($raw, $full);

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
