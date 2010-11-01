<?php
/**
 * Abstraction of the IMAP4rev1 search criteria (see RFC 3501 [6.4.4]).  This
 * class allows translation between abstracted search criteria and a
 * generated IMAP search criteria string suitable for sending to a remote
 * IMAP server.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Imap_Client
 */
class Horde_Imap_Client_Search_Query implements Serializable
{
    /* Serialized version. */
    const VERSION = 1;

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
     * Sets the charset of the search text.
     *
     * @param string $charset     The charset to use for the search.
     * @param callback $callback  A callback function to run on all text
     *                            values when the charset changes.  It must
     *                            accept three parameters: the text, the old
     *                            charset (will be null if no charset was
     *                            previously given), and the new charset. It
     *                            should return the converted text value.
     */
    public function charset($charset, $callback = null)
    {
        $oldcharset = $this->_charset;
        $this->_charset = strtoupper($charset);
        if (is_null($callback) || ($oldcharset == $this->_charset)) {
            return;
        }

        foreach (array('header', 'text') as $item) {
            if (isset($this->_search[$item])) {
                foreach (array_keys($this->_search[$item]) as $key) {
                    $this->_search[$item][$key]['text'] = call_user_func_array($callback, array($this->_search[$item][$key]['text'], $oldcharset, $this->_charset));
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
     *                     Horde_Imap_Client_Base::capability().
     *
     * @return array  An array with these elements:
     * <pre>
     * 'charset' - (string) The charset of the search string.
     * 'exts' - (array) The list of IMAP extensions used to create the string.
     * 'imap4' - (boolean) True if the search uses IMAP4 criteria (as opposed
     *           to IMAP2 search criteria).
     * 'query' - (array) The IMAP search string.
     * </pre>
     * @throws Horde_Imap_Client_Exception
     */
    public function build($exts = array())
    {
        $cmds = $exts_used = array();
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
                if ($val['not']) {
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
                $cmds[] = array('t' => Horde_Imap_Client::DATA_ASTRING, 'v' => $val['text']);
            }
        }

        if (!empty($ptr['text'])) {
            foreach ($ptr['text'] as $val) {
                if ($val['not']) {
                    $cmds[] = 'NOT';
                    // NOT searches were not in IMAP2
                    $imap4 = true;
                }
                $cmds[] = $val['type'];
                $cmds[] = array('t' => Horde_Imap_Client::DATA_ASTRING, 'v' => $val['text']);
            }
        }

        if (!empty($ptr['size'])) {
            foreach ($ptr['size'] as $key => $val) {
                if ($val['not']) {
                    $cmds[] = 'NOT';
                }
                $cmds[] = $key;
                $cmds[] = array('t' => Horde_Imap_Client::DATA_NUMBER, 'v' => $val['size']);
                // LARGER/SMALLER searches were not in IMAP2
                $imap4 = true;
            }
        }

        if (isset($ptr['sequence'])) {
            if ($ptr['sequence']['not']) {
                $cmds[] = 'NOT';
            }
            if (!$ptr['sequence']['sequence']) {
                $cmds[] = 'UID';
            }
            $cmds[] = $ptr['sequence']['ids'];

            // sequence searches were not in IMAP2
            $imap4 = true;
        }

        if (!empty($ptr['date'])) {
            foreach ($ptr['date'] as $key => $val) {
                if ($val['not']) {
                    $cmds[] = 'NOT';
                    // NOT searches were not in IMAP2
                    $imap4 = true;
                }

                if ($key == 'header') {
                    $cmds[] = 'SENT' . $val['range'];
                    // 'SENT*' searches were not in IMAP2
                    $imap4 = true;
                } else {
                    $cmds[] = $val['range'];
                }
                $cmds[] = $val['date'];
            }
        }

        if (!empty($ptr['within'])) {
            if (isset($exts['WITHIN'])) {
                foreach ($ptr['within'] as $key => $val) {
                    if ($val['not']) {
                        $cmds[] = 'NOT';
                    }
                    $cmds[] = $key;
                    $cmds[] = array('t' => Horde_Imap_Client::DATA_NUMBER, 'v' => $val['interval']);
                }
                $exts_used[] = 'WITHIN';
                $imap4 = true;
            } else {
                // This workaround is only accurate to within 1 day, due to
                // limitations with the IMAP4rev1 search commands.
                foreach ($ptr['within'] as $key => $val) {
                    if ($val['not']) {
                        $cmds[] = 'NOT';
                        // NOT searches were not in IMAP2
                        $imap4 = true;
                    }

                    $date = new DateTime('now -' . $val['interval'] . ' seconds');
                    $cmds[] = ($key == self::INTERVAL_OLDER)
                        ? self::DATE_BEFORE
                        : self::DATE_SINCE;
                    $cmds[] = $date->format('d-M-Y');
                }
            }
        }

        if (!empty($ptr['modseq'])) {
            if (!isset($exts['CONDSTORE'])) {
                throw new Horde_Imap_Client_Exception('IMAP Server does not support CONDSTORE.', Horde_Imap_Client_Exception::NOSUPPORTIMAPEXT);
            }

            $exts_used[] = 'CONDSTORE';
            $imap4 = true;

            if ($ptr['modseq']['not']) {
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
            if (!isset($exts['SEARCHRES'])) {
                throw new Horde_Imap_Client_Exception('IMAP Server does not support SEARCHRES.', Horde_Imap_Client_Exception::NOSUPPORTIMAPEXT);
            }

            $exts_used[] = 'SEARCHRES';
            $imap4 = true;

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
                    $cmds = $ret['query'];
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
            'charset' => (is_null($this->_charset) ? 'US-ASCII' : $this->_charset),
            'exts' => $exts_used,
            'imap4' => $imap4,
            'query' => $cmds
        );
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

        /* The list of defined system flags (see RFC 3501 [2.3.2]). */
        $systemflags = array(
            'ANSWERED', 'DELETED', 'DRAFT', 'FLAGGED', 'RECENT', 'SEEN'
        );

        $this->_search['flag'][$name] = array(
            'set' => $set,
            'type' => in_array($name, $systemflags) ? 'flag' : 'keyword'
        );
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
            $utils = new Horde_Imap_Client_Utils();
            $ids = $utils->toSequenceString($ids);
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
     * @param mixed $date    DateTime or Horde_Date object.
     * @param string $range  Either:
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
    public function dateSearch($date, $range, $header = true, $not = false)
    {
        $type = $header ? 'header' : 'internal';
        if (!isset($this->_search['date'])) {
            $this->_search['date'] = array();
        }
        $this->_search['date'][$header ? 'header' : 'internal'] = array(
            'date' => $date->format('d-M-Y'),
            'range' => $range,
            'not' => $not
        );
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
     * support the SEARCHRES extension (RFC 5182) for this query to be used.
     *
     * @param boolean $not  If true, don't match the previous query.
     */
    public function previousSearch($not = false)
    {
        $this->_search['prevsearch'] = $not;
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
