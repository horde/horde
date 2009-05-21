<div id="kronolithViewAgenda" class="kronolithView kronolithViewAgenda" style="display:none">
<table class="kronolithView kronolithViewAgenda">
  <caption>
    <span><?php echo _("Agenda") ?></span>
    <span class="kronolithNavigation">
      <a class="kronolithGotoToday"><?php echo _("Today") ?></a>
      <a title="<?php echo _("Previous") ?>" class="kronolithPrev">&lt;</a>
      <a title="<?php echo _("Next") ?>" class="kronolithNext">&gt;</a>
    </span>
  </caption>
  <tbody class="kronolithViewBody">
    <?php for ($i = 1; $i < 8; $i++): ?>
    <tr class="kronolithRow kronolithRow<?php echo $i % 2 ? 'Even' : 'Odd' ?>">
      <td class="kronolithFirstCol">Mon, Apr <?php echo 6 + $i ?></td>
      <td class="kronolithCol">
        <div id="kronolithEventmonthinternal|filipe4b0da45c36b92f7e1b297becfadefdf0" calendar="internal|filipe" eventid="4b0da45c36b92f7e1b297becfadefdf0" class="kronolithEvent" style="color: rgb(255, 185, 18); -moz-user-select: none;"><span class="kronolithDate">10h00</span> <span class="kronolithSep">&middot;</span> assssssssssas</div>
        <div id="kronolithEventmonthinternal|filipe4b0da45c36b92f7e1b297becfadefdf0" calendar="internal|filipe" eventid="4b0da45c36b92f7e1b297becfadefdf0" class="kronolithEvent" style="color: rgb(255, 185, 18); -moz-user-select: none;">assssssssssas</div>
        <div id="kronolithEventmonthinternal|filipe4b0da45c36b92f7e1b297becfadefdf0" calendar="internal|filipe" eventid="4b0da45c36b92f7e1b297becfadefdf0" class="kronolithEvent" style="color: rgb(255, 185, 18); -moz-user-select: none;">assssssssssas</div>
        <div id="kronolithEventmonthinternal|filipe4b0da45c36b92f7e1b297becfadefdf0" calendar="internal|filipe" eventid="4b0da45c36b92f7e1b297becfadefdf0" class="kronolithEvent" style="color: rgb(0, 136, 217); -moz-user-select: none;"><span class="kronolithDate">12h00</span> <span class="kronolithSep">&middot;</span> assssssssssas</div>
      </td>
    </tr>
    <?php endfor; ?>
  </tbody>
</table>
</div>
