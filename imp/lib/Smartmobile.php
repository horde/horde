<?php
/**
 * Base class for smartmobile view pages.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl21 GPL
 * @package  IMP
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
        $page_output->addScriptFile('indices.js');
        $page_output->addScriptFile('json2.js', 'horde');

        $page_output->addStylesheet(
            new Horde_Themes_Element('mime.css')
        );

        $notification->notify(array('listeners' => 'status'));
    }

    /**
     */
    public function render()
    {
        global $injector, $page_output;

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        echo $this->view->render('folders');
        echo $this->view->render('mailbox');
        echo $this->view->render('message');
        if (IMP::canCompose()) {
            echo $this->view->render('compose');
        }
        if ($imp_imap->access(IMP_Imap::ACCESS_SEARCH)) {
            echo $this->view->render('search');
        }
        echo $this->view->render('confirm');
        if ($imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
            echo $this->view->render('copymove');
        }

        /* jQuery Mobile plugins must be loaded AFTER the main mobile script
         * is loaded. */
        $page_output->addScriptFile('jquery.mobile/plugins/swipebutton.js', 'horde');
        if (IMP::canCompose()) {
            $page_output->addScriptFile('jquery.mobile/plugins/autocomplete.js', 'horde');
        }
    }

    /**
     */
    protected function _initPages()
    {
        global $conf, $injector, $registry;

        /* Initialize the IMP_Imap_Tree object. By default, only show INBOX,
         * special mailboxes, and polled mailboxes. */
        $imptree = $injector->getInstance('IMP_Imap_Tree');
        $imptree->setIteratorFilter(Imp_Imap_Tree::FLIST_POLLED);
        $this->view->tree = $imptree->createTree('smobile_folders', array(
            'poll_info' => true,
            'render_type' => 'IMP_Tree_Jquerymobile'
        ))->getTree(true);

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
        if ($this->view->allowFolders = $imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
            $this->view->options = IMP::flistSelect(array(
                'heading' => _("This message to"),
                'optgroup' => true,
                'inc_tasklists' => true,
                'inc_notepads' => true,
                'new_mbox' => true
            ));
        }

        $this->view->canSearch = $imp_imap->access(IMP_Imap::ACCESS_SEARCH);
        $this->view->canSpam = !empty($conf['spam']['reporting']);
        $this->view->canInnocent = !empty($conf['notspam']['reporting']);

        if ($this->view->canCompose = IMP::canCompose()) {
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

            try {
                $this->view->abook = Horde::url($registry->link('contacts/smartmobile_browse'));
            } catch (Horde_Exception $e) {}

            $this->view->composeCache = $injector->getInstance('IMP_Factory_Compose')->create()->getCacheId();
        }
    }

    /**
     * Add base javascript variables to the page.
     */
    protected function _addBaseVars()
    {
        global $conf, $injector, $page_output, $prefs;

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        $code = array(
            /* Variables. */
            'conf' => array_filter(array(
                'allow_folders' => $imp_imap->access(IMP_Imap::ACCESS_FOLDERS),
                'disable_compose' => !IMP::canCompose(),
                'flags' => array(
                    'deleted' => '\\deleted',
                    'draft' => '\\draft',
                    'seen' => '\\seen'
                ),
                'innocent_spammbox' => !empty($conf['notspam']['spamfolder']),
                'mailbox_return' => $prefs->getValue('mailbox_return'),
                'pop3' => intval($imp_imap->pop3),
                'qsearchid' => IMP_Mailbox::formTo(IMP_Search::MBOX_PREFIX . IMP_Search::DIMP_QUICKSEARCH),
                'spam_mbox' => IMP_Mailbox::formTo($prefs->getValue('spam_folder')),
                'spam_spammbox' => !empty($conf['spam']['spamfolder'])
            )),

            /* Gettext strings. */
            'text' => array(
                'confirm' => array(
                    'text' => array(
                        'delete' => _("Are you sure you want to delete this message?"),
                        'innocent' => _("Are you sure you wish to report this message as innocent?"),
                        'spam' => _("Are you sure you wish to report this message as spam?")
                    ),
                    'action' => array(
                        'delete' => _("Delete"),
                        'innocent' => _("Report as Innocent"),
                        'spam' => _("Report as Spam")
                    ),
                ),
                'copymove' => _("Copy/Move"),
                'nav' => _("%d - %d of %d Messages"),
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
