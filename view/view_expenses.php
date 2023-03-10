<!DOCTYPE html>

<head>
    <meta charset="UTF-8">
    <title>Expenses </title>
    <base href="<?= $web_root ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/style.css" rel="stylesheet" type="text/css" />
    <link href="css/expenses.css" rel="stylesheet" type="text/css" />
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400&family=Sen:wght@400;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="path/to/font-awesome/css/font-awesome.min.css">
    <script src="lib/jquery-3.6.3.min.js" type="text/javascript"></script>

</head>
<style>
    select{
		font-family: fontAwesome
	}
    </style>
<body>
<script>
/**
 * (par montant, par date, par initiateur et par titre, de façon croissante et décroissante).
 * 
 * arrow up : <i class="fa-solid fa-sort-up"></i>
 * 
 * arrow down : <i class="fa-solid fa-sort-down"></i>
 */
    const expenses = <?= $expenses_json ?>;
    let listExpenses;
    let sortColumn = 'title';
    let sortAscending = false;

    $(function(){

        //$('.data-item').hide();
        listExpenses = $('.expenses_json');
        showSelectSection();
        displayExpenses();

        $('#sort-select').on('change', function() {
            let value = $(this).val();
            sort(value);
        });

    });
    
    async function getExpenses(){
        try {
            expenses = await $.getJSON("operations/get_expenses_service")
            sortExpenses();
            displayExpenses();
        }catch(e){
            $('.data-item').html("<tr><td>Error encountered while retrieving the expenses!</td></tr>");
        }
        
    }
    

    function showSelectSection() {
        let html = "<select id='sort-select'>" +
            "<option value='title'> by title &#xf0de;</option>" +
            "<option value='title'> by title &#xf0dd;</option>" +
            "<option value='date'> by date &#xf0de;</option>" +
            "<option value='date'> by date &#xf0dd;</option>" +
            "<option value='initiator'> by creator &#xf0de;</option>" +
            "<option value='initiator'> by creator &#xf0dd;</option>" +
            "<option value='amount'> by amount &#xf0de;</option>" +
            "<option value='amount'> by amount &#xf0dd;</option>" +
            "</select>";
        $('#js_Select').html(html);

        
    }
    function sortExpenses(){
        expenses.sort(function (a,b) {
                    if (a[sortColumn] < b[sortColumn])
                        return sortAscending ? -1 : 1;
                    if (a[sortColumn] > b[sortColumn])
                        return sortAscending ? 1 : -1;
                    return 0;
                });
    }

    function sort(value){
        console.log(value);
       if(value === sortColumn){
        sortAscending = !sortAscending;
       }else{
        sortColumn = value;
        sortAscending = true;
       }
       sortExpenses();
       displayExpenses();
    }



    function displayExpenses(){
        let data_items ="";
        for (let exp of expenses){
            data_items += "<a href='Operation/detail_expense/" + exp.id + "'>";
            data_items += "<div class='data-card'>";
                data_items += "<h2 class='title'>"+exp.title + "</h2>";
                data_items += "<input type='hidden' name ='operationId' value='"+exp.id+"'";
                data_items += "<p class='amount'>"+ exp.amount + "€</p>";
                data_items += "<p class='initiator'>Paid by "+ exp.initiator + "</p>";
                data_items += "<p class='date'>"+ exp.operation_date + "</p>";
            data_items += "</div>";
            data_items += "</a>";
        }
        $('.data-item').html(data_items);
        
    }
</script>
    <?php include 'menu.html' ?>
    
    <div id="js_Select" >
    </div>
    <div class="cont">
        <div class="view_expenses">
            <button class="edit-btn">
                <a href="tricount/edit/<?= $tricount->get_id() ?>" style="text-decoration: none; color: black;">Edit</a>
            </button>
            <p>
                <?php echo $tricount->get_title(); ?> > Expenses
            </p>
            <?php
            $total_usr = 0;
            if (!is_null($operations_of_tricount)) {
                foreach ($operations_of_tricount as $operation):
                    if ($user->is_in_operation($operation->get_id()) || $user === $operation->getInitiator())
                        $total_usr += Operation::total_by_user($user->getUserId(), $operation->get_id());
                endforeach;
            } else {
                $total_usr = 0;
            }
            ?>
            <ul>
                <div class="container">
                    <ul class="data-list">
                        <?php
                        if (!empty($amounts)) {
                            foreach ($amounts as $amount):
                                if (!empty($amount)) {
                                    echo '<a href="/prwb_2223_c03/Operation/balance/' . $tricount->get_id() . '">';
                                    echo '<button class="view-balance-button">';
                                    echo '<i class="fa fa-exchange"></i>View Balance';
                                    echo '</button>';
                                    echo '</a>';
                                    echo '<div class="expenses_json"> </div>';
                                    echo '<li class="data-item">';
                                    foreach ($amount as $am):
                                        $id = $am->initiator;
                                        $id_expense = $am->id;
                                        echo '<a href="Operation/detail_expense/' . $id_expense . '">
                                            <div class="data-card">
                                                    <h2 class="title">' . $am->title . '</h2>
                                                    <input type="hidden" name="operationId" value="$id_expense">
                                                    <p class="amount">' . $am->amount . ' €</p>
                                                    <p class="initiator">Paid by ' . $am->getInitiator() . '</p>
                                                    <p class="date">' . $am->operation_date . '</p>
                                            </div>
                                        </a>';
                                    endforeach;
                                    echo '</li>';
                                    echo '<div class="totals">
                                        <div class="mytot">
                                            <p>MY TOTAL <br> ' . number_format($total_usr, 2) . ' €</p>
                                            </div>
                                            <div class="add-btn">
                                                <a href="/prwb_2223_c03/Operation/add/' . $tricount->get_id() . '">
                                                    <button class="add-button">+
                                                    </button>
                                                </a>
                                            </div>
                                            <div class="exp">
                                                <p>TOTAL EXPENSES <br> ' . number_format($totalExp["0"], 2) . ' €</p>
                                            </div>
                                        </div>';
                                }
                                if (empty($amount) && ($participants > 0) && $totalExp["0"] === null) {
                                    $totalExp["0"] = 0;
                                    echo "<h1 style='text-align:center;'>this is empty :(</h1>";
                                    echo '<a href="/prwb_2223_c03/Operation/add/' . $tricount->get_id() . '">';
                                    echo '<button class="view-balance-button">';
                                    echo 'ADD AN EXPENSE';
                                    echo '</button>';
                                    echo '</a>';
                                    echo '<div class="totals">
                                            <div class="mytot">
                                            <p>MY TOTAL <br> ' . number_format($total_usr, 2) . '  €</p>
                                        </div>
                                            <div class="exp">';
                                    echo '<p>TOTAL EXPENSES <br> ' . number_format($totalExp["0"], 2) . '  €</p>';
                                    echo '</div>
                                        </div>';
                                }
                                if (empty($amount) && ($participants == 0) && $totalExp["0"] === null) {
                                    $totalExp["0"] = 0;
                                    echo "<h1 style='text-align:center;'>you are alone loser :(</h1>";
                                    echo '<a href="tricount/edit/' . $tricount->get_id() . '">';
                                    echo '<button class="view-balance-button">';
                                    echo 'ADD FRIENDS';
                                    echo '</button>';
                                    echo '</a>';
                                    echo '<div class="totals">
                                            <div class="mytot">
                                                <p>MY TOTAL <br>' . number_format($total_usr, 2) . ' €</p>
                                            </div>
                                            <div class="add-btn">
                                                <a href="/prwb_2223_c03/Operation/add/' . $tricount->get_id() . '">
                                                    <button class="add-button">+
                                                    </button>
                                                </a>
                                            </div>
                                            <div class="exp">
                                            echo <p>TOTAL EXPENSES <br> ' . number_format($totalExp["0"], 2) . ' €</p>;
                                        </div>
                                        </div>';
                                }
                            endforeach;
                        }
                        ?>
                    </ul>

                </div>
            </ul>
        </div>
    </div>
    </div>


</body>

</html>