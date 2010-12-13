<?php
/**
 * Copyright 2006-2007 Alkaloid Networks <http://www.alkaloid.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you did
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Vilma
 */
class Vilma_Form_EditDomain extends Horde_Form
{
    public function __construct($vars)
    {
        /* Check if a form is being edited. */
        $editing = $vars->exists('domain_id');
        $domain = $GLOBALS['session']->get('vilma', 'domain');
        parent::Horde_Form($vars, $editing ? _("Edit Domain") : _("New Domain"));
        if ($editing && !$this->isSubmitted()) {
            $domain = $GLOBALS['vilma']->driver->getDomain($vars->get('domain_id'));
        }
        $vars->add('name', $domain['domain_name']);
        $vars->add('transport', $domain['domain_transport']);
        $vars->add('max_users', $domain['domain_max_users']);
        $vars->add('quota', $domain['domain_quota']);

        /* Set up the form. */
        $this->setButtons(true, true);
        $this->addHidden('', 'domain_id', 'text', false);
        $this->addVariable(_("Domain"), 'name', 'text', true);
        $this->addVariable(_("Transport"), 'transport', 'enum', false, false, null, array(Horde_Array::valuesToKeys($GLOBALS['conf']['mta']['transports'])));
        $this->addVariable(_("Max users"), 'max_users', 'int', false);
        $this->addVariable(_("Quota"), 'quota', 'int', false, false, _("Value in MB"));
    }
}
