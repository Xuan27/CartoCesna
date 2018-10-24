<?php
require '../Library/DBHelper.php';
$DB = new DBHelper();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
    <link rel="stylesheet" href="../Master/css/indexBody.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
</head>
<body>
<form id="ideaForm">
    <select id="authors">
        <option>Juan Martinez</option>
        <option>Juan Alonso</option>
    </select>
    <div>
        <textarea id="idea" placeholder="Idea"></textarea>
    </div>
    <input type="submit" value="Add idea">
</form>

<table id="ideasTab">
    <tbody id="ideasTabBod">
    </tbody>
</table>


</body>
<script type="text/javascript">
    var ideaJSON;
    //Loads all ideas stored in the database to the table when the page is ready
    $(document).ready(function(event) {
        var selectIdea = '<?php $select = $DB->SP_SELECTALL_IDEA(); echo json_encode($select); ?>';
        populateTable(selectIdea, "selectAll");
    });

    //Gets the author's information from the input form
    var author = $("#authors option:selected").text();
    $("#authors").change(function () {
        $("#authors option:selected").each(function () {
            author = $(this).text();
        });
    });

    $("#ideaForm").submit(function (event) {
        var idea = $("#idea")[0].value;
        event.preventDefault();
        if(idea == ''){
            alert("Campo de idea vacio, proporcionar informacion");
            return;
        }
        $.ajax({
             type: "POST",
             url: "../Library/IdeasQuery.php",
             data: {"author": author,"idea": idea, "id": "", "type":"insert"},
             success:function(data) {
                 if(data){
                     populateTable(data, "insert");
                     console.log(ideaJSON);
                 }
             },
             error:function(requestObject) {
                 alert(requestObject.status);
             }
         });
    });

    function populateTable(data, queryType){
        $("#ideasTabBod").empty();
        if(queryType == "insert")
            alert("Idea creada satisfactoriamente");
        else if(queryType == "delete")
            alert("Idea eliminada satisfactoriamente");
        ideaJSON = JSON.parse(data);
        for(var idea = 0; idea < ideaJSON.length;idea++) {
            var ideaTable = $("#ideasTab")[0];

            var ideaRow = ideaTable.insertRow(idea);

            var cellAuthor = ideaRow.insertCell(0);
            var cellIdea = ideaRow.insertCell(1);
            var cellTime = ideaRow.insertCell(2);
            var cellID = ideaRow.insertCell(3);
            cellID.style.display = "none";
            $(cellID).addClass("ID");

            cellAuthor.innerHTML = ideaJSON[idea].Author;
            cellIdea.innerHTML = ideaJSON[idea].Idea;
            cellTime.innerHTML = ideaJSON[idea].Time;
            cellID.innerHTML = ideaJSON[idea].ID;
        }
    }


        //Retrieves the id of the table row (last child -> ID)
        $("#ideasTab").delegate('tr', 'click', function(event) {
            var id = event.currentTarget.lastChild.innerHTML;
            $.ajax({
                type: "POST",
                url: "../Library/IdeasQuery.php",
                data: {"author": "","idea": "", "id":id, "type":"delete"},
                success:function(data) {
                    if(data){
                        populateTable(data, "delete");
                    }
                },
                error:function(requestObject) {
                    alert(requestObject.status);
                }
            });
        });



</script>
</html>