<?php
/**
 * Base controller.
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
 * Base controller.
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