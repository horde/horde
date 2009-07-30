document.observe('dom:loaded', function() {
    Ansel.deleteFace = function(image_id, face_id)
    {
        new Ajax.Request(Ansel.ajax.editFaces.url, 
            {
                method: 'post',
                parameters: {
                                 action: 'delete',
                                 image: image_id,
                                 face: face_id
                            }
            });
        $('face' + face_id).remove();
    };
    
    Ansel.setFaceName = function(image_id, face_id)
    {
        new Ajax.Request(Ansel.ajax.editFaces.url,
                         {    
                              method: 'post',
                              parameters: 
                              {
                                  action: 'setname',
                                  face: face_id,
                                  image: image_id,
                                  facename: encodeURIComponent($F('facename' + face_id))
                              },
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
        new Ajax.Request(Ansel.ajax.editFaces.url,
                         {
                             method: 'post',
                             parameters:
                             {
                                 action: 'process',
                                 image: image_id
                             },
                             onComplete: function(r) {
                                 if (r.responseJSON.response == 1) {
                                     $('faces_widget_content').update(r.responseJSON.message);
                                 }
                             }
                         }
        );
    };
});