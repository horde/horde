<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * The form to manage vacation notices.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
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

    public function __construct($vars, $title = '', $name = null, $features = null)
    {
        parent::__construct($vars, $title, $name, $features);

        $this->setSection('basic', _("Basic Settings"));

        if ($this->hasFeature('period')) {
            $this->_start = $this->addVariable(_("Start of vacation:"), 'start', 'monthdayyear', '');
            $this->_start->setHelp('vacation-period');
            $this->_end = $this->addVariable(_("End of vacation:"), 'end', 'monthdayyear', '');
        }
        if ($this->hasFeature('subject')) {
            $v = $this->addVariable(_("Subject of vacation message:"), 'subject', 'text', true);
            $v->setHelp('vacation-subject');
        }
        if ($this->hasFeature('reason')) {
            $v = $this->addVariable(_("Reason:"), 'reason', 'longtext', true, false, _("You can use placeholders like %NAME% in the vacation message. See the online help for details."), array(10, 40));
            $v->setHelp('vacation-reason');
        }

        if ($this->hasFeature('addresses') ||
            $this->hasFeature('excludes') ||
            $this->hasFeature('ignorelist') ||
            $this->hasFeature('days')) {
            $this->setSection('advanced', _("Advanced Settings"));
            if ($this->hasFeature('addresses')) {
                $v = $this->addVariable(_("My email addresses:"), 'addresses', 'longtext', true, false, null, array(5, 40));
                $v->setHelp('vacation-myemail');
            }
            if ($this->hasFeature('excludes')) {
                $v = $this->addVariable(_("Addresses to not send responses to:"), 'excludes', 'longtext', false, false, null, array(10, 40));
                $v->setHelp('vacation-noresponse');
            }
            if ($this->hasFeature('ignorelist')) {
                $v = $this->addVariable(_("Do not send responses to bulk or list messages?"), 'ignorelist', 'boolean', false);
                $v->setHelp('vacation-bulk');
            }
            if ($this->hasFeature('days')) {
                $v = $this->addVariable(_("Number of days between vacation replies:"), 'days', 'int', false);
                $v->setHelp('vacation-days');
            }
            $this->setButtons(_("Save"));
        }
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

        if ($this->hasFeature('period')) {
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
        }

        return $valid;
    }

}
