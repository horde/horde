<?php
/**
 * Base controller.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Base controller.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Controller_Base
extends Horde_Controller_Base
{
    /**
     * The routes match dictionary.
     *
     * @var Horde_Kolab_FreeBusy_Controller_MatchDict
     */
    private $_match_dict;

    /**
     * The request parameters.
     *
     * @var array
     */
    protected $params;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_FreeBusy_Controller_MatchDict $match_dict The match
     *                                                              dictionary.
     */
    public function __construct(Horde_Kolab_FreeBusy_Controller_MatchDict $match_dict)
    {
        $this->_match_dict = $match_dict;
    }

    /**
     *
     *
     * @param Horde_Controller_Request $request
     * @param Horde_Controller_Response $response
     */
    public function processRequest(Horde_Controller_Request $request, Horde_Controller_Response $response)
    {
        $this->params = $this->_match_dict->getMatchDict();
        $this->{$this->params->action}($response);
    }

    public function __call($method, $args)
    {
    }
}