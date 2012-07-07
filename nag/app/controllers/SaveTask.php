<?php
class Nag_SaveTask_Controller extends Horde_Controller_Base
{
    public function processRequest(Horde_Controller_Request $request, Horde_Controller_Response $response)
    {
        $vars = Horde_Variables::getDefaultVariables();
        $prefs = $GLOBALS['prefs'];
        $registry = $this->getInjector()->getInstance('Horde_Registry');
        $notification = $this->getInjector()->getInstance('Horde_Notification');

        $form = new Nag_Form_Task($vars, $vars->get('task_id') ? sprintf(_("Edit: %s"), $vars->get('name')) : _("New Task"));
        if (!$form->validate($vars)) {
            // Hideous
            $_REQUEST['actionID'] = 'task_form';
            require NAG_BASE . '/task.php';
            exit;
        }

        $form->getInfo($vars, $info);
        if ($prefs->isLocked('default_tasklist') ||
            count(Nag::listTasklists(false, Horde_Perms::EDIT)) <= 1) {
            $info['tasklist_id'] = $info['old_tasklist'] = Nag::getDefaultTasklist(Horde_Perms::EDIT);
        }
        try {
            $share = $GLOBALS['nag_shares']->getShare($info['tasklist_id']);
        } catch (Horde_Share_Exception $e) {
            $notification->push(sprintf(_("Access denied saving task: %s"), $e->getMessage()), 'horde.error');
            Horde::url('list.php', true)->redirect();
        }
        if (!$share->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
            $notification->push(sprintf(_("Access denied saving task to %s."), $share->get('name')), 'horde.error');
            Horde::url('list.php', true)->redirect();
        }

        /* Add new category. */
        if ($info['category']['new']) {
            $cManager = new Horde_Prefs_CategoryManager();
            $cManager->add($info['category']['value']);
        }

        /* If a task id is set, we're modifying an existing task.  Otherwise,
         * we're adding a new task with the provided attributes. */
        if (!empty($info['task_id']) && !empty($info['old_tasklist'])) {
            $storage = Nag_Driver::singleton($info['old_tasklist']);
            $info['tasklist'] = $info['tasklist_id'];
            $info['category'] = $info['category']['value'];
            $result = $storage->modify($info['task_id'], $info);
        } else {
            /* Check permissions. */
            $perms = $this->getInjector()->getInstance('Horde_Core_Perms');
            if ($perms->hasAppPermission('max_tasks') !== true &&
                $perms->hasAppPermission('max_tasks') <= Nag::countTasks()) {
                Horde::url('list.php', true)->redirect();
            }

            /* Creating a new task. */
            $storage = Nag_Driver::singleton($info['tasklist_id']);
            try {
              $info['category'] = $info['category']['value'];
              $storage->add($info);
            } catch (Nag_Exception $e) {
                $notification->push(sprintf(_("There was a problem saving the task: %s."), $result->getMessage()), 'horde.error');
                Horde::url('list.php', true)->redirect();
            }
        }

        /* Check our results. */
        $notification->push(sprintf(_("Saved %s."), $info['name']), 'horde.success');

        /* Return to the last page or to the task list. */
        $url = Horde_Util::getFormData('url', Horde::url('list.php', true));
        $response->setRedirectUrl($url);
    }
}
