<h1 class="header">
 <?php printf(_("Diff for %s between %s and %s"), Horde::link($this->pageUrl()) . htmlspecialchars($this->pageName()) . '</a>', $version, htmlspecialchars($this->version())) ?>
</h1>

<div class="text headerbox" style="padding:5px">
 <pre><?php echo $this->getDiff($version, 'inline') ?></pre>
</div>
