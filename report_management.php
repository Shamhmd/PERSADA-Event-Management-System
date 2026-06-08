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

$adminName = $_SESSION['admin_name'] ?? "PERSADA Administrator";

/* FILTER */
$search = "";
$filterEvent = "";
$filterStatus = "";
$filterFromDate = "";
$filterToDate = "";

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

if (isset($_GET['event_id'])) {
    $filterEvent = trim($_GET['event_id']);
}

if (isset($_GET['attendance_status'])) {
    $filterStatus = trim($_GET['attendance_status']);
}

if (isset($_GET['from_date'])) {
    $filterFromDate = trim($_GET['from_date']);
}

if (isset($_GET['to_date'])) {
    $filterToDate = trim($_GET['to_date']);
}

/* EVENT DROPDOWN */
$eventDropdown = $conn->query("
    SELECT event_id, event_name
    FROM events
    ORDER BY event_date DESC
");

/* TOP CARDS */
$totalParticipation = $conn->query("
    SELECT COUNT(*) AS total
    FROM event_registration
")->fetch_assoc()['total'];

$totalPresent = $conn->query("
    SELECT COUNT(*) AS total
    FROM attendance
    WHERE attendance_status = 'Present'
")->fetch_assoc()['total'];

$totalAbsent = $totalParticipation - $totalPresent;

if ($totalAbsent < 0) {
    $totalAbsent = 0;
}

$totalMembers = $conn->query("
    SELECT COUNT(*) AS total
    FROM students
")->fetch_assoc()['total'];

/* REPORT QUERY */
$reportQuery = "
    SELECT
        er.registration_id,
        er.student_id,
        er.event_id,

        s.name AS student_name,
        s.matric_number,
        s.email,
        s.phone_number,
        s.faculty,
        s.profile_picture,

        e.event_name,
        e.event_category,
        e.event_date,
        e.event_time,
        e.venue,

        a.attendance_status,
        a.scan_time,

        CASE
            WHEN a.attendance_status = 'Present' THEN 'Attended'
            ELSE 'Registered Only'
        END AS participation_type,

        (
            SELECT COUNT(*)
            FROM event_registration er2
            WHERE er2.student_id = er.student_id
        ) AS total_joined,

        (
            SELECT COUNT(*)
            FROM attendance a2
            WHERE a2.student_id = er.student_id
            AND a2.attendance_status = 'Present'
        ) AS total_attended

    FROM event_registration er

    LEFT JOIN students s
        ON er.student_id = s.id

    LEFT JOIN events e
        ON er.event_id = e.event_id

    LEFT JOIN attendance a
        ON er.student_id = a.student_id
        AND er.event_id = a.event_id

    WHERE 1=1
";

if (!empty($search)) {
    $safeSearch = $conn->real_escape_string($search);

    $reportQuery .= "
        AND (
            s.name LIKE '%$safeSearch%'
            OR s.matric_number LIKE '%$safeSearch%'
            OR s.email LIKE '%$safeSearch%'
            OR e.event_name LIKE '%$safeSearch%'
            OR e.event_category LIKE '%$safeSearch%'
        )
    ";
}

if (!empty($filterEvent)) {
    $safeEvent = $conn->real_escape_string($filterEvent);

    $reportQuery .= "
        AND er.event_id = '$safeEvent'
    ";
}

if (!empty($filterStatus)) {
    if ($filterStatus == "Present") {
        $reportQuery .= "
            AND a.attendance_status = 'Present'
        ";
    } elseif ($filterStatus == "Absent") {
        $reportQuery .= "
            AND (a.attendance_status IS NULL OR a.attendance_status = 'Absent')
        ";
    }
}

if (!empty($filterFromDate)) {
    $safeFromDate = $conn->real_escape_string($filterFromDate);

    $reportQuery .= "
        AND e.event_date >= '$safeFromDate'
    ";
}

if (!empty($filterToDate)) {
    $safeToDate = $conn->real_escape_string($filterToDate);

    $reportQuery .= "
        AND e.event_date <= '$safeToDate'
    ";
}

$reportQuery .= "
    ORDER BY e.event_date DESC, s.name ASC
";

$reportList = $conn->query($reportQuery);

/* CURRENT FILTER SUMMARY */
$showingRecords = 0;
$showingPresent = 0;
$showingAbsent = 0;

if ($reportList) {
    $showingRecords = $reportList->num_rows;

    $summaryResult = $conn->query($reportQuery);

    if ($summaryResult) {
        while ($summaryRow = $summaryResult->fetch_assoc()) {
            if ($summaryRow['attendance_status'] == "Present") {
                $showingPresent++;
            } else {
                $showingAbsent++;
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Participation Report - PERSADA Admin</title>

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
    --primary-color:#2563eb;
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

/* SIDEBAR */
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
}

.logo-text{
    display:flex;
    flex-direction:column;
}

.logo-text .name{
    font-size:20px;
    font-weight:800;
    color:var(--title-color);
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
    transition:.3s;
}

body.dark .switch::before{
    left:20px;
}

/* MAIN */
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

/* HEADER */
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

/* CARDS */
.report-cards{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:22px;
    margin-bottom:30px;
}

.report-card{
    background:var(--card-color);
    border-radius:28px;
    padding:26px;
    box-shadow:var(--shadow);
    border:1px solid var(--border-color);
    position:relative;
    overflow:hidden;
    transition:.3s ease;
}

.report-card:hover{
    transform:translateY(-7px);
}

.report-card::after{
    content:"";
    position:absolute;
    width:120px;
    height:120px;
    right:-45px;
    bottom:-45px;
    border-radius:50%;
    background:rgba(37,99,235,.07);
}

.card-icon{
    width:58px;
    height:58px;
    border-radius:20px;
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:30px;
    margin-bottom:20px;
}

.report-card h3{
    color:var(--title-color);
    font-size:32px;
    font-weight:800;
    margin-bottom:5px;
}

.report-card p{
    color:var(--text-color);
    font-size:14px;
    font-weight:600;
}

/* FILTER */
.filter-card{
    background:var(--card-color);
    border-radius:30px;
    padding:28px;
    box-shadow:var(--shadow);
    border:1px solid var(--border-color);
    margin-bottom:30px;
}

.filter-header{
    margin-bottom:20px;
}

.filter-header h2{
    color:var(--title-color);
    font-size:24px;
    font-weight:800;
}

.filter-header p{
    color:var(--text-color);
    font-size:14px;
    margin-top:4px;
}

.filter-form{
    display:grid;
    grid-template-columns:1.3fr 1fr 1fr auto auto;
    gap:14px;
    align-items:center;
}

.filter-form input,
.filter-form select{
    width:100%;
    padding:15px 18px;
    border-radius:18px;
    border:1px solid var(--border-color);
    background:#ffffff;
    color:#0f172a;
    outline:none;
    font-size:14px;
}

.filter-form input:focus,
.filter-form select:focus{
    border-color:#2563eb;
    box-shadow:0 0 0 4px rgba(37,99,235,.12);
}

.filter-btn,
.reset-btn,
.export-btn{
    border:none;
    padding:15px 20px;
    border-radius:18px;
    font-weight:800;
    cursor:pointer;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    text-decoration:none;
    white-space:nowrap;
}

.filter-btn{
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    color:white;
    box-shadow:0 12px 25px rgba(37,99,235,.22);
}

.reset-btn{
    background:#e5e7eb;
    color:#0f172a;
}

.export-btn{
    background:linear-gradient(135deg,#16a34a,#22c55e);
    color:white;
}

/* TABLE */
.report-table-card{
    background:var(--card-color);
    border-radius:30px;
    padding:28px;
    box-shadow:var(--shadow);
    border:1px solid var(--border-color);
}

.table-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:24px;
    gap:16px;
}

.table-header h2{
    color:var(--title-color);
    font-size:24px;
}

.table-header p{
    color:var(--text-color);
    font-size:14px;
    margin-top:4px;
}

.table-wrapper{
    overflow-x:auto;
}

table{
    width:100%;
    min-width:1150px;
    border-collapse:collapse;
}

th{
    text-align:left;
    color:var(--title-color);
    font-size:14px;
    padding:16px;
    border-bottom:1px solid var(--border-color);
}

td{
    padding:16px;
    color:var(--text-color);
    border-bottom:1px solid rgba(15,23,42,.06);
    font-size:14px;
}

.member-cell{
    display:flex;
    align-items:center;
    gap:12px;
}

.member-avatar{
    width:48px;
    height:48px;
    border-radius:50%;
    overflow:hidden;
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:800;
    flex-shrink:0;
}

.member-avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.member-info strong,
.event-cell strong{
    display:block;
    color:var(--title-color);
    margin-bottom:3px;
}

.member-info span,
.event-cell span{
    font-size:12px;
    color:var(--text-color);
}

.status-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:8px 14px;
    border-radius:30px;
    font-weight:800;
    font-size:13px;
}

.status-present{
    background:#dcfce7;
    color:#16a34a;
}

.status-absent{
    background:#fee2e2;
    color:#dc2626;
}

.empty-state{
    text-align:center;
    padding:60px 20px;
    color:var(--text-color);
}

.empty-state i{
    font-size:70px;
    color:#2563eb;
    margin-bottom:15px;
}

.empty-state h3{
    color:var(--title-color);
    margin-bottom:8px;
}

/* REPORT SUMMARY */
.record-summary{
    display:flex;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:22px;
}

.summary-pill{
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:10px 15px;
    border-radius:999px;
    font-size:13px;
    font-weight:800;
}

.summary-pill.total{
    background:#eff6ff;
    color:#2563eb;
}

.summary-pill.present{
    background:#dcfce7;
    color:#16a34a;
}

.summary-pill.absent{
    background:#fee2e2;
    color:#dc2626;
}

/* PARTICIPATION TYPE */
.type-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:8px 13px;
    border-radius:999px;
    font-size:12px;
    font-weight:800;
}

.type-attended{
    background:#dcfce7;
    color:#16a34a;
}

.type-registered{
    background:#fff7ed;
    color:#ea580c;
}

/* ATTENDANCE RATE */
.rate-box strong{
    display:block;
    color:var(--title-color);
    font-size:13px;
    margin-bottom:7px;
}

.rate-bar{
    width:90px;
    height:7px;
    background:#e5e7eb;
    border-radius:999px;
    overflow:hidden;
}

.rate-fill{
    height:100%;
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    border-radius:999px;
}

/* VIEW DETAILS BUTTON */
.view-detail-btn{
    border:none;
    padding:9px 13px;
    border-radius:14px;
    background:#eff6ff;
    color:#2563eb;
    font-weight:800;
    cursor:pointer;
    display:inline-flex;
    align-items:center;
    gap:6px;
    transition:.25s ease;
}

.view-detail-btn:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 20px rgba(37,99,235,.15);
}

/* MODAL */
.modal{
    position:fixed;
    inset:0;
    background:rgba(15,23,42,.55);
    backdrop-filter:blur(8px);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:9999;
    padding:25px;
}

.modal.show{
    display:flex;
}

.modal-content{
    width:850px;
    max-width:96%;
    max-height:90vh;
    overflow-y:auto;
    background:var(--card-color);
    border-radius:30px;
    padding:30px;
    box-shadow:0 25px 60px rgba(15,23,42,.25);
    position:relative;
    animation:popUp .25s ease;
}

@keyframes popUp{
    from{
        opacity:0;
        transform:translateY(20px) scale(.96);
    }
    to{
        opacity:1;
        transform:translateY(0) scale(1);
    }
}

.modal-close{
    position:absolute;
    top:18px;
    right:20px;
    width:42px;
    height:42px;
    border:none;
    border-radius:50%;
    background:var(--primary-light);
    color:var(--primary-color);
    font-size:24px;
    cursor:pointer;
}

.modal-title{
    color:var(--title-color);
    font-size:28px;
    font-weight:800;
    margin-bottom:5px;
}

.modal-subtitle{
    color:var(--text-color);
    font-size:14px;
    margin-bottom:22px;
}

.detail-profile{
    display:flex;
    align-items:center;
    gap:16px;
    padding:20px;
    border-radius:24px;
    background:linear-gradient(135deg,#eff6ff,#ecfeff);
    border:1px solid var(--border-color);
    margin-bottom:22px;
}

.detail-avatar{
    width:70px;
    height:70px;
    border-radius:22px;
    overflow:hidden;
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:26px;
    font-weight:800;
    flex-shrink:0;
}

.detail-avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.detail-profile h3{
    color:var(--title-color);
    font-size:22px;
    margin-bottom:4px;
}

.detail-profile p{
    color:var(--text-color);
    font-size:14px;
}

.detail-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:15px;
}

.detail-box{
    background:var(--primary-light);
    border:1px solid var(--border-color);
    border-radius:18px;
    padding:16px;
}

.detail-box span{
    display:block;
    color:var(--text-color);
    font-size:12px;
    margin-bottom:6px;
}

.detail-box strong{
    color:var(--title-color);
    font-size:15px;
}

.detail-box.full{
    grid-column:1 / -1;
}

.filter-form{
    grid-template-columns:1.2fr .9fr .8fr .7fr .7fr auto auto;
}

@media(max-width:1200px){
    .filter-form{
        grid-template-columns:1fr 1fr;
    }

    .detail-grid{
        grid-template-columns:1fr;
    }

    .detail-box.full{
        grid-column:auto;
    }
}

/* RESPONSIVE */
@media(max-width:1200px){
    .report-cards{
        grid-template-columns:repeat(2,1fr);
    }

    .filter-form{
        grid-template-columns:1fr 1fr;
    }
}

@media(max-width:700px){
    .main{
        padding:22px;
    }

    .report-cards{
        grid-template-columns:1fr;
    }

    .filter-form{
        grid-template-columns:1fr;
    }

    .page-header{
        flex-direction:column;
        align-items:flex-start;
        gap:16px;
    }
}
</style>
</head>
<body>


    <!-- SIDEBAR -->
<nav class="sidebar">

    <header>

        <div class="image-text">

            <div class="logo-box">
                A
            </div>

            <div class="logo-text">
                <span class="name">PERSADA</span>
                <span class="profession">Administration</span>
            </div>

        </div>

        <i class='bx bx-chevron-left toggle'></i>

    </header>

    <div class="menu-bar">

        <div class="menu">

            <ul class="menu-links">

                <li>
                    <a href="admin_dashboard.php">
                        <i class='bx bx-home-alt icon'></i>
                        <span class="text">Dashboard</span>
                    </a>
                </li>

                <li>
                    <a href="member_management.php">
                        <i class='bx bx-user icon'></i>
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
                    <a href="report_management.php" class="active">
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

                <div class="moon-sun">
                    <i class='bx bx-moon icon moon'></i>
                </div>

                <span class="mode-text text">
                    Dark mode
                </span>

                <div class="toggle-switch">
                    <span class="switch"></span>
                </div>

            </li>

        </div>

    </div>

</nav>

<!-- MAIN CONTENT -->
<section class="main">

    <!-- HEADER -->
    <div class="page-header">

        <div>
            <h1 class="page-title">
                Participation Report
            </h1>

            <p class="page-subtitle">
                View member participation history, event participant lists and attendance status.
            </p>
        </div>

        <div class="admin-badge">
            <i class='bx bx-user-circle'></i>
            <?php echo $adminName; ?>
        </div>

    </div>

    <!-- TOP CARDS -->
    <div class="report-cards">

        <div class="report-card">
            <div class="card-icon">
                <i class='bx bx-calendar-check'></i>
            </div>
            <h3><?php echo $totalParticipation; ?></h3>
            <p>Total Participation</p>
        </div>

        <div class="report-card">
            <div class="card-icon">
                <i class='bx bx-check-circle'></i>
            </div>
            <h3><?php echo $totalPresent; ?></h3>
            <p>Present Attendance</p>
        </div>

        <div class="report-card">
            <div class="card-icon">
                <i class='bx bx-x-circle'></i>
            </div>
            <h3><?php echo $totalAbsent; ?></h3>
            <p>Absent Attendance</p>
        </div>

        <div class="report-card">
            <div class="card-icon">
                <i class='bx bx-group'></i>
            </div>
            <h3><?php echo $totalMembers; ?></h3>
            <p>Registered Members</p>
        </div>

    </div>

    <!-- FILTER CARD -->
    <div class="filter-card">

        <div class="filter-header">
            <h2>Report Filter</h2>
            <p>Search participation records by student, matric number, event or attendance status.</p>
        </div>

        <form method="GET" class="filter-form">

            <input 
                type="text"
                name="search"
                placeholder="Search student, matric or event..."
                value="<?php echo htmlspecialchars($search); ?>">

            <select name="event_id">
                <option value="">All Events</option>

                <?php while($event = $eventDropdown->fetch_assoc()){ ?>
                    <option 
                        value="<?php echo $event['event_id']; ?>"
                        <?php if($filterEvent == $event['event_id']) echo "selected"; ?>>
                        <?php echo $event['event_name']; ?>
                    </option>
                <?php } ?>
            </select>

            <select name="attendance_status">
                <option value="">All Status</option>
                <option value="Present" <?php if($filterStatus == "Present") echo "selected"; ?>>
                    Present
                </option>
                <option value="Absent" <?php if($filterStatus == "Absent") echo "selected"; ?>>
                    Absent
                </option>
            </select>

            <button type="submit" class="filter-btn">
                <i class='bx bx-search'></i>
                Filter
            </button>

            <a href="report_management.php" class="reset-btn">
                <i class='bx bx-reset'></i>
                Reset
            </a>

        </form>

    </div>

    <!-- REPORT TABLE -->
    <div class="report-table-card">

        <div class="table-header">

            <div>
                <h2>Participation Records</h2>
                <p>Complete record of registered events and attendance status.</p>
            </div>

            <button type="button" class="export-btn" onclick="exportTableToCSV()">
                <i class='bx bx-download'></i>
                Export CSV
            </button>

        </div>

        <div class="record-summary">

    <span class="summary-pill total">
        <i class='bx bx-list-ul'></i>
        Showing <?php echo $showingRecords; ?> records
    </span>

    <span class="summary-pill present">
        <i class='bx bx-check-circle'></i>
        <?php echo $showingPresent; ?> Present
    </span>

    <span class="summary-pill absent">
        <i class='bx bx-x-circle'></i>
        <?php echo $showingAbsent; ?> Absent
    </span>

</div>

        <div class="table-wrapper">

            <table id="reportTable">

                <thead>
                    <tr>
                      <th>Student</th>
<th>Matric No</th>
<th>Faculty</th>
<th>Event</th>
<th>Event Date</th>
<th>Participation Type</th>
<th>Attendance Status</th>
<th>Attendance Rate</th>
<th>Scan Time</th>
<th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if($reportList && $reportList->num_rows > 0){ ?>

                    <?php while($row = $reportList->fetch_assoc()){ ?>

                        <tr>

                            <td>
                                <div class="member-cell">

                                    <div class="member-avatar">

                                        <?php if(!empty($row['profile_picture'])){ ?>
                                            <img src="<?php echo $row['profile_picture']; ?>">
                                        <?php } else { ?>
                                            <?php echo strtoupper(substr($row['student_name'], 0, 1)); ?>
                                        <?php } ?>

                                    </div>

                                    <div class="member-info">
                                        <strong><?php echo $row['student_name']; ?></strong>
                                        <span><?php echo $row['email'] ?? '-'; ?></span>
                                    </div>

                                </div>
                            </td>

                            <td><?php echo $row['matric_number']; ?></td>

                            <td><?php echo $row['faculty'] ?? '-'; ?></td>

                            <td>
                                <div class="event-cell">
                                    <strong><?php echo $row['event_name']; ?></strong>
                                    <span><?php echo $row['event_category'] ?? '-'; ?></span>
                                </div>
                            </td>

                            <td>
                                <?php 
                                    if(!empty($row['event_date'])){
                                        echo date("d M Y", strtotime($row['event_date']));
                                    } else {
                                        echo "-";
                                    }
                                ?>
                            </td>


                            <td>
    <?php if($row['participation_type'] == "Attended"){ ?>
        <span class="type-badge type-attended">
            <i class='bx bx-check-circle'></i>
            Attended
        </span>
    <?php } else { ?>
        <span class="type-badge type-registered">
            <i class='bx bx-time-five'></i>
            Registered Only
        </span>
    <?php } ?>
</td>


                            <td>
                                <?php if($row['attendance_status'] == "Present"){ ?>
                                    <span class="status-badge status-present">
                                        <i class='bx bx-check'></i>
                                        Present
                                    </span>
                                <?php } else { ?>
                                    <span class="status-badge status-absent">
                                        <i class='bx bx-x'></i>
                                        Absent
                                    </span>
                                <?php } ?>
                            </td>

                            <td>
                                <?php 
                                    if(!empty($row['scan_time'])){
                                        echo date("d M Y h:i A", strtotime($row['scan_time']));
                                    } else {
                                        echo "-";
                                    }
                                ?>
                            </td>


<td>
    <button 
        type="button" 
        class="view-detail-btn"
        onclick='openDetailModal(<?php echo json_encode($row); ?>)'>
        <i class='bx bx-show'></i>
        View
    </button>
</td>



<td>
    <?php
        $totalJoined = (int)$row['total_joined'];
        $totalAttended = (int)$row['total_attended'];
        $ratePercent = 0;

        if ($totalJoined > 0) {
            $ratePercent = round(($totalAttended / $totalJoined) * 100);
        }
    ?>

    <div class="rate-box">
        <strong><?php echo $totalAttended; ?> / <?php echo $totalJoined; ?></strong>

        <div class="rate-bar">
            <div class="rate-fill" style="width: <?php echo $ratePercent; ?>%;"></div>
        </div>
    </div>
</td>




                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                       <td colspan="10">
                            <div class="empty-state">
                                <i class='bx bx-folder-open'></i>
                                <h3>No report records found</h3>
                                <p>Try changing the search keyword or filter selection.</p>
                            </div>
                        </td>
                    </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>

    </div>

</section>

<!-- VIEW DETAILS MODAL -->
<div class="modal" id="detailModal">

    <div class="modal-content">

        <button class="modal-close" onclick="closeModal('detailModal')">
            &times;
        </button>

        <h2 class="modal-title">Participation Details</h2>
        <p class="modal-subtitle">
            Detailed record of selected member participation.
        </p>

        <div class="detail-profile">

            <div class="detail-avatar" id="detailAvatar">
                A
            </div>

            <div>
                <h3 id="detailStudentName">Student Name</h3>
                <p id="detailStudentInfo">Matric Number • Faculty</p>
            </div>

        </div>

        <div class="detail-grid">

            <div class="detail-box">
                <span>Email</span>
                <strong id="detailEmail">-</strong>
            </div>

            <div class="detail-box">
                <span>Phone Number</span>
                <strong id="detailPhone">-</strong>
            </div>

            <div class="detail-box">
                <span>Event Name</span>
                <strong id="detailEvent">-</strong>
            </div>

            <div class="detail-box">
                <span>Event Category</span>
                <strong id="detailCategory">-</strong>
            </div>

            <div class="detail-box">
                <span>Event Date</span>
                <strong id="detailEventDate">-</strong>
            </div>

            <div class="detail-box">
                <span>Event Time</span>
                <strong id="detailEventTime">-</strong>
            </div>

            <div class="detail-box">
                <span>Venue</span>
                <strong id="detailVenue">-</strong>
            </div>

            <div class="detail-box">
                <span>Participation Type</span>
                <strong id="detailType">-</strong>
            </div>

            <div class="detail-box">
                <span>Attendance Status</span>
                <strong id="detailStatus">-</strong>
            </div>

            <div class="detail-box">
                <span>Scan Time</span>
                <strong id="detailScanTime">-</strong>
            </div>

            <div class="detail-box full">
                <span>Overall Student Attendance Rate</span>
                <strong id="detailRate">-</strong>
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

/* SIDEBAR TOGGLE */
toggle.addEventListener("click", () => {
    sidebar.classList.toggle("close");
});

/* DARK MODE */
modeSwitch.addEventListener("click", () => {
    body.classList.toggle("dark");

    if(body.classList.contains("dark")){
        modeText.innerText = "Light mode";
    }else{
        modeText.innerText = "Dark mode";
    }
});


function closeModal(id){
    document.getElementById(id).classList.remove("show");
}

function openDetailModal(data){

    const avatarBox = document.getElementById("detailAvatar");

    if(data.profile_picture && data.profile_picture !== ""){
        avatarBox.innerHTML = `<img src="${data.profile_picture}" alt="Profile">`;
    }else{
        avatarBox.innerHTML = (data.student_name || "A").charAt(0).toUpperCase();
    }

    document.getElementById("detailStudentName").innerText =
        data.student_name || "-";

    document.getElementById("detailStudentInfo").innerText =
        (data.matric_number || "-") + " • " + (data.faculty || "-");

    document.getElementById("detailEmail").innerText =
        data.email || "-";

    document.getElementById("detailPhone").innerText =
        data.phone_number || "-";

    document.getElementById("detailEvent").innerText =
        data.event_name || "-";

    document.getElementById("detailCategory").innerText =
        data.event_category || "-";

    document.getElementById("detailEventDate").innerText =
        data.event_date || "-";

    document.getElementById("detailEventTime").innerText =
        data.event_time || "-";

    document.getElementById("detailVenue").innerText =
        data.venue || "-";

    document.getElementById("detailType").innerText =
        data.participation_type || "Registered Only";

    document.getElementById("detailStatus").innerText =
        data.attendance_status ? data.attendance_status : "Absent";

    document.getElementById("detailScanTime").innerText =
        data.scan_time ? data.scan_time : "-";

    let joined = parseInt(data.total_joined || 0);
    let attended = parseInt(data.total_attended || 0);
    let percent = 0;

    if(joined > 0){
        percent = Math.round((attended / joined) * 100);
    }

    document.getElementById("detailRate").innerText =
        attended + " / " + joined + " events attended (" + percent + "%)";

    document.getElementById("detailModal").classList.add("show");
}

window.addEventListener("click", function(e){
    const detailModal = document.getElementById("detailModal");

    if(e.target === detailModal){
        detailModal.classList.remove("show");
    }
});








/* EXPORT TABLE TO CSV */
function exportTableToCSV(){
    const table = document.getElementById("reportTable");
    let csv = [];
    const rows = table.querySelectorAll("tr");

    rows.forEach(row => {
        let cols = row.querySelectorAll("th, td");
        let rowData = [];

        cols.forEach(col => {
            let text = col.innerText.replace(/\s+/g, " ").trim();
            text = text.replace(/"/g, '""');
            rowData.push('"' + text + '"');
        });

        csv.push(rowData.join(","));
    });

    const csvContent = csv.join("\n");
    const blob = new Blob([csvContent], {
        type:"text/csv;charset=utf-8;"
    });

    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);

    link.setAttribute("href", url);
    link.setAttribute("download", "PERSADA_Participation_Report.csv");
    link.style.display = "none";

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

</body>
</html>