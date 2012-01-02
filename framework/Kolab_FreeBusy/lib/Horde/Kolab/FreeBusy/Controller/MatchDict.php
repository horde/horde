<?php
/**
 * Generates the match dictionary for the incoming request.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Generates the match dictionary for the incoming request.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Controller_MatchDict
{
    /**
     * The routes mapper.
     *
     * @var Horde_Routes_Mapper
     */
    private $_mapper;

    /**
     * The incoming request.
     *
     * @var Horde_Controller_Request
     */
    private $_request;

    /**
     * The match dictionary.
     *
     * @var array
     */
    private $_match_dict;

    /**
     * Constructor
     */
    public function __construct(
        Horde_Routes_Mapper $mapper, Horde_Controller_Request $request
    )
    {
        $this->_mapper = $mapper;
        $this->_request = $request;
    }

    /**
     * Return the match dictionary for the incoming request.
     *
     * @return array The match dictionary.
     */
    public function getMatchDict()
    {
        if ($this->_match_dict === null) {
            $path = $this->_request->getPath();
            if (($pos = strpos($path, '?')) !== false) {
                $path = substr($path, 0, $pos);
            }
            if (!$path) {
                $path = '/';
            }
            $this->_match_dict = new Horde_Support_Array($this->_mapper->match($path));
        }
        return $this->_match_dict;
    }
}