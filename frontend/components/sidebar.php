<?php
// sidebar.php

// Get current path from URL
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard'; // Default to dashboard if not set

// Function to check if a link matches the current page
function isActiveLink($link_path, $current_page) {
    // For dashboard and customers: exact match
    if (in_array($link_path, ['dashboard', 'customers'])) {
        return $link_path === $current_page;
    }
    
    // For category links like products, transactions: if current_page starts with link_path
    return strpos($current_page, $link_path) === 0;
}
?>

<div class="sidebar2">
  <h2>Menu</h2>
  <ul>
    <li><a href="#" data-link="dashboard" class="nav-link <?php echo isActiveLink('dashboard', $page) ? 'active' : ''; ?>">Dashboard</a></li>
    <li><a href="#" data-link="products/list" class="nav-link <?php echo isActiveLink('products', $page) ? 'active' : ''; ?>">Products</a></li>
    <li><a href="#" data-link="transactions/transaction_list" class="nav-link <?php echo isActiveLink('transactions', $page) ? 'active' : ''; ?>">Transactions</a></li>
    <li><a href="#" data-link="customers" class="nav-link <?php echo isActiveLink('customers', $page) ? 'active' : ''; ?>" id="sidebar-customers">Customers</a></li>
  </ul>
</div>