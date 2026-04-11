<?php
// finance-sidebar.php - Sidebar menu for Finance
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Sidebar</title>
</head>

<body>
    <aside>
        <h2>Finance</h2>
        <ul class="sidebar-menu">
            <li>
                <a href="#" class="sidebar-link" data-href="fees.php"><img src="res/img/fees-icon.png" alt="Fees Icon" class="menu-icon"> Fees</a>
                <div class="submenu">
                    <a href="#" class="submenu-link" data-href="collect-fees.php"><img src="res/img/collect-fees-icon.png" alt="Collect Fees Icon" class="submenu-icon"> Collect Fees</a>
                    <a href="#" class="submenu-link" data-href="view-fees.php"><img src="res/img/view-fees-icon.png" alt="View Fees Icon" class="submenu-icon"> View Fees</a>
                </div>
            </li>
            <li>
                <a href="#" class="sidebar-link" data-href="expenses.php"><img src="res/img/expenses-icon.png" alt="Expenses Icon" class="menu-icon"> Expenses</a>
                <div class="submenu">
                    <a href="#" class="submenu-link" data-href="add-expense.php"><img src="res/img/add-expense-icon.png" alt="Add Expense Icon" class="submenu-icon"> Add Expense</a>
                    <a href="#" class="submenu-link" data-href="view-expenses.php"><img src="res/img/view-expenses-icon.png" alt="View Expenses Icon" class="submenu-icon"> View Expenses</a>
                </div>
            </li>
        </ul>
    </aside>
</body>

</html>