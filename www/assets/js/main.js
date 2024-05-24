
$( document ).ready(function() {

    $('th').click(function() {
        $('th').addClass("hidesort");
        $(this).removeClass("hidesort");
        $(this).addClass("showsort");

        var table = $("#datatable").eq(0);
        var rows = table.find('tr').toArray().sort(comparer($(this).index()))
        this.asc = !this.asc
        $(this).find('i').toggleClass('fa-arrow-down-short-wide fa-arrow-up-short-wide');
        if (!this.asc){
            rows = rows.reverse()
            
        }
        for (var i = 0; i <= rows.length; i++){table.append(rows[i])}
    })
    
});

function comparer(index) {
    return function(a, b) {
        var valA = getCellValue(a, index), valB = getCellValue(b, index)
        return $.isNumeric(valA) && $.isNumeric(valB) ? valA - valB : valA.toString().localeCompare(valB)
    }
}
function getCellValue(row, index){ return $(row).children('td').eq(index).text() }

function showMenu() {
    var x = document.getElementById("myTopnav");
    if (x.className === "topnav") {
        x.className += " responsive";
    } else {
        x.className = "topnav";
    }
}