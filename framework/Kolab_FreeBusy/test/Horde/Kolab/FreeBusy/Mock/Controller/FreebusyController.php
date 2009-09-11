<?php
/**
 * Tests for the Kolab implementation of the free/busy system.
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
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

/**
 * A mockup for the main application controller.
 *
 * Copyright 2004-2009 Klarälvdalens Datakonsult AB
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
class FreeBusyController extends Horde_Controller_Base
{
    /**
     * Trigger regeneration of free/busy data in a calender.
     *
     * @return NULL
     */
    public function trigger()
    {
        $this->renderText('triggered');
    }

    /**
     * Fetch the free/busy data for a user.
     *
     * @return NULL
     */
    public function fetch()
    {
        $this->renderText('fetched "' . $this->params->type . '" data for user "' . $this->params->id . '"');
    }

    /**
     * Regenerate the free/busy cache data.
     *
     * @return NULL
     */
    public function regenerate()
    {
        $this->renderText('regenerated');
    }

    /**
     * Delete data for a specific user.
     *
     * @return NULL
     */
    public function delete()
    {
        $this->renderText('deleted');
    }
}