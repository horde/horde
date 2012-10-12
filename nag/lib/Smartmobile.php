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
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */
class Nag_Smartmobile
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
            'templatePath' => NAG_TEMPLATES . '/smartmobile'
        ));
        $this->view->addHelper('Horde_Core_Smartmobile_View_Helper');
        $this->view->addHelper('Text');

        $this->_initPages();
        $this->_addBaseVars();

        $datejs = str_replace('_', '-', $GLOBALS['language']) . '.js';
        if (!file_exists($GLOBALS['registry']->get('jsfs', 'horde') . '/date/' . $datejs)) {
            $datejs = 'en-US.js';
        }
        $page_output->addScriptFile('date/' . $datejs, 'horde');
        $page_output->addScriptFile('date/date.js', 'horde');
        $page_output->addScriptFile('horde-jquery.js', 'horde');

        $page_output->addScriptFile('smartmobile.js');

        $notification->notify(array('listeners' => 'status'));
    }

    /**
     */
    public function render()
    {
        echo $this->view->render('main');
        echo $this->view->render('taskform');
        echo $this->view->render('lists');
    }

    /**
     */
    protected function _initPages()
    {
    }

    /**
     * Add base javascript variables to the page.
     */
    protected function _addBaseVars()
    {
        global $page_output, $prefs;

        // Nag::VIEW_* constant
        switch ($prefs->getValue('show_completed')) {
        case Nag::VIEW_INCOMPLETE:
            $show_completed = 'incomplete';
            break;

        case Nag::VIEW_ALL:
            $show_completed = 'all';
            break;

        case Nag::VIEW_COMPLETE:
            $show_completed = 'complete';
            break;

        case Nag::VIEW_FUTURE:
            $show_completed = 'future';
            break;

        case Nag::VIEW_FUTURE_INCOMPLETE:
            $show_completed = 'future-incomplete';
            break;
        }

        // Tasklists. Needed in case we deep link to an existing list.
        $lists = Nag::listTasklists();
        $tasklists = array();
        foreach ($lists as $name => $list) {
            $task = new Nag_Tasklist($list);
            $tasklists[$name] = $task->toHash();
        }

        $code = array(
            'conf' => array(
                'showCompleted' => $show_completed,
                'icons' => array(
                    'completed' => strval(Horde_Themes::img('checked.png')),
                    'uncompleted' => strval(Horde_Themes::img('unchecked.png')),
                    'smartlist' => strval(Horde_Themes::img('smart.png')),
                    'tasklist' => strval(Horde_Themes::img('tasklists.png'))
                )
            ),
            'strings' => array(
                'all' => _("All Tasks"),
                'newTask' => _("New Task")
            ),
            'tasklists' => $tasklists
        );

        $page_output->addInlineJsVars(array(
            'var Nag' => $code
        ), array('top' => true));
    }

}
