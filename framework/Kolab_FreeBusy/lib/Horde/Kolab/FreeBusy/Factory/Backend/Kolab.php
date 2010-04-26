<?php
/**
 * Specific factory methods for the free/busy export from a Kolab backend.
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
 * Specific factory methods for the free/busy export from a Kolab backend.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
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
 * @since    Horde 3.2
 */
class Horde_Kolab_FreeBusy_Factory_Backend_Kolab
{
    /**
     * The injector providing required dependencies.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * Constructor.
     *
     * @param Horde_Injector $injector The injector providing required dependencies.
     */
    public function __construct(Horde_Injector $injector)
    {
        $this->_injector = $injector;
    }

    /**
     * Create the object representing the current user requesting the export.
     *
     * @return Horde_Kolab_FreeBusy_User_Interface The current user.
     *
     * @throws Horde_Exception
     */
    public function getUser()
    {
    }

    /**
     * Create the backend resource handler
     *
     * @return Horde_Kolab_FreeBusy_Resource The resource.
     */
    public function getResource()
    {
        if ($folder->getType() == 'event') {
            return new Horde_Kolab_FreeBusy_Resource_Event_Kolab(
                $folder
            );
        } else {
            throw new Horde_Kolab_FreeBusy_Exception(
                sprintf(
                    'Resource %s has type "%s" not "event" which is not supported!',
                    $folder->getName(), $folder->getType()
                )
            );
        }
    }

}