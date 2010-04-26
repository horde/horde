<?php
/**
 * This class provides the owner id requested from the free/busy system.
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
 * This class provides the owner id requested from the free/busy system.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_FreeBusy_Params_Freebusy_Folder
implements Horde_Kolab_FreeBusy_Params_Owner
{
    /**
     * The request made to the application.
     *
     * @var Horde_Controller_Request_Base
     */
    private $_request;

    /**
     * The owner id.
     *
     * @var string
     */
    private $_owner;

    /**
     * Constructor.
     *
     * @param Horde_Controller_Request_Base $request The incoming request.
     */
    public function __construct(Horde_Controller_Request_Base $request)
    {
        $this->_request = $request;
    }

    /**
     * Extract the resource owner from the request.
     *
     * @return string The resource owner.
     */
    public function getOwner()
    {
        if ($this->_owner === null) {
            $this->_owner = $this->_getOwnerParameter();
        }
        return $this->_owner;
    }

    /**
     * Return the raw owner id from the request.
     *
     * @return string The owner id.
     */
    private function _getOwnerParameter()
    {
        $parameters = $this->_request->getParameters();
        return isset($parameters['uid']) ? $parameters['uid'] : '';
    }
}