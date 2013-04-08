<input type="hidden" name="twitteractionID" id="twitteractionID" />
<?php if ($this->haveSession): ?>
<div style="float:left">
 <img src="<?php echo $this->profile_image_url ?>" alt="<?php echo $this->h($this->profile_screenname) ?>" />
 <div><?php echo $this->h($this->profile_name) ?></div>
 <div><?php echo $this->h($this->profile_location) ?></div>
</div>
<div class="clear">&nbsp;</div>
<div>
 <?php printf(_("%s can interact with your Twitter account"), $this->appname) ?>
 <div>
  <input type="submit" class="button" value="<?php echo _("Disable") ?>" onclick="document.prefs.twitteractionID.value='revokeInfinite'; return true" />
 </div>
</div>
<?php else: ?>
<div class="notice">
 <?php printf(_("Could not find authorization for %s to interact with your Twitter account"), $this->appname) ?>
</div>
<?php printf(_("Login to Twitter and authorize the %s application"), $this->appname) ?>: <?php echo $this->link ?>
<?php endif; ?>
