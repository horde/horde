<div id="kronolithViewAgenda" style="display:none">
<table class="kronolithView">
  <caption class="tabset">
    <span id="kronolithLoadingagenda" class="kronolithLoading"></span>
    <span id="kronolithAgendaDate"></span>
    <span class="kronolithNavigation">
      <span id="kronolithAgendaNavigation">
        <a title="<?php echo _("Previous") ?>" class="kronolithPrev">&lt;</a>
        <a class="kronolithGotoToday"><?php echo Horde::highlightAccessKey(_("_Today"), $todayAccessKey) ?></a>
        <a title="<?php echo _("Next") ?>" class="kronolithNext">&gt;</a>
      </span>
      <ul id="kronolithSearchNavigation" style="display:none">
        <li><a id="kronolithSearchAll"><?php echo _("All") ?></a></li>
        <li><a id="kronolithSearchPast"><?php echo _("Past") ?></a></li>
        <li class="activeTab"><a id="kronolithSearchFuture"><?php echo _("Future") ?></a></li>
      </ul>
      <a class="kronolithPrint" onclick="print()" title="<?php echo _("Print") ?>"><span><?php echo _("Print") ?></span></a>
    </span>
  </caption>
  <tbody id="kronolithViewAgendaBody">
    <tr id="kronolithAgendaTemplate" style="display:none">
      <td class="kronolithFirstCol"></td>
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
