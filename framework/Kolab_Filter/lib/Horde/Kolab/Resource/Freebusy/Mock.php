<?php
/**
 * Provides mockup methods to retrieve free/busy data for resources.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Retrieves free/busy mockup data.
 *
 * Copyright 2004-2009 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL>=2.1). If you
 * did not receive this file,
 * see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Resource_Freebusy_Mock extends Horde_Kolab_Resource_Freebusy
{
    /**
     * Retrieve Free/Busy URL for the specified resource id.
     *
     * @param string $resource The id of the resource (usually a mail address).
     *
     * @return string The Free/Busy URL for that resource.
     */
    protected function getUrl($resource)
    {
        return '';
    }

    /**
     * Retrieve Free/Busy data for the specified resource.
     *
     * @param string $resource Fetch the Free/Busy data for this resource
     *                         (usually a mail address).
     *
     * @return Horde_iCalendar_vfreebusy The Free/Busy data.
     */
    public function get($resource)
    {
        return $this->_params['data'];
    }
}