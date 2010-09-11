<?php
/**
 * This file defines the base driver class for Ingo_Script_Imap::.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/asl.php ASL
 * @package  Ingo
 */
abstract class Ingo_Script_Imap_Api
{
    /**
     * Configuration parameters.
     *
     * @var array
     */
    protected $_params;

    /**
     * TODO
     */
    static public function factory($type, $params)
    {
        $class = 'Ingo_Script_Imap_' . ucfirst($type);
        return new $class($params);
    }

    /**
     * TODO
     */
    public function __construct($params = array())
    {
        $this->_params = $params;
    }

    /**
     * TODO
     */
    public function deleteMessages($indices)
    {
        return PEAR::raiseError('Not implemented.');
    }

    /**
     * TODO
     */
    public function moveMessages($indices, $folder)
    {
        return PEAR::raiseError('Not implemented.');
    }

    /**
     * TODO
     */
    public function copyMessages($indices, $folder)
    {
        return PEAR::raiseError('Not implemented.');
    }

    /**
     * TODO
     */
    public function setMessageFlags($indices, $flags)
    {
        return PEAR::raiseError('Not implemented.');
    }

    /**
     * TODO
     */
    public function fetchEnvelope($indices)
    {
        return PEAR::raiseError('Not implemented.');
    }

    /**
     * TODO
     */
    public function search($query)
    {
        return PEAR::raiseError('Not implemented.');
    }

    /**
     * TODO
     */
    public function getCache()
    {
        return false;
    }

    /**
     * TODO
     */
    public function storeCache($timestamp)
    {
    }

}
