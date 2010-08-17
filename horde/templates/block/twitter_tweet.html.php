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
 *   ->instanceid
 *
 */
?>
<div class="hordeSmStreamstory">
  <div class="solidbox hordeSmAvatar">
    <?php echo $this->profileLink ?><img width="48" height="48" src="<?php echo $this->profileImg?>" alt="<?php echo $this->authorName?>" title="<?php echo $this->authorFullname?>" /></a>
    <div style="overflow:hidden;"><?php echo $this->profileLink . $this->authorName ?></a></div>
  </div>
  <div class="hordeSmStreambody">
    <?php echo $this->body ?>
    <div class="hordeSmStreaminfo">
      <?php echo sprintf(_("Posted %s via %s"), Horde_Date_Utils::relativeDateTime(strtotime($this->createdAt), $GLOBALS['prefs']->getValue('date_format')), $this->clientText)?>
    </div>
    <?php if (!empty($this->tweet->retweeted_status)):?>
    <div class="hordeSmStreaminfo">
      <?php echo sprintf(_("Retweeted by %s"), Horde::externalUrl('http://twitter.com/' . $this->escape($this->tweet->user->screen_name), true)) . $this->escape($this->tweet->user->screen_name) ?></a>
    </div>
    <?php endif; ?>
    <div class="hordeSmStreaminfo">
      <?php echo Horde::selfUrl()->link(array('onclick' => 'Horde.twitter' . $this->instanceid . '.buildReply(\'' . $this->tweet->id . '\', \'' . $this->tweet->user->screen_name . '\', \'' . $this->tweet->user->name . '\'); return false;')) .  _("Reply") ?></a>
      &nbsp;|&nbsp; <?php echo Horde::selfUrl()->link(array('onclick' => 'Horde.twitter' . $this->instanceid . '.retweet(\'' . $this->tweet->id . '\'); return false;')) . _("Retweet") ?></a>
    </div>
    <div class="clear">&nbsp;</div>
  </div>
</div>
