<?php
/**
 * The form to manage vacation notices.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Ingo
 */
class Ingo_Form_Vacation extends Ingo_Form_Base
{
    /**
     * The start date field.
     *
     * @var Horde_Form_Variable
     */
    protected $_start;

    /**
     * The end date field.
     *
     * @var Horde_Form_Variable
     */
    protected $_end;

    public function __construct($vars, $title = '', $name = null)
    {
        parent::__construct($vars, $title, $name);

        $this->setSection('basic', _("Basic Settings"));
        $this->_start = $this->addVariable(_("Start of vacation:"), 'start', 'monthdayyear', '');
        $this->_start->setHelp('vacation-period');
        $this->_end = $this->addVariable(_("End of vacation:"), 'end', 'monthdayyear', '');
        $v = $this->addVariable(_("Subject of vacation message:"), 'subject', 'text', false);
        $v->setHelp('vacation-subject');
        $v = $this->addVariable(_("Reason:"), 'reason', 'longtext', false, false, _("You can use placeholders like %NAME% in the vacation message. See the online help for details."), array(10, 40));
        $v->setHelp('vacation-reason');

        $this->setSection('advanced', _("Advanced Settings"));
        $v = $this->addVariable(_("My email addresses:"), 'addresses', 'longtext', true, false, null, array(5, 40));
        $v->setHelp('vacation-myemail');
        $v = $this->addVariable(_("Addresses to not send responses to:"), 'excludes', 'longtext', false, false, null, array(10, 40));
        $v->setHelp('vacation-noresponse');
        $v = $this->addVariable(_("Do not send responses to bulk or list messages?"), 'ignorelist', 'boolean', false);
        $v->setHelp('vacation-bulk');
        $v = $this->addVariable(_("Number of days between vacation replies:"), 'days', 'int', false);
        $v->setHelp('vacation-days');
        $this->setButtons(_("Save"));
    }

    /**
     * Additional validate of start and end date fields.
     */
    public function validate($vars = null, $canAutoFill = false)
    {
        $valid = true;
        if (!parent::validate($vars, $canAutoFill)) {
            $valid = false;
        }

        $this->_start->getInfo($vars, $start);
        $this->_end->getInfo($vars, $end);
        if ($start && $end && $end < $start) {
            $valid = false;
            $this->_errors['end'] = _("Vacation end date is prior to start.");
        }
        if ($end && $end < mktime(0, 0, 0)) {
            $valid = false;
            $this->_errors['end'] = _("Vacation end date is prior to today.");
        }

        return $valid;
    }
}
