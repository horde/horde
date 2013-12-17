<div class="header leftAlign">
 <?php echo $this->h($this->title) ?> (<?php echo $this->count ?>)
<?php if ($this->count): ?>
 <a id="quicksearchL" href="<?php echo $this->searchUrl ?>" title="<?php echo _("Search") ?>"><?php echo $this->searchImg ?></a>
 <div id="quicksearch" style="display:none">
  <input type="text" name="quicksearchT" id="quicksearchT" for="notes_body" empty="notes_empty" />
  <small>
   <a id="quicksearchX" title="<?php echo _("Close Search") ?>" href="#">X</a>
   <?php echo $this->searchUrl->link() . _("More Options...") . '</a>' ?>
  </small>
 </div>
<?php endif ?>
</div>
