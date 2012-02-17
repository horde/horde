<?php
/**
 * Portions Copyright 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Feed
 */

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Feed
 */
class Horde_Feed
{
    /**
     * Create a Feed object based on a DOMDocument.
     *
     * @param DOMDocument $doc The DOMDocument object to import.
     *
     * @throws Horde_Feed_Exception
     *
     * @return Horde_Feed_Base The feed object imported from $doc
     */
    public static function create(DOMDocument $doc, $uri = null)
    {
        // Try to find the base feed element or a single <entry> of an
        // Atom feed.
        if ($feed = $doc->getElementsByTagName('feed')->item(0)) {
            // Return an Atom feed.
            return new Horde_Feed_Atom($feed, $uri);
        } elseif ($entry = $doc->getElementsByTagName('entry')->item(0)) {
            // Return an Atom single-entry feed.
            $feeddoc = new DOMDocument($doc->version,
                                       $doc->actualEncoding);
            $feed = $feeddoc->appendChild($feeddoc->createElement('feed'));
            $feed->appendChild($feeddoc->importNode($entry, true));

            return new Horde_Feed_Atom($feed, $uri);
        }

        // Try to find the base feed element of an RSS feed.
        if ($channel = $doc->getElementsByTagName('channel')->item(0)) {
            // Return an RSS feed.
            return new Horde_Feed_Rss($channel, $uri);
        }

        // Try to find an outline element of an OPML blogroll.
        if ($outline = $doc->getElementsByTagName('outline')->item(0)) {
            // Return a blogroll feed.
            return new Horde_Feed_Blogroll($doc->documentElement, $uri);
        }

        // $doc does not appear to be a valid feed of the supported
        // types.
        throw new Horde_Feed_Exception('Invalid or unsupported feed format: '
                                       . substr($doc->saveXML(), 0, 80) . '...');
    }

    /**
     * Reads a feed represented by $string.
     *
     * @param string $string The XML content of the feed.
     * @param string $uri The feed's URI location, if known.
     *
     * @throws Horde_Feed_Exception
     *
     * @return Horde_Feed_Base
     */
    public static function read($string, $uri = null)
    {
        // Load the feed as a DOMDocument object.
        libxml_use_internal_errors(true);
        $doc = new DOMDocument;
        $doc->recover = true;
        $loaded = $doc->loadXML($string);
        if (!$loaded) {
            $loaded = $doc->loadHTML($string);
            if (!$loaded) {
                self::_exception('DOMDocument cannot parse XML', libxml_get_last_error());
            }
        }

        return self::create($doc);
    }

    /**
     * Read a feed located at $uri
     *
     * @param string $uri The URI to fetch the feed from.
     * @param Horde_Http_Client $httpclient The HTTP client to use.
     *
     * @throws Horde_Feed_Exception
     *
     * @return Horde_Feed_Base
     */
    public static function readUri($uri, Horde_Http_Client $httpclient = null)
    {
        if (is_null($httpclient)) {
            $httpclient = new Horde_Http_Client();
        }

        try {
            $response = $httpclient->get($uri);
        } catch (Horde_Http_Exception $e) {
            throw new Horde_Feed_Exception('Error reading feed: ' . $e->getMessage());
        }
        if ($response->code != 200) {
            throw new Horde_Feed_Exception('Unable to read feed, got response code ' . $response->code);
        }
        $feed = $response->getBody();
        return self::read($feed, $uri);
    }

    /**
     * Read a feed from $filename
     *
     * @param string $filename The location of the feed file on an accessible
     * filesystem or through an available stream wrapper.
     *
     * @throws Horde_Feed_Exception
     *
     * @return Horde_Feed_Base
     */
    public static function readFile($filename)
    {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument;
        $doc->recover = true;
        $filename = urlencode($filename);
        $loaded = $doc->load($filename);
        if (!$loaded) {
            $loaded = $doc->loadHTMLFile($filename);
            if (!$loaded) {
                self::_exception('File could not be read or parsed', libxml_get_last_error());
            }
        }

        return self::create($doc);
    }

    /**
     * Builds an exception message from a libXMLError object.
     *
     * @param string $msg         An error message.
     * @param libXMLError $error  An error object.
     *
     * @throws Horde_Feed_Exception
     */
    protected static function _exception($msg, $error)
    {
        if ($error) {
            $msg .= ': ' . $error->message;
            if ($error->file) {
                $msg .= sprintf(' in file %s, line %d, column %d',
                                $error->file, $error->line, $error->column);
            }
        }
        throw new Horde_Feed_Exception($msg);
    }
}
