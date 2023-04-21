$(document).ready(function () {

    $('.numericSort').click(function (event) {
        tableSort(event.target, true);
    })

    $('.alphanumericSort').click(function (event) {
        tableSort(event.target, false);
    })

    function tableSort(target, isNumeric) {
        let rows, i, x, y, shouldSwitch;
        let table = target.closest('table');

        let switchCount = 0;
        let switching = true;

        let dir = "asc";

        while (switching) {
            switching = false;
            rows = table.rows;

            for (i = 1; i < (rows.length - 1); i++) {
                shouldSwitch = false;

                x = rows[i].getElementsByTagName("TD")[target.cellIndex];
                y = rows[i + 1].getElementsByTagName("TD")[target.cellIndex];

                // if rows are numeric
                // if rows are alphanumeric
                if (dir === "asc") {
                    if (isNumeric) {
                        if (Number(x.innerHTML) > Number(y.innerHTML)) {
                            shouldSwitch = true;
                            break;
                        }
                    }

                    if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                        shouldSwitch = true;
                        break;
                    }
                } else if (dir === "desc") {
                    if (isNumeric) {
                        if (Number(x.innerHTML) < Number(y.innerHTML)) {
                            shouldSwitch = true;
                            break;
                        }
                    }

                    if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                        shouldSwitch = true;
                        break;
                    }
                }
            }

            if (shouldSwitch) {
                rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                switching = true;
                switchCount++;
            } else {
                if (switchCount === 0 && dir === "asc") {
                    dir = "desc";
                    switching = true;
                }
            }
        }
    }
});