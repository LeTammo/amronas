$(document).ready(function () {

    $('#editTable').click(function () {
        $('.toggleOnEdit').each(function () {
            $(this).toggleClass('d-none');
        })
    })

    $('.remove-parent').click(function () {
        $(this).parent().remove();
    })
});