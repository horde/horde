<?php
/**
 * Special prefs handling for the 'flagmanagement' preference.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Prefs_Special_Flag implements Horde_Core_Prefs_Ui_Special
{
    /**
     */
    public function init(Horde_Core_Prefs_Ui $ui)
    {
        global $prefs;

        if ($prefs->isLocked('msgflags') && $prefs->isLocked('msgflags_user')) {
            $ui->nobuttons = true;
        }
    }

    /**
     */
    public function display(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $page_output, $prefs;

        if (!$ui->nobuttons) {
            $page_output->addScriptFile('colorpicker.js', 'horde');
            $page_output->addScriptFile('flagprefs.js');
        }

        $page_output->addInlineJsVars(array(
            'ImpFlagPrefs.new_prompt' => _("Please enter the label for the new flag:"),
            'ImpFlagPrefs.confirm_delete' => _("Are you sure you want to delete this flag?")
        ));

        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/prefs'
        ));
        $view->addHelper('FormTag');
        $view->addHelper('Tag');

        $view->locked = $prefs->isLocked('msgflags');
        $view->picker_img = Horde::img('colorpicker.png', _("Color Picker"));

        $out = array();
        $flaglist = $injector->getInstance('IMP_Flags')->getList();
        foreach ($flaglist as $val) {
            $hash = hash('sha1', $val->id);
            $bgid = 'bg_' . $hash;
            $color = $val->bgdefault ? '' : $val->bgcolor;
            $tmp = array();

            if ($val instanceof IMP_Flag_User) {
                $tmp['label'] = htmlspecialchars($val->label);
                $tmp['label_name'] = 'label_' . $hash;
                $tmp['user'] = true;
            } else {
                $tmp['icon'] = $val->span;
                $tmp['label'] = Horde::label($bgid, $val->label);
            }

            $tmp['color'] = $color;
            $tmp['colorid'] = $bgid;
            $tmp['colorstyle'] = 'color:' . $val->fgcolor . ';' .
                (strlen($color) ? ('background-color:' . $color . ';') : '');

            $out[] = $tmp;
        }
        $view->flags = $out;

        return $view->render('flags');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $notification;

        $imp_flags = $injector->getInstance('IMP_Flags');

        if ($ui->vars->flag_action == 'add') {
            $notification->push(sprintf(_("Added flag \"%s\"."), $ui->vars->flag_data), 'horde.success');
            $imp_flags->addFlag($ui->vars->flag_data);
            return;
        }

        // Don't set updated on these actions. User may want to do more
        // actions.
        $update = false;
        foreach ($imp_flags->getList() as $val) {
            $sha1 = hash('sha1', $val->id);

            switch ($ui->vars->flag_action) {
            case 'delete':
                if ($ui->vars->flag_data == ('bg_' . $sha1)) {
                    unset($imp_flags[$val->id]);
                    $notification->push(sprintf(_("Deleted flag \"%s\"."), $val->label), 'horde.success');
                }
                break;

            default:
                /* Change labels for user-defined flags. */
                if ($val instanceof IMP_Flag_User) {
                    $label = $ui->vars->get('label_' . $sha1);
                    if (strlen($label) && ($label != $val->label)) {
                        $imp_flags->updateFlag($val->id, 'label', $label);
                        $update = true;
                    }
                }

                /* Change background for all flags. */
                $bg = strtolower($ui->vars->get('bg_' . $sha1));
                if ($bg != $val->bgcolor) {
                    $imp_flags->updateFlag($val->id, 'bgcolor', $bg);
                    $update = true;
                }
                break;
            }
        }

        return $update;
    }

}
