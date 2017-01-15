<?php
/**
 * Copyright 2016-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */
class Whups_Form_Ticket_DeleteMultiple extends Horde_Form
{
    /**
     * To-be-deleted tickets.
     *
     * @var array
     */
    protected $_tickets = array();

    /**
     * Constructor.
     */
    public function __construct($vars, $title = '')
    {
        global $whups_driver;

        parent::__construct($vars, $title);
        $this->addHidden('', 'tickets', 'text', true, true);
        $this->addHidden('', 'url', 'text', true, true);

        $tickets = array();
        foreach ((array)$vars->get('ticket') as $id) {
            $ticket = $whups_driver->getTicketDetails($id, false);
            if (Whups::hasPermission($ticket['queue'], 'queue', Horde_Perms::DELETE)) {
                $this->_tickets[] = (int)$id;
                $this->addVariable(
                    _("Ticket") . ' ' . $id,
                    'summary' . $id,
                    'text',
                    false,
                    true
                )
                    ->setDefault($ticket['summary']);
            }
        }

        $this->addVariable('', 'warn', 'html', false)
            ->setDefault(
                '<span class="horde-form-error">'
                . _("Really delete these tickets? They will NOT be archived, and will be gone forever.")
                . '</span>'
            );

        $this->setButtons(array(
            array('class' => 'horde-delete', 'value' => _("Delete")),
            array('class' => 'horde-cancel', 'value' => _("Cancel")),
        ));
    }

    /**
     * Returns the tickets to be deleted.
     */
    public function getTickets()
    {
        return $this->_tickets;
    }
}