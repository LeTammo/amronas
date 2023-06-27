$(document).ready(function () {
    $('.table-input').keyup(function () {
        tableFilter($(this));
    })
});

function tableFilter(event) {
    let table = document.getElementById('movieList');
    let tr = table.getElementsByTagName("tr");

    let filter = event.val().split(' ').filter(function (el) {
        return el !== '';
    });

    let td, i, txtValue;
    for (i = 0; i < tr.length; i++) {
        td = tr[i].getElementsByTagName("td")[event.closest("th").index()];
        if (td) {
            txtValue = td.textContent || td.innerText;
            tr[i].style.display = getDisplay(txtValue, filter)
        }
    }
}

function getDisplay(txtValue, filter) {
    if (filter.length === 0) {
        return "";
    }

    for (let i = 0; i < filter.length; i++) {
        if (txtValue.toLowerCase().indexOf(filter[i].toLowerCase()) > -1) {
            return "";
        }
    }
    return "none";
}