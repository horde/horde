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

        $page_output->addScriptFile('smartmobile.js');

        $notification->notify(array('listeners' => 'status'));
    }

    /**
     */
    public function render()
    {
        echo $this->view->render('main');
        // echo $this->view->render('create');
    }

    /**
     */
    protected function _initPages()
    {
        // @TODO: Still need to refactor this to a dedicated smartmobile form.
        // $max_tasks = $GLOBALS['injector']
        //     ->getInstance('Horde_Core_Perms')
        //     ->hasAppPermission('max_tasks');
        // if (($max_tasks === true) || ($max_tasks > Nag::countTasks())) {
        //     $vars = clone $this->vars
        //     if (!$vars->exists('tasklist_id')) {
        //         $vars->set('tasklist_id', Nag::getDefaultTasklist(Horde_Perms::EDIT));
        //     }
        //     $vars->mobile = true;
        //     $vars->url = Horde::url('smartmobile.php');
        //     $view->create_form = new Nag_Form_Task($vars, _("New Task"));
        //     $view->create_title = $view->create_form->getTitle();
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

        $code = array(
            'conf' => array(
                'showCompleted' => $show_completed,
                'icons' => array(
                    'completed' => strval(Horde_Themes::img('checked.png')),
                    'uncompleted' => strval(Horde_Themes::img('unchecked.png'))
                )
            )
        );

        $page_output->addInlineJsVars(array(
            'var Nag' => $code
        ), array('top' => true));
    }

}
