<?php
/**
 */
class Nag_CompleteTask {

    /**
     */
    public function result($task, $tasklist)
    {
        global $nag_shares, $notification, $registry;

        try {
            $share = $nag_shares->getShare($tasklist);
            $task = Nag::getTask($tasklist, $task);
            if (!$share->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
                $result = array('error' => 'permission denied');
                $notification->push(_("Access denied completing this task."), 'horde.error');
            } else {
                $wasCompleted = $task->completed;
                $task->loadChildren();
                if ($wasCompleted &&
                    $task->parent &&
                    $task->parent->completed) {
                    $result = array('data' => 'complete');
                    $notification->push(_("Completed parent task, mark it as incomplete first"), 'horde.error');
                } elseif (!$wasCompleted && !$task->childrenCompleted()) {
                    $result = array('data' => 'incomplete');
                    $notification->push(_("Incomplete sub tasks, complete them first"), 'horde.error');
                } else {
                    $task->toggleComplete();
                    $task->save();
                    if ($task->completed) {
                        $result = array('data' => 'complete');
                        $notification->push(sprintf(_("Completed %s."), $task->name), 'horde.success');
                    } elseif (!$wasCompleted) {
                        $result = array('data' => 'incomplete');
                        $notification->push(sprintf(_("%s is still incomplete."), $task->name), 'horde.success');
                    } else {
                        $result = array('data' => 'incomplete');
                        $notification->push(sprintf(_("%s is now incomplete."), $task->name), 'horde.success');
                    }
                }
            }
        } catch (Exception $e) {
            $result = array('error' => $e->getMessage());
            $notification->push(sprintf(_("There was a problem completing %s: %s"), $task->name, $e->getMessage()), 'horde.error');
        }

        return $result;
    }
}
