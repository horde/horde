<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * Ingo external API interface.
 *
 * This file defines Ingo's external API interface. Other applications
 * can interact with Ingo through this API.
 *
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Api extends Horde_Registry_Api
{
    /**
     */
    public function disabled()
    {
        global $prefs, $registry;

        $pushed = $registry->pushApp('ingo');

        $disabled = array();
        if ($prefs->isLocked('blacklist')) {
            $disabled[] = 'blacklistFrom';
        }
        if ($prefs->isLocked('whitelist')) {
            $disabled[] = 'whitelistFrom';
        }
        if ($prefs->isLocked('vacation')) {
            $disabled[] = 'setVacation';
            $disabled[] = 'disableVacation';
        }

        if ($pushed) {
            $registry->popApp();
        }

        return array_merge(parent::disabled(), $disabled);
    }

    /**
     */
    public function links()
    {
        global $prefs, $registry;

        $pushed = $registry->pushApp('ingo');

        $links = array(
            /* @since 3.2.0 */
            'newEmailFilter' => strval(Ingo_Basic_Rule::url()) . '&field[0]=From&match[0]=is&value[0]=|email|',
            'showFilters' => strval(Ingo_Basic_Filters::url()),
            /* @since 3.2.0 */
            'showFiltersMbox' => strval(Ingo_Basic_Filters::url(array('mbox_search' => '|mailbox|')))
        );

        if (!$prefs->isLocked('blacklist')) {
            $links['showBlacklist'] = strval(Ingo_Basic_Blacklist::url());
        }
        if (!$prefs->isLocked('whitelist')) {
            $links['showWhitelist'] = strval(Ingo_Basic_Whitelist::url());
        }
        if (!$prefs->isLocked('vacation')) {
            $links['showVacation'] = strval(Ingo_Basic_Vacation::url());
        }

        if ($pushed) {
            $registry->popApp();
        }

        return $links;
    }

    /**
     * Add addresses to the blacklist.
     *
     * @param string $addresses  The addresses to add to the blacklist.
     */
    public function blacklistFrom($addresses)
    {
        global $injector, $notification;

        if (empty($addresses)) {
            return;
        }

        try {
            $injector->getInstance('Ingo_Factory_Storage')->create()
                ->getSystemRule('Ingo_Rule_System_Blacklist')
                ->addAddresses($addresses);
            $injector->getInstance('Ingo_Factory_Script')->activateAll(false);
            foreach ($addresses as $from) {
                $notification->push(sprintf(_("The address \"%s\" has been added to your blacklist."), $from));
            }
        } catch (Ingo_Exception $e) {
            $notification->push($e);
        }
    }

    /**
     * Add addresses to the whitelist.
     *
     * @param string $addresses  The addresses to add to the whitelist.
     */
    public function whitelistFrom($addresses)
    {
        global $injector, $notification;

        try {
            $injector->getInstance('Ingo_Factory_Storage')->create()
                ->getSystemRule('Ingo_Rule_System_Whitelist')
                ->addAddresses($addresses);
            $injector->getInstance('Ingo_Factory_Script')->activateAll(false);
            foreach ($addresses as $from) {
                $notification->push(sprintf(_("The address \"%s\" has been added to your whitelist."), $from));
            }
        } catch (Ingo_Exception $e) {
            $notification->push($e);
        }
    }

    /**
     * Can this driver perform on-demand filtering?
     *
     * @return boolean  True if perform() is available, false if not.
     */
    public function canApplyFilters()
    {
        /* We intentionally check on_demand instead of calling canPerform()
         * because we only want to check if we can potentially apply filters,
         * not whether we are able to do this right now. */
        return $GLOBALS['injector']->getInstance('Ingo_Factory_Script')
            ->hasFeature('on_demand');
    }

    /**
     * Perform the filtering specified in the rules.
     *
     * @param array $params  The parameter array:
     *   - filter_seen
     *   - mailbox (UTF-8)
     *   - show_filter_msg
     */
    public function applyFilters(array $params = array())
    {
        if (isset($params['mailbox'])) {
            $mbox = new Horde_Imap_Client_Mailbox($params['mailbox']);
            $params['mailbox'] = $mbox->utf7imap;
        }
        foreach ($GLOBALS['injector']->getInstance('Ingo_Factory_Script')->createAll() as $script) {
            $script->setParams($params)->perform();
        }
    }

    /**
     * Set vacation
     *
     * @param array $info        Vacation details.
     *   - addresses: (mixed)    Address list to enable vacation for.
     *   - days: (integer)       Number of days between vacation replies.
     *   - excludes: (mixed)     Address list to exclude from vacation replies.
     *   - ignorelist: (boolean) If set, ignore mailing lists.
     *   - reason: (string)      Vacation message.
     *   - subject: (string)     Vacation email subject.
     *   - start: (integer)      Timestamp of vacation starttime.
     *   - end: (integer)        Timestamp of vacation endtime.
     * @param boolean $enable  Enable the filter?
     *
     * @throws Ingo_Exception
     */
    public function setVacation($info, $enable = true)
    {
        global $injector, $registry;

        if (empty($info)) {
            return true;
        }

        /* Get vacation filter. */
        $ingo_storage = $injector->getInstance('Ingo_Factory_Storage')
            ->create();
        $vacation = new Ingo_Rule_System_Vacation();

        /* Make sure we have at least one address. */
        if (empty($info['addresses'])) {
            $identity = $injector->getInstance('Horde_Core_Factory_Identity')
                ->create();
            /* Remove empty lines. */
            $info['addresses'] = preg_replace(
                '/\n{2,}/', "\n", implode("\n", $identity->getAll('from_addr')));
            if (empty($info['addresses'])) {
                $info['addresses'] = $registry->getAuth();
            }
        }

        $vacation->addresses($info['addresses']);
        if (isset($info['days'])) {
            $vacation->days = $info['days'];
        }
        if (isset($info['excludes'])) {
            $vacation->exclude = $info['excludes'];
        }
        if (isset($info['ignorelist'])) {
            $vacation->ignore_list = ($info['ignorelist'] == 'on');
        }
        if (isset($info['reason'])) {
            $vacation->reason = $info['reason'];
        }
        if (isset($info['subject'])) {
            $vacation->subject = $info['subject'];
        }
        if (isset($info['start'])) {
            $vacation->start = $info['start'];
        }
        if (isset($info['end'])) {
            $vacation->end = $info['end'];
        }

        $vacation->enable = $enable;

        $ingo_storage->updateRule($vacation);

        $injector->getInstance('Ingo_Factory_Script')->activateAll();
    }

    /**
     * Return the vacation message properties.
     *
     * @return array  The property hash
     */
    public function getVacation()
    {
        global $injector;

        /* Get vacation filter. */
        $ingo_storage = $injector->getInstance('Ingo_Factory_Storage')
            ->create();
        $v = $ingo_storage->getSystemRule('Ingo_Rule_System_Vacation');

        return array(
            'addresses' => $v->addresses,
            'days' => $v->days,
            'disabled' => $v->disable,
            'end' => $v->end,
            'excludes' => $v->exclude,
            'ignorelist' => $v->ignore_list,
            'reason' => $v->reason,
            'start' => $v->start,
            'subject' => $v->subject
        );
    }

    /**
     * Disable vacation
     *
     * @throws Ingo_Exception
     */
    public function disableVacation()
    {
        global $injector;

        /* Get vacation filter. */
        $ingo_storage = $injector->getInstance('Ingo_Factory_Storage')
            ->create();
        $v = $ingo_storage->getSystemRule('Ingo_Rule_System_Vacation');
        $v->disable = true;
        $ingo_storage->updateRule($v);

        $injector->getInstance('Ingo_Factory_Script')->activateAll();
    }

}
