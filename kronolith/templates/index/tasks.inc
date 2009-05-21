<div id="kronolithViewTasks" class="kronolithView kronolithViewTasks" style="display:none">
<table class="kronolithView kronolithViewTasks">
  <caption>
    <div id="kronolithLoadingtasks" class="kronolithLoading"></div>
    <span><?php echo _("Tasks") ?></span>
  </caption>
  <tbody class="kronolithViewBody">
    <?php for ( $i = 0; $i < 10; $i++ ): ?>
    <tr class="kronolithRow">
      <td class="kronolithCol kronolithTask<?php echo ($i == 1) ? 'Completed' : '' ?><?php echo ($i >= 8) ? ' kronolithTaskDue' : '' ?>">
        Verify IE compatibility
        <?php if ($i == 5): ?>
        <span class="kronolithSep">&middot;</span>
        <span class="kronolithDate">Apr 12, 2009</span>
        <span class="kronolithSep">&middot;</span>
        <?php endif; if ($i >= 8): ?>
        <span class="kronolithSep">&middot;</span>
        <span class="kronolithDate">Apr 8, 2009</span>
        <span class="kronolithSep">&middot;</span>
        <?php endif; ?>
        <span class="kronolithInfo">Probably there should be changes in IE's CSS so that it displays properly</span>
      </td>
    </tr>
    <?php endfor; ?>
  </tbody>
</table>
</div>
