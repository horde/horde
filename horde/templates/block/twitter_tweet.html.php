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
  <div class="hordeSmAvatar">
    <?php echo $this->profileLink ?><img width="48" height="48" src="<?php echo $this->profileImg?>" alt="<?php echo $this->authorName?>" title="<?php echo $this->authorFullname?>" /></a>
  </div>
  <div><?php echo $this->profileLink?> <strong><?php echo $this->authorFullname ?></strong> <em><?php echo $this->authorName?></em></a></div>
  <div class="hordeSmStreambody">
    <?php echo $this->body ?>
    <div class="hordeSmStreaminfo">
      <?php echo sprintf(_("Posted %s via %s"), Horde_Date_Utils::relativeDateTime(strtotime($this->createdAt), $GLOBALS['prefs']->getValue('date_format')), $this->clientText)?>
    </div>
    <?php if (!empty($this->tweet->retweeted_status)):?>
    <div class="hordeSmStreaminfo">
      <?php echo sprintf(_("Retweeted by %s"), Horde::externalUrl('http://twitter.com/' . $this->escape($this->tweet->user->screen_name), true)) . '@' . $this->escape($this->tweet->user->screen_name) ?></a>
    </div>
    <?php endif; ?>
    <div class="hordeSmStreaminfo">
      <?php echo Horde::selfUrl()->link(array('onclick' => 'Horde[\'twitter' . $this->instanceid . '\'].buildReply(\'' . (string)$this->tweet->id_str . '\', \'' . $this->tweet->user->screen_name . '\', \'' . $this->tweet->user->name . '\'); return false;')) .  _("Reply") ?></a>
      &nbsp;|&nbsp; <?php echo Horde::selfUrl()->link(array('onclick' => 'Horde[\'twitter' . $this->instanceid . '\'].retweet(\'' . (string)$this->tweet->id_str . '\'); return false;')) . _("Retweet") ?></a>
      <?php if (empty($this->tweet->favorited)): ?>
          &nbsp;|&nbsp; <?php echo Horde::selfUrl()->link(array('id' => 'favorite' . $this->instanceid . $this->tweet->id_str, 'onclick' => 'Horde[\'twitter' . $this->instanceid . '\'].favorite(\'' . (string)$this->tweet->id_str . '\'); return false;')) . _("Favorite") ?></a>
      <?php else: ?>
         &nbsp;|&nbsp; <?php echo Horde::selfUrl()->link(array('id' => 'favorite' . $this->instanceid . $this->tweet->id_str, 'onclick' => 'Horde[\'twitter' . $this->instanceid . '\'].unfavorite(\'' . (string)$this->tweet->id_str . '\'); return false;')) . _("Unfavorite") ?></a>
      <?php endif; ?>
    </div>
    <div class="clear">&nbsp;</div>
  </div>
</div>
