<?php
/**
 * The Ingo_Script_Procmail_Recipe:: class represents a Procmail recipe.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Ben Chavet <ben@horde.org>
 * @package Ingo
 */
class Ingo_Script_Procmail_Recipe
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
    protected $_params = array(
        'date' => 'date',
        'echo' => 'echo',
        'ls'   => 'ls'
    );

    /**
     */
    protected $_valid = true;

    /**
     * Constructs a new procmail recipe.
     *
     * @param array $params        Array of parameters.
     *                               REQUIRED FIELDS:
     *                                'action'
     *                               OPTIONAL FIELDS:
     *                                'action-value' (only used if the
     *                                'action' requires it)
     * @param array $scriptparams  Array of parameters passed to
     *                             Ingo_Script_Procmail::.
     */
    public function __construct($params = array(), $scriptparams = array())
    {
        $this->_disable = !empty($params['disable']);
        $this->_params = array_merge($this->_params, $scriptparams);

        switch ($params['action']) {
        case Ingo_Storage::ACTION_KEEP:
            // Note: you may have to set the DEFAULT variable in your
            // backend configuration.
            if (isset($this->_params['delivery_agent']) && isset($this->_params['delivery_mailbox_prefix'])) {
                $this->_action[] = '| ' . $this->_params['delivery_agent'] . ' ' . $this->_params['delivery_mailbox_prefix'] . '$DEFAULT';
            } elseif (isset($this->_params['delivery_agent'])) {
                $this->_action[] = '| ' . $this->_params['delivery_agent'] . ' $DEFAULT';
            } else {
                $this->_action[] = '$DEFAULT';
            }
            break;

        case Ingo_Storage::ACTION_MOVE:
            if (isset($this->_params['delivery_agent']) && isset($this->_params['delivery_mailbox_prefix'])) {
                $this->_action[] = '| ' . $this->_params['delivery_agent'] . ' ' . $this->_params['delivery_mailbox_prefix'] . $this->procmailPath($params['action-value']);
            } elseif (isset($this->_params['delivery_agent'])) {
                $this->_action[] = '| ' . $this->_params['delivery_agent'] . ' ' . $this->procmailPath($params['action-value']);
            } else {
                $this->_action[] = $this->procmailPath($params['action-value']);
            }
            break;

        case Ingo_Storage::ACTION_DISCARD:
            $this->_action[] = '/dev/null';
            break;

        case Ingo_Storage::ACTION_REDIRECT:
            $this->_action[] = '! ' . $params['action-value'];
            break;

        case Ingo_Storage::ACTION_REDIRECTKEEP:
            $this->_action[] = '{';
            $this->_action[] = '  :0 c';
            $this->_action[] = '  ! ' . $params['action-value'];
            $this->_action[] = '';
            $this->_action[] = '  :0' . (isset($this->_params['delivery_agent']) ? ' w' : '');
            if (isset($this->_params['delivery_agent']) && isset($this->_params['delivery_mailbox_prefix'])) {
                $this->_action[] = '  | ' . $this->_params['delivery_agent'] . ' ' . $this->_params['delivery_mailbox_prefix'] . '$DEFAULT';
            } elseif (isset($this->_params['delivery_agent'])) {
                $this->_action[] = '  | ' . $this->_params['delivery_agent'] . ' $DEFAULT';
            } else {
                $this->_action[] = '  $DEFAULT';
            }
            $this->_action[] = '}';
            break;

        case Ingo_Storage::ACTION_REJECT:
            $this->_action[] = '{';
            $this->_action[] = '  EXITCODE=' . $params['action-value'];
            $this->_action[] = '  HOST="no.address.here"';
            $this->_action[] = '}';
            break;

        case Ingo_Storage::ACTION_VACATION:
            $days = $params['action-value']['days'];
            $timed = !empty($params['action-value']['start']) &&
                !empty($params['action-value']['end']);
            $this->_action[] = '{';
            foreach ($params['action-value']['addresses'] as $address) {
                if (!empty($address)) {
                    $this->_action[] = '  :0';
                    $this->_action[] = '  * ^TO_' . $address;
                    $this->_action[] = '  {';
                    $this->_action[] = '    FILEDATE=`test -f ${VACATION_DIR:-.}/\'.vacation.' . $address . '\' && '
                        . $this->_params['ls'] . ' -lcn --time-style=+%s ${VACATION_DIR:-.}/\'.vacation.' . $address . '\' | '
                        . 'awk \'{ print $6 + (' . $days * 86400 . ') }\'`';
                    $this->_action[] = '    DATE=`' . $this->_params['date'] . ' +%s`';
                    $this->_action[] = '    DUMMY=`test -f ${VACATION_DIR:-.}/\'.vacation.' . $address . '\' && '
                        . 'test $FILEDATE -le $DATE && '
                        . 'rm ${VACATION_DIR:-.}/\'.vacation.' . $address . '\'`';
                    if ($timed) {
                        $this->_action[] = '    START=' . $params['action-value']['start'];
                        $this->_action[] = '    END=' . $params['action-value']['end'];
                    }
                    $this->_action[] = '';
                    $this->_action[] = '    :0 h';
                    $this->_action[] = '    SUBJECT=| formail -xSubject:';
                    $this->_action[] = '';
                    $this->_action[] = '    :0 Whc: ${VACATION_DIR:-.}/vacation.lock';
                    if ($timed) {
                        $this->_action[] = '    * ? test $DATE -gt $START && test $END -gt $DATE';
                    }
		    $this->_action[] = '    {';
                    $this->_action[] = '      :0 Wh';
                    $this->_action[] = '      * ^TO_' . $address;
                    $this->_action[] = '      * !^X-Loop: ' . $address;
                    $this->_action[] = '      * !^X-Spam-Flag: YES';
                    if (count($params['action-value']['excludes']) > 0) {
                        foreach ($params['action-value']['excludes'] as $exclude) {
                            if (!empty($exclude)) {
                                $this->_action[] = '      * !^From.*' . $exclude;
                            }
                        }
                    }
                    if ($params['action-value']['ignorelist']) {
                        $this->_action[] = '      * !^FROM_DAEMON';
                    }
                    $this->_action[] = '      | formail -rD 8192 ${VACATION_DIR:-.}/.vacation.' . $address;
                    $this->_action[] = '      :0 eh';
                    $this->_action[] = '      | (formail -rI"Precedence: junk" \\';
                    $this->_action[] = '       -a"From: <' . $address . '>" \\';
                    $this->_action[] = '       -A"X-Loop: ' . $address . '" \\';
                    if (Horde_Mime::is8bit($params['action-value']['reason'])) {
                        $this->_action[] = '       -i"Subject: ' . Horde_Mime::encode($params['action-value']['subject'] . ' (Re: $SUBJECT)', $scriptparams['charset']) . '" \\';
                        $this->_action[] = '       -i"Content-Transfer-Encoding: quoted-printable" \\';
                        $this->_action[] = '       -i"Content-Type: text/plain; charset=' . $scriptparams['charset'] . '" ; \\';
                        $reason = Horde_Mime::quotedPrintableEncode($params['action-value']['reason'], "\n");
                    } else {
                        $this->_action[] = '       -i"Subject: ' . Horde_Mime::encode($params['action-value']['subject'] . ' (Re: $SUBJECT)', $scriptparams['charset']) . '" ; \\';
                        $reason = $params['action-value']['reason'];
                    }
                    $reason = addcslashes($reason, "\\\n\r\t\"`");
                    $this->_action[] = '       ' . $this->_params['echo'] . ' -e "' . $reason . '" \\';
                    $this->_action[] = '      ) | $SENDMAIL -f' . $address . ' -oi -t';
                    $this->_action[] = '    }';
                    $this->_action[] = '  }';
                }
            }
            $this->_action[] = '}';
            break;

        case Ingo_Storage::ACTION_FORWARD:
            /* Make sure that we prevent mail loops using 3 methods.
             *
             * First, we call sendmail -f to set the envelope sender to be the
             * same as the original sender, so bounces will go to the original
             * sender rather than to us.  This unfortunately triggers lots of
             * Authentication-Warning: messages in sendmail's logs.
             *
             * Second, add an X-Loop header, to handle the case where the
             * address we forward to forwards back to us.
             *
             * Third, don't forward mailer daemon messages (i.e., bounces).
             * Method 1 above should make this redundant, unless we're sending
             * mail from this account and have a bad forward-to account.
             *
             * Get the from address, saving a call to formail if possible.
             * The procmail code for doing this is borrowed from the
             * Procmail Library Project, http://pm-lib.sourceforge.net/.
             * The Ingo project has the permission to use Procmail Library code
             * under Apache licence v 1.x or any later version.
             * Permission obtained 2006-04-04 from Author Jari Aalto. */
            $this->_action[] = '{';
            $this->_action[] = '  :0 ';
            $this->_action[] = '  *$ ! ^From *\/[^  ]+';
            $this->_action[] = '  *$ ! ^Sender: *\/[^   ]+';
            $this->_action[] = '  *$ ! ^From: *\/[^     ]+';
            $this->_action[] = '  *$ ! ^Reply-to: *\/[^     ]+';
            $this->_action[] = '  {';
            $this->_action[] = '    OUTPUT = `formail -zxFrom:`';
            $this->_action[] = '  }';
            $this->_action[] = '  :0 E';
            $this->_action[] = '  {';
            $this->_action[] = '    OUTPUT = $MATCH';
            $this->_action[] = '  }';
            $this->_action[] = '';

            /* Forward to each address on our list. */
            foreach ($params['action-value'] as $address) {
                if (!empty($address)) {
                    $this->_action[] = '  :0 c';
                    $this->_action[] = '  * !^FROM_MAILER';
                    $this->_action[] = '  * !^X-Loop: to-' . $address;
                    $this->_action[] = '  | formail -A"X-Loop: to-' . $address . '" | $SENDMAIL -oi -f $OUTPUT ' . $address;
                }
            }

            /* In case of mail loop or bounce, store a copy locally.  Note
             * that if we forward to more than one address, only a mail loop
             * on the last address will cause a local copy to be saved.  TODO:
             * The next two lines are redundant (and create an extra copy of
             * the message) if "Keep a copy of messages in this account" is
             * checked. */
            $this->_action[] = '  :0 E' . (isset($this->_params['delivery_agent']) ? 'w' : '');
            if (isset($this->_params['delivery_agent'])) {
                $this->_action[] = isset($this->_params['delivery_mailbox_prefix']) ?
                    ' | ' . $this->_params['delivery_agent'] . ' ' . $this->_params['delivery_mailbox_prefix'] . '$DEFAULT' :
                    ' | ' . $this->_params['delivery_agent'] . ' $DEFAULT';
            } else {
                $this->_action[] = '  $DEFAULT';
            }
            $this->_action[] = '  :0 ';
            $this->_action[] = '  /dev/null';
            $this->_action[] = '}';
            break;

        default:
            $this->_valid = false;
            break;
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
     * @param array $condition  Array of parameters. Required keys are 'field'
     *                          and 'value'. 'case' is an optional key.
     */
    public function addCondition($condition = array())
    {
        $flag = !empty($condition['case']) ? 'D' : '';
        $match = isset($condition['match']) ? $condition['match'] : null;
        $string = '';
        $prefix = '';

        switch ($condition['field']) {
        case 'Destination':
            $string = '^TO_';
            break;

        case 'Body':
            $flag .= 'B';
            break;

        default:
            // convert 'field' to PCRE pattern matching
            if (!strpos($condition['field'], ',')) {
                $string = '^' . $condition['field'] . ':';
            } else {
                $string .= '^(' . str_replace(',', '|', $condition['field']) . '):';
            }
            $prefix = ' ';
        }

        $reverseCondition = false;
        switch ($match) {
        case 'regex':
            $string .= $prefix . $condition['value'];
            break;

        case 'address':
            $string .= '(.*\<)?' . preg_quote($condition['value']);
            break;

        case 'not begins with':
            $reverseCondition = true;
            // fall through
        case 'begins with':
            $string .= $prefix . preg_quote($condition['value']);
            break;

        case 'not ends with':
            $reverseCondition = true;
            // fall through
        case 'ends with':
            $string .= '.*' . preg_quote($condition['value']) . '$';
            break;

        case 'not contain':
            $reverseCondition = true;
            // fall through
        case 'contains':
        default:
            $string .= '.*' . preg_quote($condition['value']);
            break;
        }

        $this->_conditions[] = array('condition' => ($reverseCondition ? '* !' : '* ') . $string,
                                     'flags' => $flag);
    }

    /**
     * Generates procmail code to represent the recipe.
     *
     * @return string  Procmail code to represent the recipe.
     */
    public function generate()
    {
        $nest = 0;
        $prefix = '';
        $text = array();

        if (!$this->_valid) {
            return '';
        }

        // Set the global flags for the whole rule, each condition
        // will add its own (such as Body or Case Sensitive)
        $global = $this->_flags;
        if (isset($this->_conditions[0])) {
            $global .= $this->_conditions[0]['flags'];
        }
        $text[] = ':0 ' . $global . (isset($this->_params['delivery_agent']) ? 'w' : '');
        foreach ($this->_conditions as $condition) {
            if ($nest > 0) {
                $text[] = str_repeat('  ', $nest - 1) . '{';
                $text[] = str_repeat('  ', $nest) . ':0 ' . $condition['flags'];
                $text[] = str_repeat('  ', $nest) . $condition['condition'];
            } else {
                $text[] = $condition['condition'];
            }
            $nest++;
        }

        if (--$nest > 0) {
            $prefix = str_repeat('  ', $nest);
        }
        foreach ($this->_action as $val) {
            $text[] = $prefix . $val;
        }

        for ($i = $nest; $i > 0; $i--) {
            $text[] = str_repeat('  ', $i - 1) . '}';
        }

        if ($this->_disable) {
            $code = '';
            foreach ($text as $val) {
                $comment = new Ingo_Script_Procmail_Comment($val);
                $code .= $comment->generate() . "\n";
            }
            return $code . "\n";
        } else {
            return implode("\n", $text) . "\n";
        }
    }

    /**
     * Returns a procmail-ready mailbox path, converting IMAP folder
     * pathname conventions as necessary.
     *
     * @param string $folder  The IMAP folder name.
     *
     * @return string  The procmail mailbox path.
     */
    public function procmailPath($folder)
    {
        /* NOTE: '$DEFAULT' here is a literal, not a PHP variable. */
        if (isset($this->_params) &&
            ($this->_params['path_style'] == 'maildir')) {
            if (empty($folder) || ($folder == 'INBOX')) {
                return '$DEFAULT';
            }
            if (substr($folder, 0, 6) == 'INBOX.') {
                $folder = substr($folder, 6);
            }
            return '"$DEFAULT/.' . escapeshellcmd($folder) . '/"';
        } else {
            if (empty($folder) || ($folder == 'INBOX')) {
                return '$DEFAULT';
            }
            return str_replace(' ', '\ ', escapeshellcmd($folder));
        }
    }

}
