<!-- <h3><?php /*echo date('Y-m-d', $logMessage->queryDate()) */?></h3> -->
<div class="commit-summary">
 <div class="commit-info">
  <ul>
   <li>
    <a href="<?php echo Chora::url('patchsets', $GLOBALS['where'], array('ps' => $logMessage->queryRevision())) ?>" title="<?php echo _("View commit") ?>"><?php echo $this->escape($logMessage->queryRevision()) ?></a>
    <span class="diffadd">+<?php echo $this->escape($logMessage->getAddedLines()) ?></span>, <span class="diffdel">-<?php echo $this->escape($logMessage->getDeletedLines()) ?></span>
   </li>
  </ul>
  <ul>
   <li></li>
  </ul>

  <?php if ($branchinfo = $logMessage->queryBranch()): ?>
  <h4><?php echo _("Branches") ?></h4>
  <ul>
  <?php foreach ($branchinfo as $branchname): ?>
   <li><a href="<?php echo Chora::url('browsefile', $GLOBALS['where'], array('onb' => $branchname)) ?>"><?php echo $this->escape($branchname) ?></a></li>
  <?php endforeach; ?>
  </ul>
  <?php endif; ?>

  <?php if ($tags = $logMessage->queryTags()): ?>
  <h4><?php echo _("Tags") ?></h4>
  <ul>
  <?php foreach ($tags as $tag): ?>
   <li><?php echo $this->escape($tag) ?></li>
  <?php endforeach; ?>
  </ul>
  <?php endif; ?>
 </div>

 <div class="commit-message"><?php echo Chora::formatLogMessage($logMessage->queryLog()) ?></div>

 <div class="commit-author">
  <div class="commit-author-avatar">
   <img src="http://www.gravatar.com/avatar/<?php echo md5(strtolower(trim(Chora::getAuthorEmail($logMessage->queryAuthor())))) ?>?d=mm&s=40">
  </div>
  <?php echo Chora::showAuthorName($logMessage->queryAuthor(), true) ?><br>
  <?php echo Chora::formatDate($logMessage->queryDate()) ?>
 </div>

 <div class="clear">&nbsp;</div>
</div>
