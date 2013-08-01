<input type="hidden" name="twitteractionID" id="twitteractionID" />
<?php if ($this->haveSession): ?>
<div><?php echo $this->h($this->profile_name) ?>, <?php echo $this->h($this->profile_location) ?></div>
<div>
 <img src="<?php echo $this->profile_image_url ?>" alt="<?php echo $this->h($this->profile_screenname) ?>" />
</div>
<div>
 <br />
 <?php printf(_("%s can interact with your Twitter account"), $this->appname) ?>
 <br />
 <input type="submit" class="button" value="<?php echo _("Disable") ?>" onclick="document.prefs.twitteractionID.value='revokeInfinite'; return true" />
</div>
<?php else: ?>
<div>
 <?php printf(_("Could not find authorization for %s to interact with your Twitter account"), $this->appname) ?>
</div>
<div>
 <?php printf(_("Login to Twitter and authorize %s"), $this->appname) ?>: <?php echo $this->link->link(array('class' => 'horde-default', 'onclick' => Horde::popupJs($this->link, array('urlencode' => true)) . 'return false')) ?><?php echo _("Authorize") ?></a>
</div>
<?php endif; ?>
