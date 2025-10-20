<?php
if (isset($_SESSION['user_id'])) {
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="/Healthcare_Dashboard/admin_dashboard.php">Healthcare Staff</a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <!-- Absolute paths ensure consistent navigation from any page -->
                <li class="nav-item"><a class="nav-link" href="/Healthcare_Dashboard/dashboard.php">Staff Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="/Healthcare_Dashboard/admin_dashboard.php">Admin Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="/Healthcare_Dashboard/panels/staff_panel.php">Staff</a></li>
                <li class="nav-item"><a class="nav-link" href="/Healthcare_Dashboard/panels/room_panel.php">Rooms</a></li>
                <li class="nav-item"><a class="nav-link" href="/Healthcare_Dashboard/panels/ward_panel.php">Wards</a></li>
            </ul>
            <a class="btn btn-outline-light" href="/Healthcare_Dashboard/auth/logout.php">Logout</a>
        </div>
    </div>
</nav>
<?php
}
?>