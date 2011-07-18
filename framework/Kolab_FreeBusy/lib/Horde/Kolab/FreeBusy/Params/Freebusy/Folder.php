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
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
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
    public function __construct($folder_parameter)
    {
        $folder = explode('/', $folder_parameter);
        if (count($folder) < 2) {
            throw new Horde_Kolab_FreeBusy_Exception(
                sprintf(
                    'No such folder %s. A folder must have at least two components separated by "/".',
                    $folder_parameter
                )
            );
        }

        $folder[0] = strtolower($folder[0]);
        $this->_owner = $folder[0];
        unset($folder[0]);
        $this->_folder = join('/', $folder);
    }

    /**
     * Extract the folder name from the request.
     *
     * @return string The requested folder.
     */
    public function getFolder()
    {
        return $this->_folder;
    }

    /**
     * Extract the resource owner from the request.
     *
     * @return string The resource owner.
     */
    public function getOwner()
    {
        return $this->_owner;
    }
}