<?php
class Nag_CompleteTask_Controller extends Horde_Controller_Base
{
    public function processRequest(Horde_Controller_Request $request, Horde_Controller_Response $response)
    {
        $notification = $this->getInjector()->getInstance('Horde_Notification');

        /* Toggle the task's completion status if we're provided with a
         * valid task ID. */
        $requestVars = $request->getRequestVars();
        if (isset($requestVars['task']) && isset($requestVars['tasklist'])) {
            try {
                $share = $GLOBALS['nag_shares']->getShare($requestVars['tasklist']);
                $task = Nag::getTask($requestVars['tasklist'], $requestVars['task']);
                $registry = $this->getInjector()->getInstance('Horde_Registry');
                if (!$share->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
                    $result = array('error' => 'permission denied');
                    $notification->push(sprintf(_("Access denied completing task %s."), $task->name), 'horde.error');
                } else {
                    $task->toggleComplete();
                    $task->save();
                    if ($task->completed) {
                        $result = array('data' => 'complete');
                        $notification->push(sprintf(_("Completed %s."), $task->name), 'horde.success');
                    } else {
                        $result = array('data' => 'incomplete');
                        $notification->push(sprintf(_("%s is now incomplete."), $task->name), 'horde.success');
                    }
                }
            } catch (Exception $e) {
                $result = array('error' => $e->getMessage());
                $notification->push(sprintf(_("There was a problem completing %s: %s"),
                                                       $task->name, $e->getMessage()), 'horde.error');
            }
        } else {
            $result = array('error' => 'missing parameters');
        }

        $requestVars = $request->getGetVars();
        if ($requestVars['format'] == 'json') {
            $response->setContentType('application/json');
            $response->setBody(json_encode($result));
        } elseif ($requestVars['url']) {
            $response->setRedirectUrl($requestVars['url']);
        }
    }
}
