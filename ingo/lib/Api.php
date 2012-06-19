<?php
/**
 * Ingo external API interface.
 *
 * This file defines Ingo's external API interface. Other applications
 * can interact with Ingo through this API.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @package Ingo
 */
class Ingo_Api extends Horde_Registry_Api
{
    /**
     * Links.
     *
     * @var array
     */
    public $links = array(
        'showBlacklist' => '%application%/blacklist.php',
        'showWhitelist' => '%application%/whitelist.php',
        'showFilters' => '%application%/filters.php',
        'showVacation' => '%application%/vacation.php'
    );

    /**
     * Add addresses to the blacklist.
     *
     * @param string $addresses  The addresses to add to the blacklist.
     */
    public function blacklistFrom($addresses)
    {
        global $injector, $notification;

        if (!empty($addresses)) {
            try {
                $bl = $injector->getInstance('Ingo_Factory_Storage')->create()->retrieve(Ingo_Storage::ACTION_BLACKLIST)->getBlacklist();
                Ingo::updateListFilter(array_merge($bl, $addresses), Ingo_Storage::ACTION_BLACKLIST);
                Ingo::updateScript();
                foreach ($addresses as $from) {
                    $notification->push(sprintf(_("The address \"%s\" has been added to your blacklist."), $from));
                }
            } catch (Ingo_Exception $e) {
                $notification->push($e);
            }
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
            $wl = $injector->getInstance('Ingo_Factory_Storage')->create()->retrieve(Ingo_Storage::ACTION_WHITELIST)->getWhitelist();
            Ingo::updateListFilter(array_merge($wl, $addresses), Ingo_Storage::ACTION_WHITELIST);
            Ingo::updateScript();
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
        try {
            return $GLOBALS['injector']->getInstance('Ingo_Script')->performAvailable();
        } catch (Ingo_Exception $e) {
            return false;
        }
    }

    /**
     * Perform the filtering specified in the rules.
     *
     * @param array $params  The parameter array:
     *   - filter_seen
     *   - mailbox (UTF-8)
     *   - show_filter_msg
     *
     * @return boolean  True if filtering was performed, false if not.
     */
    public function applyFilters(array $params = array())
    {
        try {
            if (isset($params['mailbox'])) {
                $params['mailbox'] = Horde_String::convertCharset($params['mailbox'], 'UTF-8', 'UTF7-IMAP');
            }
            return $GLOBALS['injector']->getInstance('Ingo_Script')->perform(array_merge(array(
                'filter_seen' => $GLOBALS['prefs']->getValue('filter_seen'),
                'show_filter_msg' => $GLOBALS['prefs']->getValue('show_filter_msg')
            ), $params));
        } catch (Ingo_Exception $e) {
            return false;
        }
    }

    /**
     * Set vacation
     *
     * @param array $info      Vacation details.
     * @param boolean $enable  Enable the filter?
     *
     * @throws Ingo_Exception
     */
    public function setVacation($info, $enable = true)
    {
        if (empty($info)) {
            return true;
        }

        /* Get vacation filter. */
        $ingo_storage = $GLOBALS['injector']
            ->getInstance('Ingo_Factory_Storage')
            ->create();
        $vacation = $ingo_storage->retrieve(Ingo_Storage::ACTION_VACATION);
        $filters = $ingo_storage->retrieve(Ingo_Storage::ACTION_FILTERS);
        $vacation_id = $filters->findRuleId(Ingo_Storage::ACTION_VACATION);

        /* Make sure we have at least one address. */
        if (empty($info['addresses'])) {
            $identity = $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_Identity')
                ->create();
            /* Remove empty lines. */
            $info['addresses'] = preg_replace(
                '/\n{2,}/', "\n", implode("\n", $identity->getAll('from_addr')));
            if (empty($info['addresses'])) {
                $info['addresses'] = $GLOBALS['registry']->getAuth();
            }
        }

        $vacation->setVacationAddresses($info['addresses']);
        if (isset($info['days'])) {
            $vacation->setVacationDays($info['days']);
        }
        if (isset($info['excludes'])) {
            $vacation->setVacationExcludes($info['excludes']);
        }
        if (isset($info['ignorelist'])) {
            $vacation->setVacationIgnorelist($info['ignorelist'] == 'on');
        }
        if (isset($info['reason'])) {
            $vacation->setVacationReason($info['reason']);
        }
        if (isset($info['subject'])) {
            $vacation->setVacationSubject($info['subject']);
        }
        if (isset($info['start'])) {
            $vacation->setVacationStart($info['start']);
        }
        if (isset($info['end'])) {
            $vacation->setVacationEnd($info['end']);
        }

        $ingo_storage->store($vacation);
        if ($enable) {
            $filters->ruleEnable($vacation_id);
        } else {
            $filters->ruleDisable($vacation_id);
        }
        $ingo_storage->store($filters);
        if ($GLOBALS['prefs']->getValue('auto_update')) {
            Ingo::updateScript();
        }

        /* Update the timestamp for the rules. */
        $GLOBALS['session']->set('ingo', 'change', time());
    }

    /**
     * Return the vacation message properties.
     *
     * @return array  The property hash
     */
    public function getVacation()
    {
        /* Get vacation filter. */
        $ingo_storage = $GLOBALS['injector']
            ->getInstance('Ingo_Factory_Storage')
            ->create();
        $filters = $ingo_storage->retrieve(Ingo_Storage::ACTION_FILTERS);
        $vacation_id = $filters->findRuleId(Ingo_Storage::ACTION_VACATION);
        $rule = $filters->getRule($vacation_id);
        $vacation = $ingo_storage->retrieve(Ingo_Storage::ACTION_VACATION);
        $res = $vacation->toHash();
        $res['disabled'] = $rule['disable'];

        return $res;
    }

    /**
     * Disable vacation
     *
     * @throws Ingo_Exception
     */
    public function disableVacation()
    {
        /* Get vacation filter. */
        $ingo_storage = $GLOBALS['injector']
            ->getInstance('Ingo_Factory_Storage')
            ->create();
        $filters = $ingo_storage->retrieve(Ingo_Storage::ACTION_FILTERS);
        $vacation_id = $filters->findRuleId(Ingo_Storage::ACTION_VACATION);
        $filters->ruleDisable($vacation_id);
        $ingo_storage->store($filters);
        if ($GLOBALS['prefs']->getValue('auto_update')) {
            Ingo::updateScript();
        }

        /* Update the timestamp for the rules. */
        $GLOBALS['session']->set('ingo', 'change', time());
    }

}
