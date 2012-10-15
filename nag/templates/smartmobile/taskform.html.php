<div data-role="page" id="nag-taskform-view">
   <?php echo $this->smartmobileHeader(array('backlink' => true, 'logout' => true, 'title' => _("My Tasks"))) ?>
  <div data-role="content">
    <form id='nag-task-form'>
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
        <select id="task_priority" name="task_priority">
          <option value="1">1 (<?php echo _("Highest") ?>)</option>
          <option value="2">2</option>
          <option value="3">3</option>
          <option value="4">4</option>
          <option value="5">5 (<?php echo _("Lowest") ?>)</option>
        </select>
      </div>

      <div data-role="field-contain">
        <label for="task_estimate"><?php echo _("Estimate") ?></label>
        <input type="number" id="task_estimate" name="task_estimate" />
      </div>

      <div data-role="field-contain">
        <label for="task_completed"><?php echo _("Completed") ?></label>
        <input type="checkbox" id="task_completed" name="task_completed" />
      </div>

      <!-- @TODO: Alarm -->
      <div data-role="footer" class="ui-bar" data-position="fixed">
        <a href="#task-submit"><?php echo _("Save Task") ?></a>
        <a href="#task-cancel"><?php echo _("Cancel") ?></a>
        <a href="#task-delete"><?php echo _("Delete") ?></a>
       </div>
    </form>
  </div>
</div>