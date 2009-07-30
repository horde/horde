function addTag()
{
    if (!$('addtag').value.blank()) {
        var params = new Object();
        params.action = 'add';
        params[Ansel.ajax.tagActions.input] = $('addtag').value;
        new Ajax.Request(Ansel.ajax.tagActions.url,
                         {
                            method: 'post',
                            parameters: params,
                            onComplete: function(r) {
                                $('addtag').value = "";
                                if (r.responseJSON.response == 1) {
                                    $('tags').update(r.responseJSON.message);
                                }
                            }      
                         });
    }

    return true;
}

function removeTag(tagid)
{
    var params = new Object();
    params.action = 'remove';
    params[Ansel.ajax.tagActions.input] = tagid;
    new Ajax.Request(Ansel.ajax.tagActions.url,
                    {
                        method: 'post',
                        parameters: params,
                        onComplete: function(r) {
                            if (r.responseJSON.response == 1) {
                                $('tags').update(r.responseJSON.message);
                            }
                        }
                    });

    return true;
}

// Since onsubmit is never called when submitting programatically we
// can use this function to add tags when we press enter on the tag form.
function submitcheck()
{
    return !addTag();
}