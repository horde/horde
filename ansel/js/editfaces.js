document.observe('dom:loaded', function() {
    Ansel.deleteFace = function(image_id, face_id)
    {
        new Ajax.Request(Ansel.ajax.editFaces.url + "/action=delete/post=values", 
            {
                method: 'post',
                parameters: { "values": "image=" + image_id + "/face=" + face_id }
            });
        $('face' + face_id).remove();
    };
    
    Ansel.setFaceName = function(image_id, face_id)
    {
        new Ajax.Request(Ansel.ajax.editFaces.url + "/action=setname/post=values",
                         {    
                              method: 'post',
                              parameters: { "values": "face=" + face_id + "/image=" + image_id + "/facename=" + encodeURIComponent($F('facename' + face_id)) },
                              onComplete: function(r) {
                                  if (r.responseJSON.response == 1) {
                                      $('faces_widget_content').update(r.responseJSON.message);
                                  }
                              }
                         }
        );
    };
    
    Ansel.doFaceEdit = function(image_id)
    {
        $('faces_widget_content').update(Ansel.ajax.editFaces.text.loading);
        new Ajax.Request(Ansel.ajax.editFaces.url + "/action=process/post=values",
                         {
                             method: 'post',
                             parameters: { "values": "image=" + image_id },
                             onComplete: function(r) {
                                 if (r.responseJSON.response == 1) {
                                     $('faces_widget_content').update(r.responseJSON.message);
                                 }
                             }
                         }
        );
    };
});