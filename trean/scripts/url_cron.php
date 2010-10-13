#!/usr/bin/env php
<?php
/**
 * $Horde: trean/scripts/check_links.php,v 1.30 2009/01/06 18:02:14 jan Exp $
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Ben Chavet <ben@horde.org>
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

// Find the base file path of Horde.
@define('HORDE_BASE', dirname(__FILE__) . '/../..');

// Find the base file path of Trean.
@define('TREAN_BASE', dirname(__FILE__) . '/..');

// Do CLI checks and environment setup first.
require_once HORDE_BASE . '/lib/core.php';
require_once 'Horde/CLI.php';

// Make sure no one runs this from the web.
if (!Horde_CLI::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment - make sure there's no time limit, init
// some variables, etc.
Horde_CLI::init();

// Now load the Registry and setup conf, etc.
$registry = &Registry::singleton();
$registry->pushApp('trean', false);

// Include needed libraries.
require_once TREAN_BASE . '/lib/Trean.php';
require_once TREAN_BASE . '/lib/Bookmarks.php';

// Create Trean objects.
$trean_db = Trean::getDb();
$trean_shares = new Trean_Bookmarks();

$ids = $trean_db->queryCol('SELECT bookmark_id FROM trean_bookmarks');
foreach ($ids as $bookmark_id) {
    $bookmark = $trean_shares->getBookmark($bookmark_id);
    $check = @_getHeaders($bookmark->url, 1);
    if (!$check) {
        $bookmark->http_status = 'error';
    } else {
        $status = explode(' ', $check[0]);
        if ($status[1] != $bookmark->http_status) {
            $bookmark->http_status = $status[1];
        }

        if ($bookmark->http_status == '200' || $bookmark->http_status == '302') {
            $body = get_body($bookmark);
            if ($favicon = get_favicon($bookmark, $body)) {
                $bookmark->favicon = $favicon;
            }
        }

        // If we've been redirected, update the bookmark's URL.
        if (isset($check['Location']) && $check['Location'] != $bookmark->url) {
            $location = @parse_url($check['Location']);
            if ($location && !empty($location['scheme'])) {
                $bookmark->url = $check['Location'];
                $bookmark->http_status = '';
            }
        }
    }

    $bookmark->save();
}

/**
 */
function _getHeaders($url, $format = 0)
{
    $url_info = @parse_url($url);
    $port = isset($url_info['port']) ? $url_info['port'] : 80;
    $fp = @fsockopen($url_info['host'], $port, $errno, $errstr, 30);

    if (!$fp) {
        return false;
    }

    // Generate HTTP/1.0 HEAD request.
    $head = 'HEAD ' .
        (empty($url_info['path']) ? '/' : $url_info['path']) .
        (empty($url_info['query']) ? '' : '?' . $url_info['query']) .
        " HTTP/1.0\r\nHost: " . $url_info['host'] . "\r\n\r\n";

    $headers = array();
    fputs($fp, $head);

    stream_set_timeout($fp, 10);
    while (!feof($fp)) {
        $info = stream_get_meta_data($fp);
        if ($info['timed_out']) {
            return false;
        }
        if ($header = trim(fgets($fp, 1024))) {
            if ($format == 1) {
                $tmp = explode(':', $header);
                $key = array_shift($tmp);
                if ($key == $header) {
                    $headers[] = $header;
                } else {
                    $headers[$key] = substr($header, strlen($key) + 2);
                }
            } else {
                $headers[] = $header;
            }
        }
    }
    return $headers;
}

function get_body($bookmark)
{
    $body = file_get_contents($bookmark->url);

    // @TODO get headers

    get_favicon($bookmark, $body);


/**
 * Attempts to retrieve a favicon for the given bookmark.  If successful, the
 * favicon is stored in the trean_urls table for later use.
 */
function get_favicon($bookmark, $body)
{
    global $favicon;
    $favicon = '';

    // Attempt to parse a favicon.
    $error = false;
    $xml_parser = xml_parser_create();
    xml_set_element_handler($xml_parser, 'startElement', 'endElement');
    if (!xml_parse($xml_parser, $body, true)) {
        $error = true;
    }
    xml_parser_free($xml_parser);

    $url = parse_url($bookmark->url);

    // If parsing a favicon failed, look for favicon.ico.
    if (!$favicon) {
        $headers = @_getHeaders($url['scheme'] . '://' . $url['host'] . '/favicon.ico', 1);
        if ($headers) {
            $status = explode(' ', $headers[0]);
            if ($status[1] == '200') {
                $favicon = $url['scheme'] . '://' . $url['host'] . '/favicon.ico';
            } else {
                if (isset($url['path'])) {
                    $path = pathinfo($url['path']);
                } else {
                    $path = array('dirname' => '');
                }
                $headers = @_getHeaders($url['scheme'] . '://' . $url['host'] . $path['dirname'] . '/favicon.ico', 1);
                if ($headers) {
                    $status = explode(' ', $headers[0]);
                    if ($status[1] == '200') {
                        $favicon = $url['scheme'] . '://' . $url['host'] . $path['dirname'] . '/favicon.ico';
                    }
                }
            }
        }
    }

    // If a favicon was found, try to get it.
    if ($favicon) {
        // Make sure $favicon is a full URL.
        if (false && substr(strtolower($favicon), 0, 7) != 'http://') {
            if (substr($favicon, 0, 1) == '/') {
                $favicon = $url['scheme'] . '://' . $url['host'] . $favicon;
            } else {
                $path = pathinfo($url['path']);
                $favicon = $url['scheme'] . '://' . $url['host'] . $path['dirname'] . '/' . $favicon;
            }
        }

        // Attempt to read and store $favicon.
        if ($data = @file_get_contents($favicon)) {
            $info = pathinfo($favicon);
            $favicon_ext = $info['extension'];
        }
    }

    return false;
}

/**
 * get_favicon html parsing helper function
 */
function startElement($parser, $name, $attrs)
{
    global $favicon;

    if (strtoupper($name) == 'LINK' && is_array($attrs)) {
        $use = false;
        $href = '';
        foreach ($attrs as $key => $val) {
            if (strtoupper($key) == 'REL' &&
                (strtoupper($val) == 'SHORTCUT ICON' || strtoupper($val) == 'ICON')) {
                $use = true;
            }
            if (strtoupper($key) == 'HREF') {
                $href = $val;
            }
        }
        if ($use && $href) {
            $favicon = $href;
        }
    }
}

/**
 * get_favicon html parsing helper function
 */
function endElement($parser, $name)
{
}
