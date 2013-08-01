<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * Ingo external API interface.
 *
 * This file defines Ingo's external API interface. Other applications
 * can interact with Ingo through this API.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
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
            'showFilters' => strval(Ingo_Basic_Filters::url())
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
            $params['mailbox'] = Horde_String::convertCharset(
                $params['mailbox'], 'UTF-8', 'UTF7-IMAP');
        }
        foreach ($GLOBALS['injector']->getInstance('Ingo_Factory_Script')->createAll() as $script) {
            $script
                ->setParams($params)
                ->perform($GLOBALS['session']->get('ingo', 'change'));
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
