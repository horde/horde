<div id="newArticleForm" class="newArticleForm" style="display:none;">
  <h2><?php echo _("New Wiki Article") ?></h2>

  <form action="new.php" method="post">

    <label for="page_title"><?php echo _("Page Title") ?>:</label>
    <input type="text" name="page_title" id="page_title" class="page_title"/>

    <input type="submit" class="horde-default" onclick="console.log($('newArticleCreateButton').previous('#page_title').getValue())" value="<?php echo _("Create") ?>" id="newArticleCreateButton" />
    <input type="button" class="horde-default" onclick="RedBox.close();RedBox.getWindow().down().remove();" value="<?php echo _("Cancel") ?>" id="newArticleCancelButton" />

  </form>

</div>
