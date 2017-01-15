<?php
/**
 * Copyright 2015-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2015-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   ActiveSync
 */

/**
 * Recursive iterator for Horde_Mime_Part objects. This iterator is
 * self-contained and independent of all other iterators. Adapted from
 * Horde_Mime package. Changed to allow what EAS would consider attachments
 * to be ignored during iteration and to always include the base part.
 *
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   ActiveSync
 * @since     2.29.0
 */
class Horde_ActiveSync_Mime_Iterator
implements Countable, Iterator
{
    /**
     * Flag to ignore parts that EAS considers attachments.
     *
     * @var boolean
     */
    protected $_ignoreAttachments;

    /**
     * Base part.
     *
     * @var Horde_Mime_Part
     */
    protected $_part;

    /**
     * State data.
     *
     * @var object
     */
    protected $_state;

    /**
     * Constructor.
     */
    public function __construct(Horde_Mime_Part $part, $ignoreAttachments = false)
    {
        $this->_ignoreAttachments = $ignoreAttachments;
        $this->_part = $part;
    }

    /* Countable methods. */

    /**
     * Returns the number of message parts.
     *
     * @return integer  Number of message parts.
     */
    public function count()
    {
        return count(iterator_to_array($this));
    }

    protected function _isAttachment($part)
    {
        if ($part->getDisposition() == 'attachment') {
            return true;
        }
        $id = $part->getMimeId();
        $mime_type = $part->getType();
        switch ($mime_type) {
        case 'text/plain':
            if (!($this->_part->findBody('plain') == $id)) {
                return true;
            }
            return false;
        case 'text/html':
            if (!($this->_part->findBody('html') == $id)) {
                return true;
            }
            return false;
        case 'application/pkcs7-signature':
        case 'application/x-pkcs7-signature':
            return false;
        }

        list($ptype,) = explode('/', $mime_type, 2);

        switch ($ptype) {
        case 'message':
            return in_array($mime_type, array('message/rfc822', 'message/disposition-notification'));

        case 'multipart':
            return false;

        default:
            return true;
        }
    }

    /**
     * Return whether or not to allow recursion into a mime part when iterating
     * all of the parts. So far, only disallows this for message/rfc822 parts
     * to prevent each mime part of the rfc822 part to display as an attachment.
     *
     * @param Horde_Mime_Part $part  The part to check.
     *
     * @return boolean  True is we can descend into the part. False otherwise.
     */
    protected function _allowRecursion($part)
    {
        return !in_array($part->getType(), array('message/rfc822'));
    }

    /* RecursiveIterator methods. */

    /**
     */
    public function current()
    {
        return $this->valid()
            ? $this->_state->current
            : null;
    }

    /**
     */
    public function key()
    {
        return ($curr = $this->current())
            ? $curr->getMimeId()
            : null;
    }

    /**
     */
    public function next()
    {
        if (!isset($this->_state)) {
            return;
        }

        $out = $this->_state->current->getPartByIndex($this->_state->index++);
        if ($out) {
            if (($this->_ignoreAttachments && $this->_isAttachment($out)) ||
                !$this->_allowRecursion($this->_state->current)) {
                return $this->next();
            }
            $this->_state->recurse[] = array(
                $this->_state->current,
                $this->_state->index
            );

            $this->_state->current = $out;
            $this->_state->index = 0;
        } elseif ($tmp = array_pop($this->_state->recurse)) {
            $this->_state->current = $tmp[0];
            $this->_state->index = $tmp[1];
            $this->next();
        } else {
            unset($this->_state);
        }
    }

    /**
     */
    public function rewind()
    {
        $this->_state = new stdClass;
        $this->_state->current = $this->_part;
        $this->_state->index = 0;
        $this->_state->recurse = array();
    }

    /**
     */
    public function valid()
    {
        return !empty($this->_state);
    }

}
