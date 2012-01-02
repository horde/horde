<?php
/**
 * The factory interface.
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
 * The factory interface.
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
interface Horde_Kolab_FreeBusy_Factory
{
    /**
     * Create the object representing the current request.
     *
     * @return Horde_Controller_Request The current request.
     *
     * @throws Horde_Exception
     */
    public function createRequest();

    /**
     * Create the instance that will output the response.
     *
     * @return Horde_Controller_ResponseWriter The response writer.
     *
     * @throws Horde_Exception
     */
    public function createResponseWriter();

    /**
     * Create the view object.
     *
     * @return Horde_View The view helper.
     */
    public function createView();

    /**
     * Return the logger.
     *
     * @return Horde_Log_Logger The logger.
     */
    public function createLogger();

    /**
     * Create the mapper.
     *
     * @return Horde_Route_Mapper The mapper.
     *
     * @throws Horde_Exception
     */
    public function createMapper();

    /**
     * Create the dispatcher.
     *
     * @return Horde_Controller_Dispatcher The dispatcher.
     */
    public function createRequestConfiguration();

    /**
     * Create the user representation.
     *
     * @return Horde_Kolab_FreeBusy_User The user.
     */
    public function createUser();

    /**
     * Create the owner representation.
     *
     * @return Horde_Kolab_FreeBusy_Owner The owner.
     */
    public function createOwner();

    /**
     * Create the data provider.
     *
     * @return Horde_Kolab_FreeBusy_Provider The provider.
     */
    public function createProvider();
}
