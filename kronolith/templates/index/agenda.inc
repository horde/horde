<div id="kronolithViewAgenda" style="display:none">
<table class="kronolithView">
  <caption>
    <span id="kronolithLoadingagenda" class="kronolithLoading"></span>
    <span id="kronolithAgendaDate"></span>
    <span class="kronolithNavigation">
      <a title="<?php echo _("Previous") ?>" class="kronolithPrev">&lt;</a>
      <a class="kronolithGotoToday"><?php echo Horde::highlightAccessKey(_("_Today"), $todayAccessKey) ?></a>
      <a title="<?php echo _("Next") ?>" class="kronolithNext">&gt;</a>
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
