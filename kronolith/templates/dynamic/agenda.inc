<div id="kronolithViewAgenda" style="display:none">
<div class="tabset">
  <span id="kronolithAgendaDate"></span>
  <span class="kronolithNavigation">
    <ul id="kronolithSearchNavigation">
      <li><a id="kronolithSearchAll"><?php echo _("All") ?></a></li>
      <li><a id="kronolithSearchPast"><?php echo _("Past") ?></a></li>
      <li class="horde-active"><a id="kronolithSearchFuture"><?php echo _("Future") ?></a></li>
    </ul>
  </span>
  <span id="kronolithLoadingagenda" class="kronolithLoading"></span>
</div>
<table class="kronolith-view">
  <tbody id="kronolithViewAgendaBody">
    <tr id="kronolithAgendaTemplate" style="display:none">
      <td class="kronolith-first-col"></td>
      <td>
      </td>
    </tr>
    <tr id="kronolithAgendaNoItems" style="display:none">
      <td colspan="2">
        <?php echo _("No items") ?>
      </td>
    </tr>
  </tbody>
</table>
</div>
