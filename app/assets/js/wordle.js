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
    $(document).bind("keyup", function(e) {
        evaluateKey(e.keyCode);
    });

    $("#keyboard .key").click(function () {
        evaluateKey($(this).text().charCodeAt(0));
    });

    /**************************************************************
            Functions
     **************************************************************/

    const evaluateKey = (keyCode) => {
        //console.log(keyCode)

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
            case 10550:
                keysDisabled = true;
                submitTry();
                break;
            case 8:
            case 8592:
                removeLetterFromFocus();
                changeFocusToPrevious();
        }

        if (keyCode > 64 && keyCode < 91) {
            fillLetterToFocus(String.fromCharCode(keyCode));
            changeFocusToNext();
        }
    }

    const fillLetterToFocus = letter => {
        focus.children().first().text(letter);
    }

    const removeLetterFromFocus = () => {
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
            .fail(() => onError());
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

    const onSuccess = (data) => {
        location.reload();
        return;

        clearWaiting();
        changeFocusToFirst();
        focus.addClass(data[0]);
        changeFocusToNext();
        focus.addClass(data[1]);
        changeFocusToNext();
        focus.addClass(data[2]);
        changeFocusToNext();
        focus.addClass(data[3]);
        changeFocusToNext();
        focus.addClass(data[4]);
        changeFocusToNextRow();
    }

    const onError = () => {
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
