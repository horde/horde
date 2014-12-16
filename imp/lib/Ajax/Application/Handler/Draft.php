<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Defines AJAX actions used for saving compose drafts.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ajax_Application_Handler_Draft extends Horde_Core_Ajax_Application_Handler
{
    /**
     * The list of disabled draft actions.
     *
     * @var array
     */
    public $disabled = array();

    /**
     * AJAX action: Auto save a draft message.
     *
     * @return object  See _draftAction().
     */
    public function autoSaveDraft()
    {
        return $this->_draftAction('autoSaveDraft');
    }

    /**
     * AJAX action: Save a draft message.
     *
     * @return object  See _draftAction().
     */
    public function saveDraft()
    {
        return $this->_draftAction('saveDraft');
    }

    /**
     * AJAX action: Save a template message.
     *
     * @return object  See _draftAction().
     */
    public function saveTemplate()
    {
        return $this->_draftAction('saveTemplate');
    }

    /* Protected methods. */

    /**
     * Save a draft composed message.
     *
     * See the list of variables needed for
     * IMP_Ajax_Application#composeSetup(). Additional variables used:
     *   - html: (integer) In HTML compose mode?
     *   - message: (string) The message text.
     *   - priority: (string) The priority of the message.
     *   - request_read_receipt: (boolean) Add request read receipt header?
     *
     * @param string $action  AJAX action.
     *
     * @return object  An object with the following entries:
     *   - action: (string) The AJAX action string
     *   - success: (integer) 1 on success, 0 on failure.
     */
    protected function _draftAction($action)
    {
        if (in_array($action, $this->disabled)) {
            return false;
        }

        try {
            list($result, $imp_compose, $headers, ) = $this->_base->composeSetup($action);
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e);

            $result = new stdClass;
            $result->action = $action;
            $result->success = 0;
            return $result;
        }

        $opts = array(
            'autosave' => ($action == 'autoSaveDraft'),
            'html' => $this->vars->html,
            'priority' => $this->vars->priority,
            'readreceipt' => $this->vars->request_read_receipt
        );

        try {
            switch ($action) {
            case 'saveTemplate':
                $res = $imp_compose->saveTemplate($headers, $this->vars->message, $opts);
                break;

            default:
                $res = $imp_compose->saveDraft($headers, $this->vars->message, $opts);
                break;
            }

            switch ($action) {
            case 'saveDraft':
                if ($GLOBALS['prefs']->getValue('close_draft')) {
                    $imp_compose->destroy('save_draft');
                }
                // Fall-through

            default:
                $GLOBALS['notification']->push($res);
                break;
            }
        } catch (IMP_Compose_Exception $e) {
            $result->success = 0;
            $GLOBALS['notification']->push($e);
        }

        return $result;
    }

}
