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

/* TOTAL CARDS */
$totalEvents = $conn->query("
    SELECT COUNT(*) AS total FROM events
")->fetch_assoc()['total'];

$totalParticipants = $conn->query("
    SELECT COUNT(*) AS total FROM event_registration
")->fetch_assoc()['total'];

$totalPresent = $conn->query("
    SELECT COUNT(*) AS total 
    FROM attendance 
    WHERE attendance_status = 'Present'
")->fetch_assoc()['total'];

$totalAbsent = $totalParticipants - $totalPresent;

if ($totalAbsent < 0) {
    $totalAbsent = 0;
}

/* EVENT LIST */
$eventList = $conn->query("
    SELECT * 
    FROM events 
    ORDER BY event_date DESC
");

/* SELECT EVENT */
$selectedEvent = "";

if (isset($_GET['event_id'])) {
    $selectedEvent = $_GET['event_id'];
}

/* GENERATE QR */
if (isset($_POST['generate_qr'])) {

    $eventID = $_POST['event_id'];

    $qrToken = md5($eventID . time() . rand(1000, 9999));

    $stmt = $conn->prepare("
        UPDATE events 
        SET qr_token = ? 
        WHERE event_id = ?
    ");

    $stmt->bind_param("si", $qrToken, $eventID);
    $stmt->execute();

    header("Location: attendance_management.php?event_id=" . $eventID);
    exit();
}

/* SELECTED EVENT DATA */
$eventData = null;

if (!empty($selectedEvent)) {

    $stmt = $conn->prepare("
        SELECT * 
        FROM events 
        WHERE event_id = ?
    ");

    $stmt->bind_param("i", $selectedEvent);
    $stmt->execute();

    $eventResult = $stmt->get_result();

    if ($eventResult->num_rows > 0) {
        $eventData = $eventResult->fetch_assoc();
    }
}

/* SEARCH */
$search = "";

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

/* ATTENDANCE LIST */
$attendanceQuery = "
    SELECT
        er.*,
        e.event_name,
        e.event_date,
        e.event_time,
        s.id AS student_real_id,
        s.name AS full_name,
        s.matric_number,
        s.profile_picture,
        a.attendance_status,
        a.scan_time

    FROM event_registration er

    LEFT JOIN events e
        ON er.event_id = e.event_id

    LEFT JOIN students s
        ON er.student_id = s.id

    LEFT JOIN attendance a
        ON er.student_id = a.student_id
        AND er.event_id = a.event_id

    WHERE 1=1
";
if (!empty($selectedEvent)) {
    $attendanceQuery .= "
        AND er.event_id = '$selectedEvent'
    ";
}

if (!empty($search)) {
    $safeSearch = $conn->real_escape_string($search);

    $attendanceQuery .= "
       AND (
    s.name LIKE '%$safeSearch%'
    OR s.matric_number LIKE '%$safeSearch%'
    OR e.event_name LIKE '%$safeSearch%'
)
    ";
}

$attendanceQuery .= "
    ORDER BY s.name ASC
";

$attendanceList = $conn->query($attendanceQuery);

/* QR URL */
/* QR URL */
$qrURL = "";

if ($eventData && !empty($eventData['qr_token'])) {
    $qrURL = "http://localhost/PERSADA/scan_attendance_api.php?token=" . $eventData['qr_token'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Attendance Management - PERSADA Admin</title>

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

/* STATS */
.attendance-cards{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:22px;
    margin-bottom:30px;
}

.attendance-card{
    background:var(--card-color);
    border-radius:28px;
    padding:26px;
    box-shadow:var(--shadow);
    border:1px solid var(--border-color);
    position:relative;
    overflow:hidden;
    transition:.3s ease;
}

.attendance-card:hover{
    transform:translateY(-7px);
}

.attendance-card::after{
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

.attendance-card h3{
    color:var(--title-color);
    font-size:32px;
    font-weight:800;
    margin-bottom:5px;
}

.attendance-card p{
    color:var(--text-color);
    font-size:14px;
    font-weight:600;
}

/* CONTROL PANEL */
.control-panel{
    background:var(--card-color);
    border-radius:30px;
    padding:28px;
    box-shadow:var(--shadow);
    border:1px solid var(--border-color);
    margin-bottom:30px;
}

.panel-top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:22px;
}

.panel-top h2{
    color:var(--title-color);
    font-size:24px;
}

.panel-top p{
    color:var(--text-color);
    font-size:14px;
    margin-top:4px;
}

.control-form{
    display:grid;
    grid-template-columns:1fr auto auto;
    gap:14px;
    align-items:center;
}

.control-form select,
.search-box input{
    width:100%;
    padding:15px 18px;
    border-radius:18px;
    border:1px solid var(--border-color);
    background:#ffffff;
    color:#0f172a;
    outline:none;
    font-size:14px;
}

.control-form select:focus,
.search-box input:focus{
    border-color:#2563eb;
    box-shadow:0 0 0 4px rgba(37,99,235,.12);
}

.primary-btn,
.qr-btn,
.search-btn{
    border:none;
    padding:15px 22px;
    border-radius:18px;
    font-weight:800;
    color:white;
    cursor:pointer;
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    text-decoration:none;
    box-shadow:0 12px 25px rgba(37,99,235,.22);
}

.qr-btn{
    background:linear-gradient(135deg,#7c3aed,#2563eb);
}

/* QR PREVIEW */
.qr-section{
    margin-top:24px;
    padding:24px;
    border-radius:26px;
    background:linear-gradient(135deg,#eff6ff,#ecfeff);
    border:1px solid var(--border-color);
    display:grid;
    grid-template-columns:1fr auto;
    gap:20px;
    align-items:center;
}

.qr-section h3{
    color:var(--title-color);
    margin-bottom:6px;
}

.qr-section p{
    color:var(--text-color);
    font-size:14px;
}

.qr-code-box{
    width:110px;
    height:110px;
    border-radius:24px;
    background:white;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#2563eb;
    font-size:58px;
    box-shadow:0 12px 25px rgba(37,99,235,.12);
}

/* TABLE SECTION */
.attendance-table-card{
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

.search-box{
    display:flex;
    gap:12px;
    min-width:420px;
}

.table-wrapper{
    overflow-x:auto;
}

table{
    width:100%;
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
    width:46px;
    height:46px;
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

.member-info strong{
    display:block;
    color:var(--title-color);
    margin-bottom:3px;
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

.event-name-cell strong{
    display:block;
    color:var(--title-color);
    font-size:14px;
    font-weight:800;
    margin-bottom:4px;
}

.event-name-cell span{
    color:var(--text-color);
    font-size:12px;
}

/* RESPONSIVE */
@media(max-width:1100px){
    .attendance-cards{
        grid-template-columns:repeat(2,1fr);
    }

    .control-form{
        grid-template-columns:1fr;
    }

    .qr-section{
        grid-template-columns:1fr;
    }

    .table-header{
        flex-direction:column;
        align-items:stretch;
    }

    .search-box{
        min-width:100%;
    }
}
button.qr-code-box{
    border:none;
    cursor:pointer;
}
@media(max-width:700px){
    .attendance-cards{
        grid-template-columns:1fr;
    }

    .main{
        padding:22px;
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
                    <a href="attendance_management.php" class="active">
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

    <!-- PAGE HEADER -->
    <div class="page-header">

        <div>
            <h1 class="page-title">
                Attendance Management
            </h1>

            <p class="page-subtitle">
                Generate QR attendance, monitor participants and track event attendance.
            </p>
        </div>

        <div class="admin-badge">
            <i class='bx bx-user-circle'></i>
            PERSADA Administrator
        </div>

    </div>

    <!-- STATISTIC CARDS -->
    <div class="attendance-cards">

        <div class="attendance-card">

            <div class="card-icon">
                <i class='bx bx-calendar-event'></i>
            </div>

            <h3>
                <?php echo $totalEvents; ?>
            </h3>

            <p>Total Events</p>

        </div>

        <div class="attendance-card">

            <div class="card-icon">
                <i class='bx bx-group'></i>
            </div>

            <h3>
                <?php echo $totalParticipants; ?>
            </h3>

            <p>Total Participants</p>

        </div>

        <div class="attendance-card">

            <div class="card-icon">
                <i class='bx bx-check-circle'></i>
            </div>

            <h3>
                <?php echo $totalPresent; ?>
            </h3>

            <p>Present</p>

        </div>

        <div class="attendance-card">

            <div class="card-icon">
                <i class='bx bx-x-circle'></i>
            </div>

            <h3>
                <?php echo $totalAbsent; ?>
            </h3>

            <p>Absent</p>

        </div>

    </div>

    <!-- CONTROL PANEL -->
    <div class="control-panel">

        <div class="panel-top">

            <div>
                <h2>Generate Event QR Attendance</h2>
                <p>Select event and generate QR code for student attendance.</p>
            </div>

        </div>

       <form method="GET" action="attendance_management.php" class="control-form">

    <select name="event_id" id="eventSelect">
        <option value="">All Events / Select Event</option>

        <?php
        $eventList->data_seek(0);

        while($event = $eventList->fetch_assoc()){
        ?>
            <option
                value="<?php echo $event['event_id']; ?>"
                <?php if($selectedEvent == $event['event_id']) echo "selected"; ?>>
                <?php echo $event['event_name']; ?>
            </option>
        <?php } ?>
    </select>

    <button type="button" class="qr-btn" onclick="generateQR()">
        <i class='bx bx-qr'></i>
        Generate QR
    </button>

    <button type="submit" class="primary-btn">
        <i class='bx bx-search'></i>
        Load Event
    </button>

</form>

<form method="POST" id="generateQRForm" style="display:none;">
    <input type="hidden" name="event_id" id="generate_event_id">
    <input type="hidden" name="generate_qr" value="1">
</form>

        <?php if($eventData){ ?>

        <div class="qr-section">

            <div>

                <h3>
                    QR Attendance Generated
                </h3>

                <p>
                    Students can scan this QR code to mark attendance.
                </p>

                <br>

                <strong>
                    Event:
                    <?php echo $eventData['event_name']; ?>
                </strong>

                <br><br>

                <small>
                    Token:
                    <?php echo $eventData['qr_token']; ?>
                </small>

            </div>

           <button type="button" class="qr-code-box" onclick="openQRModal()">
    <i class='bx bx-qr'></i>
</button>

        </div>

        <?php } ?>

    </div>

    <!-- ATTENDANCE TABLE -->
    <div class="attendance-table-card">

        <div class="table-header">

            <h2>
                Attendance List
            </h2>

            <form method="GET" class="search-box">

                <input
                type="hidden"
                name="event_id"
                value="<?php echo $selectedEvent; ?>">

                <input
                type="text"
                name="search"
                placeholder="Search student..."
                value="<?php echo htmlspecialchars($search); ?>">

                <button class="search-btn">
                    <i class='bx bx-search'></i>
                </button>

            </form>

        </div>

        <div class="table-wrapper">

            <table>

                <thead>

                <tr>

                   <th>Student</th>
<th>Matric Number</th>
<th>Event</th>
<th>Status</th>
<th>Scan Time</th>

                </tr>

                </thead>

                <tbody>

                <?php

                if($attendanceList && $attendanceList->num_rows > 0){

                    while($row = $attendanceList->fetch_assoc()){

                ?>

                <tr>

                    <td>

                        <div class="member-cell">

                            <div class="member-avatar">

                                <?php

                                if(!empty($row['profile_picture'])){

                                ?>

                                <img
                                src="<?php echo $row['profile_picture']; ?>">

                                <?php

                                }else{

                                    echo strtoupper(substr($row['full_name'],0,1));

                                }

                                ?>

                            </div>

                            <div class="member-info">

                                <strong>
                                    <?php echo $row['full_name']; ?>
                                </strong>

                            </div>

                        </div>

                    </td>

                    <td>
                        <?php echo $row['matric_number']; ?>
                    </td>

                    <td>
    <div class="event-name-cell">
        <strong><?php echo $row['event_name'] ?? '-'; ?></strong>
        <span>
            <?php 
                if(!empty($row['event_date'])){
                    echo date("d M Y", strtotime($row['event_date']));
                }
            ?>
        </span>
    </div>
</td>



                    <td>

                        <?php

                        if($row['attendance_status']=="Present"){

                            echo '
                            <span class="status-badge status-present">
                            Present
                            </span>
                            ';

                        }else{

                            echo '
                            <span class="status-badge status-absent">
                            Absent
                            </span>
                            ';

                        }

                        ?>

                    </td>

                    <td>

                        <?php

                        if(!empty($row['scan_time'])){

                            echo date(
                                "d M Y h:i A",
                                strtotime($row['scan_time'])
                            );

                        }else{

                            echo "-";

                        }

                        ?>

                    </td>

                </tr>

                <?php

                    }

                }else{

                ?>

                <tr>

                   <td colspan="5">

                        <div class="empty-state">

                            <i class='bx bx-qr-scan'></i>

                            <h3>
                                No attendance records found
                            </h3>

                            <p>
                                Select an event and generate attendance QR code.
                            </p>

                        </div>

                    </td>

                </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>

    </div>

</section>

<!-- QR MODAL -->
<div class="qr-modal" id="qrModal">

    <div class="qr-modal-box">

        <button type="button" class="modal-close" onclick="closeQRModal()">
            <i class='bx bx-x'></i>
        </button>

        <div class="qr-modal-header">
            <div class="qr-modal-icon">
                <i class='bx bx-qr-scan'></i>
            </div>

            <h2>Event Attendance QR</h2>

            <p>
                Students can scan this QR code to record their attendance for the selected event.
            </p>
        </div>

        <div class="qr-preview-card">

            <div class="qr-image-box" id="qrImageBox">
                <?php if($eventData && !empty($eventData['qr_token'])) { ?>

                    <img
                        id="qrImage"
                        src="https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=<?php echo urlencode($qrURL); ?>"
                        alt="Attendance QR Code">

                <?php } else { ?>

                    <div class="qr-empty">
                        <i class='bx bx-qr'></i>
                        <span>No QR generated</span>
                    </div>

                <?php } ?>
            </div>

            <div class="qr-event-info">

                <span class="qr-status-badge">
                    <i class='bx bx-check-circle'></i>
                    QR Ready
                </span>

                <h3>
                    <?php echo $eventData ? $eventData['event_name'] : 'No Event Selected'; ?>
                </h3>

                <p>
                    <?php
                    if($eventData){
                        echo date("d F Y", strtotime($eventData['event_date']));
                        echo " • ";
                        echo date("h:i A", strtotime($eventData['event_time']));
                    }else{
                        echo "Please select an event first.";
                    }
                    ?>
                </p>

                <div class="qr-link-box">
                    <span>QR Attendance Link</span>

                    <input
                        type="text"
                        id="qrLink"
                        value="<?php echo $qrURL; ?>"
                        readonly>
                </div>

                <div class="qr-action-row">

                    <button type="button" class="copy-btn" onclick="copyQRLink()">
                        <i class='bx bx-copy'></i>
                        Copy Link
                    </button>

                    <button type="button" class="download-btn" onclick="downloadQRCode()">
                        <i class='bx bx-download'></i>
                        Download QR
                    </button>

                </div>

            </div>

        </div>

    </div>

</div>

<style>
/* =========================
   QR MODAL
========================= */
.qr-modal{
    position:fixed;
    inset:0;
    background:rgba(15,23,42,.62);
    backdrop-filter:blur(10px);
    display:none;
    justify-content:center;
    align-items:center;
    z-index:9999;
    padding:25px;
}

.qr-modal.show{
    display:flex;
}

.qr-modal-box{
    width:850px;
    max-width:96%;
    background:var(--card-color);
    border-radius:34px;
    box-shadow:0 35px 90px rgba(0,0,0,.28);
    padding:32px;
    position:relative;
    animation:qrPop .25s ease;
}

@keyframes qrPop{
    from{
        opacity:0;
        transform:translateY(25px) scale(.96);
    }

    to{
        opacity:1;
        transform:translateY(0) scale(1);
    }
}

.modal-close{
    position:absolute;
    top:18px;
    right:18px;
    width:44px;
    height:44px;
    border:none;
    border-radius:50%;
    background:var(--primary-light);
    color:var(--primary-color);
    font-size:24px;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    transition:.3s ease;
}

.modal-close:hover{
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    color:white;
    transform:rotate(90deg);
}

.qr-modal-header{
    text-align:center;
    margin-bottom:28px;
}

.qr-modal-icon{
    width:72px;
    height:72px;
    border-radius:24px;
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:38px;
    margin:0 auto 16px;
    box-shadow:0 16px 35px rgba(37,99,235,.25);
}

.qr-modal-header h2{
    color:var(--title-color);
    font-size:30px;
    font-weight:800;
    margin-bottom:8px;
}

.qr-modal-header p{
    color:var(--text-color);
    max-width:560px;
    margin:0 auto;
    font-size:14px;
    line-height:1.7;
}

.qr-preview-card{
    display:grid;
    grid-template-columns:280px 1fr;
    gap:26px;
    align-items:center;
    background:linear-gradient(135deg,#eff6ff,#ecfeff);
    border:1px solid var(--border-color);
    border-radius:28px;
    padding:26px;
}

.qr-image-box{
    width:250px;
    height:250px;
    background:white;
    border-radius:28px;
    display:flex;
    align-items:center;
    justify-content:center;
    box-shadow:0 18px 35px rgba(37,99,235,.12);
    padding:18px;
    margin:auto;
}

.qr-image-box img{
    width:100%;
    height:100%;
    object-fit:contain;
}

.qr-empty{
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    color:#2563eb;
    gap:8px;
}

.qr-empty i{
    font-size:70px;
}

.qr-empty span{
    font-size:14px;
    font-weight:700;
}

.qr-event-info{
    min-width:0;
}

.qr-status-badge{
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:8px 14px;
    background:#dcfce7;
    color:#16a34a;
    border-radius:30px;
    font-size:13px;
    font-weight:800;
    margin-bottom:14px;
}

.qr-event-info h3{
    color:var(--title-color);
    font-size:26px;
    font-weight:800;
    margin-bottom:8px;
}

.qr-event-info p{
    color:var(--text-color);
    font-size:14px;
    margin-bottom:22px;
}

.qr-link-box{
    margin-bottom:18px;
}

.qr-link-box span{
    display:block;
    color:var(--title-color);
    font-size:13px;
    font-weight:800;
    margin-bottom:8px;
}

.qr-link-box input{
    width:100%;
    padding:14px 16px;
    border-radius:16px;
    border:1px solid var(--border-color);
    outline:none;
    color:#0f172a;
    background:white;
    font-size:13px;
}

.qr-action-row{
    display:flex;
    gap:12px;
}

.copy-btn,
.download-btn{
    border:none;
    padding:13px 18px;
    border-radius:16px;
    color:white;
    font-weight:800;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    transition:.3s ease;
}

.copy-btn{
    background:linear-gradient(135deg,#2563eb,#06b6d4);
}

.download-btn{
    background:linear-gradient(135deg,#7c3aed,#2563eb);
}

.copy-btn:hover,
.download-btn:hover{
    transform:translateY(-3px);
    box-shadow:0 14px 28px rgba(37,99,235,.25);
}

/* TOAST */
.toast{
    position:fixed;
    right:30px;
    bottom:30px;
    background:#0f172a;
    color:white;
    padding:14px 20px;
    border-radius:16px;
    box-shadow:0 18px 40px rgba(0,0,0,.25);
    display:none;
    align-items:center;
    gap:10px;
    z-index:10000;
    font-weight:700;
}

.toast.show{
    display:flex;
    animation:toastIn .25s ease;
}

@keyframes toastIn{
    from{
        opacity:0;
        transform:translateY(15px);
    }

    to{
        opacity:1;
        transform:translateY(0);
    }
}

@media(max-width:800px){
    .qr-preview-card{
        grid-template-columns:1fr;
    }

    .qr-image-box{
        width:220px;
        height:220px;
    }

    .qr-action-row{
        flex-direction:column;
    }
}
</style>

<!-- TOAST MESSAGE -->
<div class="toast" id="toast">
    <i class='bx bx-check-circle'></i>
    <span id="toastText">Copied successfully</span>
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

/* OPEN QR MODAL */
function openQRModal(){
    document.getElementById("qrModal").classList.add("show");
}

/* CLOSE QR MODAL */
function closeQRModal(){
    document.getElementById("qrModal").classList.remove("show");
}

/* CLICK OUTSIDE MODAL TO CLOSE */
window.addEventListener("click", function(e){
    const qrModal = document.getElementById("qrModal");

    if(e.target === qrModal){
        closeQRModal();
    }
});

/* COPY QR LINK */
function copyQRLink(){
    const qrInput = document.getElementById("qrLink");

    if(!qrInput || qrInput.value.trim() === ""){
        showToast("No QR link available.");
        return;
    }

    qrInput.select();
    qrInput.setSelectionRange(0, 99999);

    navigator.clipboard.writeText(qrInput.value)
        .then(() => {
            showToast("QR attendance link copied.");
        })
        .catch(() => {
            document.execCommand("copy");
            showToast("QR attendance link copied.");
        });
}

/* DOWNLOAD QR CODE */
function downloadQRCode(){
    const qrImage = document.getElementById("qrImage");

    if(!qrImage){
        showToast("No QR code to download.");
        return;
    }

    const link = document.createElement("a");
    link.href = qrImage.src;
    link.download = "PERSADA_Attendance_QR.png";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    showToast("QR code download started.");
}

/* TOAST */
function showToast(message){
    const toast = document.getElementById("toast");
    const toastText = document.getElementById("toastText");

    toastText.innerText = message;
    toast.classList.add("show");

    setTimeout(() => {
        toast.classList.remove("show");
    }, 2600);
}

function generateQR(){
    const eventSelect = document.getElementById("eventSelect");
    const selectedEvent = eventSelect.value;

    if(selectedEvent === ""){
        alert("Please select an event before generating QR.");
        return;
    }

    document.getElementById("generate_event_id").value = selectedEvent;
    document.getElementById("generateQRForm").submit();
}






</script>

</body>
</html>