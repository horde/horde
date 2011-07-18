<?php
/**
 * The factory for the free/busy provider.
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
 * The factory for the free/busy provider.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If
 * you did not receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Export_Freebusy_Provider_Factory
{
    /**
     * Factory configuration.
     *
     * @var array
     */
    private $_params;

    /**
     * Constructor
     *
     * @param array $params The factory configuration.
     */
    public function __construct($params = array())
    {
        if (!isset($params['server'])) {
            $params['server'] = 'https://localhost/freebusy';
        }
        $this->_params = $params;
    }

    /**
     * Create the required provider.
     *
     * @param Horde_Kolab_FreeBusy_Owner $owner The owner of the f/b data.
     *
     * @return Horde_Kolab_FreeBusy_Export_Freebusy_Provider
     */
    public function create(Horde_Kolab_FreeBusy_Owner $owner)
    {
        $owner_fb = $owner->getFreebusyServer();
        if (!empty($owner_fb) && $owner_fb != $this->_params['server']) {
            if (!empty($this->_params['logger'])) {
                $this->_params['logger']->info(
                    sprintf(
                        "URL \"%s\" indicates remote free/busy server since we only offer \"%s\". Redirecting.", 
                        $owner_fb,
                        $this->_params['server']
                    )
                );
            }
            if (empty($this->_params['redirect'])) {
                $client = null;
                if (!empty($this->_params['http_client'])) {
                    $client = $this->_params['http_client'];
                }
                return new Horde_Kolab_FreeBusy_Export_Freebusy_Provider_Remote_PassThrough(
                    $client
                );
            } else {
                return new Horde_Kolab_FreeBusy_Export_Freebusy_Provider_Remote_Redirect();
            }
        } else {
            return new Horde_Kolab_FreeBusy_Export_Freebusy_Provider_Local();
        }
    }
}
