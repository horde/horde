<div id="kronolithViewTasks" style="display:none">
<div class="tabset">
  <span class="kronolithNavigation">
    <ul>
      <li><a id="kronolithTasksAll"><?php echo _("All") ?></a></li>
      <li><a id="kronolithTasksComplete"><?php echo _("Completed") ?></a></li>
      <li><a id="kronolithTasksIncomplete"><?php echo _("Incomplete") ?></a></li>
      <li><a id="kronolithTasksFuture"><?php echo _("Future") ?></a></li>
    </ul>
  </span>
  <span id="kronolithLoadingtasks" class="kronolithLoading"></span>
</div>
<table class="kronolith-view kronolithViewTasks">
  <tbody id="kronolithViewTasksBody">
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
