<!-- <h3><?php /*echo date('Y-m-d', $logMessage->getDate()) */?></h3> -->
<div class="commit-summary">
<?php if (empty($hide_diff)): ?>
 <div class="commit-info">
  <ul>
   <li>
    <a href="<?php echo Chora::url('commit', '', array('commit' => $logMessage->getRevision())) ?>" title="<?php echo _("View commit") ?>"><?php echo $this->escape($logMessage->getRevision()) ?></a>
    <div>
     <span class="diffadd">+<?php echo $this->escape($logMessage->getAddedLines()) ?></span>, <span class="diffdel">-<?php echo $this->escape($logMessage->getDeletedLines()) ?></span>
<?php if (empty($diff_page)): ?>
     <span class="difflink">[<a href="<?php echo Chora::url('diff', $GLOBALS['where'], array('r1' => $logMessage->getRevision())) ?>"><?php echo _("Diff") ?></a>]</span>
<?php endif; ?>
    </div>
   </li>
  </ul>
  <ul>
   <li></li>
  </ul>

  <?php if ($branchinfo = $logMessage->getBranch()): ?>
  <h4><?php echo _("Branches") ?></h4>
  <ul>
  <?php foreach ($branchinfo as $branchname): ?>
   <li><a href="<?php echo Chora::url('browsefile', $GLOBALS['where'], array('onb' => $branchname)) ?>"><?php echo $this->escape($branchname) ?></a></li>
  <?php endforeach; ?>
  </ul>
  <?php endif; ?>

  <?php if ($tags = $logMessage->getTags()): ?>
  <h4><?php echo _("Tags") ?></h4>
  <ul>
  <?php foreach ($tags as $tag): ?>
   <li><?php echo $this->escape($tag) ?></li>
  <?php endforeach; ?>
  </ul>
  <?php endif; ?>
 </div>
<?php endif; ?>

 <div class="commit-message"><?php echo Chora::formatLogMessage($logMessage->getMessage()) ?></div>

 <div class="commit-author">
  <div class="commit-author-avatar">
   <img src="http://www.gravatar.com/avatar/<?php echo md5(strtolower(trim(Chora::getAuthorEmail($logMessage->getAuthor())))) ?>?d=mm&s=40">
  </div>
  <?php echo Chora::showAuthorName($logMessage->getAuthor(), true) ?><br>
  <?php echo Chora::formatDate($logMessage->getDate()) ?>
 </div>

 <div class="clear">&nbsp;</div>
</div>
