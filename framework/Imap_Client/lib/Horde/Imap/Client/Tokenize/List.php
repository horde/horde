<?php
/**
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2012 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 */

/**
 * Tokenizer view window into an IMAP list element.
 *
 * NOTE: This class is NOT intended to be accessed outside of this package.
 * There is NO guarantees that the API of this class will not change across
 * versions.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012 Horde LLC
 * @internal
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 */
class Horde_Imap_Client_Tokenize_List extends Horde_Imap_Client_Tokenize
{
    /**
     * Sublevel.
     *
     * @var integer
     */
    protected $_level;

    /**
     * Master Tokenize object.
     *
     * @var Horde_Imap_Client_Tokenize_Master
     */
    protected $_master;

    /**
     * Start location in master stream.
     *
     * @var integer
     */
    protected $_start;

    /**
     * Constructor.
     *
     * @param Horde_Imap_Client_Tokenize_Master $data  Tokenizer object.
     * @param integer $level                           Sublevel.
     */
    public function __construct(Horde_Imap_Client_Tokenize_Master $data,
                                $level)
    {
        $this->_level = $level;
        $this->_master = $data;
        $this->_start = ftell($data->stream->stream);
    }

    /**
     */
    public function __toString()
    {
        return implode(' ', $this->flushIterator(true));
    }

    /**
     */
    public function next()
    {
        $this->_current = $this->_master->parseStream($this->_level);
        $this->_key = ($this->_current === false)
            ? false
            : (($this->_key === false) ? 0 : ($this->_key + 1));

        return $this->_current;
    }

    /**
     */
    public function rewind()
    {
        fseek($this->_master->stream->stream, $this->_start);
        $this->_key = false;
        return $this->next();
    }

}
