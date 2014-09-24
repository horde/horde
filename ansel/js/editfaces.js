/**
 * Javascript for handling edit faces actions.
 */
var AnselEditFaces = {

    remove: function(params)
    {
        HordeCore.doAction('deleteFaces', params);
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
