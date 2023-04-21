$(document).ready(function () {

    $('#table-filter').keyup(function (event) {
        tableFilter($(this), $(this)[0].value);
    })

    $('#selectGenre').change(function (event) {
        tableFilter($(this), event.target.value);
    })
});

function tableFilter(event, value) {
    let table = document.getElementById(event.data('for'));
    let tr = table.getElementsByTagName("tr");

    let filter = value.toUpperCase();

    let td, i, txtValue;
    for (i = 0; i < tr.length; i++) {
        td = tr[i].getElementsByTagName("td")[event.data('row')];
        if (td) {
            txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}