<?php
/**
 * Twitter client initial layout
 * Uses:
 *   ->instance
 *   ->defaultText
 *   ->loadingImg
 *   ->latestStatus
 *   ->latestDate
 *   ->bodyHeight
 */
?>
<div style="padding: 0 8px 8px">
 <div class="fbgreybox">
   <textarea rows="2" style="width:98%;margin-top:4px;margin-bottom:4px;" type="text" id="<?php echo $this->instance ?>_newStatus" name="<?php echo $this->instance ?>_newStatus"><?php echo $this->defaultText ?></textarea> <a class="button" onclick="Horde['twitter<?php echo $this->instance ?>'].updateStatus($F('<?php echo $this->instance ?>_newStatus'));" href="#"><?php echo _("Tweet") ?></a>
   <span id="<?php echo $this->instance ?>_counter" style="color:rgb(204, 204, 204); margin-left:6px;">140</span>
   <span id="<?php echo $this->instance ?>_inReplyTo"></span>
   <?php echo $this->loadingImg ?>
   <div id="currentStatus" class="" style="margin:10px;"><strong>'<?php echo _("Latest") ?></strong><?php echo $this->latestStatus ?> - <span class="fbstreaminfo"><?php echo $this->latestDate ?></span></div>
 </div>
 <div>
   <a href="#" onclick="Horde['twitter<?php echo $this->instance ?>'].showStream();"><?php echo _("Stream")?></a>
   <a href="#" onclick="Horde['twitter<?php echo $this->instance ?>'].showMentions();"><?php echo _("Mentions") ?></a>
 </div>
 <div style="height:<?php echo $this->bodyHeight ?>px; overflow-y:auto;" id="<?php echo $this->instance ?>_twitter_body">
   <div id="<?php echo $this->instance ?>_stream"></div>
   <div id="<?php echo $this->instance ?>_mentions"></div>
 </div>
 <div class="hordeSmGetmore"><input type="button" class="button" id="<?php echo $this->instance ?>_getmore" value="<?php echo _("Get More") ?>"></div>
</div>