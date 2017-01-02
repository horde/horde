<?php
/**
 * Copyright 2012-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2017 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Special prefs handling for the 'smimeprivatekey' preference.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2012-2017 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Prefs_Special_SmimePrivateKey implements Horde_Core_Prefs_Ui_Special
{
    /**
     */
    public function init(Horde_Core_Prefs_Ui $ui)
    {
    }

    /**
     */
    public function display(Horde_Core_Prefs_Ui $ui)
    {
        global $browser, $injector, $page_output, $prefs;

        $page_output->addScriptPackage('IMP_Script_Package_Imp');

        $p_css = new Horde_Themes_Element('prefs.css');
        $page_output->addStylesheet($p_css->fs, $p_css->uri);

        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/prefs'
        ));
        $view->addHelper('Horde_Core_View_Helper_Help');

        if (!Horde::isConnectionSecure()) {
            $view->notsecure = true;
            return $view->render('smimeprivatekey');
        }

        $smime_url = IMP_Basic_Smime::url();

        $view->has_key = $prefs->getValue('smime_public_key') &&
            $prefs->getValue('smime_private_key');
        $view->has_sign_key = $prefs->getValue('smime_public_sign_key') &&
            $prefs->getValue('smime_private_sign_key');

        if ($browser->allowFileUploads()) {
            $view->import = true;
            $page_output->addInlineScript(array(
                '$("import_smime_personal").observe("click", function(e) { ' . Horde::popupJs($smime_url, array('params' => array('actionID' => 'import_personal_certs', 'reload' => base64_encode($ui->selfUrl()->setRaw(true))), 'height' => 450, 'width' => 750, 'urlencode' => true)) . '; e.stop(); })'
            ), true);
        }
        if (!$view->has_key) {
            return $view->render('smimeprivatekey');
        }

        $smime = $injector->getInstance('IMP_Smime');
        foreach (array('' => false, '_sign' => true) as $suffix => $secondary) {
            if ($secondary && !$view->has_sign_key) {
                continue;
            }

            $cert = $smime->parseCert($smime->getPersonalPublicKey($secondary));
            if (!empty($cert['validity']['notafter'])) {
                $expired = new Horde_Date($cert['validity']['notafter']);
                if ($expired->before(time())) {
                    $view->{'expiredate' . $suffix} = $expired->strftime(
                        $prefs->getValue('date_format')
                    );
                    $view->{'expiretime' . $suffix} = $expired->strftime(
                        $prefs->getValue('time_format')
                    );
                }
            }

            $view->{'viewpublic' . $suffix} = $smime_url->copy()
                ->add('actionID', 'view_personal_public' . $suffix . '_key')
                ->link(array(
                    'title' => $secondary
                        ? _("View Secondary Personal Public Certificate")
                        : _("View Personal Public Certificate"),
                    'target' => 'view_key'
                ))
                . _("View") . '</a>';
            $view->{'infopublic' . $suffix} = $smime_url->copy()
                ->add('actionID', 'info_personal_public' . $suffix . '_key')
                ->link(array(
                    'title' => _("Information on Personal Public Certificate"),
                    'target' => 'info_key'
                ))
                . _("Details") . '</a>';

            if ($smime->getPassphrase($secondary)) {
                $view->{'passphrase' . $suffix} = $ui->selfUrl(array(
                    'special' => true,
                    'token' => true
                ))
                ->add('unset_smime' . $suffix . '_passphrase', 1)
                ->link(array(
                    'title' => _("Unload Passphrase")
                ))
                . _("Unload Passphrase") . '</a>';
            } else {
                $imple = $injector->getInstance('Horde_Core_Factory_Imple')
                    ->create(
                        'IMP_Ajax_Imple_PassphraseDialog',
                        array(
                            'params' => array(
                                'reload' => $ui->selfUrl()->setRaw(true),
                                'secondary' => intval($secondary)
                            ),
                            'type' => 'smimePersonal'
                        )
                    );
                $view->{'passphrase' . $suffix} = Horde::link(
                    '#',
                    _("Enter Passphrase"),
                    null,
                    null,
                    null,
                    null,
                    null,
                    array('id' => $imple->getDomId())
                ) . _("Enter Passphrase");
            }

            $view->{'viewprivate' . $suffix} = $smime_url->copy()
                ->add('actionID', 'view_personal_private' . $suffix . '_key')
                ->link(array(
                    'title' => _("View Secondary Personal Private Key"),
                    'target' => 'view_key'
                ))
                . _("View") . '</a>';
            $page_output->addInlineScript(array(
                '$("delete_smime_personal' . $suffix . '").observe("click", function(e) { if (!window.confirm(' . json_encode(_("Are you sure you want to delete your keypair? (This is NOT recommended!)")) . ')) { e.stop(); } })'
            ), true);
        }

        return $view->render('smimeprivatekey');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $notification;

        if (isset($ui->vars->delete_smime_personal) ||
            isset($ui->vars->delete_smime_personal_sign)) {
            $injector->getInstance('IMP_Smime')->deletePersonalKeys(
                $ui->vars->delete_smime_personal_sign
            );
            $notification->push(
                isset($ui->vars->delete_smime_personal_sign)
                    ? _("Secondary personal S/MIME keys deleted successfully.")
                    : _("Personal S/MIME keys deleted successfully."),
                'horde.success'
            );
        } elseif (isset($ui->vars->unset_smime_passphrase) ||
                  isset($ui->vars->unset_smime_sign_passphrase)) {
            $injector->getInstance('IMP_Smime')->unsetPassphrase(
                $ui->vars->unset_smime_sign_passphrase
            );
            $notification->push(
                _("S/MIME passphrase successfully unloaded."),
                'horde.success'
            );
        }

        return false;
    }

}
