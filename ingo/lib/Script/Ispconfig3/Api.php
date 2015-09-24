<?php
/**
* Copyright 2003-2015 Horde LLC (http://www.horde.org/)
*
* See the enclosed file LICENSE for license information (ASL).  If you
* did not receive this file, see http://www.horde.org/licenses/apache.
*
* @author   Michael Epstein <mepstein@mediabox.cl>
* @category Horde
* @license  http://www.horde.org/licenses/apache ASL
* @package  Ingo
*/

/**
* This file defines the api class for Ingo_Script_Ispconfig3.
*
* @author   Michael Epstein <mepstein@mediabox.cl>
* @category Horde
* @license  http://www.horde.org/licenses/apache ASL
* @package  Ingo
*/
class Ingo_Script_Ispconfig3_Api
{
    /**
    * The SOAP connection
    *
    * @var SoapClient
    */
    protected $_soap;

    /**
    * The SOAP session id
    *
    * @var string
    */
    protected $_soap_session;

    /**
    * Params
    * 
    * @var mixed
    */
    protected $_params;

    /**
    * Mail user data.
    * 
    * @var mixed
    */
    protected $_mail_user;

    /**
    * Remote user id
    * 
    * @var int
    */
    protected $_uid;

    /**
    * Spam user data.
    * 
    * @var mixed
    */
    protected $_spam_user;

    /**
    * User filters.
    * 
    * @var mixed
    */
    protected $_user_filters;

    /**
    * Client data.
    * 
    * @var mixed
    */
    protected $_client;

    /**
    * Data for add, update or delete.
    * 
    * @var stdClass
    */
    protected $_data = array();

    /**
    * Whitelist, blacklist and rules count.
    * 
    * @var mixed
    */
    protected $_count = array(
        'wblist' => 0,
        'rules' => 0
    );

    /**
    * Unobtrusive messages
    * 
    * @var mixed
    */
    protected $_messages = array();

    /**
    * Constructor.
    * 
    * @param array $params
    */
    public function __construct($params = array())
    {
        $this->_params = $params;
    }

    /**
    * Process loaded rules.
    * 
    * @param array $rules Rules to be processed.
    * @throws Ingo_Exception
    */
    public function processRules($rules)
    {
        try
        {
            // Connect and retrieve the basic data.
            $this->_getData();

            foreach ($rules as $rule)
            {
                if (!count($rule))
                {
                    return;
                }

                $status = $rule->disable ? 'n' : 'y';

                switch (get_class($rule))
                {
                    case 'Ingo_Rule_System_Blacklist':
                        $this->_wblist($rule, $status, 'B');
                        break;
                    case 'Ingo_Rule_System_Whitelist':
                        $this->_wblist($rule, $status, 'W');
                        break;
                    case 'Ingo_Rule_System_Vacation':
                        $this->_vacation($rule, $status);
                        break;
                    case 'Ingo_Rule_System_Forward':
                        $this->_forward($rule, $status);
                        break;
                    case 'Ingo_Rule_User_Move':
                        $this->_filter($rule, $status, 'move');
                        break;
                    case 'Ingo_Rule_User_Discard':
                    $this->_filter($rule, $status, 'delete');
                    break;
                }
            }

            // Save data.
            $this->_saveData();
        }
        catch (Exception $e)
        {
            throw new Ingo_Exception(sprintf(_('SOAP error: %s'), $e->getMessage()));
        }

        $this->sendMessages();
    }

    /**
    * Sync whitelist & blacklist data.
    * 
    * @param Ingo_Rule $rule Rule object.
    * @param string $status Disable state
    * @param string $filter Filter type (W/B)
    */
    protected function _wblist(Ingo_Rule $rule, $status, $filter)
    {
        $this->_getSpamUser();

        // If no spamuser, we can't save whitelist & blacklist filters.
        if (empty($this->_spam_user))
        {
            return;
        }

        // Load filter
        if ($filter == 'W')
        {
            $api_list = $this->_soap->mail_spamfilter_whitelist_get(
                $this->_soap_session,
                array(
                    'rid' => $this->_spam_user['id'],
                    'wb' => $filter
                )
            );

            $type = 'whitelist';
        }
        else
        {
            $api_list = $this->_soap->mail_spamfilter_blacklist_get(
                $this->_soap_session,
                array(
                    'rid' => $this->_spam_user['id'],
                    'wb' => $filter
                )
            );

            $type = 'blacklist';
        }

        // No action if no filters.
        if (empty($rule->addresses) && empty($api_list))
        {
            return;
        }

        // Sync rules.
        if (!empty($api_list))
        {
            $this->_count['wblist'] += count($api_list);

            foreach ($api_list as $api_id => $api_check)
            {
                if ($rule->addresses)
                {
                    $candidate = array_search($api_check['email'], $rule->addresses);

                    // Delete record not in the server.
                    if ($candidate === false)
                    {
                        $this->_data[$type]['delete'][$api_check['wblist_id']] = null;
                        $this->_count['wblist']--;
                    }
                    // Record in the server.
                    else
                    {
                        // Update if they don't have the same disable status.
                        if ($api_check['active'] !== $status)
                        {
                            $params = array(
                                'server_id' => $this->_spam_user['server_id'],
                                'rid' => $this->_spam_user['id'],
                                'wb' => $filter,
                                'email' => $api_check['email'],
                                'priority' => '5',
                                'active' => $status
                            );

                            $this->_data[$type]['update'][$api_check['wblist_id']] = $params;
                        }

                        unset($rule->addresses[$candidate]);
                    }
                }
                else
                {
                    $this->_data[$type]['delete'][$api_check['wblist_id']] = null;
                    $this->_count['wblist']--;
                }
            }
        }

        if (!empty($rule->addresses))
        {
            $limit = array();

            foreach ($rule->addresses as $wbl_add)
            {
                // Check for rules limit.
                if ($this->_client['limit_spamfilter_wblist'] > 0 && $this->_count['wblist'] >= $this->_client['limit_spamfilter_wblist'])
                {
                    $limit[] = $wbl_add;

                    continue;
                }

                // Add new rule
                $params = array(
                    'sys_userid' => $this->_spam_user['sys_userid'],
                    'sys_groupid' => $this->_spam_user['sys_groupid'],
                    'server_id' => $this->_spam_user['server_id'],
                    'rid' => $this->_spam_user['id'],
                    'wb' => $filter,
                    'email' => $wbl_add,
                    'priority' => '5',
                    'active' => $status
                );

                $this->_data[$type]['add'][] = $params;

                $this->_count['wblist']++;
            }

            // Send message about limit reached.
            if ($limit)
            {
                $this->setMessage(sprintf(
                    _('The following emails cannot be added to the %s filter because the limit of %s rules have been reached: %s'),
                    _($type),
                    $this->_client['limit_spamfilter_wblist'],
                    implode(', ', $limit)
                    ),
                    'error');
            }
        }
    }

    /**
    * Set the vacation details for the user.
    * 
    * @param Ingo_Rule $rule Rule object.
    * @param string $status Disable state
    */
    protected function _vacation(Ingo_Rule $rule, $status)
    {
        $params = array();

        if ($this->_mail_user['autoresponder'] != $status)
        {
            $params['autoresponder'] = $status;
        }

        if ($this->_mail_user['autoresponder_subject'] != $rule->subject)
        {
            $params['autoresponder_subject'] = $rule->subject;
        }

        $text = Ingo_Rule_System_Vacation::vacationReason(
            $rule->reason,
            $rule->start,
            $rule->end
        );

        if ($this->_mail_user['autoresponder_text'] != $text)
        {
            $params['autoresponder_text'] = $text;
        }

        if ($rule->start)
        {
            $start_check = array(
                'year' => date('Y', $rule->start),
                'month' => date('m', $rule->start),
                'day' => date('d', $rule->start),
                'hour' => '00',
                'minute' => '00'
            );

            if (!isset($this->_mail_user['autoresponder_start_date']) ||
            array_diff_assoc($start_check, $this->_mail_user['autoresponder_start_date']))
            {
                $params['autoresponder_start_date'] = $start_check;
            }
        }
        elseif (isset($this->_mail_user['autoresponder_start_date']))
        {
            unset($this->_mail_user['autoresponder_start_date']);
        }

        if ($rule->end)
        {
            $end_check = array(
                'year' => date('Y', $rule->end),
                'month' => date('m', $rule->end),
                'day' => date('d', $rule->end),
                'hour' => '23',
                'minute' => '55'
            );

            if (!isset($this->_mail_user['autoresponder_end_date']) ||
            array_diff_assoc($end_check, $this->_mail_user['autoresponder_end_date']))
            {
                $params['autoresponder_end_date'] = $end_check;
            }
        }
        elseif (isset($this->_mail_user['autoresponder_end_date']))
        {
            unset($this->_mail_user['autoresponder_end_date']);
        }

        if ($params)
        {
            $this->_mail_user = array_merge($this->_mail_user, $params);

            $this->_data['mail'] = $this->_mail_user;
        }
    }

    /**
    * Set forward addresses
    * 
    * @param Ingo_Rule $rule Rule object.
    * @param string $status Disable state
    */
    protected function _forward(Ingo_Rule $rule, $status)
    {
        $action = array();

        if ($status == 'y' && !empty($rule->addresses))
        {
            foreach ($rule->addresses as $addr)
            {
                $addr = trim($addr);

                if (!empty($addr))
                {
                    $action[] = $addr;
                }
            }
        }

        $cc = $action ? implode(',', $action) : '';

        if ($this->_mail_user['cc'] != $cc)
        {
            $this->_mail_user['cc'] = $cc;

            $this->_data['mail'] = $this->_mail_user;
        }
    }

    /**
    * Sync user filters
    * 
    * @param Ingo_Rule $rule Rule object.
    * @param string $status Disable state
    * @param string $action Action (delete/move)
    */
    protected function _filter(Ingo_Rule $rule, $status, $action)
    {
        $target = $action == 'delete' ? '' : $rule->value;

        $limit = array();

        foreach ($rule->conditions as $rule_key => $rule_data)
        {
            $rule_mix = $rule->id . ':' . $rule_key;

            if (!in_array($rule_data['field'], array('To', 'Subject', 'From')))
            {
                throw new Ingo_Exception(_('The Ingo Ispconfig 3 fields is not properly configured. Please edit your ingo/config/fields.local.php.'));
            }

            $params = array(
                'mailuser_id' => $this->_mail_user['mailuser_id'],
                'rulename' => $rule->name . ' (Horde:' . $rule_mix . ')',
                'source' => $rule_data['field'],
                'searchterm' => $rule_data['value'],
                'op' => $rule_data['match'],
                'action' => $action,
                'target' => $target,
                'active' => $status
            );

            if (isset($this->_user_filters[$rule_mix]))
            {
                $check = $this->_user_filters[$rule_mix];

                $filter_id = $check['filter_id'];

                unset($check['filter_id']);

                if (array_diff_assoc($check, $params))
                {
                    $this->_data['filter']['update'][$filter_id] = $params;
                }

                // Remove this filter from filters list.
                unset($this->_user_filters[$rule_mix]);

                // Remove this filter from delete list.
                unset($this->_data['filter']['delete'][$filter_id]);
            }
            else
            {
                if ($this->_client['limit_spamfilter_policy'] > 0 && $this->_count['rules'] >= $this->_client['limit_spamfilter_policy'])
                {
                    $limit[] = $rule->name . '(' . ($rule_key + 1) . ')';
                }
                else
                {
                    $this->_data['filter']['add'][] = $params;
                }
            }
        }

        if ($limit)
        {
            $this->setMessage(sprintf(
                _('The following rules cannot be added because the limit of %s rules have been reached: %s'),
                $this->_client['limit_spamfilter_policy'],
                implode(', ', $limit)
                ),
                'error');
        }
    }

    /**
    * Get the spamfilter user data and create a new one if do not exist.
    * 
    * @throws Ingo_Exception
    */
    protected function _getSpamUser()
    {
        // Need to load this data only once.
        if ($this->_spam_user !== null)
        {
            return;
        }

        $user_spam = $this->_soap->mail_spamfilter_user_get(
            $this->_soap_session,
            array(
                'email' => $this->_mail_user['email']
            )
        );

        // Try to create new spamfilter user if we don't have one.
        if (empty($user_spam[0]['id']))
        {
            $params = array(
                'server_id' => $this->_mail_user['server_id'],
                'priority' => '10',
                'policy_id' => (int) $this->_params['policy_id'],
                'email' => $this->_mail_user['email'],
                'fullname' => $this->_mail_user['email'],
                'local' => 'Y'
            );

            try
            {
                $this->_soap->mail_spamfilter_user_add(
                    $this->_soap_session,
                    $this->_uid,
                    $params
                );

                $user_spam = $this->_soap->mail_spamfilter_user_get(
                    $this->_soap_session,
                    array(
                        'email' => $this->_mail_user['email']
                    )
                );
            }
            catch (SoapFault $e)
            {
                $this->setMessage(sprintf(
                    _('Unable to create a new spamfilter user. Whitelist and Blacklist filters can not be saved. The error returned was: %s'),
                    $e->getMessage()
                    ), 'error');
            }
        }

        $this->_spam_user = empty($user_spam[0]['id']) ? array() : $user_spam[0];
    }

    /**
    * Connects to the SOAP server.
    *
    * @throws Ingo_Exception
    */
    protected function _getData()
    {
        $this->_checkConfig();

        $soap_uri = $this->_params['soap_uri'];

        $this->_soap = new SoapClient(null, array(
            'location' => $soap_uri . 'index.php',
            'uri'      => $soap_uri
            )
        );

        if (!($this->_soap_session = $this->_soap->login(
        $this->_params['soap_user'],
        $this->_params['soap_pass'])))
        {
            throw new Ingo_Exception(sprintf(_("Login to %s failed."), $soap_uri));
        }

        if (!($email = filter_var(Ingo::getUser(), FILTER_VALIDATE_EMAIL)))
        {
            throw new Ingo_Exception(_('Invalid user email.'));
        }

        $mail_user = $this->_soap->mail_user_get(
            $this->_soap_session,
            array(
                'login' => $email
            )
        );

        if (count($mail_user) != 1)
        {
            throw new Ingo_Exception(
                sprintf(
                    _("%d users with login %s found, one expected."),
                    count($response),
                    $email
                )
            );
        }

        // Remove user password.
        unset($mail_user[0]['password']);

        // Convert start date to array only if we have a valid date.
        if (!($start = $this->_checkDate($mail_user[0]['autoresponder_start_date'])))
        {
            unset($mail_user[0]['autoresponder_start_date']);
        }
        else
        {
            $mail_user[0]['autoresponder_start_date'] = array(
                'year' => $start['year'],
                'month' => $start['month'],
                'day' => $start['day'],
                'hour' => '00',
                'minute' => '00'
            );
        }

        // Convert end date to array only if we have a valid date.
        if (!($end = $this->_checkDate($mail_user[0]['autoresponder_end_date'])))
        {
            unset($mail_user[0]['autoresponder_end_date']);
        }
        else
        {
            $mail_user[0]['autoresponder_end_date'] = array(
                'year' => $end['year'],
                'month' => $end['month'],
                'day' => $end['day'],
                'hour' => '23',
                'minute' => '55'
            );
        }

        $this->_mail_user = $mail_user[0];

        $uid = $this->_soap->client_get_id(
            $this->_soap_session,
            $this->_mail_user['sys_userid']
        );

        if (!is_int($uid))
        {
            throw new Ingo_Exception(_("Invalid data returned by the user id request."));
        }

        $this->_uid = $uid;

        $client = $this->_soap->client_get(
            $this->_soap_session,
            $this->_uid
        );

        if (!isset($client['limit_spamfilter_wblist']) || !isset($client['limit_spamfilter_policy']))
        {
            throw new Ingo_Exception(_('Invalid data returned by the client request.'));
        }

        $this->_client = $client;

        $user_filters = $this->_soap->mail_user_filter_get(
            $this->_soap_session,
            array(
                'mailuser_id' => $this->_mail_user['mailuser_id']
            )
        );

        $this->_count['rules'] += count($user_filters);

        $this->_user_filters = array();

        foreach ($user_filters as $filter)
        {
            preg_match('/\(Horde:([0-9]+:[0-9]+)\)/', $filter['rulename'], $matches);

            // Manage only rules created by Ingo.
            if ($matches && !isset($this->_user_filters[$matches[1]]))
            {
                $filter_add = array(
                    'filter_id' => $filter['filter_id'],
                    'mailuser_id' => $filter['mailuser_id'],
                    'rulename' => $filter['rulename'],
                    'source' => $filter['source'],
                    'searchterm' => $filter['searchterm'],
                    'op' => $filter['op'],
                    'action' => $filter['action'],
                    'target' => $filter['target'],
                    'active' => $filter['active']
                );

                $this->_user_filters[$matches[1]] = $filter_add;

                // By default, we put all the rules in delete state.
                $this->_data['filter']['delete'][$filter['filter_id']] = null;
            }
            // Delete rules not created by Ingo?
            elseif ($this->_params['delete_orphan'] === true)
            {
                $this->_soap->mail_user_filter_delete(
                    $this->_soap_session,
                    $filter['filter_id']
                );

                $this->_count['rules']--;
            }
        }
    }

    /**
    * Check for a valid API date.
    * 
    * @param string $date API date.
    * 
    * @return mixed
    */
    protected function _checkDate($date)
    {
        $parts = explode(' ', $date);

        $ymd = (int) $parts[0];

        if (!$ymd)
        {
            return false;
        }

        $parse = explode('-', $parts[0]);

        if (count($parse) != 3 || in_array('00', $parse))
        {
            return false;
        }

        return array(
            'year' => $parse[0],
            'month' => $parse[1],
            'day' => $parse[2]
        );
    }

    /**
    * Save stored data.
    * 
    */
    protected function _saveData()
    {
        if (!empty($this->_data))
        {
            // Update the mail user first.
            if (isset($this->_data['mail']))
            {
                $this->_soap->mail_user_update(
                    $this->_soap_session,
                    $this->_uid,
                    $this->_data['mail']['mailuser_id'],
                    $this->_data['mail']
                );

                unset($this->_data['mail']);
            }

            foreach ($this->_data as $name => $actions)
            {
                krsort($actions);

                foreach ($actions as $action => $filters)
                {
                    if (empty($filters))
                    {
                        continue;
                    }

                    foreach ($filters as $id => $params)
                    {
                        switch ($name)
                        {
                            case 'blacklist':
                            switch ($action)
                            {
                                case 'update':
                                    $this->_soap->mail_spamfilter_blacklist_update(
                                        $this->_soap_session,
                                        $this->_uid,
                                        $id,
                                        $params
                                    );
                                    break;
                                case 'delete':
                                    $this->_soap->mail_spamfilter_blacklist_delete(
                                        $this->_soap_session,
                                        $id
                                    );
                                    break;
                                case 'add':
                                    $this->_soap->mail_spamfilter_blacklist_add(
                                        $this->_soap_session,
                                        $this->_uid,
                                        $params
                                    );
                                    break;
                            }
                            break;
                            case 'whitelist':
                            switch ($action)
                            {
                                case 'update':
                                    $this->_soap->mail_spamfilter_whitelist_update(
                                        $this->_soap_session,
                                        $this->_uid,
                                        $id,
                                        $params
                                    );
                                    break;
                                case 'delete':
                                    $this->_soap->mail_spamfilter_whitelist_delete(
                                        $this->_soap_session,
                                        $id
                                    );
                                    break;
                                case 'add':
                                    $this->_soap->mail_spamfilter_whitelist_add(
                                        $this->_soap_session,
                                        $this->_uid,
                                        $params
                                    );
                                    break;
                            }
                            break;
                            case 'filter':
                            switch ($action)
                            {
                                case 'update':
                                    $this->_soap->mail_user_filter_update(
                                        $this->_soap_session,
                                        $this->_uid,
                                        $id,
                                        $params
                                    );
                                    break;
                                case 'delete':
                                    $this->_soap->mail_user_filter_delete(
                                        $this->_soap_session,
                                        $id
                                    );
                                    break;
                                case 'add':
                                    $this->_soap->mail_user_filter_add(
                                        $this->_soap_session,
                                        $this->_uid,
                                        $params
                                    );
                                    break;
                            }
                            break;
                        }
                    }
                }
            }
        }

        // Close the SOAP session.
        $this->_soap->logout($this->_soap_session);

        // Set success message
        $this->setMessage(_('The filters configuration have been successfully saved in the server.'), 'success');
    }

    /**
    * Checks basic config.
    *
    * @throws Ingo_Exception
    */
    protected function _checkConfig()
    {
        if (empty($this->_params['soap_uri']) || empty($this->_params['soap_user']))
        {
            throw new Ingo_Exception(_('The Ingo Ispconfig 3 script is not properly configured. Please edit your ingo/config/backends.local.php.'));
        }
    }

    /**
    * Set unobtrusive message
    * 
    * @param string $message Message string
    * @param string $type Message type.
    */
    public function setMessage($message, $type = 'message')
    {
        $message = trim($message);

        if (empty($message))
        {
            return;
        }

        switch ($type)
        {
            case 'error':
            case 'success':
                $this->_messages[$type][] = $message;
                break;
            default:
                $this->_messages['message'][] = $message;
                break;
        }
    }

    /**
    * Send stored messages.
    */
    public function sendMessages()
    {
        if (empty($this->_messages))
        {
            return;
        }

        global $notification;

        foreach ($this->_messages as $type => $messages)
        {
            foreach ($messages as $message)
            {
                $notification->push($message, 'horde.' . $type);
            }
        }
    }
}