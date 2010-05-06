<?php
/**
 * This class provides the Kolab specific resource name requested from
 * the free/busy system.
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
 * This class provides the Kolab specific resource name requested from
 * the free/busy system.
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
class Horde_Kolab_FreeBusy_Params_Freebusy_Resource_Kolab
{
    /**
     * The current user.
     *
     * @var Horde_Kolab_FreeBusy_Params_User
     */
    private $_user;

    /**
     * The requested folder.
     *
     * @var Horde_Kolab_FreeBusy_Params_Freebusy_Folder
     */
    private $_folder;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_FreeBusy_Params_User             $user  The current user.
     * @param Horde_Kolab_FreeBusy_Params_Freebusy_Folder $folder The requested
     *                                                            folder.
     */
    public function __construct(
        Horde_Kolab_FreeBusy_Params_User $user,
        Horde_Kolab_FreeBusy_Params_Freebusy_Folder $folder
    ) {
        $this->_user = $user;
        $this->_folder = $folder;
    }

    /**
     * Extract the resource name from the request.
     *
     * @return string The requested resource.
     */
    public function getResourceId()
    {
        list($user, $userdom) = $this->_splitMailAddress(
            $this->_user->getId()
        );
        list($owner, $ownerdom) = $this->_splitMailAddress(
            $this->_folder->getOwner()
        );

        //@todo: This should be based on the namespaces.
        $fldrcomp = array();
        if ($user == $owner) {
            $fldrcomp[] = 'INBOX';
        } else {
            $fldrcomp[] = 'user';
            $fldrcomp[] = $owner;
        }

        if (!empty($this->_folder->getFolder()) {
            $fldrcomp[] = $this->_folder->getFolder();
        }

        $folder = join('/', $fldrcomp);
        if ($ownerdom && !$userdom) {
            $folder .= '@' . $ownerdom;
        }
        return $folder;
    }

    /**
     * Split a mail address at the '@' sign.
     *
     * @param string $address The address to split.
     *
     * @return array The two splitted parts.
     */
    private function _splitMailAddress($address)
    {
        if (preg_match('/(.*)@(.*)/', $address, $regs)) {
            return array($regs[1], $regs[2]);
        } else {
            return array($address, false);
        }
    }
}