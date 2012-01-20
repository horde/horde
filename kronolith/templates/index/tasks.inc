<div id="kronolithViewTasks" style="display:none">
<table class="kronolithView kronolithViewTasks">
  <caption class="tabset">
    <span id="kronolithLoadingtasks" class="kronolithLoading"></span>
    <span><?php echo _("Tasks") ?></span>
    <span class="kronolithNavigation">
      <ul>
        <li><a id="kronolithTasksAll"><?php echo _("All") ?></a></li>
        <li><a id="kronolithTasksComplete"><?php echo _("Completed") ?></a></li>
        <li><a id="kronolithTasksIncomplete"><?php echo _("Incomplete") ?></a></li>
        <li><a id="kronolithTasksFuture"><?php echo _("Future") ?></a></li>
      </ul>
    </span>
  </caption>
  <tbody id="kronolithViewTasksBody">
    <tr id="kronolithNewTask" class="kronolithTaskRow">
      <td class="kronolithTaskCol kronolithAddTask button">
        <div class="kronolithAddTaskIcon"></div><?php echo _("Add Task") ?>
      </td>
    </tr>
    <tr id="kronolithTasksTemplate" class="kronolithTaskRow" style="display:none">
      <td class="kronolithTaskCol">
        <div class="kronolithTaskCheckbox"></div>
      </td>
    </tr>
    <tr id="kronolithTasksNoItems" class="kronolithTaskRow">
      <td class="kronolithTaskCol">
        <?php echo _("No items") ?>
      </td>
    </tr>
  </tbody>
</table>
</div>
