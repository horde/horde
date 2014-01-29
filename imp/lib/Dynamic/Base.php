<?php
/**
 * Copyright 2012-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Base class for dynamic view pages.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
abstract class IMP_Dynamic_Base
{
    /**
     * @var IMP_Indices_Mailbox
     */
    public $indices;

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
     */
    public function __construct(Horde_Variables $vars)
    {
        global $page_output;

        $this->vars = $vars;
        $this->view = $this->getEmptyView();

        $this->indices = new IMP_Indices_Mailbox($vars);

        $this->_addBaseVars();

        $page_output->addScriptFile('dimpcore.js');
        $page_output->addScriptFile('viewport_utils.js');
        $page_output->addScriptFile('contextsensitive.js', 'horde');
        $page_output->addScriptFile('imple.js', 'horde');

        $mimecss = new Horde_Themes_Element('mime.css');
        $page_output->addStylesheet($mimecss->fs, $mimecss->uri);

        $page_output->sidebar = $page_output->topbar = false;

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
        global $prefs, $registry, $session;

        /* Variables used in core javascript files. */
        $this->js_conf = array_filter(array(
            // URL variables
            'MAX_FILE_SIZE' => intval($session->get('imp', 'file_upload')),
            'URI_COMPOSE' => strval(IMP_Dynamic_Compose::url()->setRaw(true)),
            'URI_VIEW' => strval(Horde::url('view.php')->add(IMP_Contents_View::addToken())),

            // Other variables
            'disable_compose' => !IMP_Compose::canCompose(),
            'pref_prefix' => base64_encode(pack('H*', hash('sha1', $registry->getAuth() . '|' . $_SERVER['SERVER_NAME'])))
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
                // Empty sub item needs to be a javascript object
                '_sub1' => new stdClass,
                'new' => _("New Message"),
                'add' => _("Add to Address Book"),
                'copy' => _("Copy to Clipboard")
            ),
            'ctx_reply' => array(
                'reply' => _("To Sender"),
                'reply_all' => _("To All"),
                'reply_list' => _("To List")
            )
        );

        if ($registry->hasInterface('mail/newEmailFilter')) {
            $context['ctx_contacts']['addfilter'] = _("Create Filter");
        }

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
            'emailcopy' => _("Your browser security settings don't permit the editor to copy to the clipboard.") . "\n" . _("You need to manually use the keyboard instead (Crtl/Cmd + C)."),
            'loading' => _("Loading..."),
            'strip_warn' => _("Are you sure you wish to PERMANENTLY delete this attachment?"),
            'verify' => _("Verifying...")
        );
    }

    static public function url(array $opts = array())
    {
        throw new Exception('Missing implementation for url method.');
    }

    /**
     */
    abstract protected function _init();

}
