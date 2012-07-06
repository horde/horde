<?php
/**
 * This class parses a multipart/related MIME part (RFC 2387) to provide
 * information on the part contents.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Mime
 */
class Horde_Mime_Related implements IteratorAggregate
{
    /**
     * Content IDs.
     *
     * @var array
     */
    protected $_cids = array();

    /**
     * Start ID.
     *
     * @var string
     */
    protected $_start;

    /**
     * Constructor.
     *
     * @param Horde_Mime_Part $mime_part  A MIME part object. Must be of
     *                                    type multipart/related.
     */
    public function __construct(Horde_Mime_Part $mime_part)
    {
        if ($mime_part->getType() != 'multipart/related') {
            throw new InvalidArgumentException('MIME part must be of type multipart/related');
        }

        $ids = array_keys($mime_part->contentTypeMap());
        $related_id = $mime_part->getMimeId();
        $id = null;

        /* Build a list of parts -> CIDs. */
        foreach ($ids as $val) {
            if ((strcmp($related_id, $val) !== 0) &&
                ($cid = $mime_part->getPart($val)->getContentId())) {
                $this->_cids[$val] = $cid;
            }
        }

        /* Look at the 'start' parameter to determine which part to start
         * with. If no 'start' parameter, use the first part (RFC 2387
         * [3.1]). */
        $start = $mime_part->getContentTypeParameter('start');
        if (!empty($start)) {
            $id = $this->cidSearch($start);
        }

        if (empty($id)) {
            reset($ids);
            $id = next($ids);
        }

        $this->_start = $id;
    }

    /**
     * Return the start ID.
     *
     * @return string  The start ID.
     */
    public function startId()
    {
        return $this->_start;
    }

    /**
     * Search for a CID in the related part.
     *
     * @param string $cid  The CID to search for.
     *
     * @return string  The MIME ID or false if not found.
     */
    public function cidSearch($cid)
    {
        return array_search($cid, $this->_cids);
    }

    /**
     * Scan for CID strings in HTML data and replace with data returned from
     * a callback method.
     *
     * @param string $text        The HTML text.
     * @param callback $callback  Callback method. Receives three arguments:
     *                            MIME ID, the attribute name containing the
     *                            content ID, and the node object. Expects
     *                            return value of URL to display the data.
     * @param string $charset     HTML data charset.
     *
     * @return Horde_Domhtml  A Horde_Domhtml object.
     */
    public function cidReplace($text, $callback, $charset = 'UTF-8')
    {
        $dom = new Horde_Domhtml($text, $charset);

        foreach ($dom as $node) {
            if ($node instanceof DOMElement) {
                switch (Horde_String::lower($node->tagName)) {
                case 'body':
                case 'td':
                    $this->_cidReplace($node, 'background', $callback);
                    break;

                case 'img':
                    $this->_cidReplace($node, 'src', $callback);
                    break;
                }
            }
        }

        return $dom;
    }

    /**
     */
    protected function _cidReplace($node, $attribute, $callback)
    {
        if ($node->hasAttribute($attribute)) {
            $val = $node->getAttribute($attribute);
            if ((strpos($val, 'cid:') === 0) &&
                ($id = $this->cidSearch(substr($val, 4)))) {
                $node->setAttribute($attribute, call_user_func($callback, $id, $attribute, $node));
            }
        }
    }

    /* IteratorAggregate method. */

    public function getIterator()
    {
        return new ArrayIterator($this->_cids);
    }

}
