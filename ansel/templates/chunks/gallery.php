<div id="anselGalleryDialog" class="anselDialog">

  <form method="post" name="gallery" id="anselGalleryForm" $action="<?php echo Horde::selfUrl() ?>" >
    <?php Horde_Util::pformInput() ?>
    <input type="hidden" id="actionID" name="actionID" value="save" />
    <input type="hidden" id="anselGalleryFormParentId" name="gallery_parent" value="" />
    <input type="hidden" id="anselGalleryFormId" name="gallery_id" value="" />

    <!-- Display Name -->
    <div>
      <p><label><?php echo _("Gallery Title")?>:<br />
       <input id="anselGalleryFormTitle" name="gallery_name" type="text" value="" size="50" maxlength="100" />
      </label></p>
    </div>

    <!-- Description -->
    <div><p>
      <label><?php echo _("Gallery Description") ?>:<br />
      <textarea id="anselGalleryFormDescription" name="gallery_desc" cols="50" rows="5"></textarea></label>
    </p></div>

  <hr />

    <!-- Parent -->
    <p>
      <label for="anselGalleryFormParent"><?php echo _("Gallery Parent") ?></label>
      <select name="gallery_parent" id="anselGalleryFormParent">
       <option value=""><?php echo _("Top Level Gallery") ?></option>
      </select>
    </p>

    <!-- Display Mode -->
    <p>
      <label for="anselGalleryFormViewMode"><?php echo _("Display Mode") ?></label>
      <select name="view_mode" id="anselGalleryFormViewMode">
        <option value="Normal"><?php echo _("Normal") ?></option>
        <option value="Date"><?php echo _("Group By Date") ?></option>
      </select>
    </p>

    <!-- Slug -->
    <p>
      <label for="anselGalleryFormSlug" id="slug_flag"><?php echo _("Gallery Slug") ?></label>
      <input name="gallery_slug" id="anselGalleryFormSlug" type="text" value="" size="50" class="anselEventValue"/>
      <br /><?php echo _("Slug names may contain only letters, numbers, @, or _ (underscore).") ?>
    </p>

    <!-- Tags -->
    <p>
      <label for="anselGalleryFormTags"><?php echo _("Gallery Tags") ?></label>
      <input id="anselGalleryFormTags" name="gallery_tags" type="text" value="" size="50" class="anselEventValue" />
      <br /><?php echo _("Separate tags with commas."); ?>
    </p>

  <!-- Download ability -->
  <p>
    <!--@TODO  Need to check if this pref is locked. -->
    <label for="anselGalleryDownload"><?php echo _("Who should be allowed to download original photos?") ?></label>
    <select name="gallery_download" id="anselGalleryDownload">
      <option value="all"><?php echo _("Anyone") ?></option>
      <option value="authenticated"><?php echo _("Authenticated users") ?></option>
      <option value="edit"><?php echo _("Users with edit permissions") ?></option>
    </select>
  </p>

  <!-- Submission -->
  <div class="anselFormActions">
    <input type="button" class="anselGallerySave horde-default" value="<?php echo _("Save") ?>" />
    <input type="button" value="<?php echo _("Delete") ?>" class="anselGalleryDelete horde-delete" />
    <span class="anselSeparator"><?php echo _("or") ?></span> <a class="horde-cancel"><?php echo _("Cancel") ?></a>
  </div>

  </form>

</div>