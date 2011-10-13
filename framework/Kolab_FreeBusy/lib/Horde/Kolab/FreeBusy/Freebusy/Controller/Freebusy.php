<?php
/**
 * The Kolab implementation of the free/busy system.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Thomas Arendsen Hein <thomas@intevation.de>
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * The core Controller handling the different request types.
 *
 * Copyright 2004-2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Thomas Arendsen Hein <thomas@intevation.de>
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Freebusy_Controller_Freebusy
extends Horde_Kolab_FreeBusy_Controller_Base
{
    /**
     * Fetch the free/busy data for a user.
     *
     * @return NULL
     */
    public function fetch()
    {
        $this->logger->debug(sprintf("Starting generation of free/busy data for user %s",
                                     $this->params->callee));

        $params = array('extended' => $this->params->type == 'xfb');

        // @todo: Reconsider this. We have been decoupled from the
        // global context here but reinjecting this value seems
        // extremely weird. Are there any other options?
        $this->app->callee = $this->params->callee;
        $this->data = $this->app->driver->fetch($this->params);

        $this->logger->debug('Delivering complete free/busy data.');

        /* Display the result to the user */
        $this->render();

        $this->logger->debug('Free/busy generation complete.');
    }

    /**
     * Trigger regeneration of free/busy data in a calender.
     *
     * @return NULL
     */
    public function trigger()
    {
        //@todo: FIX:: Horde::logMessage(sprintf("Starting generation of partial free/busy data for folder %s", 
        //                          $req_folder), __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $this->export = $this->_app->get(
            'Horde_Kolab_FreeBusy_Export'
        );
    }
/*         $this->logger->debug(sprintf("Starting generation of partial free/busy data for folder %s", */
/*                                       $this->params->part)); */

/*         $params = array('extended' => $this->params->type == 'pxfb', */
/*                         'cached' => $this->params->cache); */

/* 	// @todo: Reconsider this. We have been decoupled from the */
/* 	// global context here but reinjecting this value seems */
/* 	// extremely weird. Are there any other options? */
/* 	$this->app->callee_part = $this->params->part; */
/*         $this->data = $this->app->driver->trigger($this->params); */

/*         $this->logger->debug("Delivering partial free/busy data."); */

/*         /\* Display the result to the user *\/ */
/*         $this->render(); */

/*         $this->logger->debug("Free/busy generation complete."); */


    /**
     * Regenerate the free/busy cache data.
     *
     * @return NULL
     */
    function &regenerate($reporter)
    {
        $access = new Horde_Kolab_FreeBusy_Access();
        $result = $access->authenticated();
        if (is_a($result, 'PEAR_Error')) {
            return $result->getMessage();
        }

        /* Load the required Kolab libraries */
        require_once "Horde/Kolab/Storage/List.php";

        $list = &Kolab_List::singleton();
        $calendars = $list->getByType('event');
        if (is_a($calendars, 'PEAR_Error')) {
            return $calendars->getMessage();
        }

        $this->_initCache();

        $lines = array();

        foreach ($calendars as $calendar) {
            /**
             * We are using imap folders for our calendar list but
             * the library expects us to follow the trigger format
             * used by pfb.php
             */
            $req_domain = explode('@', $calendar->name);
            if (isset($req_domain[1])) {
                $domain = $req_domain[1];
            } else {
                $domain = null;
            }
            $req_folder = explode('/', $req_domain[0]);
            if ($req_folder[0] == 'user') {
                unset($req_folder[0]);
                $owner = $req_folder[1];
                unset($req_folder[1]);
            } else if ($req_folder[0] == 'INBOX') {
                $owner = $access->user;
                unset($req_folder[0]);
            }

            $trigger = $owner . ($domain ? '@' . $domain : '') . '/' . join('/', $req_folder);
            $trigger = Horde_String::convertCharset($trigger, 'UTF7-IMAP', 'UTF-8');

            /* Validate folder access */
            $result = $access->parseFolder($trigger);
            if (is_a($result, 'PEAR_Error')) {
                $reporter->failure($calendar->name, $result->getMessage());
                continue;
            }

            /* Hack for allowing manager access */
            if ($access->user == 'manager') {
                $imapc = &Horde_Kolab_IMAP::singleton($GLOBALS['conf']['kolab']['imap']['server'],
                                                      $GLOBALS['conf']['kolab']['imap']['port']);
                $result = $imapc->connect($access->user, $GLOBALS['registry']->getAuthCredential('password'));
                if (is_a($result, 'PEAR_Error')) {
                    $reporter->failure($calendar->name, $result->getMessage());
                    continue;
                }
                $acl = $imapc->getACL($calendar->name);
                if (is_a($acl, 'PEAR_Error')) {
                    $reporter->failure($calendar->name, $result->getMessage());
                    continue;
                }
                $oldacl = '';
                if (isset($acl['manager'])) {
                    $oldacl = $acl['manager'];
                }
                $result = $imapc->setACL($calendar->name, 'manager', 'lrs');
                if (is_a($result, 'PEAR_Error')) {
                    $reporter->failure($calendar->name, $result->getMessage());
                    continue;
                }
            }

            /* Update the cache */
            $result = $this->_cache->store($access);
            if (is_a($result, 'PEAR_Error')) {
                $reporter->failure($calendar->name, $result->getMessage());
                continue;
            }

            /* Revert the acl  */
            if ($access->user == 'manager' && $oldacl) {
                $result = $imapc->setACL($calendar->name, 'manager', $oldacl);
                if (is_a($result, 'PEAR_Error')) {
                    $reporter->failure($calendar->name, $result->getMessage());
                    continue;
                }
            }

            $reporter->success($calendar->name);

        }
        return $lines;
    }

    /**
     * Delete data for a specific user.
     *
     * @return NULL
     */
    public function delete()
    {
    }
}
