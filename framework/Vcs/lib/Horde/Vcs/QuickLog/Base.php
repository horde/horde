<?php
/**
 * Base quick log class.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @package Vcs
 */
abstract class Horde_Vcs_QuickLog_Base
{
    /**
     * A repository object.
     *
     * @var Horde_Vcs_Base
     */
    protected $_rep;

    /**
     * A log revision.
     *
     * @var string
     */
    protected $_rev;

    /**
     * A log author.
     *
     * @var string
     */
    protected $_author;

    /**
     * A log timestamp.
     *
     * @var integer
     */
    protected $_date;

    /**
     * A log message.
     *
     * @var string
     */
    protected $_log;

    /**
     * Constructor.
     *
     * @param Horde_Vcs_Base $rep  A repository object.
     * @param string $rev          A log revision.
     * @param integer $date        A log timestamp.
     * @param string $author       A log author.
     * @param string $log          A log message.
     */
    public function __construct($rep, $rev, $date = null, $author = null,
                                $log = null)
    {
        $this->_rep    = $rep;
        $this->_rev    = $rev;
        $this->_date   = $date;
        $this->_author = $author;
        $this->_log    = $log;
    }

    /**
     * When serializing, don't return the repository object
     */
    public function __sleep()
    {
        return array_diff(array_keys(get_object_vars($this)), array('_rep'));
    }

    /**
     * Returns the log revision.
     *
     * @return string  A revision.
     */
    public function getRevision()
    {
        return $this->_rev;
    }

    /**
     * Returns the log date.
     *
     * @return integer  A date.
     */
    public function getDate()
    {
        return $this->_date;
    }

    /**
     * Returns the log author.
     *
     * @return string  An author.
     */
    public function getAuthor()
    {
        return $this->_author;
    }

    /**
     * Returns the log message.
     *
     * @return string  A message.
     */
    public function getMessage()
    {
        return $this->_log;
    }
}
