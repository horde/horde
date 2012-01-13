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
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * This class provides the folder name requested from the free/busy system.
 *
 * Copyright 2004-2007 Klarälvdalens Datakonsult AB
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Freebusy_Params_Folder
implements Horde_Kolab_FreeBusy_Params_Owner,
    Horde_Kolab_FreeBusy_Params_Resource
{
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
     * @param string $folder_parameter The folder parameter.
     */
    public function __construct(
        Horde_Kolab_FreeBusy_Controller_MatchDict $match_dict
    )
    {
        $folder_param = $match_dict->getMatchDict()->folder;
        if (!empty($folder_param)) {
            $folder = explode('/', $folder_param);
            if (count($folder) < 2) {
                throw new Horde_Kolab_FreeBusy_Exception(
                    sprintf(
                        'No such folder %s. A folder must have at least two components separated by "/".',
                        $folder_param
                    )
                );
            }

            $folder[0] = strtolower($folder[0]);
            $this->_owner = $folder[0];
            unset($folder[0]);
            $this->_folder = join('/', $folder);
        }
        $owner_param = $match_dict->getMatchDict()->owner;
        if (!empty($owner_param)) {
            $this->_owner = $owner_param;
        }
    }

    /**
     * Extract the folder name from the request.
     *
     * @return string The requested folder.
     */
    public function getResource()
    {
        if ($this->_folder === null) {
            throw new Horde_Kolab_FreeBusy_Exception(
                'The resource parameter has not been provided!'
            );
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
            throw new Horde_Kolab_FreeBusy_Exception(
                'The owner parameter has not been provided!'
            );
        }
        return $this->_owner;
    }
}