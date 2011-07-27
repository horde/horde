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
     * The actual data provider.
     *
     * @var Horde_Kolab_FreeBusy_Provider
     */
    private $_provider;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_FreeBusy_Controller_MatchDict $match_dict The match
     *                                                              dictionar     * @param Horde_Kolab_FreeBusy_Provider             $provider   The data
     *                                                              provider.
     */
    public function __construct(
        Horde_Kolab_FreeBusy_Controller_MatchDict $match_dict,
        Horde_Kolab_FreeBusy_Provider $provider
    )
    {
        $this->_match_dict = $match_dict;
        $this->_provider = $provider;
    }

    /**
     * Process the incoming request.
     *
     * @param Horde_Controller_Request $request
     * @param Horde_Controller_Response $response
     */
    public function processRequest(Horde_Controller_Request $request, Horde_Controller_Response $response)
    {
        $params = $this->_match_dict->getMatchDict();
        $this->_provider->{$params->action}($response, $params);
    }
}