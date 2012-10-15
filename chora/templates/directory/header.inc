<?php if (count($branches) > 1): ?>
<div class="options">
 <span>
  <form method="post" action="<?php echo Chora::url('browsedir', Horde_Util::getFormData('f')) ?>">
   <?php echo _("View Branch:") ?>
   <select name="onb" onchange="this.form.submit()">
    <option value="0"<?php if (!isset($onb) || !$onb) echo ' selected="selected"' ?>><?php echo _("All Branches") ?></option>
<?php foreach ($branches as $branch): ?>
    <option value="<?php echo htmlspecialchars($branch) ?>"<?php echo ($branch == $onb) ? ' selected="selected"' : '' ?>><?php echo htmlspecialchars($branch) ?></option>
<?php endforeach; ?>
   </select>
   <noscript><input class="button" type="submit" value="<?php echo _("View") ?>" /></noscript>
  </form>
 </span>
</div>
<?php endif; ?>

<table id="browse" cellspacing="0" class="browse sortable striped nowrap">
<thead>
 <tr>
  <th id="s<?php echo Horde_Vcs::SORT_NAME ?>"<?php if ($acts['sbt'] == Horde_Vcs::SORT_NAME) echo ' class="' . $sortdirclass . '"' ?>>
   <?php echo Horde::widget(array('url' => $url['name'], 'class' => 'sortlink', 'title' => _("_File"))) ?>
  </th>
  <th id="s<?php echo Horde_Vcs::SORT_REV ?>"<?php if ($acts['sbt'] == Horde_Vcs::SORT_REV) echo ' class="' . $sortdirclass . '"' ?>>
   <?php echo Horde::widget(array('url' => $url['rev'], 'class' => 'sortlink', 'title' => _("_Rev"))) ?>
  </th>
  <th id="s<?php echo Horde_Vcs::SORT_AUTHOR ?>"<?php if ($acts['sbt'] == Horde_Vcs::SORT_AUTHOR) echo ' class="' . $sortdirclass . '"' ?>>
   <?php echo Horde::widget(array('url' => $url['author'], 'class' => 'sortlink', 'title' => _("_Author"))) ?>
  </th>
  <th id="s<?php echo Horde_Vcs::SORT_AGE ?>"<?php if ($acts['sbt'] == Horde_Vcs::SORT_AGE) echo ' class="' . $sortdirclass . '"' ?>>
   <?php echo Horde::widget(array('url' => $url['age'], 'class' => 'sortlink', 'title' => _("Da_te"))) ?>
  </th>
  <th class="nosort"><?php echo _("Last Log") ?></th>
 </tr>
</thead>
