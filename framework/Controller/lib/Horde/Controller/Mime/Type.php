<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Controller
 * @subpackage Response
 */

/**
 * Handles managing of what types of responses the client can handle and which
 * one was requested.
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Controller
 * @subpackage Response
 */
class Horde_Controller_Mime_Type
{
    public $symbol;
    public $synonyms;
    public $string;

    public static $set             = array();
    public static $lookup          = array();
    public static $extensionLookup = array();
    public static $registered      = false;

    public function __construct($string, $symbol = null, $synonyms = array())
    {
        $this->string   = $string;
        $this->symbol   = $symbol;
        $this->synonyms = $synonyms;
    }

    public function __toString()
    {
        return $this->symbol;
    }

    public static function lookup($string)
    {
        if (!empty(self::$lookup[$string])) {
            return self::$lookup[$string];
        } else {
            return null;
        }
    }

    public static function lookupByExtension($ext)
    {
        if (!empty(self::$extensionLookup[$ext])) {
            return self::$extensionLookup[$ext];
        } else {
            return null;
        }
    }

    public static function register($string, $symbol, $synonyms = array(), $extSynonyms = array())
    {
        $type = new Horde_Controller_Mime_Type($string, $symbol, $synonyms);
        self::$set[] = $type;

        // add lookup strings
        foreach (array_merge((array)$string, $synonyms) as $string) {
            self::$lookup[$string] = $type;
        }

        // add extesnsion lookups
        foreach (array_merge((array)$symbol, $extSynonyms) as $ext) {
            self::$extensionLookup[$ext] = $type;
        }
    }

    /**
     * @todo - actually parse the header. This is simply mocked out
     * with common types for now
     */
    public static function parse($acceptHeader)
    {
        $types = array();

        if (strstr($acceptHeader, 'text/javascript')) {
            if (isset(self::$extensionLookup['js'])) {
                $types[] = self::$extensionLookup['js'];
            }

        } elseif (strstr($acceptHeader, 'text/html')) {
            if (isset(self::$extensionLookup['html'])) {
                $types[] = self::$extensionLookup['html'];
            }

        } elseif (strstr($acceptHeader, 'text/xml')) {
            if (isset(self::$extensionLookup['xml'])) {
                $types[] = self::$extensionLookup['xml'];
            }

        // default to html
        } else {
            if (isset(self::$extensionLookup['html'])) {
                $types[] = self::$extensionLookup['html'];
            }
        }
        return $types;
    }

    /**
     * Register mime types
     * @todo - move this elsewhere?
     */
    public static function registerTypes()
    {
        if (!self::$registered) {
            Horde_Controller_Mime_Type::register("*/*",              'all');
            Horde_Controller_Mime_Type::register("text/plain",       'text', array(), array('txt'));
            Horde_Controller_Mime_Type::register("text/html",        'html', array('application/xhtml+xml'), array('xhtml'));
            Horde_Controller_Mime_Type::register("text/javascript",  'js',   array('application/javascript', 'application/x-javascript'), array('xhtml'));
            Horde_Controller_Mime_Type::register("application/json", 'json');
            Horde_Controller_Mime_Type::register("text/csv",         'csv');
            Horde_Controller_Mime_Type::register("application/xml",  'xml',  array('text/xml', 'application/x-xml'));
            self::$registered = true;
        }
    }

}
