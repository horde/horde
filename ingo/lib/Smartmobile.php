<?php
/**
 * Base class for smartmobile view pages.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Smartmobile
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
            'templatePath' => INGO_TEMPLATES . '/smartmobile'
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
        echo $this->view->render('rules');
        echo $this->view->render('rule');
    }

    /**
     */
    protected function _initPages()
    {
        global $injector, $session;

        $this->view->list = array();

        $filters = $injector->getInstance('Ingo_Factory_Storage')->create()->retrieve(Ingo_Storage::ACTION_FILTERS)->getFilterList();
        $s_categories = $session->get('ingo', 'script_categories');

        foreach ($filters as $key => $val) {
            // For now, skip non-display categories and disabled rules.
            if (!empty($val['disable']) ||
                !in_array($val['action'], $s_categories)) {
                continue;
            }

            switch ($val['action']) {
            case Ingo_Storage::ACTION_BLACKLIST:
                $img = 'blacklist.png';
                $name = _("Blacklist");
                break;

            case Ingo_Storage::ACTION_WHITELIST:
                $img = 'whitelist.png';
                $name = _("Whitelist");
                break;

            case Ingo_Storage::ACTION_VACATION:
                $img = 'vacation.png';
                $name = _("Vacation");
                break;

            case Ingo_Storage::ACTION_FORWARD:
                $img = 'forward.png';
                $name = _("Forward");
                break;

            case Ingo_Storage::ACTION_SPAM:
                $img = 'spam.png';
                $name = _("Spam Filter");
                break;

            default:
                $img = null;
                $name = $val['name'];
                break;
            }

            $url = new Horde_Core_Smartmobile_Url();
            $url->add('rulenum', $key);
            $url->setAnchor('rule');

            $this->view->list[] = array(
                'img' => is_null($img) ? null : Horde::img($img, '', array('class' => 'ui-li-icon')),
                'name' => $name,
                'url' => $url
            );
        }
    }

    /**
     * Add base javascript variables to the page.
     */
    protected function _addBaseVars()
    {
        global $page_output;

        $code = array(
            'text' => array(
                'no_descrip' => _("No Description")
            )
        );

        $page_output->addInlineJsVars(array(
            'var Ingo' => $code
        ), array('top' => true));
    }

}
