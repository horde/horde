<?php
/**
 * The Kolab implementation of the free/busy system.
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
 * The Horde_Kolab_FreeBusy class serves as Registry aka ServiceLocator for the
 * Free/Busy application. It also provides the entry point into the the Horde
 * MVC system and allows to dispatch a request.
 *
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
class Horde_Kolab_FreeBusy_Driver_Base
{
    /**
     * The logging handler.
     *
     * @var Horde_Log_Logger
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param array       $params        Any additional options
     */
    public function __construct($callee = null, $callee_part = null,
                                $logger = null)
    {

        if (!empty($callee)) {
            list($this->callee, $this->remote) = $this->handleCallee($callee);
        }
        if (!empty($callee_part)) {
            list($this->callee, $this->remote, $this->part) = $this->handleCallee($callee_part);
        }

        $this->logger = $logger;
    }

    /**
     * Create a new driver.
     *
     * @param Horde_Provider $provider The instance providing required
     *                                 dependencies.
     *
     * @return Horde_Kolab_FreeBusy_Driver_Base The new driver.
     */
    static public function factory($provider)
    {
        $class       = 'Horde_Kolab_FreeBusy_Driver_Freebusy_Kolab';
        $callee      = isset($provider->callee) ? $provider->callee : null;
        $callee_part = isset($provider->callee_part) ? $provider->callee_part : null;
        $driver      = new $class($callee, $callee_part, $provider->logger);
        return $driver;
    }

    /**
     * Check if we are in an authenticated situation.
     *
     * @return boolean|PEAR_Error True if successful.
     */
    public function authenticated()
    {
        global $conf;

        if (empty($this->user)) {
            header('WWW-Authenticate: Basic realm="Kolab Freebusy"');
            return PEAR::raiseError(Horde_Kolab_FreeBusy_Translation::t("Please authenticate!"));
        }

        if (!$this->_authenticated) {
            return PEAR::raiseError(sprintf(Horde_Kolab_FreeBusy_Translation::t("Invalid authentication for user %s!"),
                                            $this->user));
        }
        return true;
    }

    /**
     * Fetch the data.
     *
     * @params array $params Additional options.
     *
     * @return array The fetched data.
     */
    //abstract public function fetch($params = array());
}
