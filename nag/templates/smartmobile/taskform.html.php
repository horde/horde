<div data-role="page" id="nag-task-view">
   <?php echo $this->smartmobileHeader(array('backlink' => true, 'logout' => true, 'title' => _("My Tasks"))) ?>
  <div data-role="content">
    <form id='nag-task-form' name='nag-task-form'>
      <input type="hidden" name="task_id" id="task_id" />

      <div data-role="field-contain">
        <label for="task_title"><?php echo _("Title") ?></label>
        <input id="task_title" type="text" name="task_title" />
      </div>

      <div data-role="field-contain">
        <label for="task_desc"><?php echo _("Description") ?></label>
        <textarea id="task_desc" name="task_desc"></textarea>
      </div>

      <div data-role="field-contain">
        <label for="task_assignee"><?php echo _("Assignee") ?></label>
        <input type="text" id="task_assignee" name="task_assignee" />
      </div>

      <div data-role="field-contain">
        <label for="task_private"><?php echo _("Private") ?></label>
        <input type="checkbox" id="task_private" name="task_private" />
      </div>

      <div data-role="field-contain">
        <label for="task_start"><?php echo _("Start Date") ?></label>
        <input type="date" id="task_start" name="task_start" />
      </div>

      <div data-role="field-contain">
        <label for="task_due"><?php echo _("Due Date") ?></label>
        <input type="date" id="task_due" name="task_due" />
      </div>

      <div data-role="field-contain">
        <label for="task_priority"><?php echo _("Priority") ?></label>
        <input id="task_priority" name="task_priority" />
      </div>

      <div data-role="field-contain">
        <label for="task_completed"><?php echo _("Completed") ?></label>
        <input type="checkbox" id="task_completed" name="task_completed" />
      </div>

    </form>
  </div>
</div>