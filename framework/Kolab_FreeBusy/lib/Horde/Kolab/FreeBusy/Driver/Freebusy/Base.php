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
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * The Horde_Kolab_FreeBusy class serves as Registry aka ServiceLocator for the
 * Free/Busy application. It also provides the entry point into the the Horde
 * MVC system and allows to dispatch a request.
 *
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
class Horde_Kolab_FreeBusy_Driver_Freebusy_Base extends Horde_Kolab_FreeBusy_Driver_Base
{

    /**
     * Fetch the free/busy data.
     *
     * @params array   $params   Additional options.
     *
     * @return array The free/busy data.
     */
    public function fetch($params = array())
    {
        $this->logger->debug(sprintf("Free/busy data of owner %s requested by user %s (remote: %s).",
                                     $this->callee, $this->user, $this->remote));

        if (!empty($this->remote)) {
            /* Try to fetch the data if it is stored on a remote server */
            //@todo: How to determine which hook/processor to run?
            return $this->fetchRemote($params);
            // if (is_a($result, 'PEAR_Error')) {
            //    $error = array('type' => FREEBUSY_ERROR_UNAUTHORIZED, 'error' => $result);
        }

        global $conf;

        /* Which files will we access? */
        if (!empty($conf['fb']['use_acls'])) {
            $aclcache = &Horde_Kolab_FreeBusy_Cache_DB_acl::singleton('acl', $this->_cache_dir);
            $files = $aclcache->get($access->owner);
            if (is_a($files, 'PEAR_Error')) {
                return $files;
            }
        } else {
            $file_uid = str_replace("\0", '', str_replace(".", "^", $access->owner));
            $files = array();
            $this->findAll_readdir($file_uid, $conf['fb']['cache_dir'].'/'.$file_uid, $files);
        }

        $owner = $access->owner;
        if (ereg('(.*)@(.*)', $owner, $regs)) {
            $owner = $regs[2] . '/' . $regs[1];
        }
        $user = $access->user;
        if (ereg('(.*)@(.*)', $user, $regs)) {
            $user = $regs[2] . '/' . $regs[1];
        }
        $c_file = str_replace("\0", '', str_replace('.', '^', $user . '/' . $owner));

        $c_vcal = new Horde_Kolab_FreeBusy_Cache_File_vcal($this->_cache_dir,
                                $c_file, $extended);

        /* If the current vCal cache did not expire, we can deliver it */
        if (!$this->cache->expired($files)) {
            return $this->cache->loadVcal();
        }

        // Create the new iCalendar.
        $vCal = new Horde_Icalendar();
        $vCal->setAttribute('PRODID', '-//kolab.org//NONSGML Kolab Server 2//EN');
        $vCal->setAttribute('METHOD', 'PUBLISH');

        // Create new vFreebusy.
        $vFb = Horde_Icalendar::newComponent('vfreebusy', $vCal);
        $params = array();

        $cn = $access->owner_object->get(Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_CN);
        if (!empty($cn) || is_a($cn, 'PEAR_Error')) {
            $params['cn'] = $access->owner_object->get(Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_CN);
        }
        $vFb->setAttribute('ORGANIZER', 'MAILTO:' . $access->owner, $params);

        $vFb->setAttribute('DTSTAMP', time());
        if (isset($_SERVER['SERVER_NAME'])) {
            $host = $_SERVER['SERVER_NAME'];
        } else {
            $host = 'localhost';
        }
        if (isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
        } else {
            $uri = '/';
        }
        $vFb->setAttribute('URL', 'http://' . $host . $uri);

        $mtimes = array();
        foreach ($files as $file) {
            if ($extended && !empty($conf['fb']['use_acls'])) {
                $extended_pvc = $this->_allowExtended($file, $access);
            } else {
                $extended_pvc = $extended;
            }
            $c_pvcal = new Horde_Kolab_FreeBusy_Cache_File_pvcal($this->_cache_dir, $file);
            $pvCal = $c_pvcal->loadPVcal($extended_pvc);
            if (is_a($pvCal, 'PEAR_Error')) {
                Horde::logMessage(sprintf("Ignoring partial free/busy file %s: %s)",
                                          $file, $pvCal->getMessage()),
                                  __FILE__, __LINE__, PEAR_LOG_INFO);
                continue;
            }
            $pvFb = &$pvCal->findComponent('vfreebusy');
            if( !$pvFb ) {
                Horde::logMessage(sprintf("Could not find free/busy info in file %s.)",
                                          $file), __FILE__, __LINE__, PEAR_LOG_INFO);
                continue;
            }
            if ($ets = $pvFb->getAttributeDefault('DTEND', false) !== false) {
                // PENDING(steffen): Make value configurable
                if ($ets < time()) {
                    Horde::logMessage(sprintf("Free/busy info in file %s is too old.)",
                                              $file), __FILE__, __LINE__, PEAR_LOG_INFO);
                    $c_pvcal->purge();
                    continue;
                }
            }
            $vFb->merge($pvFb);

            /* Store last modification time */
            $mtimes[$file] = array($c_pvcal->getFile(), $c_pvcal->getMtime());
        }

        if (!empty($conf['fb']['remote_servers'])) {
            $remote_vfb = $this->_fetchRemote($conf['fb']['remote_servers'],
                                              $access);
            if (is_a($remote_vfb, 'PEAR_Error')) {
                Horde::logMessage(sprintf("Ignoring remote free/busy files: %s)",
                                          $remote_vfb->getMessage()),
                                  __FILE__, __LINE__, PEAR_LOG_INFO);
            } else {
                $vFb->merge($remote_vfb);
            }
        }

        if (!(boolean)$vFb->getBusyPeriods()) {
            /* No busy periods in fb list. We have to add a
             * dummy one to be standards compliant
             */
            $vFb->setAttribute('COMMENT', 'This is a dummy vfreebusy that indicates an empty calendar');
            $vFb->addBusyPeriod('BUSY', 0,0, null);
        }

        $vCal->addComponent($vFb);

        $c_vcal->storeVcal($vCal, $mtimes);

        return $vCal;

        $result = $this->app->getCache->load($access, $extended);
        // if (is_a($result, 'PEAR_Error')) {
        //    $error = array('type' => FREEBUSY_ERROR_NOTFOUND, 'error' => $result);

        //$data = array('fb' => $result, 'name' => $access->owner . '.vfb');
        //$view = &new Horde_Kolab_FreeBusy_View_vfb($data);
    }

    /**
     * Trigger regeneration of free/busy data in a calender.
     *
     * @return NULL
     */
    function &trigger($params = array())
    {
        $this->logger->debug(sprintf("Partial free/busy data of owner %s on server %s requested by user %s.",
                                     $this->callee, $this->freebusyserver, $this->user));

        if (!empty($this->remote)) {
            /* Try to fetch the data if it is stored on a remote server */
            //@todo: How to determine which hook/processor to run?
            return $this->triggerRemote($params);
            // if (is_a($result, 'PEAR_Error')) {
            //    $error = array('type' => FREEBUSY_ERROR_UNAUTHORIZED, 'error' => $result);
        }

        if (!$req_cache) {
            /* User wants to regenerate the cache */

            /* Here we really need an authenticated IMAP user */
            $result = $access->authenticated();
            if (is_a($result, 'PEAR_Error')) {
                $error = array('type' => FREEBUSY_ERROR_UNAUTHORIZED,
                               'error' => $result);
                $view = new Horde_Kolab_FreeBusy_View_error($error);
                return $view;
            }

            if (empty($access->owner)) {
                $message = sprintf(Horde_Kolab_FreeBusy_Translation::t("No such account %s!"),
                                   htmlentities($access->req_owner));
                $error = array('type' => FREEBUSY_ERROR_NOTFOUND,
                               'error' => PEAR::raiseError($message));
                $view = new Horde_Kolab_FreeBusy_View_error($error);
                return $view;
            }

            /* Update the cache */
            $result = $this->_cache->store($access);
            if (is_a($result, 'PEAR_Error')) {
                $error = array('type' => FREEBUSY_ERROR_NOTFOUND,
                               'error' => $result);
                $view = new Horde_Kolab_FreeBusy_View_error($error);
                return $view;
            }
        }

        /* Load the cache data */
        $vfb = $this->_cache->loadPartial($access, $req_extended);
        if (is_a($vfb, 'PEAR_Error')) {
            $error = array('type' => FREEBUSY_ERROR_NOTFOUND,
                           'error' => $vfb);
            $view = new Horde_Kolab_FreeBusy_View_error($error);
            return $view;
        }

        /* Generate the renderer */
        //$data = array('fb' => $vfb, 'name' => $access->owner . '.ifb');
        //$view = new Horde_Kolab_FreeBusy_View_vfb($data);

        /* Finish up */
        return $view;
    }

    /**
     * Fetch remote free/busy user if the current user is not local or
     * redirect to the other server if configured this way.
     *
     * @param boolean $trigger Have we been called for triggering?
     * @param boolean $extended Should the extended information been delivered?
     */
    function fetchRemote($trigger = false, $extended = false)
    {
        global $conf;

        if (!empty($conf['kolab']['freebusy']['server'])) {
            $server = $conf['kolab']['freebusy']['server'];
        } else {
            $server = 'https://localhost/freebusy';
        }
        if (!empty($conf['fb']['redirect'])) {
            $do_redirect = $conf['fb']['redirect'];
        } else {
            $do_redirect = false;
        }

        if ($trigger) {
            $path = sprintf('/trigger/%s/%s.' . ($extended)?'pxfb':'pfb',
                            urlencode($this->owner), urlencode($this->imap_folder));
        } else {
            $path = sprintf('/%s.' . ($extended)?'xfb':'ifb', urlencode($this->owner));
        }

        /* Check if we are on the right server and redirect if appropriate */
        if ($this->freebusyserver && $this->freebusyserver != $server) {
            $redirect = $this->freebusyserver . $path;
            Horde::logMessage(sprintf("URL %s indicates remote free/busy server since we only offer %s. Redirecting.",
                                      $this->freebusyserver, $server), __FILE__,
                              __LINE__, PEAR_LOG_ERR);
            if ($do_redirect) {
                header("Location: $redirect");
            } else {
                header("X-Redirect-To: $redirect");
                $redirect = 'https://' . urlencode($this->user) . ':' . urlencode($GLOBALS['registry']->getAuthCredential('password'))
                    . '@' . $this->freebusyserver . $path;
                if (!@readfile($redirect)) {
                    $message = sprintf(Horde_Kolab_FreeBusy_Translation::t("Unable to read free/busy information from %s"),
                                       'https://' . urlencode($this->user) . ':XXX'
                                       . '@' . $this->freebusyserver . $_SERVER['REQUEST_URI']);
                    return PEAR::raiseError($message);
                }
            }
            exit;
        }
    }
}
