function resizeImage(v)
{
    $('edit_image').width = v;
    $('width').value = $('edit_image').width;
    $('height').value = $('edit_image').height;

}

function resetImage()
{
    Ansel.slider.setValue(Ansel.image_geometry['height']);
}