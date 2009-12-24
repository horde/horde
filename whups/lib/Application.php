<?php
/**
 * Whups application API.
 *
 * @package Whups
 */
class Whups_Application extends Horde_Registry_Application
{
    public $version = 'H3 (2.0-cvs)';

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        static $perms = array();
        if (!empty($perms)) {
            return $perms;
        }

        require_once dirname(__FILE__) . '/base.php';
        global $whups_driver;

        /* Available Whups permissions. */
        $perms['tree']['whups']['admin'] = false;
        $perms['title']['whups:admin'] = _("Administration");

        $perms['tree']['whups']['hiddenComments'] = false;
        $perms['title']['whups:hiddenComments'] = _("Hidden Comments");

        $perms['tree']['whups']['queues'] = array();
        $perms['title']['whups:queues'] = _("Queues");

        /* Loop through queues and add their titles. */
        $queues = $whups_driver->getQueues();
        foreach ($queues as $id => $name) {
            $perms['tree']['whups']['queues'][$id] = false;
            $perms['title']['whups:queues:' . $id] = $name;

            $perms['tree']['whups']['queues'][$id]['update'] = false;
            $perms['title']['whups:queues:' . $id . ':update'] = _("Update");
            $perms['type']['whups:queues:' . $id . ':update'] = 'boolean';
            $perms['params']['whups:queues:' . $id . ':update'] = array();

            $perms['tree']['whups']['queues'][$id]['assign'] = false;
            $perms['title']['whups:queues:' . $id . ':assign'] = _("Assign");
            $perms['type']['whups:queues:' . $id . ':assign'] = 'boolean';
            $perms['params']['whups:queues:' . $id . ':assign'] = array();

            $perms['tree']['whups']['queues'][$id]['requester'] = false;
            $perms['title']['whups:queues:' . $id . ':requester'] = _("Set Requester");
            $perms['type']['whups:queues:' . $id . ':requester'] = 'boolean';
            $perms['params']['whups:queues:' . $id . ':requester'] = array();
        }

        $perms['tree']['whups']['replies'] = array();
        $perms['title']['whups:replies'] = _("Form Replies");

        /* Loop through type and replies and add their titles. */
        foreach ($whups_driver->getAllTypes() as $type_id => $type_name) {
            foreach ($whups_driver->getReplies($type_id) as $reply_id => $reply) {
                $perms['tree']['whups']['replies'][$reply_id] = false;
                $perms['title']['whups:replies:' . $reply_id] = $type_name . ': ' . $reply['reply_name'];
            }
        }

        return $perms;
    }

}
