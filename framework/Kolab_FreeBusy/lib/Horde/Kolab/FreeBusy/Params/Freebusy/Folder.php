<?php
/**
 * This class provides the folder name requested from the free/busy system.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * This class provides the folder name requested from the free/busy system.
 *
 * Copyright 2004-2007 Klarälvdalens Datakonsult AB
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
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
     * The owner of the folder.
     *
     * @var string
     */
    private $_owner;

    /**
     * The extracted folder name.
     *
     * @var string
     */
    private $_folder;

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
     * Extract the folder name from the request.
     *
     * @return string The requested folder.
     */
    public function getFolder()
    {
        if ($this->_folder === null) {
            $this->_extractOwnerAndFolder();
        }
        return $this->_folder;
    }

    /**
     * Extract the resource owner from the request.
     *
     * @return string The resource owner.
     */
    public function getOwner()
    {
        if ($this->_owner === null) {
            $this->_extractOwnerAndFolder();
        }
        return $this->_owner;
    }

    /**
     * Extract the owner and folder name from the request.
     *
     * @return NULL
     */
    private function _extractOwnerAndFolder()
    {
        $folder = explode('/', $this->_getFolderParameter());
        if (count($folder) < 2) {
            throw new Horde_Kolab_FreeBusy_Exception(
                sprintf('No such folder %s', $this->_getFolderParameter())
            );
        }

        $folder[0] = strtolower($folder[0]);
        $this->_owner = $folder[0];
        unset($folder[0]);
        $this->_folder = join('/', $folder);
    }

    /**
     * Return the raw folder name from the request.
     *
     * @return string The folder name.
     */
    private function _getFolderParameter()
    {
        $parameters = $this->_request->getParameters();
        return isset($parameters['folder']) ? $parameters['folder'] : '';
    }
}