<?php
/**
 * Base patchset class.
 *
 * @package Vcs
 */
abstract class Horde_Vcs_Patchset_Base
{
    /**
     * @var array
     */
    protected $_patchsets = array();

    /**
     * Constructor
     *
     * @param Horde_Vcs $rep  A Horde_Vcs repository object.
     * @param array $opts     Additional options:
     * <pre>
     * 'file' - (string) The filename to produce patchsets for.
     * 'range' - (array) The patchsets to process.
     *           DEFAULT: None (all patchsets are processed).
     * </pre>
     */
    abstract public function __construct($rep, $opts = array());

    /**
     * TODO
     *
     * @return array  TODO
     * 'date'
     * 'author'
     * 'branches'
     * 'tags'
     * 'log'
     * 'members' - array:
     *     'file'
     *     'from'
     *     'to'
     *     'status'
     *     'added'
     *     'deleted'
     */
    public function getPatchsets()
    {
        return $this->_patchsets;
    }
}
