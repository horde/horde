<?php
/**
 * Utility functions for the Horde IMAP client.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */
class Horde_Imap_Client_Utils
{
    /**
     * Parse a POP3 (RFC 2384) or IMAP (RFC 5092/5593) URL.
     *
     * Absolute IMAP URLs takes one of the following forms:
     *   - imap://<iserver>[/]
     *   - imap://<iserver>/<enc-mailbox>[<uidvalidity>][?<enc-search>]
     *   - imap://<iserver>/<enc-mailbox>[<uidvalidity>]<iuid>[<isection>][<ipartial>][<iurlauth>]
     *
     * POP URLs take one of the following forms:
     *   - pop://<user>;auth=<auth>@<host>:<port>
     *
     * @param string $url  A URL string.
     *
     * @return mixed  False if the URL is invalid.  If valid, an array with
     *                the following fields:
     *   - auth: (string) The authentication method to use.
     *   - hostspec: (string) The remote server. (Not present for relative
     *               URLs).
     *   - mailbox: (string) The IMAP mailbox.
     *   - partial: (string) A byte range for use with IMAP FETCH.
     *   - port: (integer) The remote port. (Not present for relative URLs).
     *   - relative: (boolean) True if this is a relative URL.
     *   - search: (string) A search query to be run with IMAP SEARCH.
     *   - section: (string) A MIME part ID.
     *   - type: (string) Either 'imap' or 'pop'. (Not present for relative
     *           URLs).
     *   - username: (string) The username to use on the remote server.
     *   - uid: (string) The IMAP UID.
     *   - uidvalidity: (integer) The IMAP UIDVALIDITY for the given mailbox.
     *   - urlauth: (string) URLAUTH info (not parsed).
     */
    public function parseUrl($url)
    {
        $data = parse_url(trim($url));

        if (isset($data['scheme'])) {
            $type = strtolower($data['scheme']);
            if (!in_array($type, array('imap', 'pop'))) {
                return false;
            }
            $relative = false;
        } else {
            $type = null;
            $relative = true;
        }

        $ret_array = array(
            'hostspec' => isset($data['host']) ? $data['host'] : null,
            'port' => isset($data['port']) ? $data['port'] : 143,
            'relative' => $relative,
            'type' => $type
        );

        /* Check for username/auth information. */
        if (isset($data['user'])) {
            if (($pos = stripos($data['user'], ';AUTH=')) !== false) {
                $auth = substr($data['user'], $pos + 6);
                if ($auth != '*') {
                    $ret_array['auth'] = $auth;
                }
                $data['user'] = substr($data['user'], 0, $pos);
            }

            if (strlen($data['user'])) {
                $ret_array['username'] = $data['user'];
            }
        }

        /* IMAP-only information. */
        if (!$type || ($type == 'imap')) {
            if (isset($data['path'])) {
                $data['path'] = ltrim($data['path'], '/');
                $parts = explode('/;', $data['path']);

                $mbox = array_shift($parts);
                if (($pos = stripos($mbox, ';UIDVALIDITY=')) !== false) {
                    $ret_array['uidvalidity'] = substr($mbox, $pos + 13);
                    $mbox = substr($mbox, 0, $pos);
                }
                $ret_array['mailbox'] = urldecode($mbox);

            }

            if (count($parts)) {
                foreach ($parts as $val) {
                    list($k, $v) = explode('=', $val);
                    $ret_array[strtolower($k)] = $v;
                }
            } elseif (isset($data['query'])) {
                $ret_array['search'] = urldecode($data['query']);
            }
        }

        return $ret_array;
    }

    /**
     * Create a POP3 (RFC 2384) or IMAP (RFC 5092/5593) URL.
     *
     * @param array $data  The data used to create the URL. See the return
     *                     value from parseUrl() for the available fields.
     *
     * @return string  A URL string.
     */
    public function createUrl($data)
    {
        $url = '';

        if (isset($data['type'])) {
            $url = $data['type'] . '://';

            if (isset($data['username'])) {
                $url .= $data['username'];
            }

            if (isset($data['auth'])) {
                $url .= ';AUTH=' . $data['auth'] . '@';
            } elseif (isset($data['username'])) {
                $url .= '@';
            }

            $url .= $data['hostspec'];

            if (isset($data['port']) && ($data['port'] != 143)) {
                $url .= ':' . $data['port'];
            }
        }

        $url .= '/';

        if (!isset($data['type']) || ($data['type'] == 'imap')) {
            $url .= urlencode($data['mailbox']);

            if (!empty($data['uidvalidity'])) {
                $url .= ';UIDVALIDITY=' . $data['uidvalidity'];
            }

            if (isset($data['search'])) {
                $url .= '?' . urlencode($data['search']);
            } else {
                if (isset($data['uid'])) {
                    $url .= '/;UID=' . $data['uid'];
                }

                if (isset($data['section'])) {
                    $url .= '/;SECTION=' . $data['section'];
                }

                if (isset($data['partial'])) {
                    $url .= '/;PARTIAL=' . $data['partial'];
                }

                if (isset($data['urlauth'])) {
                    $url .= '/;URLAUTH=' . $data['urlauth'];
                }
            }
        }


        return $url;
    }

}
