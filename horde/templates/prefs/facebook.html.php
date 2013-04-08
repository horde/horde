<input type="hidden" name="fbactionID" id="fbactionID" />

<?php if ($this->have_session): ?>
 <div class="fbbluebox" style="float:left">
  <span><img src="<?php echo $this->user_pic_url ?>" /></span>
  <span><?php echo $this->user_name ?></span>
 </div>
 <div class="clear">&nbsp;</div>
 <div class="fbbluebox">
  <?php echo _("Logged in to Facebook") ?>
  <div class="fbaction">
   <input type="submit" class="fbbutton" value="<?php echo _("Logout") ?>" onclick="document.prefs.fbactionID.value='revokeApplication'; return true" />
  </div>
 </div>

 <div class="fbbluebox">
<?php if ($this->have_publish): ?>
  <?php echo _("Publish enabled.") ?>
  <div class="fbaction">
   <input type="submit" class="fbbutton" value="<?php echo _("Disable") ?>" onclick="document.prefs.fbactionID.value='revokePublish'; return true" />
  </div>
<?php else: ?>
  <?php printf(_("%s cannot set your status messages or publish other content to Facebook."), $this->app_name) ?>
  <div class="fbaction">
   <?php echo _("Authorize Publish") ?>
   <a class="fbbutton" href="<?php echo $this->publish_url ?>">Facebook</a>
  </div>
<?php endif; ?>
 </div>

 <div class="fbbluebox">
<?php if ($this->have_read): ?>
  <?php echo _("Read enabled.") ?>
  <div class="fbaction">
   <input type="submit" class="fbbutton" value="<?php echo _("Disable") ?>" onclick="document.prefs.fbactionID.value='revokeRead'; return true" />
  </div>
<?php else: ?>
  <?php printf(_("%s cannot read your stream messages and various other Facebook data items."), $this->app_name) ?>
  <div class="fbaction">
   <?php echo _("Authorize Read") ?>
   <a class="fbbutton" href="<?php echo $this->read_url ?>">Facebook</a>
  </div>
<?php endif; ?>
 </div>

 <div class="fbbluebox">
<?php if ($this->have_friends): ?>
  <?php echo _("Friends enabled.") ?>
  <div class="fbaction">
   <input type="submit" class="fbbutton" value="<?php echo _("Disable") ?>" onclick="document.prefs.fbactionID.value='revokeFriends'; return true" />
  </div>
<?php else: ?>
  <?php printf(_("%s cannot read information about your Facebook friends."), $this->app_name) ?>
  <div class="fbaction">
   <?php echo _("Authorize Access to Friends Data") ?>
   <a class="fbbutton" href="<?php echo $this->friends_url ?>">Facebook</a>
  </div>
<?php endif; ?>
 </div>

<?php else: ?>
 <div class="notice"><?php printf(_("Could not find authorization for %s to interact with your Facebook account."), $this->user_name) ?></div>
 <br />
 <?php printf(_("Login to Facebook and authorize %s"), $this->app_name) ?>
 <a href="<?php echo $this->authUrl ?>" class="fbbutton">Facebook</a>
<?php endif; ?>
