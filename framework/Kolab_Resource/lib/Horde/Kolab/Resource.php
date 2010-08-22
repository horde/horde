<?php
/**
 * Resource management for the Kolab server.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/** Load the iCal handling */
require_once 'Horde/Icalendar.php';

/** Load MIME handlers */
require_once 'Horde/MIME.php';
require_once 'Horde/MIME/Message.php';
require_once 'Horde/MIME/Headers.php';
require_once 'Horde/MIME/Part.php';
require_once 'Horde/MIME/Structure.php';

/** Load Kolab_Resource elements */
require_once 'Horde/Kolab/Resource/Epoch.php';
require_once 'Horde/Kolab/Resource/Itip.php';
require_once 'Horde/Kolab/Resource/Reply.php';
require_once 'Horde/Kolab/Resource/Freebusy.php';

require_once 'Horde/String.php';
Horde_String::setDefaultCharset('utf-8');

// What actions we can take when receiving an event request
define('RM_ACT_ALWAYS_ACCEPT',              'ACT_ALWAYS_ACCEPT');
define('RM_ACT_REJECT_IF_CONFLICTS',        'ACT_REJECT_IF_CONFLICTS');
define('RM_ACT_MANUAL_IF_CONFLICTS',        'ACT_MANUAL_IF_CONFLICTS');
define('RM_ACT_MANUAL',                     'ACT_MANUAL');
define('RM_ACT_ALWAYS_REJECT',              'ACT_ALWAYS_REJECT');

// What possible ITIP notification we can send
define('RM_ITIP_DECLINE',                   1);
define('RM_ITIP_ACCEPT',                    2);
define('RM_ITIP_TENTATIVE',                 3);

/**
 * Provides Kolab resource handling
 *
 * Copyright 2004-2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @package Kolab_Filter
 * @author  Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 */
class Kolab_Resource
{

    /**
     * Returns the resource policy applying for the given sender
     *
     * @param string $sender   The sender address
     * @param string $resource The resource
     *
     * @return array|PEAR_Error An array with "cn", "home server" and the policy.
     */
    function _getResourceData($sender, $resource)
    {
        require_once 'Horde/Kolab/Server.php';
        $db = Horde_Kolab_Server::singleton();
        if ($db instanceOf PEAR_Error) {
            $db->code = OUT_LOG | EX_SOFTWARE;
            return $db;
        }

        $dn = $db->uidForMail($resource, Horde_Kolab_Server_Object::RESULT_MANY);
        if ($dn instanceOf PEAR_Error) {
            $dn->code = OUT_LOG | EX_NOUSER;
            return $dn;
        }
        if (is_array($dn)) {
            if (count($dn) > 1) {
                Horde::logMessage(sprintf("%s objects returned for %s",
                                          $count($dn), $resource), 'WARN');
                return false;
            } else {
                $dn = $dn[0];
            }
        }
        $user = $db->fetch($dn, 'Horde_Kolab_Server_Object_Kolab_User');

        $cn      = $user->get(Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_CN);
        $id      = $user->get(Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_MAIL);
        $hs      = $user->get(Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_HOMESERVER);
        if (is_a($hs, 'PEAR_Error')) {
            return $hs;
        }
        $hs      = strtolower($hs);
        $actions = $user->get(Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_IPOLICY, false);
        if (is_a($actions, 'PEAR_Error')) {
            $actions->code = OUT_LOG | EX_UNAVAILABLE;
            return $actions;
        }
        if ($actions === false) {
            $actions = array(RM_ACT_MANUAL);
        }

        $policies = array();
        $defaultpolicy = false;
        foreach ($actions as $action) {
            if (preg_match('/(.*):(.*)/', $action, $regs)) {
                $policies[strtolower($regs[1])] = $regs[2];
            } else {
                $defaultpolicy = $action;
            }
        }
        // Find sender's policy
        if (array_key_exists($sender, $policies)) {
            // We have an exact match, stop processing
            $action = $policies[$sender];
        } else {
            $action = false;
            $dn = $db->uidForMailOrAlias($sender);
            if (is_a($dn, 'PEAR_Error')) {
                $dn->code = OUT_LOG | EX_NOUSER;
                return $dn;
            }
            if ($dn) {
                // Sender is local, check for groups
                foreach ($policies as $gid => $policy) {
                    if ($db->memberOfGroupAddress($dn, $gid)) {
                        // User is member of group
                        if (!$action) {
                            $action = $policy;
                        } else {
                            $action = min($action, $policy);
                        }
                    }
                }
            }
            if (!$action && $defaultpolicy) {
                $action = $defaultpolicy;
            }
        }
        return array('cn' => $cn, 'id' => $id,
                     'homeserver' => $hs, 'action' => $action);
    }

    function &_getICal($filename)
    {
        $requestText = '';
        $handle = fopen($filename, 'r');
        while (!feof($handle)) {
            $requestText .= fread($handle, 8192);
        }

        $mime = &MIME_Structure::parseTextMIMEMessage($requestText);

        $parts = $mime->contentTypeMap();
        foreach ($parts as $mimeid => $conttype) {
            if ($conttype == 'text/calendar') {
                $part = $mime->getPart($mimeid);

                $iCalendar = new Horde_Icalendar();
                $iCalendar->parsevCalendar($part->transferDecode());

                return $iCalendar;
            }
        }
        // No iCal found
        return false;
    }

    function _imapConnect($id)
    {
        global $conf;

        // Handle virtual domains
        list($user, $domain) = explode('@', $id);
        if (empty($domain)) {
            $domain = $conf['kolab']['filter']['email_domain'];
        }
        $calendar_user = $conf['kolab']['filter']['calendar_id'] . '@' . $domain;

        /* Load the authentication libraries */
        $auth = $GLOBALS['injector']->getInstance('Horde_Auth')->getAuth(isset($conf['auth']['driver']) ? null : 'kolab');
        $authenticated = $auth->authenticate($calendar_user,
                                             array('password' => $conf['kolab']['filter']['calendar_pass']),
                                             false);

        if (is_a($authenticated, 'PEAR_Error')) {
            $authenticated->code = OUT_LOG | EX_UNAVAILABLE;
            return $authenticated;
        }
        if (!$authenticated) {
            return PEAR::raiseError(sprintf('Failed to authenticate as calendar user: %s',
                                            $auth->getLogoutReasonString()),
                                    OUT_LOG | EX_UNAVAILABLE);
        }
        @session_start();

        $secret = $GLOBALS['injector']->getInstance('Horde_Secret');

        $_SESSION['__auth'] = array(
            'authenticated' => true,
            'userId' => $calendar_user,
            'timestamp' => time(),
            'credentials' => $secret->write($secret->getKey('auth'),
                                            serialize(array('password' => $conf['kolab']['filter']['calendar_pass']))),
            'remote_addr' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null,
        );

        /* Kolab IMAP handling */
        require_once 'Horde/Kolab/Storage/List.php';
        $list = &Kolab_List::singleton();
        $default = $list->getForeignDefault($id, 'event');
        if (!$default || is_a($default, 'PEAR_Error')) {
            $default = new Kolab_Folder();
            $default->setList($list);
            $default->setName($conf['kolab']['filter']['calendar_store']);
            //FIXME: The calendar user needs access here
            $attributes = array('default' => true,
                                'type' => 'event',
                                'owner' => $id);
            $result = $default->save($attributes);
            if (is_a($result, 'PEAR_Error')) {
                $result->code = OUT_LOG | EX_UNAVAILABLE;
                return $result;
            }
        }
        return $default;
    }

    function handleMessage($fqhostname, $sender, $resource, $tmpfname)
    {
        global $conf;

        $rdata = $this->_getResourceData($sender, $resource);
        if (is_a($rdata, 'PEAR_Error')) {
            return $rdata;
        } else if ($rdata === false) {
            /* No data, probably not a local user */
            return true;
        } else if ($rdata['homeserver'] && $rdata['homeserver'] != $fqhostname) {
            /* Not the users homeserver, ignore */
            return true;
        }

        $cn = $rdata['cn'];
        $id = $rdata['id'];
        if (isset($rdata['action'])) {
            $action = $rdata['action'];
        } else {
            // Manual is the only safe default!
            $action = RM_ACT_MANUAL;
        }
        Horde::logMessage(sprintf('Action for %s is %s',
                                  $sender, $action), 'DEBUG');

        // Get out as early as possible if manual
        if ($action == RM_ACT_MANUAL) {
            Horde::logMessage(sprintf('Passing through message to %s', $id), 'INFO');
            return true;
        }

        /* Get the iCalendar data (i.e. the iTip request) */
        $iCalendar = &$this->_getICal($tmpfname);
        if ($iCalendar === false) {
            // No iCal in mail
            Horde::logMessage(sprintf('Could not parse iCalendar data, passing through to %s', $id), 'INFO');
            return true;
        }
        // Get the event details out of the iTip request
        $itip = &$iCalendar->findComponent('VEVENT');
        if ($itip === false) {
            Horde::logMessage(sprintf('No VEVENT found in iCalendar data, passing through to %s', $id), 'INFO');
            return true;
        }
        $itip = new Horde_Kolab_Resource_Itip($itip);

        // What is the request's method? i.e. should we create a new event/cancel an
        // existing event, etc.
        $method = strtoupper(
            $iCalendar->getAttributeDefault(
                'METHOD',
                $itip->getMethod()
            )
        );

        // What resource are we managing?
        Horde::logMessage(sprintf('Processing %s method for %s', $method, $id), 'DEBUG');

        // This is assumed to be constant across event creation/modification/deletipn
        $uid = $itip->getUid();
        Horde::logMessage(sprintf('Event has UID %s', $uid), 'DEBUG');

        // Who is the organiser?
        $organiser = $itip->getOrganizer();
        Horde::logMessage(sprintf('Request made by %s', $organiser), 'DEBUG');

        // What is the events summary?
        $summary = $itip->getSummary();

        $estart = new Horde_Kolab_Resource_Epoch($itip->getStart());
        $dtstart = $estart->getEpoch();
        $eend = new Horde_Kolab_Resource_Epoch($itip->getEnd());
        $dtend = $eend->getEpoch();

        Horde::logMessage(sprintf('Event starts on <%s> %s and ends on <%s> %s.',
                                  $dtstart, $this->iCalDate2Kolab($dtstart), $dtend, $this->iCalDate2Kolab($dtend)), 'DEBUG');

        if ($action == RM_ACT_ALWAYS_REJECT) {
            if ($method == 'REQUEST') {
                Horde::logMessage(sprintf('Rejecting %s method', $method), 'INFO');
                return $this->sendITipReply($cn, $resource, $itip, RM_ITIP_DECLINE,
                                            $organiser, $uid, $is_update);
            } else {
                Horde::logMessage(sprintf('Passing through %s method for ACT_ALWAYS_REJECT policy', $method), 'INFO');
                return true;
            }
        }

        $is_update  = false;
        $imap_error = false;
        $ignore     = array();

        $folder = $this->_imapConnect($id);
        if (is_a($folder, 'PEAR_Error')) {
            $imap_error = &$folder;
        }
        if (!is_a($imap_error, 'PEAR_Error') && !$folder->exists()) {
            $imap_error = &PEAR::raiseError('Error, could not open calendar folder!',
                                    OUT_LOG | EX_TEMPFAIL);
        }

        if (!is_a($imap_error, 'PEAR_Error')) {
            $data = $folder->getData();
            if (is_a($data, 'PEAR_Error')) {
                $imap_error = &$data;
            }
        }

        if (is_a($imap_error, 'PEAR_Error')) {
            Horde::logMessage(sprintf('Failed accessing IMAP calendar: %s',
                                      $folder->getMessage()), 'ERR');
            if ($action == RM_ACT_MANUAL_IF_CONFLICTS) {
                return true;
            }
        }

        switch ($method) {
        case 'REQUEST':
            if ($action == RM_ACT_MANUAL) {
                Horde::logMessage(sprintf('Passing through %s method', $method), 'INFO');
                break;
            }

            if (is_a($imap_error, 'PEAR_Error') || !$data->objectUidExists($uid)) {
                $old_uid = null;
            } else {
                $old_uid = $uid;
                $ignore[] = $uid;
                $is_update = true;
            }

            /** Generate the Kolab object */
            $object = $itip->getKolabObject();

            $outofperiod=0;

            // Don't even bother checking free/busy info if RM_ACT_ALWAYS_ACCEPT
            // is specified
            if ($action != RM_ACT_ALWAYS_ACCEPT) {

                try {
                    require_once 'Horde/Kolab/Resource/Freebusy.php';
                    $fb  = Horde_Kolab_Resource_Freebusy::singleton();
                    $vfb = $fb->get($resource);
                } catch (Exception $e) {
                    return PEAR::raiseError($e->getMessage(),
                                            OUT_LOG | EX_UNAVAILABLE);
                }

                $vfbstart = $vfb->getAttributeDefault('DTSTART', 0);
                $vfbend = $vfb->getAttributeDefault('DTEND', 0);
                Horde::logMessage(sprintf('Free/busy info starts on <%s> %s and ends on <%s> %s',
                                          $vfbstart, $this->iCalDate2Kolab($vfbstart), $vfbend, $this->iCalDate2Kolab($vfbend)), 'DEBUG');

                $evfbend = new Horde_Kolab_Resource_Epoch($vfbend);
                if ($vfbstart && $dtstart > $evfbend->getEpoch()) {
                    $outofperiod=1;
                } else {
                    // Check whether we are busy or not
                    $busyperiods = $vfb->getBusyPeriods();
                    Horde::logMessage(sprintf('Busyperiods: %s',
                                              print_r($busyperiods, true)), 'DEBUG');
                    $extraparams = $vfb->getExtraParams();
                    Horde::logMessage(sprintf('Extraparams: %s',
                                              print_r($extraparams, true)), 'DEBUG');
                    $conflict = false;
                    if (!empty($object['recurrence'])) {
                        $recurrence = new Horde_Date_Recurrence($dtstart);
                        $recurrence->fromHash($object['recurrence']);
                        $duration = $dtend - $dtstart;
                        $events = array();
                        $next_start = $vfbstart;
                        $next = $recurrence->nextActiveRecurrence($vfbstart);
                        while ($next !== false && $next->compareDate($vfbend) <= 0) {
                            $next_ts = $next->timestamp();
                            $events[$next_ts] = $next_ts + $duration;
                            $next = $recurrence->nextActiveRecurrence(array('year' => $next->year,
                                                                            'month' => $next->month,
                                                                            'mday' => $next->mday + 1,
                                                                            'hour' => $next->hour,
                                                                            'min' => $next->min,
                                                                            'sec' => $next->sec));
                        }
                    } else {
                        $events = array($dtstart => $dtend);
                    }

                    foreach ($events as $dtstart => $dtend) {
                        Horde::logMessage(sprintf('Requested event from %s to %s',
                                                  strftime('%a, %d %b %Y %H:%M:%S %z', $dtstart),
                                                  strftime('%a, %d %b %Y %H:%M:%S %z', $dtend)
                                          ), 'DEBUG');
                        foreach ($busyperiods as $busyfrom => $busyto) {
                            if (empty($busyfrom) && empty($busyto)) {
                                continue;
                            }
                            Horde::logMessage(sprintf('Busy period from %s to %s',
                                                      strftime('%a, %d %b %Y %H:%M:%S %z', $busyfrom),
                                                      strftime('%a, %d %b %Y %H:%M:%S %z', $busyto)
                                              ), 'DEBUG');
                            if ((isset($extraparams[$busyfrom]['X-UID'])
                                 && in_array(base64_decode($extraparams[$busyfrom]['X-UID']), $ignore))
                                || (isset($extraparams[$busyfrom]['X-SID'])
                                    && in_array(base64_decode($extraparams[$busyfrom]['X-SID']), $ignore))) {
                                // Ignore
                                continue;
                            }
                            if (($busyfrom >= $dtstart && $busyfrom < $dtend) || ($dtstart >= $busyfrom && $dtstart < $busyto)) {
                                Horde::logMessage('Request overlaps', 'DEBUG');
                                $conflict = true;
                                break;
                            }
                        }
                        if ($conflict) {
                            break;
                        }
                    }

                    if ($conflict) {
                        if ($action == RM_ACT_MANUAL_IF_CONFLICTS) {
                            //sendITipReply(RM_ITIP_TENTATIVE);
                            Horde::logMessage('Conflict detected; Passing mail through', 'INFO');
                            return true;
                        } else if ($action == RM_ACT_REJECT_IF_CONFLICTS) {
                            Horde::logMessage('Conflict detected; rejecting', 'INFO');
                            return $this->sendITipReply($cn, $id, $itip, RM_ITIP_DECLINE,
                                                        $organiser, $uid, $is_update);
                        }
                    }
                }
            }

            if (is_a($imap_error, 'PEAR_Error')) {
                Horde::logMessage('Could not access users calendar; rejecting', 'INFO');
                return $this->sendITipReply($cn, $id, $itip, RM_ITIP_DECLINE,
                                            $organiser, $uid, $is_update);
            }

            // At this point there was either no conflict or RM_ACT_ALWAYS_ACCEPT
            // was specified; either way we add the new event & send an 'ACCEPT'
            // iTip reply

            Horde::logMessage(sprintf('Adding event %s', $uid), 'INFO');

            if (!empty($conf['kolab']['filter']['simple_locks'])) {
                if (!empty($conf['kolab']['filter']['simple_locks_timeout'])) {
                    $timeout = $conf['kolab']['filter']['simple_locks_timeout'];
                } else {
                    $timeout = 60;
                }
                if (!empty($conf['kolab']['filter']['simple_locks_dir'])) {
                    $lockdir = $conf['kolab']['filter']['simple_locks_dir'];
                } else {
                    $lockdir = Horde::getTempDir() . '/Kolab_Filter_locks';
                    if (!is_dir($lockdir)) {
                        mkdir($lockdir, 0700);
                    }
                }
                if (is_dir($lockdir)) {
                    $lockfile = $lockdir . '/' . $resource . '.lock';
                    $counter = 0;
                    while ($counter < $timeout && file_exists($lockfile)) {
                        sleep(1);
                        $counter++;
                    }
                    if ($counter == $timeout) {
                        Horde::logMessage(sprintf('Lock timeout of %s seconds exceeded. Rejecting invitation.', $timeout), 'ERR');
                        return $this->sendITipReply($cn, $id, $itip, RM_ITIP_DECLINE,
                                                    $organiser, $uid, $is_update);
                    }
                    $result = file_put_contents($lockfile, 'LOCKED');
                    if ($result === false) {
                        Horde::logMessage(sprintf('Failed creating lock file %s.', $lockfile), 'ERR');
                    } else {
                        $this->lockfile = $lockfile;
                    }
                } else {
                    Horde::logMessage(sprintf('The lock directory %s is missing. Disabled locking.', $lockdir), 'ERR');
                }
            }

            $itip->setAccepted($resource);

            $result = $data->save($itip->getKolabObject(), $old_uid);
            if (is_a($result, 'PEAR_Error')) {
                $result->code = OUT_LOG | EX_UNAVAILABLE;
                return $result;
            }

            if ($outofperiod) {
                Horde::logMessage('No freebusy information available', 'NOTICE');
                return $this->sendITipReply($cn, $resource, $itip, RM_ITIP_TENTATIVE,
                                            $organiser, $uid, $is_update);
            } else {
                return $this->sendITipReply($cn, $resource, $itip, RM_ITIP_ACCEPT,
                                            $organiser, $uid, $is_update);
            }

        case 'CANCEL':
            Horde::logMessage(sprintf('Removing event %s', $uid), 'INFO');

            if (is_a($imap_error, 'PEAR_Error')) {
                $body = sprintf(_("Unable to access %s's calendar:"), $resource) . "\n\n" . $summary;
                $subject = sprintf(_("Error processing \"%s\""), $summary);
            } else if (!$data->objectUidExists($uid)) {
                Horde::logMessage(sprintf('Canceled event %s is not present in %s\'s calendar',
                                          $uid, $resource), 'WARNING');
                $body = sprintf(_("The following event that was canceled is not present in %s's calendar:"), $resource) . "\n\n" . $summary;
                $subject = sprintf(_("Error processing \"%s\""), $summary);
            } else {
                /**
                 * Delete the messages from IMAP
                 * Delete any old events that we updated
                 */
                Horde::logMessage(sprintf('Deleting %s because of cancel',
                                          $uid), 'DEBUG');

                $result = $data->delete($uid);
                if (is_a($result, 'PEAR_Error')) {
                    Horde::logMessage(sprintf('Deleting %s failed with %s',
                                              $uid, $result->getMessage()), 'DEBUG');
                }

                $body = _("The following event has been successfully removed:") . "\n\n" . $summary;
                $subject = sprintf(_("%s has been cancelled"), $summary);
            }

            Horde::logMessage(sprintf('Sending confirmation of cancelation to %s', $organiser), 'WARNING');

            $body = new MIME_Part('text/plain', Horde_String::wrap($body, 76, "\n", 'utf-8'), 'utf-8');
            $mime = &MIME_Message::convertMimePart($body);
            $mime->setTransferEncoding('quoted-printable');
            $mime->transferEncodeContents();

            // Build the reply headers.
            $msg_headers = new MIME_Headers();
            $msg_headers->addHeader('Date', date('r'));
            $msg_headers->addHeader('From', $resource);
            $msg_headers->addHeader('To', $organiser);
            $msg_headers->addHeader('Subject', $subject);
            $msg_headers->addMIMEHeaders($mime);

            $reply = new Horde_Kolab_Resource_Reply(
                $resource, $organiser, $msg_headers, $mime
            );
            Horde::logMessage('Successfully prepared cancellation reply', 'INFO');
            return $reply;

        default:
            // We either don't currently handle these iTip methods, or they do not
            // apply to what we're trying to accomplish here
            Horde::logMessage(sprintf('Ignoring %s method and passing message through to %s',
                                      $method, $resource), 'INFO');
            return true;
        }
    }

    /**
     * Helper function to clean up after handling an invitation
     *
     * @return NULL
     */
    function cleanup()
    {
        if (!empty($this->lockfile)) {
            @unlink($this->lockfile);
            if (file_exists($this->lockfile)) {
                Horde::logMessage(sprintf('Failed removing the lockfile %s.', $lockfile), 'ERR');
            }
            $this->lockfile = null;
        }
    }

    /**
     * Send an automated reply.
     *
     * @param string  $cn                     Common name to be used in the iTip
     *                                        response.
     * @param string  $resource               Resource we send the reply for.
     * @param string  $Horde_Icalendar_Vevent The iTip information.
     * @param int     $type                   Type of response.
     * @param string  $organiser              The event organiser.
     * @param string  $uid                    The UID of the event.
     * @param boolean $is_update              Is this an event update?
     */
    function sendITipReply(
        $cn, $resource, $itip, $type, $organiser, $uid, $is_update, $comment = null
    ) {
        Horde::logMessage(sprintf('sendITipReply(%s, %s, %s, %s)',
                                  $cn, $resource, get_class($itip), $type),
                          'DEBUG');

        $itip_reply = new Horde_Kolab_Resource_Itip_Response(
            $itip,
            new Horde_Kolab_Resource_Itip_Resource_Base(
                $resource, $cn
            )
        );
        switch($type) {
        case RM_ITIP_DECLINE:
            $type = new Horde_Kolab_Resource_Itip_Response_Type_Decline(
                $resource, $itip
            );
            break;
        case RM_ITIP_ACCEPT:
            $type = new Horde_Kolab_Resource_Itip_Response_Type_Accept(
                $resource, $itip
            );
            break;
        case RM_ITIP_TENTATIVE:
            $type = new Horde_Kolab_Resource_Itip_Response_Type_Tentative(
                $resource, $itip
            );
            break;
        }
        list($headers, $message) = $itip_reply->getMessage(
            $type,
            '-//kolab.org//NONSGML Kolab Server 2//EN',
            $comment
        );

        Horde::logMessage(sprintf('Sending %s iTip reply to %s',
                                  $type->getStatus(),
                                  $organiser), 'DEBUG');

        $reply = new Horde_Kolab_Resource_Reply(
            $resource, $organiser, $headers, $message
        );
        Horde::logMessage('Successfully prepared iTip reply', 'DEBUG');
        return $reply;
    }

    /**
     * Clear information from a date array.
     *
     * @param array $ical_date  The array to clear.
     *
     * @return array The cleaned array.
     */
    function cleanArray($ical_date)
    {
        if (!array_key_exists('hour', $ical_date)) {
            $temp['DATE'] = '1';
        }
        $temp['hour']   = array_key_exists('hour', $ical_date) ? $ical_date['hour'] :  '00';
        $temp['minute']   = array_key_exists('minute', $ical_date) ? $ical_date['minute'] :  '00';
        $temp['second']   = array_key_exists('second', $ical_date) ? $ical_date['second'] :  '00';
        $temp['year']   = array_key_exists('year', $ical_date) ? $ical_date['year'] :  '0000';
        $temp['month']   = array_key_exists('month', $ical_date) ? $ical_date['month'] :  '00';
        $temp['mday']   = array_key_exists('mday', $ical_date) ? $ical_date['mday'] :  '00';
        $temp['zone']   = array_key_exists('zone', $ical_date) ? $ical_date['zone'] :  'UTC';

        return $temp;
    }

    /**
     * Conveert iCal dates to Kolab format.
     *
     * An all day event must have a dd--mm-yyyy notation and not a
     * yyyy-dd-mmT00:00:00z notation Otherwise the event is shown as a
     * 2-day event --> do not try to convert everything to epoch first
     *
     * @param array  $ical_date  The array to convert.
     * @param string $type       The type of the date to convert.
     *
     * @return string The converted date.
     */
    function iCalDate2Kolab($ical_date, $type= ' ')
    {
        Horde::logMessage(sprintf('Converting to kolab format %s',
                                  print_r($ical_date, true)), 'DEBUG');

        // $ical_date should be a timestamp
        if (is_array($ical_date)) {
            // going to create date again
            $temp = $this->cleanArray($ical_date);
            if (array_key_exists('DATE', $temp)) {
                if ($type == 'ENDDATE') {
                    $etemp = new Horde_Kolab_Resource_Epoch($temp);
                    // substract a day (86400 seconds) using epochs to take number of days per month into account
                    $epoch= $etemp->getEpoch() - 86400;
                    $date = gmstrftime('%Y-%m-%d', $epoch);
                } else {
                    $date= sprintf('%04d-%02d-%02d', $temp['year'], $temp['month'], $temp['mday']);
                }
            } else {
                $time = sprintf('%02d:%02d:%02d', $temp['hour'], $temp['minute'], $temp['second']);
                if ($temp['zone'] == 'UTC') {
                    $time .= 'Z';
                }
                $date = sprintf('%04d-%02d-%02d', $temp['year'], $temp['month'], $temp['mday']) . 'T' . $time;
            }
        }  else {
            $date = gmstrftime('%Y-%m-%dT%H:%M:%SZ', $ical_date);
        }
        Horde::logMessage(sprintf('To <%s>', $date), 'DEBUG');
        return $date;
    }
}
