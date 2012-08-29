<?php
/**
 * Abstraction of the IMAP4rev1 search criteria (see RFC 3501 [6.4.4]).
 * Allows translation between abstracted search criteria and a generated IMAP
 * search criteria string suitable for sending to a remote IMAP server.
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
class Horde_Imap_Client_Search_Query implements Serializable
{
    /* Serialized version. */
    const VERSION = 3;

    /* Constants for dateSearch() */
    const DATE_BEFORE = 'BEFORE';
    const DATE_ON = 'ON';
    const DATE_SINCE = 'SINCE';

    /* Constants for intervalSearch() */
    const INTERVAL_OLDER = 'OLDER';
    const INTERVAL_YOUNGER = 'YOUNGER';

    /**
     * The charset of the search strings.  All text strings must be in
     * this charset. By default, this is 'US-ASCII' (see RFC 3501 [6.4.4]).
     *
     * @var string
     */
    protected $_charset = null;

    /**
     * The list of search params.
     *
     * @var array
     */
    protected $_search = array();

    /**
     * Temp array used when building search string.
     *
     * @var array
     */
    protected $_temp = array();

    /**
     * String representation: The IMAP search string.
     */
    public function __toString()
    {
        $utils = new Horde_Imap_Client_Utils();
        $res = $this->build(null);
        return trim($utils->parseCommandArray($res['query']));
    }

    /**
     * Sets the charset of the search text.
     *
     * @param string $charset   The charset to use for the search.
     * @param boolean $convert  Convert existing text values?
     *
     * @throws Horde_Imap_Client_Exception_SearchCharset
     */
    public function charset($charset, $convert = true)
    {
        $oldcharset = $this->_charset;
        $this->_charset = strtoupper($charset);

        if (!$convert || ($oldcharset == $this->_charset)) {
            return;
        }

        foreach (array('header', 'text') as $item) {
            if (isset($this->_search[$item])) {
                foreach ($this->_search[$item] as $key => $val) {
                    $new_val = Horde_String::convertCharset($val['text'], $oldcharset, $this->_charset);
                    if (Horde_String::convertCharset($new_val, $this->_charset, $oldcharset) != $val['text']) {
                        throw new Horde_Imap_Client_Exception_SearchCharset($this->_charset);
                    }
                    $this->_search[$item][$key]['text'] = $new_val;
                }
            }
        }
    }

    /**
     * Builds an IMAP4rev1 compliant search string.
     *
     * @param array $exts  The list of extensions supported by the server.
     *                     This determines whether certain criteria can be
     *                     used, and determines whether workarounds are used
     *                     for other criteria. In the format returned by
     *                     Horde_Imap_Client_Base::capability(). If this value
     *                     is null, all extensions are assumed to be
     *                     available.
     *
     * @return array  An array with these elements:
     *   - charset: (string) The charset of the search string. If null, no
     *              text strings appear in query.
     *   - exts: (array) The list of IMAP extensions used to create the
     *           string.
     *   - imap4: (boolean) True if the search uses IMAP4 criteria (as opposed
     *            to IMAP2 search criteria).
     *   - query: (array) The IMAP search string.
     *
     * @throws Horde_Imap_Client_Exception_NoSupportExtension
     */
    public function build($exts = array())
    {
        $this->_temp = array(
            'cmds' => array(),
            'exts' => $exts,
            'exts_used' => array(),
            'imap4' => false
        );
        $cmds = &$this->_temp['cmds'];
        $charset = null;
        $exts_used = &$this->_temp['exts_used'];
        $imap4 = &$this->_temp['imap4'];
        $ptr = &$this->_search;

        if (isset($ptr['new'])) {
            $this->_addFuzzy(!empty($ptr['newfuzzy']));
            if ($ptr['new']) {
                $cmds[] = 'NEW';
                unset($ptr['flag']['UNSEEN']);
            } else {
                $cmds[] = 'OLD';
            }
            unset($ptr['flag']['RECENT']);
        }

        if (!empty($ptr['flag'])) {
            foreach ($ptr['flag'] as $key => $val) {
                if ($key == 'draft') {
                    // DRAFT flag was not in IMAP2
                    $imap4 = true;
                }

                $this->_addFuzzy(!empty($val['fuzzy']));

                $tmp = '';
                if (empty($val['set'])) {
                    // This is a 'NOT' search.  All system flags but \Recent
                    // have 'UN' equivalents.
                    if ($key == 'RECENT') {
                        $cmds[] = 'NOT';
                        // NOT searches were not in IMAP2
                        $imap4 = true;
                    } else {
                        $tmp = 'UN';
                    }
                }

                if ($val['type'] == 'keyword') {
                    $cmds[] = $tmp . 'KEYWORD';
                    $cmds[] = array('t' => Horde_Imap_Client::DATA_ATOM, 'v' => $key);
                } else {
                    $cmds[] = $tmp . $key;
                }
            }
        }

        if (!empty($ptr['header'])) {
            /* The list of 'system' headers that have a specific search
             * query. */
            $systemheaders = array(
                'BCC', 'CC', 'FROM', 'SUBJECT', 'TO'
            );

            foreach ($ptr['header'] as $val) {
                $this->_addFuzzy(!empty($val['fuzzy']));

                if (!empty($val['not'])) {
                    $cmds[] = 'NOT';
                    // NOT searches were not in IMAP2
                    $imap4 = true;
                }

                if (in_array($val['header'], $systemheaders)) {
                    $cmds[] = $val['header'];
                } else {
                    // HEADER searches were not in IMAP2
                    $cmds[] = 'HEADER';
                    $cmds[] = array('t' => Horde_Imap_Client::DATA_ASTRING, 'v' => $val['header']);
                    $imap4 = true;
                }
                $cmds[] = array('t' => Horde_Imap_Client::DATA_ASTRING, 'v' => isset($val['text']) ? $val['text'] : '');
                $charset = is_null($this->_charset)
                    ? 'US-ASCII'
                    : $this->_charset;
            }
        }

        if (!empty($ptr['text'])) {
            foreach ($ptr['text'] as $val) {
                $this->_addFuzzy(!empty($val['fuzzy']));

                if (!empty($val['not'])) {
                    $cmds[] = 'NOT';
                    // NOT searches were not in IMAP2
                    $imap4 = true;
                }
                $cmds[] = $val['type'];
                $cmds[] = array('t' => Horde_Imap_Client::DATA_ASTRING, 'v' => $val['text']);
                if (is_null($charset)) {
                    $charset = is_null($this->_charset)
                        ? 'US-ASCII'
                        : $this->_charset;
                }
            }
        }

        if (!empty($ptr['size'])) {
            foreach ($ptr['size'] as $key => $val) {
                $this->_addFuzzy(!empty($val['fuzzy']));
                if (!empty($val['not'])) {
                    $cmds[] = 'NOT';
                }
                $cmds[] = $key;
                $cmds[] = array('t' => Horde_Imap_Client::DATA_NUMBER, 'v' => $val['size']);
                // LARGER/SMALLER searches were not in IMAP2
                $imap4 = true;
            }
        }

        if (isset($ptr['ids']) &&
            (count($ptr['ids']['ids']) || $ptr['ids']['ids']->all)) {
            $this->_addFuzzy(!empty($val['fuzzy']));
            if (!empty($ptr['ids']['not'])) {
                $cmds[] = 'NOT';
            }
            if (!$ptr['ids']['ids']->sequence) {
                $cmds[] = 'UID';
            }
            $cmds[] = $ptr['ids']['ids']->all
                ? '1:*'
                : strval($ptr['ids']['ids']);

            // ID searches were not in IMAP2
            $imap4 = true;
        }

        if (!empty($ptr['date'])) {
            foreach ($ptr['date'] as $val) {
                $this->_addFuzzy(!empty($val['fuzzy']));

                if (!empty($val['not'])) {
                    $cmds[] = 'NOT';
                    // NOT searches were not in IMAP2
                    $imap4 = true;
                }

                if (empty($val['header'])) {
                    $cmds[] = $val['range'];
                } else {
                    $cmds[] = 'SENT' . $val['range'];
                    // 'SENT*' searches were not in IMAP2
                    $imap4 = true;
                }
                $cmds[] = $val['date'];
            }
        }

        if (!empty($ptr['within'])) {
            if (is_null($exts) || isset($exts['WITHIN'])) {
                $exts_used[] = 'WITHIN';
                $imap4 = true;
            }

            foreach ($ptr['within'] as $key => $val) {
                $this->_addFuzzy(!empty($val['fuzzy']));
                if (!empty($val['not'])) {
                    $cmds[] = 'NOT';
                    // NOT searches were not in IMAP2
                    $imap4 = true;
                }

                if (is_null($exts) || isset($exts['WITHIN'])) {
                    $cmds[] = $key;
                    $cmds[] = array('t' => Horde_Imap_Client::DATA_NUMBER, 'v' => $val['interval']);
                } else {
                    // This workaround is only accurate to within 1 day, due
                    // to limitations with the IMAP4rev1 search commands.
                    $date = new DateTime('now -' . $val['interval'] . ' seconds');
                    $cmds[] = ($key == self::INTERVAL_OLDER)
                        ? self::DATE_BEFORE
                        : self::DATE_SINCE;
                    $cmds[] = $date->format('d-M-Y');
                }
            }
        }

        if (!empty($ptr['modseq'])) {
            if (!is_null($exts) && !isset($exts['CONDSTORE'])) {
                throw new Horde_Imap_Client_Exception_NoSupportExtension('IMAP Server does not support CONDSTORE.');
            }

            $exts_used[] = 'CONDSTORE';
            $imap4 = true;

            $this->_addFuzzy(!empty($ptr['modseq']['fuzzy']));

            if (!empty($ptr['modseq']['not'])) {
                $cmds[] = 'NOT';
            }
            $cmds[] = 'MODSEQ';
            if (!is_null($ptr['modseq']['name'])) {
                $cmds[] = array('t' => Horde_Imap_Client::DATA_STRING, 'v' => $ptr['modseq']['name']);
                $cmds[] = $ptr['modseq']['type'];
            }
            $cmds[] = array('t' => Horde_Imap_Client::DATA_NUMBER, 'v' => $ptr['modseq']['value']);
        }

        if (isset($ptr['prevsearch'])) {
            if (!is_null($exts) && !isset($exts['SEARCHRES'])) {
                throw new Horde_Imap_Client_Exception_NoSupportExtension('IMAP Server does not support SEARCHRES.');
            }

            $exts_used[] = 'SEARCHRES';
            $imap4 = true;

            $this->_addFuzzy(!empty($ptr['prevsearchfuzzy']));

            if (!$ptr['prevsearch']) {
                $cmds[] = 'NOT';
            }
            $cmds[] = '$';
        }

        // Add AND'ed queries
        if (!empty($ptr['and'])) {
            foreach ($ptr['and'] as $val) {
                $ret = $val->build();
                $cmds = array_merge($cmds, $ret['query']);
            }
        }

        // Add OR'ed queries
        if (!empty($ptr['or'])) {
            foreach ($ptr['or'] as $val) {
                // OR queries were not in IMAP 2
                $imap4 = true;

                $ret = $val->build();

                // First OR'd query
                if (empty($cmds)) {
                    $cmds = array($ret['query']);
                } else {
                    $cmds = array_merge(array(
                        'OR',
                        $ret['query']
                    ), $cmds);
                }
            }
        }

        // Default search is 'ALL'
        if (empty($cmds)) {
            $cmds[] = 'ALL';
        }

        return array(
            'charset' => $charset,
            'exts' => array_keys(array_flip($exts_used)),
            'imap4' => $imap4,
            'query' => $cmds
        );
    }

    /**
     * Adds fuzzy modifier to search keys.
     *
     * @param boolean $add  Add the fuzzy modifier?
     *
     * @throws Horde_Imap_Client_Exception_NoSupport_Extension
     */
    protected function _addFuzzy($add)
    {
        if ($add) {
            if (!isset($this->_temp['exts']['SEARCH']) ||
                !in_array('FUZZY', $this->_temp['exts']['SEARCH'])) {
                throw new Horde_Imap_Client_Exception_NoSupportExtension('IMAP Server does not support SEARCH=FUZZY.');
            }
            $this->_temp['cmds'][] = 'FUZZY';
            $this->_temp['exts_used'][] = 'SEARCH=FUZZY';
            $this->_temp['imap4'] = true;
        }
    }

    /**
     * Search for a flag/keywords.
     *
     * @param string $name  The flag or keyword name.
     * @param boolean $set  If true, search for messages that have the flag
     *                      set.  If false, search for messages that do not
     *                      have the flag set.
     * @param array $opts   Additional options:
     *   - fuzzy: (boolean) If true, perform a fuzzy search. The IMAP server
     *            MUST support RFC 6203.
     */
    public function flag($name, $set = true, array $opts = array())
    {
        $name = strtoupper(ltrim($name, '\\'));
        if (!isset($this->_search['flag'])) {
            $this->_search['flag'] = array();
        }

        /* The list of defined system flags (see RFC 3501 [2.3.2]). */
        $systemflags = array(
            'ANSWERED', 'DELETED', 'DRAFT', 'FLAGGED', 'RECENT', 'SEEN'
        );

        $this->_search['flag'][$name] = array_filter(array(
            'fuzzy' => !empty($opts['fuzzy']),
            'set' => $set,
            'type' => in_array($name, $systemflags) ? 'flag' : 'keyword'
        ));
    }

    /**
     * Determines if flags are a part of the search.
     *
     * @return boolean  True if search query involves flags.
     */
    public function flagSearch()
    {
        return !empty($this->_search['flag']);
    }

    /**
     * Search for either new messages (messages that have the '\Recent' flag
     * but not the '\Seen' flag) or old messages (messages that do not have
     * the '\Recent' flag).  If new messages are searched, this will clear
     * any '\Recent' or '\Unseen' flag searches.  If old messages are searched,
     * this will clear any '\Recent' flag search.
     *
     * @param boolean $newmsgs  If true, searches for new messages.  Else,
     *                          search for old messages.
     * @param array $opts       Additional options:
     *   - fuzzy: (boolean) If true, perform a fuzzy search. The IMAP server
     *            MUST support RFC 6203.
     */
    public function newMsgs($newmsgs = true, array $opts = array())
    {
        $this->_search['new'] = $newmsgs;
        if (!empty($opts['fuzzy'])) {
            $this->_search['newfuzzy'] = true;
        }
    }

    /**
     * Search for text in the header of a message.
     *
     * @param string $header  The header field.
     * @param string $text    The search text.
     * @param boolean $not    If true, do a 'NOT' search of $text.
     * @param array $opts     Additional options:
     *   - fuzzy: (boolean) If true, perform a fuzzy search. The IMAP server
     *            MUST support RFC 6203.
     */
    public function headerText($header, $text, $not = false,
                                array $opts = array())
    {
        if (!isset($this->_search['header'])) {
            $this->_search['header'] = array();
        }
        $this->_search['header'][] = array_filter(array(
            'fuzzy' => !empty($opts['fuzzy']),
            'header' => strtoupper($header),
            'text' => $text,
            'not' => $not
        ));
    }

    /**
     * Search for text in either the entire message, or just the body.
     *
     * @param string $text      The search text.
     * @param string $bodyonly  If true, only search in the body of the
     *                          message. If false, also search in the headers.
     * @param boolean $not      If true, do a 'NOT' search of $text.
     * @param array $opts       Additional options:
     *   - fuzzy: (boolean) If true, perform a fuzzy search. The IMAP server
     *            MUST support RFC 6203.
     */
    public function text($text, $bodyonly = true, $not = false,
                         array $opts = array())
    {
        if (!isset($this->_search['text'])) {
            $this->_search['text'] = array();
        }

        $this->_search['text'][] = array_filter(array(
            'fuzzy' => !empty($opts['fuzzy']),
            'not' => $not,
            'text' => $text,
            'type' => $bodyonly ? 'BODY' : 'TEXT'
        ));
    }

    /**
     * Search for messages smaller/larger than a certain size.
     *
     * @param integer $size    The size (in bytes).
     * @param boolean $larger  Search for messages larger than $size?
     * @param boolean $not     If true, do a 'NOT' search of $text.
     * @param array $opts      Additional options:
     *   - fuzzy: (boolean) If true, perform a fuzzy search. The IMAP server
     *            MUST support RFC 6203.
     */
    public function size($size, $larger = false, $not = false,
                         array $opts = array())
    {
        if (!isset($this->_search['size'])) {
            $this->_search['size'] = array();
        }
        $this->_search['size'][$larger ? 'LARGER' : 'SMALLER'] = array_filter(array(
            'fuzzy' => !empty($opts['fuzzy']),
            'not' => $not,
            'size' => (float)$size
        ));
    }

    /**
     * Search for messages within a given ID sequence range. Only one message
     * range can be specified per query.
     *
     * @param Horde_Imap_Client_Ids $ids  The list of IDs to search.
     * @param boolean $not                If true, do a 'NOT' search of the
     *                                    IDs.
     * @param array $opts                 Additional options:
     *   - fuzzy: (boolean) If true, perform a fuzzy search. The IMAP server
     *            MUST support RFC 6203.
     */
    public function ids(Horde_Imap_Client_Ids $ids, $not = false,
                        array $opts = array())
    {
        $this->_search['ids'] = array_filter(array(
            'fuzzy' => !empty($opts['fuzzy']),
            'ids' => $ids,
            'not' => $not
        ));
    }

    /**
     * Search for messages within a date range.
     *
     * @param mixed $date    DateTime or Horde_Date object.
     * @param string $range  Either:
     *   - Horde_Imap_Client_Search_Query::DATE_BEFORE
     *   - Horde_Imap_Client_Search_Query::DATE_ON
     *   - Horde_Imap_Client_Search_Query::DATE_SINCE
     * @param boolean $header  If true, search using the date in the message
     *                         headers. If false, search using the internal
     *                         IMAP date (usually arrival time).
     * @param boolean $not     If true, do a 'NOT' search of the range.
     * @param array $opts      Additional options:
     *   - fuzzy: (boolean) If true, perform a fuzzy search. The IMAP server
     *            MUST support RFC 6203.
     */
    public function dateSearch($date, $range, $header = true, $not = false,
                               array $opts = array())
    {
        if (!isset($this->_search['date'])) {
            $this->_search['date'] = array();
        }
        $this->_search['date'][] = array_filter(array(
            'date' => $date->format('d-M-Y'),
            'fuzzy' => !empty($opts['fuzzy']),
            'header' => $header,
            'range' => $range,
            'not' => $not
        ));
    }

    /**
     * Search for messages within a given interval. Only one interval of each
     * type can be specified per search query. If the IMAP server supports
     * the WITHIN extension (RFC 5032), it will be used.  Otherwise, the
     * search query will be dynamically created using IMAP4rev1 search
     * terms.
     *
     * @param integer $interval  Seconds from the present.
     * @param string $range      Either:
     *   - Horde_Imap_Client_Search_Query::INTERVAL_OLDER
     *   - Horde_Imap_Client_Search_Query::INTERVAL_YOUNGER
     * @param boolean $not       If true, do a 'NOT' search.
     * @param array $opts        Additional options:
     *   - fuzzy: (boolean) If true, perform a fuzzy search. The IMAP server
     *            MUST support RFC 6203.
     */
    public function intervalSearch($interval, $range, $not = false,
                                   array $opts = array())
    {
        if (!isset($this->_search['within'])) {
            $this->_search['within'] = array();
        }
        $this->_search['within'][$range] = array(
            'fuzzy' => !empty($opts['fuzzy']),
            'interval' => $interval,
            'not' => $not
        );
    }

    /**
     * AND queries - the contents of this query will be AND'ed (in its
     * entirety) with the contents of EACH of the queries passed in.  All
     * AND'd queries must share the same charset as this query.
     *
     * @param mixed $queries  A query, or an array of queries, to AND with the
     *                        current query.
     */
    public function andSearch($queries)
    {
        if (!isset($this->_search['and'])) {
            $this->_search['and'] = array();
        }

        if ($queries instanceof Horde_Imap_Client_Search_Query) {
            $queries = array($queries);
        }

        $this->_search['and'] = array_merge($this->_search['and'], $queries);
    }

    /**
     * OR a query - the contents of this query will be OR'ed (in its entirety)
     * with the contents of EACH of the queries passed in.  All OR'd queries
     * must share the same charset as this query.  All contents of any single
     * query will be AND'ed together.
     *
     * @param mixed $queries  A query, or an array of queries, to OR with the
     *                        current query.
     */
    public function orSearch($queries)
    {
        if (!isset($this->_search['or'])) {
            $this->_search['or'] = array();
        }

        if ($queries instanceof Horde_Imap_Client_Search_Query) {
            $queries = array($queries);
        }

        $this->_search['or'] = array_merge($this->_search['or'], $queries);
    }

    /**
     * Search for messages modified since a specific moment. The IMAP server
     * must support the CONDSTORE extension (RFC 4551) for this query to be
     * used.
     *
     * @param integer $value  The mod-sequence value.
     * @param string $name    The entry-name string.
     * @param string $type    Either 'shared', 'priv', or 'all'. Defaults to
     *                        'all'
     * @param boolean $not    If true, do a 'NOT' search.
     * @param array $opts     Additional options:
     *   - fuzzy: (boolean) If true, perform a fuzzy search. The IMAP server
     *            MUST support RFC 6203.
     */
    public function modseq($value, $name = null, $type = null, $not = false,
                           array $opts = array())
    {
        if (!is_null($type)) {
            $type = strtolower($type);
            if (!in_array($type, array('shared', 'priv', 'all'))) {
                $type = 'all';
            }
        }

        $this->_search['modseq'] = array_filter(array(
            'fuzzy' => !empty($opts['fuzzy']),
            'name' => $name,
            'not' => $not,
            'type' => (!is_null($name) && is_null($type)) ? 'all' : $type,
            'value' => $value
        ));
    }

    /**
     * Use the results from the previous SEARCH command. The IMAP server must
     * support the SEARCHRES extension (RFC 5182) for this query to be used.
     *
     * @param boolean $not  If true, don't match the previous query.
     * @param array $opts   Additional options:
     *   - fuzzy: (boolean) If true, perform a fuzzy search. The IMAP server
     *            MUST support RFC 6203.
     */
    public function previousSearch($not = false, array $opts = array())
    {
        $this->_search['prevsearch'] = $not;
        if (!empty($opts['fuzzy'])) {
            $this->_search['prevsearchfuzzy'] = true;
        }
    }

    /* Serializable methods. */

    /**
     * Serialization.
     *
     * @return string  Serialized data.
     */
    public function serialize()
    {
        $data = array(
            // Serialized data ID.
            self::VERSION,
            $this->_search
        );

        if (!is_null($this->_charset)) {
            $data[] = $this->_charset;
        }

        return serialize($data);
    }

    /**
     * Unserialization.
     *
     * @param string $data  Serialized data.
     *
     * @throws Exception
     */
    public function unserialize($data)
    {
        $data = @unserialize($data);
        if (!is_array($data) ||
            !isset($data[0]) ||
            ($data[0] != self::VERSION)) {
            throw new Exception('Cache version change');
        }

        $this->_search = $data[1];
        if (isset($data[2])) {
            $this->_charset = $data[2];
        }
    }

}
