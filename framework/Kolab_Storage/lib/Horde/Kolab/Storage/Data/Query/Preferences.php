<?php
/**
 * Defines the data query for preferences data.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Defines the data query for preferences data.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @since Horde_Kolab_Storage 1.1.0
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
interface Horde_Kolab_Storage_Data_Query_Preferences
extends Horde_Kolab_Storage_Data_Query
{
    /**
     * Return the preferences for the specified application.
     *
     * @param string $application The application.
     *
     * @return array The preferences.
     */
    public function getApplicationPreferences($application);

    /**
     * Return the applications for which preferences exist in the backend.
     *
     * @param string $application The application.
     *
     * @return array The applications.
     */
    public function getApplications();
}