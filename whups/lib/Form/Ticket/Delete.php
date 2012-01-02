<?php
/**
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */
class Whups_Form_Ticket_Delete extends Horde_Form
{
    protected $_queue;

    public function __construct(&$vars, $title = '')
    {
        parent::__construct($vars, $title);

        $info = $GLOBALS['whups_driver']->getTicketDetails($vars->get('id'));
        $this->_queue = $info['queue'];
        $this->addHidden('', 'id', 'int', true, true);
        $summary = &$this->addVariable(_("Summary"), 'summary', 'text', false,
                                       true);
        $summary->setDefault($info['summary']);
        $yesno = array(0 => _("No"), 1 => _("Yes"));
        $this->addVariable(_("Really delete this ticket? It will NOT be archived, and will be gone forever."), 'yesno', 'enum', true, false, null, array($yesno));
    }

    public function validate(&$vars)
    {
        if (!Whups::hasPermission($this->_queue, 'queue', Horde_Perms::DELETE)) {
            $this->setError('yesno', _("Permission Denied."));
        }

        return parent::validate($vars);
    }

}