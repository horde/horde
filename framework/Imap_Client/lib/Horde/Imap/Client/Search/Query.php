<?php
/**
 * Abstraction of the IMAP4rev1 search criteria (see RFC 3501 [6.4.4]).  This
 * class allows translation between abstracted search criteria and a
 * generated IMAP search criteria string suitable for sending to a remote
 * IMAP server.
 *
 * Copyright 2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Horde_Imap_Client
 */
class Horde_Imap_Client_Search_Query
{
    /* Constants for dateSearch() */
    const DATE_BEFORE = 'BEFORE';
    const DATE_ON = 'ON';
    const DATE_SINCE = 'SINCE';

    /* Constants for intervalSearch() */
    const INTERVAL_OLDER = 'OLDER';
    const INTERVAL_YOUNGER = 'YOUNGER';

    /**
     * The charset of the search strings.  All text strings must be in
     * this charset.
     *
     * @var string
     */
    protected $_charset = 'US-ASCII';

    /**
     * The list of defined system flags (see RFC 3501 [2.3.2]).
     *
     * @var array
     */
    protected $_systemflags = array(
        'ANSWERED', 'DELETED', 'DRAFT', 'FLAGGED', 'RECENT', 'SEEN'
    );

    /**
     * The list of 'system' headers that have a specific search query.
     *
     * @var array
     */
    protected $_systemheaders = array(
        'BCC', 'CC', 'FROM', 'SUBJECT', 'TO'
    );

    /**
     * The list of search params.
     *
     * @var array
     */
    protected $_search = array();

    /**
     * List of extensions needed for advanced queries.
     *
     * @var array
     */
    protected $_exts = array();

    /**
     * Sets the charset of the search text.
     *
     * @param string $charset  The charset to use for the search.
     */
    public function charset($charset)
    {
        $this->_charset = strtoupper($charset);
    }

    /**
     * Builds an IMAP4rev1 compliant search string.
     *
     * @return array  An array with 3 elements:
     * <pre>
     * 'charset' - (string) The charset of the search string.
     * 'imap4' - (boolean) True if the search uses IMAP4 criteria (as opposed
     *           to IMAP2 search criteria)
     * 'query' - (string) The IMAP search string
     * </pre>
     */
    public function build()
    {
        $cmds = array();
        $imap4 = false;
        $ptr = &$this->_search;

        if (isset($ptr['new'])) {
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

                $tmp = '';
                if (!$val['set']) {
                    // This is a 'NOT' search.  All system flags but \Recent
                    // have 'UN' equivalents.
                    if ($key == 'RECENT') {
                        $tmp = 'NOT ';
                        // NOT searches were not in IMAP2
                        $imap4 = true;
                    } else {
                        $tmp = 'UN';
                    }
                }

                $cmds[] = $tmp . ($val['type'] == 'keyword' ? 'KEYWORD ' : '') . $key;
            }
        }

        if (!empty($ptr['header'])) {
            foreach ($ptr['header'] as $val) {
                $tmp = '';
                if ($val['not']) {
                    $tmp = 'NOT ';
                    // NOT searches were not in IMAP2
                    $imap4 = true;
                }

                if (!in_array($val['header'], $this->_systemheaders)) {
                    // HEADER searches were not in IMAP2
                    $tmp .= 'HEADER ';
                    $imap4 = true;
                }
                $cmds[] = $tmp . $val['header'] . ' ' . Horde_Imap_Client::escape($val['text']);
            }
        }

        if (!empty($ptr['text'])) {
            foreach ($ptr['text'] as $val) {
                $tmp = '';
                if ($val['not']) {
                    $tmp = 'NOT ';
                    // NOT searches were not in IMAP2
                    $imap4 = true;
                }
                $cmds[] = $tmp . $val['type'] . ' ' . Horde_Imap_Client::escape($val['text']);
            }
        }

        if (!empty($ptr['size'])) {
            foreach ($ptr['size'] as $key => $val) {
                $cmds[] = ($val['not'] ? 'NOT ' : '' ) . $key . ' ' . $val['size'];
                // LARGER/SMALLER searches were not in IMAP2
                $imap4 = true;
            }
        }

        if (isset($ptr['sequence'])) {
            $cmds[] = ($ptr['sequence']['not'] ? 'NOT ' : '') . ($ptr['sequence']['sequence'] ? '' : 'UID ') . $ptr['sequence']['ids'];

            // sequence searches were not in IMAP2
            $imap4 = true;
        }

        if (!empty($ptr['date'])) {
            foreach ($ptr['date'] as $key => $val) {
                $tmp = '';
                if ($val['not']) {
                    $tmp = 'NOT ';
                    // NOT searches were not in IMAP2
                    $imap4 = true;
                }

                if ($key == 'header') {
                    $tmp .= 'SENT';
                    // 'SENT*' searches were not in IMAP2
                    $imap4 = true;
                }
                $cmds[] = $tmp . $val['range'] . ' ' . $val['date'];
            }
        }

        if (!empty($ptr['within'])) {
            $imap4 = true;
            $this->_exts['WITHIN'] = true;

            foreach ($ptr['within'] as $key => $val) {
                $cmds[] = ($val['not'] ? 'NOT ' : '') . $key . ' ' . $val['interval'];
            }
        }

        if (!empty($ptr['modseq'])) {
            $imap4 = true;
            $this->_exts['CONDSTORE'] = true;
            $cmds[] = ($ptr['modseq']['not'] ? 'NOT ' : '') .
                'MODSEQ ' .
                (is_null($ptr['modseq']['name'])
                    ? ''
                    : Horde_Imap_Client::escape($ptr['modseq']['name']) . ' ' . $ptr['modseq']['type'] . ' ') .
                $ptr['modseq']['value'];
        }

        if (isset($ptr['prevsearch'])) {
            $imap4 = true;
            $this->_exts['SEARCHRES'] = true;
            $cmds[] = ($ptr['prevsearch'] ? '' : 'NOT ') . '$';
        }

        $query = '';

        // Add OR'ed queries
        if (!empty($ptr['or'])) {
            foreach ($ptr['or'] as $key => $val) {
                // OR queries were not in IMAP 2
                $imap4 = true;

                if ($key == 0) {
                    $query = '(' . $query . ')';
                }

                $ret = $val->build();
                $query = 'OR (' . $ret['query'] . ') ' . $query;
            }
        }

        // Add AND'ed queries
        if (!empty($ptr['and'])) {
            foreach ($ptr['and'] as $key => $val) {
                $ret = $val->build();
                $query .= ' ' . $ret['query'];
            }
        }

        // Default search is 'ALL'
        if (empty($cmds)) {
            $query .= empty($query) ? 'ALL' : '';
        } else {
            $query .= implode(' ', $cmds);
        }

        return array(
            'charset' => $this->_charset,
            'imap4' => $imap4,
            'query' => trim($query)
        );
    }

    /**
     * Return the list of any IMAP extensions needed to perform the query.
     *
     * @return array  The list of extensions (CAPABILITY responses) needed to
     *                perform the query.
     */
    public function extensionsNeeded()
    {
        return $this->_exts;
    }

    /**
     * Search for a flag/keywords.
     *
     * @param string $name  The flag or keyword name.
     * @param boolean $set  If true, search for messages that have the flag
     *                      set.  If false, search for messages that do not
     *                      have the flag set.
     */
    public function flag($name, $set = true)
    {
        $name = strtoupper(ltrim($name, '\\'));
        if (!isset($this->_search['flag'])) {
            $this->_search['flag'] = array();
        }
        $this->_search['flag'][$name] = array(
            'set' => $set,
            'type' => in_array($name, $this->_systemflags) ? 'flag' : 'keyword'
        );
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
     */
    public function newMsgs($newmsgs = true)
    {
        $this->_search['new'] = $newmsgs;
    }

    /**
     * Search for text in the header of a message.
     *
     * @param string $header  The header field.
     * @param string $text    The search text.
     * @param boolean $not    If true, do a 'NOT' search of $text.
     */
    public function headerText($header, $text, $not = false)
    {
        if (!isset($this->_search['header'])) {
            $this->_search['header'] = array();
        }
        $this->_search['header'][] = array(
            'header' => strtoupper($header),
            'text' => $text,
            'not' => $not
        );
    }

    /**
     * Search for text in either the entire message, or just the body.
     *
     * @param string $text      The search text.
     * @param string $bodyonly  If true, only search in the body of the
     *                          message. If false, also search in the headers.
     * @param boolean $not      If true, do a 'NOT' search of $text.
     */
    public function text($text, $bodyonly = true, $not = false)
    {
        if (!isset($this->_search['text'])) {
            $this->_search['text'] = array();
        }
        $this->_search['text'][] = array(
            'text' => $text,
            'not' => $not,
            'type' => $bodyonly ? 'BODY' : 'TEXT'
        );
    }

    /**
     * Search for messages smaller/larger than a certain size.
     *
     * @param integer $size    The size (in bytes).
     * @param boolean $larger  Search for messages larger than $size?
     * @param boolean $not     If true, do a 'NOT' search of $text.
     */
    public function size($size, $larger = false, $not = false)
    {
        if (!isset($this->_search['size'])) {
            $this->_search['size'] = array();
        }
        $this->_search['size'][$larger ? 'LARGER' : 'SMALLER'] = array(
            'size' => (float)$size,
            'not' => $not
        );
    }

    /**
     * Search for messages within a given message range. Only one message
     * range can be specified per query.
     *
     * @param array $ids         The list of messages to search.
     * @param boolean $sequence  By default, $ids is assumed to be UIDs. If
     *                           this param is true, $ids are taken to be
     *                           message sequence numbers instead.
     * @param boolean $not       If true, do a 'NOT' search of the sequence.
     */
    public function sequence($ids, $sequence = false, $not = false)
    {
        if (empty($ids)) {
            $ids = '1:*';
        } else {
            $ids = Horde_Imap_Client::toSequenceString($ids);
        }
        $this->_search['sequence'] = array(
            'ids' => $ids,
            'not' => $not,
            'sequence' => $sequence
        );
    }

    /**
     * Search for messages within a date range. Only one internal date and
     * one RFC 2822 date can be specified per query.
     *
     * @param integer $month   Month (from 1-12).
     * @param integer $day     Day of month (from 1-31).
     * @param integer $year    Year (4-digit year).
     * @param string $range    Either:
     * <pre>
     * Horde_Imap_Client_Search_Query::DATE_BEFORE,
     * Horde_Imap_Client_Search_Query::DATE_ON, or
     * Horde_Imap_Client_Search_Query::DATE_SINCE.
     * </pre>
     * @param boolean $header  If true, search using the date in the message
     *                         headers. If false, search using the internal
     *                         IMAP date (usually arrival time).
     * @param boolean $not     If true, do a 'NOT' search of the range.
     */
    public function dateSearch($month, $day, $year, $range, $header = true,
                               $not = false)
    {
        $type = $header ? 'header' : 'internal';
        if (!isset($this->_search['date'])) {
            $this->_search['date'] = array();
        }
        $this->_search['date'][$header ? 'header' : 'internal'] = array(
            'date' => date("d-M-y", mktime(0, 0, 0, $month, $day, $year)),
            'range' => $range,
            'not' => $not
        );
    }

    /**
     * Search for messages within a given interval. Only one interval of each
     * type can be specified per search query. The IMAP server must support
     * the WITHIN extension (RFC 5032) for this query to be used.
     *
     * @param integer $interval  Seconds from the present.
     * @param string $range      Either:
     * <pre>
     * Horde_Imap_Client_Search_Query::INTERVAL_OLDER, or
     * Horde_Imap_Client_Search_Query::INTERVAL_YOUNGER
     * </pre>
     * @param boolean $not       If true, do a 'NOT' search.
     */
    public function intervalSearch($interval, $range, $not = false)
    {
        if (!isset($this->_search['within'])) {
            $this->_search['within'] = array();
        }
        $this->_search['within'][$range] = array(
            'interval' => $interval,
            'not' => $not
        );
    }

    /**
     * AND queries - the contents of this query will be AND'ed (in its
     * entirety) with the contents of each of the queries passed in.  All
     * AND'd queries must share the same charset as this query.
     *
     * @param array $queries  An array of queries to AND with this one.  Each
     *                        query is a Horde_Imap_Client_Search_Query
     *                        object.
     */
    public function andSearch($queries)
    {
        if (!isset($this->_search['and'])) {
            $this->_search['and'] = array();
        }
        $this->_search['and'] = array_merge($this->_search['and'], $queries);
    }

    /**
     * OR a query - the contents of this query will be OR'ed (in its entirety)
     * with the contents of each of the queries passed in.  All OR'd queries
     * must share the same charset as this query.  All contents of any single
     * query will be AND'ed together.
     *
     * @param array $queries  An array of queries to OR with this one.  Each
     *                        query is a Horde_Imap_Client_Search_Query
     *                        object.
     */
    public function orSearch($queries)
    {
        if (!isset($this->_search['or'])) {
            $this->_search['or'] = array();
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
     */
    public function modseq($value, $name = null, $type = null, $not = false)
    {
        if (!is_null($type)) {
            $type = strtolower($type);
            if (!in_array($type, array('shared', 'priv', 'all'))) {
                $type = 'all';
            }
        }

        $this->_search['modseq'] = array(
            'value' => $value,
            'name' => $name,
            'not' => $not,
            'type' => (!is_null($name) && is_null($type)) ? 'all' : $type
        );
    }

    /**
     * Use the results from the previous SEARCH command. The IMAP server must
     * support the SEARCHRES extension (RFC 5032) for this query to be used.
     *
     * @param boolean $not  If true, don't match the previous query.
     */
    public function previousSearch($not = false)
    {
        $this->_search['prevsearch'] = $not;
    }

}
