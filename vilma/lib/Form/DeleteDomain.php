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
class Vilma_Form_DeleteDomain extends Horde_Form
{
    public function __construct($vars)
    {
        parent::Horde_Form($vars, _("Delete Domain"));

        $domain_record = $GLOBALS['vilma']->driver->getDomain($vars->get('domain_id'));
        $domain = $domain_record['domain_name'];

        /* Set up the form. */
        $this->setButtons(array(_("Delete"), _("Do not delete")));
        $this->addHidden('', 'domain_id', 'text', false);
        $this->addVariable(sprintf(_("Delete domain \"%s\" and all associated email addresses?"), $domain), 'description', 'description', false);
    }
}
