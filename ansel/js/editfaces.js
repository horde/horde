/**
 */

var AnselEditFaces = {

    delete: function(params)
    {
        HordeCore.doAction('deleteFaceNames', params);
        $('face' + params.face_id).remove();
    },

    set: function(params)
    {
        params.face_name = $F('facename' + params.face_id);
        HordeCore.doAction('setFaceName', params, {
            callback: function(r) {
                $('faces_widget_content').update(r.response);
            }
        });
    }

}
