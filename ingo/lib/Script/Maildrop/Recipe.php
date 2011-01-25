<?php
/**
 * The Ingo_Script_Maildrop_Recipe:: class represents a maildrop recipe.
 *
 * Copyright 2005-2007 Matt Weyland <mathias@weyland.ch>
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Matt Weyland <mathias@weyland.ch>
 * @package Ingo
 */
class Ingo_Script_Maildrop_Recipe
{
    /**
     */
    protected $_action = array();

    /**
     */
    protected $_conditions = array();

    /**
     */
    protected $_disable = '';

    /**
     */
    protected $_flags = '';

    /**
     */
    protected $_params = array();

    /**
     */
    protected $_combine = '';

    /**
     */
    protected $_valid = true;

    /**
     */
    protected $_operators = array(
        'less than'                => '<',
        'less than or equal to'    => '<=',
        'equal'                    => '==',
        'not equal'                => '!=',
        'greater than'             => '>',
        'greater than or equal to' => '>=',
    );

    /**
     * Constructs a new maildrop recipe.
     *
     * @param array $params        Array of parameters.
     *                             REQUIRED FIELDS:
     *                             'action'
     *                             OPTIONAL FIELDS:
     *                             'action-value' (only used if the
     *                             'action' requires it)
     * @param array $scriptparams  Array of parameters passed to
     *                             Ingo_Script_Maildrop::.
     */
    public function __construct($params = array(), $scriptparams = array())
    {
        $this->_disable = !empty($params['disable']);
        $this->_params = $scriptparams;
        $this->_action[] = 'exception {';

        switch ($params['action']) {
        case Ingo_Storage::ACTION_KEEP:
            $this->_action[] = '   to "${DEFAULT}"';
            break;

        case Ingo_Storage::ACTION_MOVE:
            $this->_action[] = '   to ' . $this->maildropPath($params['action-value']);
            break;

        case Ingo_Storage::ACTION_DISCARD:
            $this->_action[] = '   exit';
            break;

        case Ingo_Storage::ACTION_REDIRECT:
            $this->_action[] = '   to "! ' . $params['action-value'] . '"';
            break;

        case Ingo_Storage::ACTION_REDIRECTKEEP:
            $this->_action[] = '   cc "! ' . $params['action-value'] . '"';
            $this->_action[] = '   to "${DEFAULT}"';
            break;

        case Ingo_Storage::ACTION_REJECT:
            $this->_action[] = '   EXITCODE=77'; # EX_NOPERM (permanent failure)
            $this->_action[] = '   echo "5.7.1 ' . $params['action-value'] . '"';
            $this->_action[] = '   exit';
            break;

        case Ingo_Storage::ACTION_VACATION:
            $from = '';
            foreach ($params['action-value']['addresses'] as $address) {
                $from = $address;
            }

            /**
             * @TODO
             *
             * Exclusion and listfilter
             */
            $exclude = '';
            foreach ($params['action-value']['excludes'] as $address) {
                $exclude .= $address . ' ';
            }

            $start = strftime($params['action-value']['start']);
            if ($start === false) {
                $start = 0;
            }
            $end = strftime($params['action-value']['end']);
            if ($end === false) {
                $end = 0;
            }
            $days = strftime($params['action-value']['days']);
            if ($days === false) {
                // Set to same value as $_days in ingo/lib/Storage.php
                $days = 7;
            }

            // Writing vacation.msg file
            $transport = Ingo::getTransport();
            $transport->_connect();
            $result = $transport->_vfs->writeData($transport->_params['vfs_path'], 'vacation.msg', $params['action-value']['reason'], true);

            // Rule : Do not send responses to bulk or list messages
            if ($params['action-value']['ignorelist'] == 1) {
                $params['combine'] = Ingo_Storage::COMBINE_ALL;
                $this->addCondition(array('match' => 'filter', 'field' => '', 'value' => '! /^Precedence: (bulk|list|junk)/'));
                $this->addCondition(array('match' => 'filter', 'field' => '', 'value' => '! /^Return-Path:.*<#@\[\]>/'));
                $this->addCondition(array('match' => 'filter', 'field' => '', 'value' => '! /^Return-Path:.*<>/'));
                $this->addCondition(array('match' => 'filter', 'field' => '', 'value' => '! /^From:.*MAILER-DAEMON/'));
                $this->addCondition(array('match' => 'filter', 'field' => '', 'value' => '! /^X-ClamAV-Notice-Flag: *YES/'));
                $this->addCondition(array('match' => 'filter', 'field' => '', 'value' => '! /^Content-Type:.*message\/delivery-status/'));
                $this->addCondition(array('match' => 'filter', 'field' => '', 'value' => '! /^Subject:.*Delivery Status Notification/'));
                $this->addCondition(array('match' => 'filter', 'field' => '', 'value' => '! /^Subject:.*Undelivered Mail Returned to Sender/'));
                $this->addCondition(array('match' => 'filter', 'field' => '', 'value' => '! /^Subject:.*Delivery failure/'));
                $this->addCondition(array('match' => 'filter', 'field' => '', 'value' => '! /^Subject:.*Message delay/'));
                $this->addCondition(array('match' => 'filter', 'field' => '', 'value' => '! /^Subject:.*Mail Delivery Subsystem/'));
                $this->addCondition(array('match' => 'filter', 'field' => '', 'value' => '! /^Subject:.*Mail System Error.*Returned Mail/'));
                $this->addCondition(array('match' => 'filter', 'field' => '', 'value' => '! /^X-Spam-Flag: YES/ '));
            } else {
                $this->addCondition(array('field' => 'From', 'value' => ''));
            }

            // Rule : Start/End of vacation
            if (($start != 0) && ($end !== 0)) {
                $this->_action[] = '  flock "vacationprocess.lock" {';
                $this->_action[] = '    current_time=time';
                $this->_action[] = '      if ( \ ';
                $this->_action[] = '        ($current_time >= ' . $start . ') && \ ';
                $this->_action[] = '        ($current_time <= ' . $end . ')) ';
                $this->_action[] = '      {';
            }
            $this->_action[] = "  cc \"| mailbot -D " . $params['action-value']['days'] . " -c '" . $scriptparams['charset'] . "' -t \$HOME/vacation.msg -d \$HOME/vacation -A 'From: $from' -s '" . Horde_Mime::encode($params['action-value']['subject'], $scriptparams['charset'])  . "' /usr/sbin/sendmail -t \"";
            if (($start != 0) && ($end !== 0)) {
                $this->_action[] = '      }';
                $this->_action[] = '  }';
            }

            break;

        case Ingo_Storage::ACTION_FORWARD:
        case Ingo_Script_Maildrop::MAILDROP_STORAGE_ACTION_STOREANDFORWARD:
            foreach ($params['action-value'] as $address) {
                if (!empty($address)) {
                    $this->_action[] = '  cc "! ' . $address . '"';
                }
            }

            /* The 'to' must be the last action, because maildrop
             * stops processing after it. */
            if ($params['action'] == Ingo_Script_Maildrop::MAILDROP_STORAGE_ACTION_STOREANDFORWARD) {
                $this->_action[] = ' to "${DEFAULT}"';
            } else {
                $this->_action[] = ' exit';
            }
            break;

        default:
            $this->_valid = false;
            break;
        }

        $this->_action[] = '}';

        if (isset($params['combine']) &&
            ($params['combine'] == Ingo_Storage::COMBINE_ALL)) {
            $this->_combine = '&& ';
        } else {
            $this->_combine = '|| ';
        }
    }

    /**
     * Adds a flag to the recipe.
     *
     * @param string $flag  String of flags to append to the current flags.
     */
    public function addFlag($flag)
    {
        $this->_flags .= $flag;
    }

    /**
     * Adds a condition to the recipe.
     *
     * @param optonal array $condition  Array of parameters. Required keys
     *                                  are 'field' and 'value'. 'case' is
     *                                  an optional keys.
     */
    public function addCondition($condition = array())
    {
        $flag = (!empty($condition['case'])) ? 'D' : '';
        if (empty($this->_conditions)) {
            $this->addFlag($flag);
        }

        $string = '';
        $extra = '';

        $match = (isset($condition['match'])) ? $condition['match'] : null;
        // negate tests starting with 'not ', except 'not equals', which simply uses the != operator
        if ($match != 'not equal' && substr($match, 0, 4) == 'not ') {
            $string .= '! ';
        }

        // convert 'field' to PCRE pattern matching
        if (strpos($condition['field'], ',') == false) {
            $string .= '/^' . $condition['field'] . ':\\s*';
        } else {
            $string .= '/^(' . str_replace(',', '|', $condition['field']) . '):\\s*';
        }

        switch ($match) {
        case 'not regex':
        case 'regex':
            $string .= $condition['value'] . '/:h';
            break;

        case 'filter':
            $string = $condition['value'];
            break;

        case 'exists':
        case 'not exist':
            // Just run a match for the header name
            $string .= '/:h';
            break;

        case 'less than or equal to':
        case 'less than':
        case 'equal':
        case 'not equal':
        case 'greater than or equal to':
        case 'greater than':
            $string .= '(\d+(\.\d+)?)/:h';
            $extra = ' && $MATCH1 ' . $this->_operators[$match] . ' ' . (int)$condition['value'];
            break;

        case 'begins with':
        case 'not begins with':
            $string .= preg_quote($condition['value'], '/') . '/:h';
            break;

        case 'ends with':
        case 'not ends with':
            $string .= '.*' . preg_quote($condition['value'], '/') . '$/:h';
            break;

        case 'is':
        case 'not is':
            $string .= preg_quote($condition['value'], '/') . '$/:h';
            break;

        case 'matches':
        case 'not matches':
            $string .= str_replace(array('\\*', '\\?'), array('.*', '.'), preg_quote($condition['value'], '/') . '$') . '/:h';
            break;

        case 'contains':
        case 'not contain':
        default:
            $string .= '.*' . preg_quote($condition['value'], '/') . '/:h';
            break;
        }

        $this->_conditions[] = array('condition' => $string, 'flags' => $flag, 'extra' => $extra);
    }

    /**
     * Generates maildrop code to represent the recipe.
     *
     * @return string  maildrop code to represent the recipe.
     */
    public function generate()
    {
        $text = array();

        if (!$this->_valid) {
            return '';
        }

        if (count($this->_conditions) > 0) {

            $text[] = "if( \\";

            $nest = false;
            foreach ($this->_conditions as $condition) {
                $cond = $nest ? $this->_combine : '   ';
                $text[] = $cond . $condition['condition'] . $condition['flags'] . $condition['extra'] . " \\";
                $nest = true;
            }

            $text[] = ')';
        }

        foreach ($this->_action as $val) {
            $text[] = $val;
        }

        if ($this->_disable) {
            $code = '';
            foreach ($text as $val) {
                $comment = new Ingo_Script_Maildrop_Comment($val);
                $code .= $comment->generate() . "\n";
            }
            return $code . "\n";
        } else {
            return implode("\n", $text) . "\n";
        }
    }

    /**
     * Returns a maildrop-ready mailbox path, converting IMAP folder pathname
     * conventions as necessary.
     *
     * @param string $folder  The IMAP folder name.
     *
     * @return string  The maildrop mailbox path.
     */
    public function maildropPath($folder)
    {
        /* NOTE: '$DEFAULT' here is a literal, not a PHP variable. */
        if (isset($this->_params) &&
            ($this->_params['path_style'] == 'maildir')) {
            if (empty($folder) || ($folder == 'INBOX')) {
                return '"${DEFAULT}"';
            }
            if ($this->_params['strip_inbox'] &&
                substr($folder, 0, 6) == 'INBOX.') {
                $folder = substr($folder, 6);
            }
            return '"${DEFAULT}/.' . $folder . '/"';
        } else {
            if (empty($folder) || ($folder == 'INBOX')) {
                return '${DEFAULT}';
            }
            return str_replace(' ', '\ ', $folder);
        }
    }

}
