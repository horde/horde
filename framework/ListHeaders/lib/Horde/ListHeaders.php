<?php
/**
 * Class to parse List Header fields (RFC 2369/2919).
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  IMP
 */

/**
 * Class to parse List Header fields (RFC 2369/2919).
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  IMP
 */
class Horde_ListHeaders extends Horde_Mail_Rfc822
{
    /**
     * Parse a list header.
     *
     * @param string $id     Header ID.
     * @param string $value  Header value.
     *
     * @return mixed  An array of Horde_ListHeaders_Base objects, a
     *                Horde_ListHeaders_Id object, or false if unable to
     *                parse.
     */
    public function parse($id, $value)
    {
        if (!strlen($value)) {
            return false;
        }

        $this->_data = $value;
        $this->_datalen = strlen($value);
        $this->_params['validate'] = true;

        switch (strtolower($id)) {
        case 'list-archive':
        case 'list-help':
        case 'list-owner':
        case 'list-subscribe':
        case 'list-unsubscribe':
            return $this->_parseBase();

        case 'list-id':
            return $this->_parseListId();

        case 'list-post':
            return $this->_parseListPost();

        default:
            return false;
        }
    }

    /**
     * Parse a base list header (RFC 2369).
     *
     * @return array  List of Horde_List_Headers_Base objects.
     */
    protected function _parseBase()
    {
        $this->_ptr = 0;

        $out = array();

        while ($this->_curr() !== false) {
            $this->_comments = array();

            $this->_rfc822SkipLwsp();

            if ($this->_curr(true) != '<') {
                break;
            }

            $this->_rfc822SkipLwsp();

            $url = '';
            while ((($curr = $this->_curr(true)) !== false) &&
                   ($curr != '>')) {
                $url .= $curr;
            }

            if ($curr != '>') {
                return false;
            }

            $this->_rfc822SkipLwsp();

            switch ($this->_curr()) {
            case ',':
                $this->_rfc822SkipLwsp(true);
                break;

            case false:
                // No-op
                break;

            default:
                // RFC 2369 [2] Need to ignore this and all other fields.
                break 2;
            }

            $out[] = new Horde_ListHeaders_Base(rtrim($url), $this->_comments);
        }

        return $out;
    }

    /**
     * Parse a List-ID (RFC 2919).
     *
     * @return Horde_ListHeaders_Id  Id object.
     */
    protected function _parseListId()
    {
        $this->_ptr = 0;

        $phrase = '';
        $this->_rfc822ParsePhrase($phrase);

        if ($this->_curr(true) != '<') {
            return false;
        }

        $this->_rfc822ParseDotAtom($listid);

        if ($this->_curr(true) != '>') {
            return false;
        }

        return new Horde_ListHeaders_Id($listid, $phrase);
    }

    /**
     * Parse a List-Post header (RFC 2369 [3.4]).
     *
     * @return array  List of Horde_List_Headers_Base objects.
     */
    protected function _parseListPost()
    {
        /* This value can be the special phrase "NO". */
        $this->_comments = array();
        $this->_ptr = 0;

        $this->_rfc822SkipLwsp();

        $phrase = '';
        $this->_rfc822ParsePhrase($phrase);

        if (strcasecmp(rtrim($phrase), 'NO') !== 0) {
            return $this->_parseBase();
        }

        $this->_rfc822SkipLwsp();
        return array(new Horde_ListHeaders_NoPost($this->_comments));
    }

}
