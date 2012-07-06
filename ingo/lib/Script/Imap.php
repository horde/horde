<?php
/**
 * The Ingo_Script_Imap:: class represents an IMAP client-side script
 * generator.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Script_Imap extends Ingo_Script
{
    /**
     * The list of actions allowed (implemented) for this driver.
     *
     * @var array
     */
    protected $_actions = array(
        Ingo_Storage::ACTION_KEEP,
        Ingo_Storage::ACTION_MOVE,
        Ingo_Storage::ACTION_DISCARD,
        Ingo_Storage::ACTION_MOVEKEEP
    );

    /**
     * The categories of filtering allowed.
     *
     * @var array
     */
    protected $_categories = array(
        Ingo_Storage::ACTION_BLACKLIST,
        Ingo_Storage::ACTION_WHITELIST
    );

    /**
     * The list of tests allowed (implemented) for this driver.
     *
     * @var array
     */
    protected $_tests = array(
        'contains', 'not contain'
    );

    /**
     * The types of tests allowed (implemented) for this driver.
     *
     * @var array
     */
    protected $_types = array(
        Ingo_Storage::TYPE_HEADER,
        Ingo_Storage::TYPE_SIZE,
        Ingo_Storage::TYPE_BODY
    );

    /**
     * Does the driver support setting IMAP flags?
     *
     * @var boolean
     */
    protected $_supportIMAPFlags = true;

    /**
     * Does the driver support the stop-script option?
     *
     * @var boolean
     */
    protected $_supportStopScript = true;

    /**
     * This driver can perform on demand filtering (in fact, that is all
     * it can do).
     *
     * @var boolean
     */
    protected $_ondemand = true;

    /**
     * The API to use for IMAP functions.
     *
     * @var Ingo_Script_Imap_Api
     */
    protected $_api;

    /**
     * Perform the filtering specified in the rules.
     *
     * @param array $params  The parameter array. It MUST contain:
     * <pre>
     * filter_seen: (boolean) Only filter seen messages?
     * mailbox: (string) The name of the mailbox to filter.
     * show_filter_msg: (boolean) Show detailed filter status messages?
     * </pre>
     *
     * @return boolean  True if filtering performed, false if not.
     */
    public function perform($params)
    {
        if (empty($params['api'])) {
            $this->_api = Ingo_Script_Imap_Api::factory('Live', $params);
        } else {
            $this->_api = &$params['api'];
        }

        /* Indices that will be ignored by subsequent rules. */
        $ignore_ids = array();

        /* Only do filtering if:
           1. We have not done filtering before -or-
           2. The mailbox has changed -or-
           3. The rules have changed. */
        $cache = $this->_api->getCache();
        if (($cache !== false) && ($cache == $GLOBALS['session']->get('ingo', 'change'))) {
            return true;
        }

        /* Grab the rules list. */
        $ingo_storage = $GLOBALS['injector']->getInstance('Ingo_Factory_Storage')->create();
        $filters = $ingo_storage->retrieve(Ingo_Storage::ACTION_FILTERS);

        /* Parse through the rules, one-by-one. */
        foreach ($filters->getFilterList() as $rule) {
            /* Check to make sure this is a valid rule and that the rule is
               not disabled. */
            if (!$this->_validRule($rule['action']) ||
                !empty($rule['disable'])) {
                continue;
            }

            switch ($rule['action']) {
            case Ingo_Storage::ACTION_BLACKLIST:
            case Ingo_Storage::ACTION_WHITELIST:
                $bl_folder = null;

                if ($rule['action'] == Ingo_Storage::ACTION_BLACKLIST) {
                    $blacklist = $ingo_storage->retrieve(Ingo_Storage::ACTION_BLACKLIST);
                    $addr = $blacklist->getBlacklist();
                    $bl_folder = $blacklist->getBlacklistFolder();
                } else {
                    $whitelist = $ingo_storage->retrieve(Ingo_Storage::ACTION_WHITELIST);
                    $addr = $whitelist->getWhitelist();
                }

                /* If list is empty, move on. */
                if (empty($addr)) {
                    continue;
                }

                $query = $this->_getQuery($params);
                $or_ob = new Horde_Imap_Client_Search_Query();
                foreach ($addr as $val) {
                    $ob = new Horde_Imap_Client_Search_Query();
                    $ob->headerText('from', $val);
                    $or_ob->orSearch(array($ob));
                }
                $query->andSearch(array($or_ob));
                $indices = $this->_api->search($query);

                /* Remove any indices that got in there by way of partial
                 * address match. */
                if (!$msgs = $this->_api->fetchEnvelope($indices)) {
                    continue;
                }

                foreach ($msgs as $v) {
                    if (!$v->getEnvelope()->from->match($addr)) {
                        $indices = array_diff($indices, array($v->getUid()));
                    }
                }

                if ($rule['action'] == Ingo_Storage::ACTION_BLACKLIST) {
                    $indices = array_diff($indices, $ignore_ids);
                    if (!empty($indices)) {
                        if (!empty($bl_folder)) {
                            $this->_api->moveMessages($indices, $bl_folder);
                        } else {
                            $this->_api->deleteMessages($indices);
                        }
                        $GLOBALS['notification']->push(sprintf(_("Filter activity: %s message(s) that matched the blacklist were deleted."), count($indices)), 'horde.message');
                    }
                } else {
                    $ignore_ids = $indices;
                }
                break;

            case Ingo_Storage::ACTION_KEEP:
            case Ingo_Storage::ACTION_MOVE:
            case Ingo_Storage::ACTION_DISCARD:
                $base_query = $this->_getQuery($params);
                $query = new Horde_Imap_Client_Search_Query();

                foreach ($rule['conditions'] as $val) {
                    $ob = new Horde_Imap_Client_Search_Query();

                    if (!empty($val['type']) &&
                        ($val['type'] == Ingo_Storage::TYPE_SIZE)) {
                        $ob->size($val['value'], ($val['match'] == 'greater than'));
                    } elseif (!empty($val['type']) &&
                              ($val['type'] == Ingo_Storage::TYPE_BODY)) {
                        $ob->text($val['value'], true, ($val['match'] == 'not contain'));
                    } else {
                        if (strpos($val['field'], ',') == false) {
                            $ob->headerText($val['field'], $val['value'], $val['match'] == 'not contain');
                        } else {
                            foreach (explode(',', $val['field']) as $header) {
                                $hdr_ob = new Horde_Imap_Client_Search_Query();
                                $hdr_ob->headerText($header, $val['value'], $val['match'] == 'not contain');
                                if ($val['match'] == 'contains') {
                                    $ob->orSearch(array($hdr_ob));
                                } elseif ($val['match'] == 'not contain') {
                                    $ob->andSearch(array($hdr_ob));
                                }
                            }
                        }
                    }

                    if ($rule['combine'] == Ingo_Storage::COMBINE_ALL) {
                        $query->andSearch(array($ob));
                    } else {
                        $query->orSearch(array($ob));
                    }
                }

                $base_query->andSearch(array($query));
                $indices = $this->_api->search($base_query);

                if (($indices = array_diff($indices, $ignore_ids))) {
                    if ($rule['stop']) {
                        /* If the stop action is set, add these
                         * indices to the list of ids that will be
                         * ignored by subsequent rules. */
                        $ignore_ids = array_unique($indices + $ignore_ids);
                    }

                    /* Set the flags. */
                    if (!empty($rule['flags']) &&
                        ($rule['action'] != Ingo_Storage::ACTION_DISCARD)) {
                        $flags = array();
                        if ($rule['flags'] & Ingo_Storage::FLAG_ANSWERED) {
                            $flags[] = '\\answered';
                        }
                        if ($rule['flags'] & Ingo_Storage::FLAG_DELETED) {
                            $flags[] = '\\deleted';
                        }
                        if ($rule['flags'] & Ingo_Storage::FLAG_FLAGGED) {
                            $flags[] = '\\flagged';
                        }
                        if ($rule['flags'] & Ingo_Storage::FLAG_SEEN) {
                            $flags[] = '\\seen';
                        }
                        $this->_api->setMessageFlags($indices, $flags);
                    }

                    if ($rule['action'] == Ingo_Storage::ACTION_KEEP) {
                        /* Add these indices to the ignore list. */
                        $ignore_ids = array_unique($indices + $ignore_ids);
                    } elseif ($rule['action'] == Ingo_Storage::ACTION_MOVE) {
                        /* We need to grab the envelope first. */
                        if ($params['show_filter_msg'] &&
                            !($fetch = $this->_api->fetchEnvelope($indices))) {
                            continue;
                        }

                        /* Move the messages to the requested mailbox. */
                        $this->_api->moveMessages($indices, $rule['action-value']);

                        /* Display notification message(s). */
                        if ($params['show_filter_msg']) {
                            foreach ($fetch as $msg) {
                                $envelope = $msg->getEnvelope();
                                $GLOBALS['notification']->push(
                                    sprintf(_("Filter activity: The message \"%s\" from \"%s\" has been moved to the folder \"%s\"."),
                                            !empty($envelope->subject) ? Horde_Mime::decode($envelope->subject) : _("[No Subject]"),
                                            !empty($envelope->from) ? strval($envelope->from) : _("[No Sender]"),
                                            Horde_String::convertCharset($rule['action-value'], 'UTF7-IMAP', 'UTF-8')),
                                    'horde.message');
                            }
                        } else {
                            $GLOBALS['notification']->push(sprintf(_("Filter activity: %s message(s) have been moved to the folder \"%s\"."),
                                                        count($indices),
                                                        Horde_String::convertCharset($rule['action-value'], 'UTF7-IMAP', 'UTF-8')), 'horde.message');
                        }
                    } elseif ($rule['action'] == Ingo_Storage::ACTION_DISCARD) {
                        /* We need to grab the envelope first. */
                        if ($params['show_filter_msg'] &&
                            !($fetch = $this->_api->fetchEnvelope($indices))) {
                            continue;
                        }

                        /* Delete the messages now. */
                        $this->_api->deleteMessages($indices);

                        /* Display notification message(s). */
                        if ($params['show_filter_msg']) {
                            foreach ($fetch as $msg) {
                                $envelope = $msg->getEnvelope();
                                $GLOBALS['notification']->push(
                                    sprintf(_("Filter activity: The message \"%s\" from \"%s\" has been deleted."),
                                            !empty($envelope->subject) ? Horde_Mime::decode($envelope->subject) : _("[No Subject]"),
                                            !empty($envelope->from) ? strval($envelope->from) : _("[No Sender]")),
                                    'horde.message');
                            }
                        } else {
                            $GLOBALS['notification']->push(sprintf(_("Filter activity: %s message(s) have been deleted."), count($indices)), 'horde.message');
                        }
                    } elseif ($rule['action'] == Ingo_Storage::ACTION_MOVEKEEP) {
                        /* Copy the messages to the requested mailbox. */
                        $this->_api->copyMessages($indices,
                                                  $rule['action-value']);

                        /* Display notification message(s). */
                        if ($params['show_filter_msg']) {
                            if (!($fetch = $this->_api->fetchEnvelope($indices))) {
                                continue;
                            }
                            foreach ($fetch as $msg) {
                                $envelope = $msg->getEnvelope();
                                $GLOBALS['notification']->push(
                                    sprintf(_("Filter activity: The message \"%s\" from \"%s\" has been copied to the folder \"%s\"."),
                                            !empty($envelope->subject) ? Horde_Mime::decode($envelope->subject) : _("[No Subject]"),
                                            !empty($envelope->from) ? strval($envelope->from) : _("[No Sender]"),
                                            Horde_String::convertCharset($rule['action-value'], 'UTF7-IMAP', 'UTF-8')),
                                    'horde.message');
                            }
                        } else {
                            $GLOBALS['notification']->push(sprintf(_("Filter activity: %s message(s) have been copied to the folder \"%s\"."), count($indices), Horde_String::convertCharset($rule['action-value'], 'UTF7-IMAP', 'UTF-8')), 'horde.message');
                        }
                    }
                }
                break;
            }
        }

        /* Set cache flag. */
        $this->_api->storeCache($GLOBALS['session']->get('ingo', 'change'));

        return true;
    }

    /**
     * Is the apply() function available?
     *
     * @return boolean  True if apply() is available, false if not.
     */
    public function canApply()
    {
        if ($this->performAvailable() &&
            $GLOBALS['registry']->hasMethod('mail/server')) {
            try {
                $server = $GLOBALS['registry']->call('mail/server');
                return ($server['protocol'] == 'imap');
            } catch (Horde_Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Apply the filters now.
     *
     * @return boolean  See perform().
     */
    public function apply()
    {
        return $this->canApply()
            ? $this->perform(array('mailbox' => 'INBOX', 'filter_seen' => $GLOBALS['prefs']->getValue('filter_seen'), 'show_filter_msg' => $GLOBALS['prefs']->getValue('show_filter_msg')))
            : false;
    }

    /**
     * Returns a query object prepared for adding further criteria.
     *
     * @param array $params  The parameter array. It MUST contain:
     *   - filter_seen: Only filter seen messages?
     *
     * @return Ingo_IMAP_Search_Query  A query object.
     */
    protected function _getQuery($params)
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob->flag('\\deleted', false);
        if ($params['filter_seen'] == Ingo_Script::FILTER_SEEN ||
            $params['filter_seen'] == Ingo_Script::FILTER_UNSEEN) {
            $ob->flag('\\seen', $params['filter_seen'] == Ingo_Script::FILTER_SEEN);
        }

        return $ob;
    }

}
