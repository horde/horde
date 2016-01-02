<?php
/**
 * Copyright 2015-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2015-2016 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * The vacation rule.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015-2016 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 *
 * @property integer $days  Number of vacation days.
 * @property integer $end  End date.
 * @property-read integer $end_day  End date (day).
 * @property-read integer $end_month  End date (month).
 * @property-read integer $end_year  End date (year).
 * @property-read array $exclude  Vacation address exclusions.
 * @property-write mixed $exclude  Vacation address exclusions (array or
 *                                 string).
 * @property boolean $ignore_list  Ignore list messages?
 * @property string $reason  Vacation reason.
 * @property integer $start  Start date.
 * @property-read integer $start_day  Start date (day).
 * @property-read integer $start_month  Start date (month).
 * @property-read integer $start_year  Start date (year).
 * @property string $subject  Outgoing message subject line.
 */
class Ingo_Rule_System_Vacation
extends Ingo_Rule_Addresses
implements Ingo_Rule_System
{
    /**
     * Number of vacation days.
     *
     * @var integer
     */
    protected $_days = 7;

    /**
     * End date.
     *
     * @var integer
     */
    protected $_end = 0;

    /**
     * Vacation address exclusions.
     *
     * @var array
     */
    protected $_exclude = array();

    /**
     * Ignore list messages?
     *
     * @var boolean
     */
    protected $_ignoreList = true;

    /**
     * Reason.
     *
     * @var string
     */
    protected $_reason = '';

    /**
     * Start date.
     *
     * @var integer
     */
    protected $_start = 0;

    /**
     * Subject of outgoing message.
     *
     * @var string
     */
    protected $_subject = '';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->name = _("Vacation");
    }

    /**
     */
    public function __get($name)
    {
        global $injector;

        switch ($name) {
        case 'addresses':
            $addr = parent::__get($name);

            try {
                $addr = $injector->getInstance('Horde_Core_Hooks')->callHook(
                    'vacation_addresses',
                    'ingo',
                    array(Ingo::getUser(), $addr)
                );
            } catch (Horde_Exception_HookNotSet $e) {}

            return $addr;

        case 'days':
            return $this->_days;

        case 'end':
            return $this->_end;

        case 'end_day':
            return date('j', $this->end);

        case 'end_month':
            return date('n', $this->end);

        case 'end_year':
            return date('Y', $this->end);

        case 'exclude':
            return $this->_exclude;

        case 'ignore_list':
            return $this->_ignoreList;

        case 'reason':
            return $this->_reason;

        case 'start':
            return $this->_start;

        case 'start_day':
            return date('j', $this->start);

        case 'start_month':
            return date('n', $this->start);

        case 'start_year':
            return date('Y', $this->start);

        case 'subject':
            return $this->_subject;

        default:
            return parent::__get($name);
        }
    }

    /**
     */
    public function __set($name, $data)
    {
        switch ($name) {
        case 'days':
            $this->_days = intval($data);
            break;

        case 'end':
            $this->_end = intval($data);
            break;

        case 'exclude':
            $exclude = new Horde_Mail_Rfc822_List(
                is_array($data) ? $data : preg_split("/\s+/", $data)
            );
            $exclude->unique();
            $this->_exclude = $exclude->bare_addresses;
            break;

        case 'ignore_list':
            $this->_ignoreList = (bool)$data;
            break;

        case 'reason':
            $this->_reason = strval($data);
            break;

        case 'start':
            $this->_start = intval($data);
            break;

        case 'subject':
            $this->_subject = strval($data);
            break;

        default:
            parent::__set($name, $data);
            break;
        }
    }

    /**
     * Returns the vacation reason with all placeholder replaced.
     *
     * @param string $reason  The vacation reason including placeholders.
     * @param integer $start  The vacation start timestamp.
     * @param integer $end    The vacation end timestamp.
     *
     * @return string  The vacation reason suitable for usage in the filter
     *                 scripts.
     */
    public static function vacationReason($reason, $start, $end)
    {
        global $injector, $prefs;

        $format = $prefs->getValue('date_format');
        $identity = $injector->getInstance('Horde_Core_Factory_Identity')
            ->create(Ingo::getUser());

        $replace = array(
            '%NAME%' => $identity->getName(),
            '%EMAIL%' => $identity->getDefaultFromAddress(),
            '%SIGNATURE%' => $identity->getValue('signature'),
            '%STARTDATE%' => $start ? strftime($format, $start) : '',
            '%ENDDATE%' => $end ? strftime($format, $end) : ''
        );

        return str_replace(
            array_keys($replace),
            array_values($replace),
            $reason
        );
    }

}
