<?php
/**
 * Copyright 2003-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * The Ingo_Script_Imap class represents an IMAP client-side script generator.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Script_Imap extends Ingo_Script_Base
{
    /**
     * A list of driver features.
     *
     * @var array
     */
    protected $_features = array(
        /* Can tests be case sensitive? */
        'case_sensitive' => false,
        /* Does the driver support setting IMAP flags? */
        'imap_flags' => true,
        /* Does the driver support the stop-script option? */
        'stop_script' => true,
        /* Can this driver perform on demand filtering? */
        'on_demand' => true,
        /* Does the driver require a script file to be generated? */
        'script_file' => false,
    );

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
     * Performs the filtering specified in the rules.
     *
     * @param integer $change  The timestamp of the latest rule change during
     *                         the current session.
     */
    public function perform($change)
    {
        $api = $this->_params['api'];
        $notification = $this->_params['notification'];

        /* Indices that will be ignored by subsequent rules. */
        $ignore_ids = array();

        /* Only do filtering if:
           1. We have not done filtering before -or-
           2. The mailbox has changed -or-
           3. The rules have changed. */
        $cache = $api->getCache();
        if ($cache !== false && $cache == $change) {
            return;
        }

        /* Grab the rules list. */
        $filters = $this->_params['storage']
            ->retrieve(Ingo_Storage::ACTION_FILTERS);

        /* Parse through the rules, one-by-one. */
        foreach ($filters->getFilterList($this->_params['skip']) as $rule) {
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
                    $blacklist = $this->_params['storage']->retrieve(Ingo_Storage::ACTION_BLACKLIST);
                    $addr = $blacklist->getBlacklist();
                    $bl_folder = $blacklist->getBlacklistFolder();
                } else {
                    $whitelist = $this->_params['storage']->retrieve(Ingo_Storage::ACTION_WHITELIST);
                    $addr = $whitelist->getWhitelist();
                }

                /* If list is empty, move on. */
                if (empty($addr)) {
                    continue;
                }

                $query = $this->_getQuery();
                $or_ob = new Horde_Imap_Client_Search_Query();
                foreach ($addr as $val) {
                    $ob = new Horde_Imap_Client_Search_Query();
                    $ob->headerText('from', $val);
                    $or_ob->orSearch(array($ob));
                }
                $query->andSearch(array($or_ob));
                $indices = $api->search($query);

                /* Remove any indices that got in there by way of partial
                 * address match. */
                if (!$msgs = $api->fetchEnvelope($indices)) {
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
                            $api->moveMessages($indices, $bl_folder);
                        } else {
                            $api->deleteMessages($indices);
                        }
                        $notification->push(sprintf(_("Filter activity: %s message(s) that matched the blacklist were deleted."), count($indices)), 'horde.message');
                    }
                } else {
                    $ignore_ids = $indices;
                }
                break;

            case Ingo_Storage::ACTION_KEEP:
            case Ingo_Storage::ACTION_MOVE:
            case Ingo_Storage::ACTION_DISCARD:
                $base_query = $this->_getQuery();
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
                $indices = $api->search($base_query);

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
                        $api->setMessageFlags($indices, $flags);
                    }

                    if ($rule['action'] == Ingo_Storage::ACTION_KEEP) {
                        /* Add these indices to the ignore list. */
                        $ignore_ids = array_unique($indices + $ignore_ids);
                    } elseif ($rule['action'] == Ingo_Storage::ACTION_MOVE) {
                        /* We need to grab the envelope first. */
                        if ($this->_params['show_filter_msg'] &&
                            !($fetch = $api->fetchEnvelope($indices))) {
                            continue;
                        }

                        $mbox = new Horde_Imap_Client_Mailbox($rule['action-value']);

                        /* Move the messages to the requested mailbox. */
                        $api->moveMessages($indices, strval($mbox));

                        /* Display notification message(s). */
                        if ($this->_params['show_filter_msg']) {
                            foreach ($fetch as $msg) {
                                $envelope = $msg->getEnvelope();
                                $notification->push(
                                    sprintf(_("Filter activity: The message \"%s\" from \"%s\" has been moved to the folder \"%s\"."),
                                            !empty($envelope->subject) ? Horde_Mime::decode($envelope->subject) : _("[No Subject]"),
                                            !empty($envelope->from) ? strval($envelope->from) : _("[No Sender]"),
                                            $mbox),
                                    'horde.message');
                            }
                        } else {
                            $notification->push(sprintf(_("Filter activity: %s message(s) have been moved to the folder \"%s\"."),
                                                        count($indices),
                                                        $mbox), 'horde.message');
                        }
                    } elseif ($rule['action'] == Ingo_Storage::ACTION_DISCARD) {
                        /* We need to grab the envelope first. */
                        if ($this->_params['show_filter_msg'] &&
                            !($fetch = $api->fetchEnvelope($indices))) {
                            continue;
                        }

                        /* Delete the messages now. */
                        $api->deleteMessages($indices);

                        /* Display notification message(s). */
                        if ($this->_params['show_filter_msg']) {
                            foreach ($fetch as $msg) {
                                $envelope = $msg->getEnvelope();
                                $notification->push(
                                    sprintf(_("Filter activity: The message \"%s\" from \"%s\" has been deleted."),
                                            !empty($envelope->subject) ? Horde_Mime::decode($envelope->subject) : _("[No Subject]"),
                                            !empty($envelope->from) ? strval($envelope->from) : _("[No Sender]")),
                                    'horde.message');
                            }
                        } else {
                            $notification->push(sprintf(_("Filter activity: %s message(s) have been deleted."), count($indices)), 'horde.message');
                        }
                    } elseif ($rule['action'] == Ingo_Storage::ACTION_MOVEKEEP) {
                        $mbox = new Horde_Imap_Client_Mailbox($rule['action-value']);

                        /* Copy the messages to the requested mailbox. */
                        $api->copyMessages($indices, strval($mbox));

                        /* Display notification message(s). */
                        if ($this->_params['show_filter_msg']) {
                            if (!($fetch = $api->fetchEnvelope($indices))) {
                                continue;
                            }
                            foreach ($fetch as $msg) {
                                $envelope = $msg->getEnvelope();
                                $notification->push(
                                    sprintf(_("Filter activity: The message \"%s\" from \"%s\" has been copied to the folder \"%s\"."),
                                            !empty($envelope->subject) ? Horde_Mime::decode($envelope->subject) : _("[No Subject]"),
                                            !empty($envelope->from) ? strval($envelope->from) : _("[No Sender]"),
                                            $mbox),
                                    'horde.message');
                            }
                        } else {
                            $notification->push(sprintf(_("Filter activity: %s message(s) have been copied to the folder \"%s\"."), count($indices), $mbox), 'horde.message');
                        }
                    }
                }
                break;
            }
        }

        /* Set cache flag. */
        $api->storeCache($change);
    }

    /**
     * Is the perform() function available?
     *
     * @return boolean  True if perform() is available, false if not.
     */
    public function canPerform()
    {
        if ($this->_params['registry']->hasMethod('mail/server')) {
            try {
                $server = $this->_params['registry']->call('mail/server');
                return ($server['protocol'] == 'imap');
            } catch (Horde_Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Returns a query object prepared for adding further criteria.
     *
     * @return Ingo_IMAP_Search_Query  A query object.
     */
    protected function _getQuery()
    {
        $ob = new Horde_Imap_Client_Search_Query();
        $ob->flag('\\deleted', false);
        if ($this->_params['filter_seen'] == Ingo::FILTER_SEEN ||
            $this->_params['filter_seen'] == Ingo::FILTER_UNSEEN) {
            $ob->flag('\\seen', $this->_params['filter_seen'] == Ingo::FILTER_SEEN);
        }

        return $ob;
    }

}
