<div id="kronolithViewTasks" style="display:none">
<table class="kronolithView kronolithViewTasks">
  <caption class="tabset">
    <span id="kronolithLoadingtasks" class="kronolithLoading"></span>
    <span><?php echo _("Tasks") ?></span>
    <ul class="kronolithNavigation">
      <li><a id="kronolithTasksAll"><?php echo _("All") ?></a></li>
      <li><a id="kronolithTasksComplete"><?php echo _("Completed") ?></a></li>
      <li><a id="kronolithTasksIncomplete"><?php echo _("Incomplete") ?></a></li>
      <li><a id="kronolithTasksFuture"><?php echo _("Future") ?></a></li>
    </ul>
  </caption>
  <tbody id="kronolithViewTasksBody">
     <tr class="kronolithTaskRow">
       <td class="kronolithTaskCol kronolithAddTask">
         <div class="kronolithAddTaskIcon"></div><?php echo _("Add Task") ?>
       </td>
    </tr>
    <tr id="kronolithTasksTemplate" class="kronolithTaskRow" style="display:none">
      <td class="kronolithTaskCol">
        <div class="kronolithTaskCheckbox"/>
      </td>
    </tr>
  </tbody>
</table>
</div>
