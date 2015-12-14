<?php
/**
 * Ansel wrapper for the base AJAX framework handler.
 *
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
class Ansel_Ajax
{
    const VIEW_ME = 'me';
    const VIEW_GROUPS = 'groups';
    const VIEW_ALL    = 'all';

    /**
     */
    public function init()
    {
        global $page_output;

        $page_output->addScriptFile('redbox.js', 'horde');
        $page_output->addScriptFile('tooltips.js', 'horde');
        $page_output->addScriptFile('ansel.js');
        $page_output->addScriptFile('layout.js');
        $page_output->addScriptFile('imageview.js');
        $page_output->addScriptPackage('Horde_Core_Script_Package_Datejs');

        $page_output->addScriptFile('plupload/plupload.js', 'horde');
        $page_output->addScriptFile('plupload/plupload.flash.js', 'horde');
        $page_output->addScriptFile('plupload/plupload.silverlight.js', 'horde');
        $page_output->addScriptFile('plupload/plupload.html5.js', 'horde');
        $page_output->addScriptFile('plupload/plupload.browserplus.js', 'horde');
        $page_output->addScriptFile('plupload/uploader.js', 'horde');


        $page_output->addInlineJsVars(array(
            'var Ansel' => $this->_addBaseVars()
        ), array('top' => true));
        $page_output->header(array(
            'body_class' => 'horde-ajax',
            'growler_log' => true
        ));
    }

    /**
     * Add base javascript variables to the page.
     */
    protected function _addBaseVars()
    {
        global $conf, $injector, $prefs, $registry;

        $auth_name = $registry->getAuth();
        $identity = $injector->getInstance('Horde_Core_Factory_Identity')->create();

        $app_urls = $js_vars = array();
        if (isset($conf['menu']['apps']) &&
            is_array($conf['menu']['apps'])) {
            foreach ($conf['menu']['apps'] as $app) {
                $app_urls[$app] = strval(Horde::url($registry->getInitialPage($app), true));
            }
        }
        $style = Ansel::getStyleDefinition('ansel_dynamic');

        /* Variables used in core javascript files. */
        $js_vars['conf'] = array_filter(array(
            'images' => array(
                //'alarm'     => strval(Horde_Themes::img('alarm-fff.png')),
                'info' => strval(Horde_Themes::img('info_icon.png')),
                'edit' => strval(Horde_Themes::img('edit.png')),
                'download' => strval(Horde_Themes::img('download.png')),
                // @todo Better upload icon
                'upload' => strval(Horde_Themes::img('add.png'))
            ),
            'jsuri' =>  $registry->get('jsuri', 'horde'),
            'user' => $registry->convertUsername($auth_name, false),
            'name' => $identity->getName(),
            'email' => strval($identity->getDefaultFromAddress()),
            'prefs_url' => strval($registry->getServiceLink('prefs', 'ansel')->setRaw(true)),
            'app_urls' => $app_urls,
            'name' => $registry->get('name'),
            'login_view' => $prefs->getValue('defaultview'),
            'date_format' => Horde_Core_Script_Package_Datejs::translateFormat(
                Horde_Nls::getLangInfo(D_FMT)
            ),
            'time_format' => $prefs->getValue('twentyFour') ? 'HH:mm' : 'hh:mm tt',
            'perms' => array(
                'all' => Horde_Perms::ALL,
                'show' => Horde_Perms::SHOW,
                'read' => Horde_Perms::READ,
                'edit' => Horde_Perms::EDIT,
                'delete' => Horde_Perms::DELETE
            ),
            'style' => array(
                'gallery-width' => $style->width
            )
        ));

        /* Gettext strings. */
        $js_vars['text'] = array(
            'alerts' => _("Notifications"),
            'fix_form_values' => _("Please enter correct values in the form first."),
            'geocode_error' => _("Unable to locate requested address"),
            'hidelog' => _("Hide Notifications"),
            'more' => _("more..."),
            'no_gallery_title' => _("The gallery title must not be empty."),
            'prefs' => _("Preferences"),
            'searching' => sprintf(_("Images matching \"%s\""), '#{term}'),
            'images' => _("Images"),
            'subgalleries' => _("Sub-galleries"),
            'taken' => _("Taken"),
            'choose_gallery' => _("Choose gallery"),
            'galleries_by' => _("Galleries by"),
            'edit' => _("Edit"),
            'download' => _("Download"),
            'upload' => _("Upload"),
            'uploader' => array(
                'start' => _("Upload"),
                'add' => _("Add Images"),
                'header' => _("Images to upload"),
                'returnButton' => _("View Gallery"),
                'subheader' => _("Add files to the upload queue and click the start button."),
                'size' => _("File size error."),
                'type' => _("File type error.")
            ),
        );

        // Maps
        $js_vars['conf']['maps'] = $conf['maps'];

        return $js_vars;
    }

}
