<?php
/**
 * This file contains the Horde_Url class for manipulating URLs.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category Horde
 * @package  Url
 */

/**
 * The Horde_Url class represents a single URL and provides methods for
 * manipulating URLs.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Url
 */
class Horde_Url
{
    /**
     * The basic URL, without query parameters.
     *
     * @var string
     */
    public $url;

    /**
     * Whether to output the URL in the raw URL format or HTML-encoded.
     *
     * @var boolean
     */
    public $raw;

    /**
     * The query parameters.
     *
     * The keys are paramter names, the values parameter values. Array values
     * will be added to the URL using name[]=value notation.
     *
     * @var array
     */
    public $parameters = array();

    /**
     * Any PATH_INFO to be added to the URL.
     *
     * @var string
     */
    public $pathInfo;

    /**
     * The anchor string.
     *
     * @var string
     */
    public $anchor = '';

    /**
     * A callback function to use when converting to a string.
     *
     * @var callback
     */
    public $toStringCallback;

    /**
     * Constructor.
     *
     * @param string $url   The basic URL, with or without query parameters.
     * @param boolean $raw  Whether to output the URL in the raw URL format or
     *                      HTML-encoded.
     */
    public function __construct($url, $raw = null)
    {
        if ($url instanceof Horde_Url) {
            $this->anchor = $url->anchor;
            $this->parameters = $url->parameters;
            $this->pathInfo = $url->pathInfo;
            $this->raw = is_null($raw) ? $url->raw : $raw;
            $this->url = $url->url;
            return;
        }

        if (($pos = strrpos($url, '#')) !== false) {
            $this->anchor = urldecode(substr($url, $pos + 1));
            $url = substr($url, 0, $pos);
        }

        if (($pos = strrpos($url, '?')) !== false) {
            $query = substr($url, $pos + 1);
            $url = substr($url, 0, $pos);

            /* Check if the argument separator has been already
             * htmlentities-ized in the URL. */
            if (preg_match('/&amp;/', $query)) {
                $query = html_entity_decode($query);
                if (is_null($raw)) {
                    $raw = false;
                }
            } elseif (preg_match('/&/', $query)) {
                if (is_null($raw)) {
                    $raw = true;
                }
            }
            $pairs = explode('&', $query);
            foreach ($pairs as $pair) {
                @list($parameter, $value) = explode('=', urldecode($pair), 2);
                $this->add($parameter, $value);
            }
        }

        $this->url = $url;
        $this->raw = $raw;
    }

    /**
     * Returns a clone of this object. Useful for chaining.
     *
     * @return Horde_Url  A clone of this object.
     */
    public function copy()
    {
        $url = clone $this;
        return $url;
    }

    /**
     * Adds one or more query parameters.
     *
     * @param mixed $parameters  Either the name value or an array of
     *                           name/value pairs.
     * @param string $value      If specified, the value part ($parameters is
     *                           then assumed to just be the parameter name).
     *
     * @return Horde_Url  This (modified) object, to allow chaining.
     */
    public function add($parameters, $value = null)
    {
        if (!is_array($parameters)) {
            $parameters = array($parameters => $value);
        }

        foreach ($parameters as $parameter => $value) {
            if (substr($parameter, -2) == '[]') {
                $parameter = substr($parameter, 0, -2);
                if (!isset($this->parameters[$parameter])) {
                    $this->parameters[$parameter] = array();
                }
                $this->parameters[$parameter][] = $value;
            } else {
                $this->parameters[$parameter] = $value;
            }
        }

        return $this;
    }

    /**
     * Removes one ore more parameters.
     *
     * @param mixed $remove  Either a single parameter to remove or an array
     *                       of parameters to remove.
     *
     * @return Horde_Url  This (modified) object, to allow chaining.
     */
    public function remove($parameters)
    {
        if (!is_array($parameters)) {
            $parameters = array($parameters);
        }

        foreach ($parameters as $parameter) {
            unset($this->parameters[$parameter]);
        }

        return $this;
    }

    /**
     * Sets the URL anchor.
     *
     * @param string $anchor  An anchor to add.
     *
     * @return Horde_Url  This (modified) object, to allow chaining.
     */
    public function setAnchor($anchor)
    {
        $this->anchor = $anchor;
        return $this;
    }

    /**
     * Sets the $raw value.  This call can be chained.
     *
     * @param boolean $raw  Whether to output the URL in the raw URL format or
     *                      HTML-encoded.
     *
     * @return Horde_Url  This object, to allow chaining.
     */
    public function setRaw($raw)
    {
        $this->raw = $raw;
        return $this;
    }

    /**
     * Creates the full URL string.
     *
     * @param boolean $raw  Whether to output the URL in the raw URL format or
     *                      HTML-encoded.
     *
     * @return string  The string representation of this object.
     */
    public function toString($raw = false)
    {
        if ($this->toStringCallback) {
            $callback = $this->toStringCallback;
            $this->toStringCallback = null;
            $ret = call_user_func($callback, $this);
            $this->toStringCallback = $callback;
            return $ret;
        }

        $url_params = array();
        foreach ($this->parameters as $parameter => $value) {
            if (is_array($value)) {
                foreach ($value as $val) {
                    $url_params[] = rawurlencode($parameter) . '[]=' . rawurlencode($val);
                }
            } else {
                if (strlen($value)) {
                    $url_params[] = rawurlencode($parameter) . '=' . rawurlencode($value);
                } else {
                    $url_params[] = rawurlencode($parameter);
                }
            }
        }

        $url = $this->url;
        if (strlen($this->pathInfo)) {
            $url = rtrim($url, '/');
            $url .= '/' . $this->pathInfo;
        }
        if (count($url_params)) {
            $url .= '?' . implode($raw ? '&' : '&amp;', $url_params);
        }
        if ($this->anchor) {
            $url .= '#' . rawurlencode($this->anchor);
        }

        return $url;
    }

    /**
     * Creates the full URL string.
     *
     * @return string  The string representation of this object.
     */
    public function __toString()
    {
        return $this->toString($this->raw);
    }

    /**
     * Generates a HTML link tag out of this URL.
     *
     * @param array $attributes A hash with any additional attributes to be
     *                          added to the link. If the attribute name is
     *                          suffixed with ".raw", the attribute value
     *                          won't be HTML-encoded.
     *
     * @return string  An <a> tag representing this URL.
     */
    public function link(array $attributes = array())
    {
        $url = (string)$this;
        $link = '<a';
        if (!empty($url)) {
            $link .= " href=\"$url\"";
        }
        foreach ($attributes as $name => $value) {
            if (!strlen($value)) {
                continue;
            }
            if (substr($name, -4) == '.raw') {
                $link .= ' ' . htmlspecialchars(substr($name, 0, -4))
                    . '="' . $value . '"';
            } else {
                $link .= ' ' . htmlspecialchars($name)
                    . '="' . htmlspecialchars($value) . '"';
            }
        }
        return $link . '>';
    }

    /**
     * Add a unique parameter to the URL to aid in cache-busting.
     *
     * @return Horde_Url  This (modified) object, to allow chaining.
     */
    public function unique()
    {
        return $this->add('u', uniqid(mt_rand()));
    }

    /**
     * URL-safe base64 encoding, with trimmed '='.
     *
     * @param string $string  String to encode.
     *
     * @return string  URL-safe, base64 encoded data.
     */
    static public function uriB64Encode($string)
    {
        return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($string));
    }

    /**
     * Decode URL-safe base64 data, dealing with missing '='.
     *
     * @param string $string  Encoded data.
     *
     * @return string  Decoded data.
     */
    static public function uriB64Decode($string)
    {
        $data = str_replace(array('-', '_'), array('+', '/'), $string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }

}
