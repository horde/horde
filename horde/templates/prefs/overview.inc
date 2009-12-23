<div class="prefsOverview">
<?php foreach ($columns as $key => $column): ?>
 <div style="width:<?php echo $span - 1 ?>%">
  <div>
   <h2><?php echo htmlspecialchars($key) ?></h2>

   <dl>
   <?php foreach ($column as $group => $gvals): ?>
    <?php if (Horde_Prefs_Ui::groupIsEditable($group, $prefGroups)): ?>
     <dt><?php echo Horde::widget(Horde_Util::addParameter(Horde::selfUrl(), array('app' => $GLOBALS['app'], 'group' => $group)), $gvals['label'], '', '', '', $gvals['label']) ?></dt>
     <dd><?php echo htmlspecialchars($gvals['desc']) ?></dd>
    <?php endif; ?>
   <?php endforeach; ?>
   </dl>
  </div>
 </div>
<?php endforeach; ?>
</div>
