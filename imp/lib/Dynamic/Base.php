<?php
/**
 * Base class for dynamic view pages.
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
abstract class IMP_Dynamic_Base
{
    /**
     * Display the growler log?
     *
     * @var boolean
     */
    public $growlerLog = false;

    /**
     * @var array
     */
    public $js_conf = array();

    /**
     * @var array
     */
    public $js_context = array();

    /**
     * @var array
     */
    public $js_text = array();

    /**
     * @var string
     */
    public $title = '';

    /**
     * @var Horde_Variables
     */
    public $vars;

    /**
     * @var Horde_View
     */
    public $view;

    /**
     * @var array
     */
    protected $_pages = array(
        'header'
    );

    /**
     * @var boolean
     */
    public $topbar = false;

    /**
     */
    public function __construct(Horde_Variables $vars)
    {
        global $page_output;

        $this->vars = $vars;
        $this->view = $this->getEmptyView();

        $this->_addBaseVars();

        $page_output->addScriptFile('dimpcore.js');
        $page_output->addScriptFile('indices.js');
        $page_output->addScriptFile('contextsensitive.js', 'horde');
        $page_output->addScriptFile('imple.js', 'horde');

        $mimecss = new Horde_Themes_Element('mime.css');
        $page_output->addStylesheet($mimecss->fs, $mimecss->uri);

        $this->_init();

        $page_output->addInlineJsVars(array(
            'DimpCore.conf' => $this->js_conf,
            'DimpCore.context' => $this->js_context,
            'DimpCore.text' => $this->js_text
        ), array('top' => true));
    }

    /**
     */
    public function render()
    {
        foreach ($this->_pages as $val) {
            echo $this->view->render($val);
        }
    }

    /**
     * Return a View object.
     *
     * @return Horde_View  View object.
     */
    public function getEmptyView()
    {
        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/dynamic'
        ));
        $view->addHelper('Text');
        $view->addHelper('IMP_Dynamic_Helper_Base');

        return $view;
    }

    /**
     * Add base javascript variables to the page.
     */
    protected function _addBaseVars()
    {
        global $injector, $prefs;

        /* Variables used in core javascript files. */
        $this->js_conf = array_filter(array(
            // URL variables
            'URI_COMPOSE' => strval(IMP_Dynamic_Compose::url()->setRaw(true)),
            'URI_VIEW' => strval(Horde::url('view.php')),

            // Other variables
            'disable_compose' => !IMP::canCompose(),
            'pop3' => intval($injector->getInstance('IMP_Factory_Imap')->create()->pop3)
        ));

        /* Context menu definitions.
         * Keys:
         *   - Begin with '_mbox': A mailbox name container entry
         *   - Begin with '_sep': A separator
         *   - Begin with '_sub': All subitems wrapped in a DIV
         *   - Begin with a '*': No icon
         */
        $context = array(
            'ctx_contacts' => array(
                'new' => _("New Message"),
                'add' => _("Add to Address Book")
            ),
            'ctx_reply' => array(
                'reply' => _("To Sender"),
                'reply_all' => _("To All"),
                'reply_list' => _("To List")
            )
        );

        /* Forward context menu. */
        $context['ctx_forward'] = array(
            'attach' => _("As Attachment"),
            'body' => _("In Body Text"),
            'both' => _("Attachment and Body Text"),
            '_sep1' => null,
            'editasnew' => _("Edit as New"),
            '_sep2' => null,
            'redirect' => _("Redirect")
        );
        if ($prefs->isLocked('forward_default')) {
            unset(
                $context['ctx_forward']['attach'],
                $context['ctx_forward']['body'],
                $context['ctx_forward']['both'],
                $context['ctx_forward']['_sep1']
            );
        }

        $this->js_context = $context;

        /* Gettext strings used in core javascript files. */
        $this->js_text = array(
            'allparts_label' => _("Parts"),
            'loading' => _("Loading..."),
            'strip_warn' => _("Are you sure you wish to PERMANENTLY delete this attachment?"),
            'verify' => _("Verifying...")
        );
    }

    /**
     */
    abstract protected function _init();

    /**
     */
    abstract static public function url(array $opts = array());

}
