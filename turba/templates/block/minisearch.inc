<form name="turba_minisearch" id="turba_minisearch" action="<?php echo Horde::url('minisearch.php') ?>" target="turba_minisearch_iframe">
 <?php Horde_Util::pformInput() ?>
 <?php echo Horde::label('search', _("Search for: ")) ?>
 <input name="search" id="search" size="35" />
 <input type="hidden" name="addressbooks" id="addressbooks" value="<?php echo $calendars; ?>" />
 <input type="submit" class="button" value="<?php echo _("Search") ?>" />
 <input type="submit" class="button" style="display:none" value="<?php echo _("Close") ?>" id="turba_minisearch_close" />
 <span class="iconImg" id="turba_minisearch_searching" style="display:none"></span>
</form>

<div>
 <iframe id="turba_minisearch_iframe" name="turba_minisearch_iframe" style="display:none" src="about:blank"></iframe>
</div>
