<?php
/**
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */
class Whups_Form_Ticket_Delete extends Horde_Form
{
    protected $_queue;
    protected $_warn;

    public function __construct(&$vars, $title = '')
    {
        parent::__construct($vars, $title);

        $info = $GLOBALS['whups_driver']->getTicketDetails($vars->get('id'));
        $this->_queue = $info['queue'];
        $this->addHidden('', 'id', 'int', true, true);
        $this->_warn = $this->addVariable('', 'warn', 'html', false);
        $this->_warn->setDefault(
            '<span class="horde-form-error">'
            . _("Really delete this ticket? It will NOT be archived, and will be gone forever.")
            . '</span>'
        );

        $this->setButtons(array(
            array('class' => 'horde-delete', 'value' => _("Delete")),
            array('class' => 'horde-cancel', 'value' => _("Cancel")),
        ));
    }

    public function validate(&$vars)
    {
        if (Whups::hasPermission($this->_queue, 'queue', Horde_Perms::DELETE)) {
            $this->_warn->setDefault(
                '<span class="horde-form-error">'
                . _("Permission Denied.")
                . '</span>'
            );
        }

        return parent::validate($vars);
    }

}