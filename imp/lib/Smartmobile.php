<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Base class for smartmobile view pages.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Smartmobile
{
    /**
     * @var Horde_Variables
     */
    public $vars;

    /**
     * @var Horde_View
     */
    public $view;

    /**
     */
    public function __construct(Horde_Variables $vars)
    {
        global $notification, $page_output;

        $this->vars = $vars;

        $this->view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/smartmobile'
        ));
        $this->view->addHelper('Horde_Core_Smartmobile_View_Helper');
        $this->view->addHelper('Text');

        $this->_initPages();
        $this->_addBaseVars();

        $page_output->addScriptFile('smartmobile.js');
        $page_output->addScriptFile('json2.js', 'horde');

        $page_output->addStylesheet(
            new Horde_Themes_Element('mime.css')
        );

        // Load full jQuery Mobile source.
        //$page_output->debug = true;

        $notification->notify(array('listeners' => 'status'));
    }

    /**
     */
    public function render()
    {
        global $injector, $page_output;

        $imp_imap = $injector->getInstance('IMP_Imap');

        echo $this->view->render('folders');
        echo $this->view->render('mailbox');
        echo $this->view->render('message');
        if (IMP_Compose::canCompose()) {
            echo $this->view->render('compose');
        }
        if ($imp_imap->access(IMP_Imap::ACCESS_SEARCH)) {
            echo $this->view->render('search');
        }
        if ($imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
            echo $this->view->render('copymove');
        }

        /* jQuery Mobile plugins must be loaded AFTER the main mobile script
         * is loaded. */
        $page_output->addScriptFile('jquery.mobile/plugins/listviewtaphold.js');
        $page_output->addScriptFile('jquery.mobile/plugins/swipebutton.js', 'horde');
        if (IMP_Compose::canCompose()) {
            $page_output->addScriptFile('jquery.mobile/plugins/autocomplete.js', 'horde');
            $page_output->addScriptFile('jquery.mobile/plugins/textchange.js');
            if (IMP_Compose::canUploadAttachment()) {
                $page_output->addScriptFile('jquery.mobile/plugins/form.js', 'horde');
            }
        }
    }

    /**
     */
    protected function _initPages()
    {
        global $injector, $registry, $session;

        $imp_imap = $injector->getInstance('IMP_Imap');
        if ($this->view->allowFolders = $imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
            $this->view->options = IMP::flistSelect(array(
                'heading' => _("This message to"),
                'optgroup' => true,
                'inc_tasklists' => true,
                'inc_notepads' => true,
                'new_mbox' => true
            ));
        }

        $this->view->canInnocent = !empty($imp_imap->config->innocent_params);
        $this->view->canSearch = $imp_imap->access(IMP_Imap::ACCESS_SEARCH);
        $this->view->canSpam = !empty($imp_imap->config->spam_params);

        if ($this->view->canCompose = IMP_Compose::canCompose()) {
            /* Setting up identities. */
            $identity = $injector->getInstance('IMP_Identity');
            $this->view->identities = array();
            foreach ($identity->getSelectList() as $id => $from) {
                $this->view->identities[] = array(
                    'label' => $from,
                    'sel' => ($id == $identity->getDefault()),
                    'val' => $id
                );
            }

            $this->view->composeCache = $injector->getInstance('IMP_Factory_Compose')->create()->getCacheId();
            $this->view->user = $registry->getAuth();

            $this->view->draft =
                ($imp_imap->access(IMP_Imap::ACCESS_DRAFTS) &&
                 ($draft = IMP_Mailbox::getPref(IMP_Mailbox::MBOX_DRAFTS)) &&
                 !$draft->readonly);

            if (IMP_Compose::canUploadAttachment()) {
                $this->view->attach = true;
                $this->view->max_size = $session->get('imp', 'file_upload');
            }
        }
    }

    /**
     * Add base javascript variables to the page.
     */
    protected function _addBaseVars()
    {
        global $injector, $page_output, $prefs;

        $imp_imap = $injector->getInstance('IMP_Imap');

        $code = array(
            /* Variables. */
            'conf' => array_filter(array(
                'allow_folders' => $imp_imap->access(IMP_Imap::ACCESS_FOLDERS),
                'disable_compose' => !IMP_Compose::canCompose(),
                'flags' => array(
                    'deleted' => '\\deleted',
                    'draft' => '\\draft',
                    'seen' => '\\seen'
                ),
                'mailbox_return' => $prefs->getValue('mailbox_return'),
                'qsearchid' => IMP_Mailbox::formTo(IMP_Search::MBOX_PREFIX . IMP_Search::DIMP_QUICKSEARCH),
                'refresh_time' => intval($prefs->getValue('refresh_time'))
            )),

            /* Gettext strings. */
            'text' => array(
                'exitsearch' => _("Exit Search"),
                'folders' => _("Folders"),
                'message_0' => _("No messages"),
                'message_1' => _("1 message"),
                'message_2' => _("%d messages"),
                'more_msgs' => _("Load More Messages..."),
                'move_nombox' => _("Must enter a non-empty name for the new destination mailbox."),
                'new_message' => _("New Message"),
                'nofrom' => _("Invalid Address"),
                'nosubject' => _("The message does not have a Subject entered.") . "\n" . _("Send message without a Subject?"),
                'searchresults' => _("Search Results"),
                'subject' => _("Subject")
            )
        );

        $page_output->addInlineJsVars(array(
            'var IMP' => $code
        ), array('top' => true));
    }

}
