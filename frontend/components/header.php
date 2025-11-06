<?php
// header.php
?>
<header class="dashboard-header">
    <div class="header-left">
        <div class="logo">
            <i class="fas fa-file-invoice-dollar"></i>
            <h1>InvoiceManager</h1>
        </div>
    </div>

    <div class="header-right">
        <div class="user-profile" id="profileDropdownBtn">
            <div class="user-avatar">
                <img src="https://ui-avatars.com/api/?name=Admin+User&background=4F46E5&color=fff" alt="Admin User">
            </div>
            <span class="user-name">Admin User</span>
            <i class="fas fa-chevron-down"></i>

            <!-- Dropdown Menu -->
            <div class="profile-dropdown" id="profileDropdown">
                <div class="dropdown-header">
                    <strong>Admin User</strong>
                    <span>admin@gmail.com</span>
                </div>

                <div class="dropdown-item" onclick="window.location.href='modules/logout.php'">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </div>
            </div>
        </div>
    </div>
</header>