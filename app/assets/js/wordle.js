let finished = false;
let keysDisabled = false;

$(document).ready(function () {

    let focus = null;
    let game = $('#wordle-game');

    if (game.data('finished') === 1) {
        finished = true;
    } else {
        focus = $(".wordle-try.unfinished").first().children().first();
        focus.toggleClass("active");
    }

    // onCellFocus
    $(".wordle-cell").click(function () {
        if (finished)
            return;

        userChangeFocus($(this));
    });

    // onKeyPressed
    $(document).bind("keyup", function (e) {
        evaluateKey(e.keyCode);
    });

    $("#keyboard .key").click(function () {
        let keyCode = $(this).text().charCodeAt(0);
        console.log(keyCode);
        if ($(this).attr("id") === "enter-key") {
            keyCode = 13;
        } else if ($(this).attr("id") === "backspace-key") {
            keyCode = 8;
        }
        console.log(keyCode);

        evaluateKey(keyCode);
    });

    /**************************************************************
     Functions
     **************************************************************/

    const evaluateKey = (keyCode) => {
        if (keysDisabled || finished || !focus)
            return;

        switch (keyCode) {
            case 46:
                fillLetterToFocus(String.fromCharCode(keyCode));
                break;
            case 37:
                changeFocusToPrevious();
                break;
            case 39:
                changeFocusToNext();
                break;
            case 13:
                keysDisabled = true;
                submitTry();
                break;
            case 8:
                if (focus.children().first().text().length === 69 || focus.children().first().text().length === 0) {
                    removePreviousLetter();
                    changeFocusToPrevious();
                } else {
                    removeCurrentLetter();
                }
        }

        if (keyCode > 64 && keyCode < 91) {
            fillLetterToFocus(String.fromCharCode(keyCode));
            changeFocusToNext();
        }
    }

    const fillLetterToFocus = letter => {
        focus.children().first().text(letter);
    }

    const removePreviousLetter = () => {
        focus.prev().children().first().text("");
    }

    const removeCurrentLetter = () => {
        focus.children().first().text("");
    }

    const userChangeFocus = to => {
        if (focus.parent()[0] === to.parent()[0]) {
            changeFocus(to)
        }
    };

    const changeFocus = to => {
        to.toggleClass("active");

        if (focus) {
            focus.toggleClass("active");
        }

        focus = to;
    }

    const changeFocusToNextRow = () => {
        changeFocus(focus.parent().next().children().first())
    }

    const changeFocusToNext = () => {
        if (focus.next()[0]) {
            changeFocus(focus.next())
        } else return false;
    }

    const changeFocusToPrevious = () => {
        if (focus.prev()[0]) {
            changeFocus(focus.prev())
        }
    }

    const changeFocusToFirst = () => {
        changeFocus(focus.siblings().first())
    };

    const removeFocus = () => {
        focus.toggleClass("active");
        focus = null;
    };

    const submitTry = () => {
        let url = game.data("url");
        let unix = game.data("unix");
        let data = {'unix': unix, 'guess': focus.parent().children().children().text().toLowerCase()};

        waiting();
        $.getJSON(url, data)
            .done(response => onSuccess(response))
            .fail(response => onError(response));
    }

    let intervalID, pulsatingColor, x = 0;

    const waiting = () => {
        focus.toggleClass('waiting-overlay')
        intervalID = window.setInterval(function () {
            pulsatingColor = Math.abs(Math.cos(x += 0.1));
            focus.parent().css("opacity", pulsatingColor)
        }, 50);
    }

    const clearWaiting = () => {
        window.clearInterval(intervalID);
        focus.parent().css("opacity", 1)
    };

    const onSuccess = () => {
        location.reload();
    }

    const onError = response => {
        console.log(response.responseText)
        clearWaiting();
        keysDisabled = false;
        focus.parent().addClass('wordle-shake')
        setTimeout(() => focus.parent().removeClass("wordle-shake"), 2000);
    };
});

if (!String.prototype.trim) {
    String.prototype.trim = function () {
        return this.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, '');
    };
}
