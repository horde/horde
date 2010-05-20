function checkSlug()
{
    slug = document.gallery.gallery_slug.value;
    // Empty slugs are always allowed.
    if (!slug.length) {
        return true;
    }
    
    if (slug != Ansel.ajax.gallerySlugCheck.slugText) {
        var url = Ansel.ajax.gallerySlugCheck.url;
        var params = new Object();
        params.slug = slug;
        new Ajax.Request(url, {
            method: 'post',
            parameters: params,
            onComplete: function(transport) {
                var slugFlag = $('slug_flag');
                response = transport.responseJSON.response;
                if (response == 1) {
                    if (slugFlag.hasClassName('problem')) {
                        slugFlag.removeClassName('problem');
                    }
                    slugFlag.addClassName('success');
                    $('gallery_submit').enable();
                    // In case we try various slugs
                    Ansel.ajax.gallerySlugCheck.slugText = slug;
                } else {
                    if (slugFlag.hasClassName('success')) {
                        slugFlag.removeClassName('success');
                    }
                    slugFlag.addClassName('problem');
                    $('gallery_submit').disable();
                }
            }
        });
    } else {
	    if (slugFlag.hasClassName('problem')) {
	        slugFlag.removeClassName('problem');
	    }
	    slugFlag.addClassName('success');
	    $('gallery_submit').enable();
    }
}
