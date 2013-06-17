<form id="turba_minisearch" action="#">
 <?php echo Horde::label('search', _("Search for: ")) ?>
 <input id="turba_minisearch_search" size="35" />
 <input type="submit" class="horde-default" value="<?php echo _("Search") ?>" />
 <input type="submit" class="horde-button" style="display:none" value="<?php echo _("Close") ?>" id="turba_minisearch_close" />
 <span class="iconImg" id="turba_minisearch_searching" style="display:none"></span>
</form>

<div id="turba_minisearch_results"></div>
