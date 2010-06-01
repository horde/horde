<?php
/**
 * @package Whups
 */
class AddCommentForm extends Horde_Form {

    function AddCommentForm(&$vars, $title = '')
    {
        global $conf;

        parent::Horde_Form($vars, $title);

        $this->addHidden('', 'id', 'int', true, true);

        if (!Horde_Auth::getAuth()) {
            $this->addVariable(_("Your Email Address"), 'user_email', 'email', true);
            if (!empty($conf['guests']['captcha'])) {
                $this->addVariable(_("Spam protection"), 'captcha', 'figlet', true, null, null, array(Whups::getCAPTCHA(!$this->isSubmitted()), $conf['guests']['figlet_font']));
            }
        }
        $this->addVariable(_("Comment"), 'newcomment', 'longtext', false);
        $this->addVariable(_("Attachment"), 'newattachment', 'file', false);
        $this->addVariable(_("Watch this ticket"), 'add_watch', 'boolean', false);

        /* Group restrictions. */
        if ($GLOBALS['registry']->isAdmin(array('permission' => 'whups:admin')) ||
            $GLOBALS['injector']->getInstance('Horde_Perms')->hasPermission('whups:hiddenComments', Horde_Auth::getAuth(), Horde_Perms::EDIT)) {
            $groups = Horde_Group::singleton();
            $mygroups = $groups->getGroupMemberships(Horde_Auth::getAuth());
            if ($mygroups) {
                foreach (array_keys($mygroups) as $gid) {
                    $grouplist[$gid] = $groups->getGroupName($gid, true);
                }
                asort($grouplist);
                $grouplist = array_merge(array(0 => _("This comment is visible to everyone")), $grouplist);
                $this->addVariable(_("Make this comment visible only to members of a group?"), 'group', 'enum', true, false, null, array($grouplist));
            }
        }
    }

    function validate(&$vars, $canAutoFill = false)
    {
        global $conf;

        if (!parent::validate($vars, $canAutoFill)) {
            if (!Horde_Auth::getAuth() && !empty($conf['guests']['captcha'])) {
                $vars->remove('captcha');
                $this->removeVariable($varname = 'captcha');
                $this->insertVariableBefore('newcomment', _("Spam protection"), 'captcha', 'figlet', true, null, null, array(Whups::getCAPTCHA(true), $conf['guests']['figlet_font']));
            }
            return false;
        }

        return true;
    }

}
