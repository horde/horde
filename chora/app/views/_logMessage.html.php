<div class="commit-summary">
<?php if (empty($hide_diff)): ?>
 <div class="commit-info">
  <ul>
   <li>
    <a href="<?php echo Chora::url('commit', '', array('commit' => $logMessage['revision'])) ?>" title="<?php echo _("View commit") ?>"><?php echo $this->escape($logMessage['revision']) ?></a>
    <div>
     <span class="diffadd">+<?php echo $this->escape($logMessage['added']) ?></span>, <span class="diffdel">-<?php echo $this->escape($logMessage['deleted']) ?></span>
<?php if (empty($diff_page)): ?>
     <span class="difflink">[<a href="<?php echo Chora::url('diff', $GLOBALS['where'], array('r1' => $logMessage['revision'])) ?>"><?php echo _("Diff") ?></a>]</span>
<?php endif; ?>
    </div>
   </li>
  </ul>
  <ul>
   <li></li>
  </ul>

  <?php if ($logMessage['branch']): ?>
  <h4><?php echo _("Branches") ?></h4>
  <ul>
  <?php foreach ($logMessage['branch'] as $branchname): ?>
   <li><a href="<?php echo Chora::url('browsefile', $GLOBALS['where'], array('onb' => $branchname)) ?>"><?php echo $this->escape($branchname) ?></a></li>
  <?php endforeach; ?>
  </ul>
  <?php endif; ?>

  <?php if (!empty($logMessage['tags'])): ?>
  <h4><?php echo _("Tags") ?></h4>
  <ul>
  <?php foreach ($logMessage['tags'] as $tag): ?>
   <li><?php echo $this->escape($tag) ?></li>
  <?php endforeach; ?>
  </ul>
  <?php endif; ?>
 </div>
<?php endif; ?>

 <div class="commit-message"><?php echo Chora::formatLogMessage($logMessage['log']) ?></div>

 <div class="commit-author">
  <div class="commit-author-avatar">
   <img src="http://www.gravatar.com/avatar/<?php echo md5(strtolower(trim(Chora::getAuthorEmail($logMessage['author'])))) ?>?d=mm&s=40">
  </div>
  <?php echo Chora::showAuthorName($logMessage['author'], true) ?><br>
  <?php echo Chora::formatDate($logMessage['date']) ?>
 </div>

 <div class="clear">&nbsp;</div>
</div>
