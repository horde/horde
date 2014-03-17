<form id="turba-minisearch" action="#">
 <?php echo Horde::label('search', _("Search for: ")) ?>
 <input id="turba-minisearch-search" size="35" />
 <input type="submit" class="horde-default" value="<?php echo _("Search") ?>" />
 <input type="submit" class="horde-button" style="display:none" value="<?php echo _("Close") ?>" id="turba-minisearch-close" />
 <span class="iconImg" id="turba-minisearch-searching" style="display:none"></span>
</form>

<div id="turba-minisearch-results"></div>
