<?php
/**
 * This file contains the Horde_Url class for manipulating URLs.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@curecanti.org>
 * @category Horde
 * @package  Horde_Url
 */

/**
 * The Horde_Url class represents a single URL and provides methods for
 * manipulating URLs.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@curecanti.org>
 * @category Horde
 * @package  Horde_Url
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
     * The query parameters.
     *
     * The keys are paramter names, the values parameter values. Array values
     * will be added to the URL using name[]=value notation.
     *
     * @var array
     */
    public $parameters;

    /**
     * Whether to output the URL in the raw URL format or HTML-encoded.
     *
     * @var boolean
     */
    public $raw;

    /**
     * Constructor.
     *
     * @param string $url   The basic URL, with or without query parameters.
     * @param boolean $raw  Whether to output the URL in the raw URL format or
     *                      HTML-encoded.
     */
    public function __construct($url, $raw = false)
    {
        if (strpos($url, '?') !== false) {
            list($url, $query) = explode('?', $url);

            /* Check if the argument separator has been already
             * htmlentities-ized in the URL. */
            if (preg_match('/=.*?&amp;.*?=/', $query)) {
                $query = html_entity_decode($query);
                $raw = false;
            } elseif (preg_match('/=.*?&.*?=/', $query)) {
                $raw = true;
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
     * Creates the full URL string.
     *
     * @return string  The string representation of this object.
     */
    public function __toString()
    {
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

        return count($url_params)
            ? $this->url . '?' . implode($this->raw ? '&' : '&amp;', $url_params)
            : $this->url;
    }

}