<?php
/**
 * Base class for minimal view pages.
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
abstract class IMP_Minimal_Base
{
    /**
     * @var string
     */
    public $title;

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
        $this->vars = $vars;

        $this->view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/minimal'
        ));
        $this->view->addHelper('Text');

        $this->_init();
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
     * Output the menu.
     *
     * @param string $page  The current page ('compose', 'folders', 'mailbox',
     *                                        'message', 'search').
     * @param array $items  Additional menu items to add to the menu. First
     *                      element is label, second is URL to link to.
     *
     * @return string  The menu.
     */
    public function getMenu($page, $items = array())
    {
        if (!in_array($page, array('mailbox', 'message')) ||
            (IMP::mailbox() != 'INBOX')) {
            $items[] = array(_("Inbox"), IMP_Minimal_Mailbox::url(array('mailbox' => 'INBOX')));
        }

        if (!in_array($page, array('compose', 'search')) && IMP::canCompose()) {
            $items[] = array(_("New Message"), IMP_Minimal_Compose::url());
        }

        if (!in_array($page, array('folders', 'search')) &&
            $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->access(IMP_Imap::ACCESS_FOLDERS)) {
            $items[] = array(_("Folders"), IMP_Minimal_Folders::url());
        }

        $items[] = array(_("Log out"), $GLOBALS['registry']->getServiceLink('logout', 'imp'));

        $menu = new Horde_Menu();
        foreach ($menu->getSiteLinks() as $menuitem) {
            if ($menuitem != 'separator') {
                $items[] = array($menuitem['text'], $menuitem['url']);
            }
        }

        return $items;
    }

    /**
     */
    abstract protected function _init();

    /**
     */
    abstract static public function url(array $opts = array());

}
