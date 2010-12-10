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
class Vilma_Form_EditUser extends Horde_Form
{
    public function __construct($vars)
    {
        $type = $vars->get('type');
        $editing = $vars->get('mode') == 'edit';
        switch ($type) {
        case 'group':
            $title = sprintf(_("Edit Group for \"%s\""), $vars->get('domain'));
            $name = _("Group Name");
            break;
        case 'forward':
            $title = sprintf(_("Edit Forward for \"%s\""), $vars->get('domain'));
            $name = _("Forward Name");
            break;
        default:
            $title = sprintf(_("Edit User for \"%s\""), $vars->get('domain'));
            $name = _("User Name");
            break;
        }
        if (!$editing) {
            $title = sprintf(_("New User @%s"), $vars->get('domain'));
        }
        parent::Horde_Form($vars, $title);

        /* Set up the form. */
        $this->setButtons(true, true);
        $this->addHidden('', 'address', 'text', false);
        $this->addHidden('', 'mode', 'text', false);
        $this->addHidden('', 'domain', 'text', false);
        $this->addHidden('', 'id', 'text', false);
        if ($editing) {
            $this->addHidden('', 'user_name', 'text', false);
        }
        $this->addVariable($name, 'user_name', 'text', true, $editing, _("Name must begin with an alphanumeric character, must contain only alphanumeric and '._-' characters, and must end with an alphanumeric character."), array('~^[a-zA-Z0-9]{1,1}[a-zA-Z0-9._-]*[a-zA-Z0-9]$~'));
        if ($editing) {
            $this->addVariable(_("Password"), 'password', 'passwordconfirm', false, false, _("Only enter a password if you wish to change this user's password"));
        } else {
            $this->addVariable(_("Password"), 'password', 'passwordconfirm', true);
        }
        $this->addVariable(_("Full Name"), 'user_full_name', 'text', true);
        $attrs = $GLOBALS['vilma']->driver->getUserFormAttributes();
        foreach ($attrs as $attr) {
            $v = $this->addVariable($attr['label'], $attr['name'],
                                    $attr['type'], $attr['required'],
                                    $attr['readonly'], $attr['description'],
                                    $attr['params']);
            if (isset($attr['default'])) {
                $v->setDefault($attr['default']);
            }
        }
    }
}
