$(document).ready(function () {
    let password1 = $('#password1');
    let password2 = $('#password2');
    let submit = $('#submitPassword');

    password1.on('keyup', keyUp);
    password2.on('keyup', keyUp);

    function keyUp() {
        let correct = (password1.val() === password2.val()) && password1.val().length > 4;
        submit.prop('disabled', !correct);
    }
});
