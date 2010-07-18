<?php
/**
 * Template for individual tweets:
 *
 * Uses:
 *   ->body
 *   ->profileLink
 *   ->profileImg
 *   ->authorName
 *   ->authorFullname
 *   ->createdAt
 *   ->clientName
 *   ->tweet
 *
 */
?>
<div class="fbstreamstory">
  <div class="solidbox" style="float:left;text-align:center;height:73px;width:73px;margin-right:5px;padding-top:5px;">
    <?php echo $this->profileLink ?><img width="48" height="48" src="<?php echo $this->profileImg?>" alt="<?php echo $this->authorName?>" title="<?php echo $this->authorFullname?>" /></a>
    <div style="overflow:hidden;"><?php echo $this->profileLink . $this->authorName ?></a></div>
  </div>
  <div class="fbstreambody">
    <?php echo $this->body ?>
    <div class="fbstreaminfo">
      <?php echo sprintf(_("Posted %s via %s"), Horde_Date_Utils::relativeDateTime(strtotime($this->createdAt), $GLOBALS['prefs']->getValue('date_format')), $this->clientText)?>
    </div>
    <?php if (!empty($tweet->retweeted_status)):?>
    <div class="fbstreaminfo">
      <?php echo sprintf(_("Retweeted by %s"), Horde::externalUrl('http://twitter.com/' . $this->escape($this->tweet->user->screen_name), true)) . $this->escape($this->tweet->user->screen_name) ?></a>
    </div>
    <?php endif; ?>
    <div class="fbstreaminfo">
      <?php echo Horde::link('#', '', '', '', 'Horde.twitter.buildReply(\'' . $this->tweet->id . '\', \'' . $this->tweet->user->screen_name . '\', \'' . $this->tweet->user->name . '\')') .  _("Reply") ?></a>
      &nbsp;|&nbsp; <?php echo Horde::link('#', '', '', '', 'Horde.twitter.retweet(\'' . $this->tweet->id . '\')') . _("Retweet") ?></a>
    </div>
    <div class="clear">&nbsp;</div>
  </div>
</div>
