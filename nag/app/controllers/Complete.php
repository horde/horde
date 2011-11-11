<?php
class Nag_Complete_Controller extends Horde_Controller_Base
{
    public function processRequest(Horde_Controller_Request $request, Horde_Controller_Response $response)
    {
        /* Toggle the task's completion status if we're provided with a
         * valid task ID. */
        $task_id = Horde_Util::getFormData('task');
        $tasklist_id = Horde_Util::getFormData('tasklist');
        if (isset($task_id)) {
            try {
                $share = $GLOBALS['nag_shares']->getShare($tasklist_id);
                $task = Nag::getTask($tasklist_id, $task_id);
                if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
                    $result = array('error' => 'permission denied');
                } else {
                    $task->completed = !$task->completed;
                    if ($task->completed) {
                        $task->completed_date = time();
                    } else {
                        $task->completed_date = null;
                    }

                    $task->save();
                    if ($task->completed) {
                        $result = array('data' => 'complete');
                    } else {
                        $result = array('data' => 'incomplete');
                    }
                }
            } catch (Exception $e) {
                $result = array('error' => $e->getMessage());
            }
        } else {
            $result = array('error' => 'missing parameters');
        }

        $response->setContentType('application/json');
        $response->setBody(json_encode($result));
    }
}
