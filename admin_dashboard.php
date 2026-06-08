<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: Login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "persada_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* BASIC ADMIN DASHBOARD DATA */
$totalMembers = $conn->query("SELECT COUNT(*) AS total FROM students")->fetch_assoc()['total'];

$totalEvents = 0;
$checkEventsTable = $conn->query("SHOW TABLES LIKE 'events'");
if ($checkEventsTable->num_rows > 0) {
    $totalEvents = $conn->query("SELECT COUNT(*) AS total FROM events")->fetch_assoc()['total'];
}

$totalRegistrations = 0;
$checkRegistrationTable = $conn->query("SHOW TABLES LIKE 'event_registration'");
if ($checkRegistrationTable->num_rows > 0) {
    $totalRegistrations = $conn->query("SELECT COUNT(*) AS total FROM event_registration")->fetch_assoc()['total'];
}

$adminName = $_SESSION['admin_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>PERSADA Admin Dashboard</title>

<link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins', sans-serif;
}

:root{
    --body-color:#f4f7fb;
    --sidebar-color:#ffffff;

    /* ADMIN THEME */
    --primary-color:#2563eb;
    --primary-dark:#1e40af;
    --secondary-color:#06b6d4;
    --primary-light:#eff6ff;

    --text-color:#64748b;
    --title-color:#0f172a;
    --card-color:#ffffff;
    --border-color:rgba(37,99,235,.12);
    --shadow:0 18px 40px rgba(15,23,42,.08);
    --tran:all .3s ease;
}

body{
    min-height:100vh;
    background:var(--body-color);
    transition:var(--tran);
}

body.dark{
    --body-color:#0f172a;
    --sidebar-color:#111827;
    --primary-light:#1e293b;
    --text-color:#cbd5e1;
    --title-color:#ffffff;
    --card-color:#1e293b;
    --border-color:rgba(255,255,255,.08);
}

/* =========================
   ADMIN SIDEBAR
========================= */
.sidebar{
    position:fixed;
    top:0;
    left:0;
    height:100%;
    width:260px;
    padding:14px;
    background:var(--sidebar-color);
    box-shadow:8px 0 30px rgba(15,23,42,.08);
    transition:var(--tran);
    z-index:100;
}

.sidebar.close{
    width:88px;
}

.sidebar header{
    position:relative;
}

.image-text{
    display:flex;
    align-items:center;
}

.logo-box{
    width:45px;
    height:45px;
    border-radius:14px;
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:22px;
    font-weight:800;
    margin-right:12px;
    box-shadow:0 10px 25px rgba(37,99,235,.28);
}

.logo-text{
    display:flex;
    flex-direction:column;
}

.logo-text .name{
    font-size:20px;
    font-weight:800;
    color:var(--title-color);
    letter-spacing:.5px;
}

.logo-text .profession{
    font-size:13px;
    color:var(--text-color);
    font-weight:600;
}

.sidebar.close .text,
.sidebar.close .logo-text{
    opacity:0;
    pointer-events:none;
}

.toggle{
    position:absolute;
    top:50%;
    right:-27px;
    transform:translateY(-50%) rotate(180deg);
    width:30px;
    height:30px;
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    color:white;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    font-size:22px;
    transition:var(--tran);
    box-shadow:0 8px 20px rgba(37,99,235,.3);
}

.sidebar.close .toggle{
    transform:translateY(-50%) rotate(0);
}

.menu-bar{
    height:calc(100% - 70px);
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    margin-top:35px;
}

.sidebar li{
    list-style:none;
    height:52px;
    margin-top:10px;
    display:flex;
    align-items:center;
}

.sidebar li a{
    width:100%;
    height:100%;
    display:flex;
    align-items:center;
    text-decoration:none;
    border-radius:16px;
    transition:var(--tran);
}

.sidebar .icon{
    min-width:60px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:22px;
    color:var(--text-color);
}

.sidebar .text{
    font-size:15px;
    font-weight:700;
    color:var(--text-color);
    white-space:nowrap;
    transition:var(--tran);
}

.sidebar li a:hover,
.sidebar li a.active{
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    box-shadow:0 12px 25px rgba(37,99,235,.25);
}

.sidebar li a:hover .icon,
.sidebar li a:hover .text,
.sidebar li a.active .icon,
.sidebar li a.active .text{
    color:white;
}

/* DARK MODE */
.mode{
    background:var(--primary-light);
    border-radius:16px;
    position:relative;
}

.toggle-switch{
    position:absolute;
    right:0;
    height:100%;
    min-width:60px;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
}

.switch{
    width:40px;
    height:22px;
    background:#cbd5e1;
    border-radius:30px;
    position:relative;
}

.switch::before{
    content:'';
    position:absolute;
    width:15px;
    height:15px;
    background:white;
    border-radius:50%;
    top:50%;
    left:5px;
    transform:translateY(-50%);
    transition:var(--tran);
}

body.dark .switch::before{
    left:20px;
}

/* =========================
   MAIN
========================= */
.main{
    margin-left:260px;
    padding:35px;
    min-height:100vh;
    background:var(--body-color);
    transition:var(--tran);
}

.sidebar.close ~ .main{
    margin-left:88px;
}

.page-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
}

.page-title{
    color:var(--title-color);
    font-size:34px;
    font-weight:800;
}

.page-subtitle{
    color:var(--text-color);
    margin-top:5px;
    font-size:15px;
}

.admin-badge{
    background:var(--card-color);
    padding:12px 18px;
    border-radius:50px;
    color:var(--primary-color);
    font-weight:700;
    box-shadow:var(--shadow);
    border:1px solid var(--border-color);
    display:flex;
    align-items:center;
    gap:8px;
}

/* HERO */
.admin-hero{
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    border-radius:30px;
    padding:35px;
    color:white;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 20px 45px rgba(37,99,235,.28);
    margin-bottom:30px;
    position:relative;
    overflow:hidden;
}

.admin-hero::after{
    content:"";
    position:absolute;
    width:220px;
    height:220px;
    right:-70px;
    bottom:-90px;
    background:rgba(255,255,255,.14);
    border-radius:50%;
}

.admin-hero h2{
    font-size:30px;
    margin-bottom:10px;
}

.admin-hero p{
    max-width:650px;
    color:rgba(255,255,255,.9);
    line-height:1.7;
}

.hero-icon{
    width:95px;
    height:95px;
    border-radius:28px;
    background:rgba(255,255,255,.18);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:48px;
    position:relative;
    z-index:1;
}
/* ADMIN ANALYTICS CARDS */
.dashboard-cards{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:22px;
    margin-bottom:30px;
}

.analytics-card{
    background:rgba(255,255,255,.95);
    border-radius:28px;
    padding:26px;
    min-height:190px;
    box-shadow:0 18px 40px rgba(15,23,42,.08);
    border:1px solid rgba(37,99,235,.12);
    position:relative;
    overflow:hidden;
    transition:.3s ease;
}

.analytics-card:hover{
    transform:translateY(-8px);
    box-shadow:0 25px 50px rgba(37,99,235,.16);
}

.analytics-card::after{
    content:"";
    position:absolute;
    width:120px;
    height:120px;
    right:-45px;
    bottom:-45px;
    border-radius:50%;
    background:rgba(37,99,235,.07);
}

.analytics-top{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:20px;
}

.analytics-icon{
    width:58px;
    height:58px;
    border-radius:20px;
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:30px;
    box-shadow:0 12px 25px rgba(37,99,235,.25);
}

.analytics-label{
    color:#2563eb;
    background:#eff6ff;
    padding:7px 13px;
    border-radius:20px;
    font-size:15px;
    font-weight:700;
}

.analytics-card h3{
    color:#0f172a;
    font-size:32px;
    font-weight:800;
    margin-bottom:5px;
}

.analytics-card p{
    color:#64748b;
    font-size:14px;
    font-weight:600;
    margin-bottom:18px;
}

.mini-bar{
    width:100%;
    height:8px;
    border-radius:20px;
    background:#e5e7eb;
    overflow:hidden;
}

.mini-bar span{
    display:block;
    height:100%;
    border-radius:20px;
    background:linear-gradient(135deg,#2563eb,#06b6d4);
}

.mini-bar.cyan span{
    background:linear-gradient(135deg,#06b6d4,#22d3ee);
}

.mini-bar.purple span{
    background:linear-gradient(135deg,#7c3aed,#3b82f6);
}

.status-card{
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    text-align:center;
}

.status-ring{
    width:100px;
    height:100px;
    border-radius:50%;
    background:
        conic-gradient(#2563eb 0deg, #06b6d4 360deg);
    display:flex;
    align-items:center;
    justify-content:center;
    margin-bottom:15px;
    position:relative;
}

.status-ring::before{
    content:"";
    position:absolute;
    width:72px;
    height:72px;
    background:white;
    border-radius:50%;
}

.status-ring span{
    position:relative;
    z-index:1;
    color:#0f172a;
    font-size:20px;
    font-weight:800;
}

/* SECTIONS */
.admin-grid{
    display:grid;
    grid-template-columns:1.3fr .7fr;
    gap:25px;
}

.admin-card{
    background:var(--card-color);
    border-radius:28px;
    padding:28px;
    box-shadow:var(--shadow);
    border:1px solid var(--border-color);
}

.admin-card h3{
    color:var(--title-color);
    font-size:22px;
    margin-bottom:18px;
}

.quick-actions{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:18px;
}

.action-item{
    background:var(--primary-light);
    border-radius:22px;
    padding:22px;
    text-decoration:none;
    display:flex;
    align-items:center;
    gap:15px;
    transition:.3s ease;
    border:1px solid var(--border-color);
}

.action-item:hover{
    transform:translateY(-5px);
    background:linear-gradient(135deg,#2563eb,#06b6d4);
}

.action-item i{
    width:45px;
    height:45px;
    border-radius:15px;
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:23px;
}

.action-item span{
    color:var(--title-color);
    font-weight:700;
}

.action-item:hover span{
    color:white;
}

.action-item:hover i{
    background:rgba(255,255,255,.18);
}

.system-box{
    background:var(--primary-light);
    border-radius:22px;
    padding:22px;
    border:1px solid var(--border-color);
}

.system-box strong{
    display:block;
    color:var(--title-color);
    font-size:18px;
    margin-bottom:6px;
}

.system-box span{
    color:var(--text-color);
    font-size:14px;
}

.status-dot{
    width:11px;
    height:11px;
    background:#22c55e;
    border-radius:50%;
    display:inline-block;
    margin-right:8px;
}

@media(max-width:1100px){
    .dashboard-cards{
        grid-template-columns:repeat(2,1fr);
    }

    .admin-grid{
        grid-template-columns:1fr;
    }
}

@media(max-width:700px){
    .dashboard-cards,
    .quick-actions{
        grid-template-columns:1fr;
    }

    .admin-hero{
        flex-direction:column;
        align-items:flex-start;
        gap:20px;
    }
}
</style>
</head>

<body>

<nav class="sidebar">
    <header>
        <div class="image-text">
            <div class="logo-box">A</div>

            <div class="text logo-text">
                <span class="name">PERSADA</span>
                <span class="profession">Administration</span>
            </div>
        </div>

        <i class='bx bx-chevron-right toggle'></i>
    </header>

    <div class="menu-bar">
        <div class="menu">
            <ul class="menu-links">

                <li>
                    <a href="admin_dashboard.php" class="active">
                        <i class='bx bx-home-alt icon'></i>
                        <span class="text">Dashboard</span>
                    </a>
                </li>

                <li>
                    <a href="member_management.php">
                        <i class='bx bx-group icon'></i>
                        <span class="text">Members</span>
                    </a>
                </li>

                <li>
                    <a href="event_management.php">
                        <i class='bx bx-calendar-event icon'></i>
                        <span class="text">Events</span>
                    </a>
                </li>

                <li>
                    <a href="attendance_management.php">
                        <i class='bx bx-qr-scan icon'></i>
                        <span class="text">Attendance</span>
                    </a>
                </li>

                <li>
                    <a href="report_management.php">
                        <i class='bx bx-bar-chart-alt-2 icon'></i>
                        <span class="text">Reports</span>
                    </a>
                </li>

              

            </ul>
        </div>

        <div class="bottom-content">
            <li>
                <a href="Login.php">
                    <i class='bx bx-log-out icon'></i>
                    <span class="text">Logout</span>
                </a>
            </li>

            <li class="mode">
                <i class='bx bx-moon icon moon'></i>
                <span class="mode-text text">Dark mode</span>

                <div class="toggle-switch">
                    <span class="switch"></span>
                </div>
            </li>
        </div>
    </div>
</nav>

<div class="main">

    <div class="page-header">
        <div>
            <h1 class="page-title">Admin Dashboard</h1>
            <p class="page-subtitle">Manage PERSADA members, events, attendance and reports.</p>
        </div>

        <div class="admin-badge">
            <i class='bx bx-user-circle'></i>
            <?php echo $adminName; ?>
        </div>
    </div>

    <div class="admin-hero">
        <div>
            <h2>Welcome Back, <?php echo $adminName; ?> 👋</h2>
            <p>
                This admin dashboard allows PERSADA committee members to monitor student membership,
                manage activities, track event attendance and generate participation reports.
            </p>
        </div>

        <div class="hero-icon">
            <i class='bx bx-shield-quarter'></i>
        </div>
    </div>

<div class="dashboard-cards analytics-cards">

    <div class="analytics-card">
        <div class="analytics-top">
            <div class="analytics-icon">
                <i class='bx bx-group'></i>
            </div>
            <span class="analytics-label">Members</span>
        </div>

        <h3><?php echo $totalMembers; ?></h3>
        <p>Total registered PERSADA members</p>

        <div class="mini-bar">
            <span style="width:75%"></span>
        </div>
    </div>

    <div class="analytics-card">
        <div class="analytics-top">
            <div class="analytics-icon">
                <i class='bx bx-calendar-event'></i>
            </div>
            <span class="analytics-label">Events</span>
        </div>

        <h3><?php echo $totalEvents; ?></h3>
        <p>Events created by committee</p>

        <div class="mini-bar cyan">
            <span style="width:55%"></span>
        </div>
    </div>

    <div class="analytics-card">
        <div class="analytics-top">
            <div class="analytics-icon">
                <i class='bx bx-user-check'></i>
            </div>
            <span class="analytics-label">Registrations</span>
        </div>

        <h3><?php echo $totalRegistrations; ?></h3>
        <p>Total event participation records</p>

        <div class="mini-bar purple">
            <span style="width:45%"></span>
        </div>
    </div>

    <div class="analytics-card status-card">
        <div class="status-ring">
            <span>100%</span>
        </div>

        <h3>Active</h3>
        <p>Admin portal status</p>
    </div>

</div>

    <div class="admin-grid">

        <div class="admin-card">
            <h3>Quick Actions</h3>

            <div class="quick-actions">

                <a href="member_management.php" class="action-item">
                    <i class='bx bx-group'></i>
                    <span>Manage Members</span>
                </a>

                <a href="event_management.php" class="action-item">
                    <i class='bx bx-calendar-plus'></i>
                    <span>Create / Manage Events</span>
                </a>

                <a href="attendance_management.php" class="action-item">
                    <i class='bx bx-qr-scan'></i>
                    <span>Attendance Management</span>
                </a>

                <a href="report_management.php" class="action-item">
                    <i class='bx bx-bar-chart-alt-2'></i>
                    <span>Generate Reports</span>
                </a>

            </div>
        </div>

        <div class="admin-card">
            <h3>System Overview</h3>

            <div class="system-box">
                <strong>
                    <span class="status-dot"></span>
                    Admin Portal Active
                </strong>
                <span>
                    Only authorised PERSADA committee members can access this admin dashboard.
                </span>
            </div>
        </div>

    </div>

</div>

<script>
const body = document.querySelector("body");
const sidebar = document.querySelector(".sidebar");
const toggle = document.querySelector(".toggle");
const modeSwitch = document.querySelector(".toggle-switch");
const modeText = document.querySelector(".mode-text");

toggle.addEventListener("click", () => {
    sidebar.classList.toggle("close");
});

modeSwitch.addEventListener("click", () => {
    body.classList.toggle("dark");

    if(body.classList.contains("dark")){
        modeText.innerText = "Light mode";
    }else{
        modeText.innerText = "Dark mode";
    }
});
</script>

</body>
</html>