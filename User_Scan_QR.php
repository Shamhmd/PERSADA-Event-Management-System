<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: Login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "persada_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$student_id = $_SESSION['student_id'];

/* GET STUDENT DATA */
$studentQuery = $conn->prepare("
    SELECT * 
    FROM students 
    WHERE id = ?
");

$studentQuery->bind_param("i", $student_id);
$studentQuery->execute();
$student = $studentQuery->get_result()->fetch_assoc();

/* TOTAL REGISTERED EVENTS */
$totalRegistered = $conn->query("
    SELECT COUNT(*) AS total
    FROM event_registration
    WHERE student_id = '$student_id'
")->fetch_assoc()['total'];

/* TOTAL ATTENDED EVENTS */
$totalAttended = $conn->query("
    SELECT COUNT(*) AS total
    FROM attendance
    WHERE student_id = '$student_id'
    AND attendance_status = 'Present'
")->fetch_assoc()['total'];

/* RECENT ATTENDANCE HISTORY */
$recentAttendance = $conn->query("
    SELECT 
        e.event_name,
        e.event_date,
        e.event_time,
        e.venue,
        e.event_poster,
        a.attendance_status,
        a.scan_time
    FROM attendance a
    JOIN events e ON a.event_id = e.event_id
    WHERE a.student_id = '$student_id'
    ORDER BY a.scan_time DESC
    LIMIT 5
");

/* UPCOMING REGISTERED EVENTS THAT NEED ATTENDANCE */
$registeredEvents = $conn->query("
    SELECT 
        e.event_id,
        e.event_name,
        e.event_date,
        e.event_time,
        e.venue,
        e.event_poster,
        e.status
    FROM event_registration er
    JOIN events e ON er.event_id = e.event_id
    WHERE er.student_id = '$student_id'
    AND e.status IN ('Upcoming','Ongoing')
    ORDER BY e.event_date ASC
");


$studentName = $student['name'] ?? 'Student';

$joinedEvents = $totalRegistered;
$attendanceCount = $totalAttended;
$participationCount = $totalRegistered;

$attendanceHistory = $recentAttendance;



$latestAttendance = $conn->query("
    SELECT 
        e.event_name,
        a.attendance_status,
        a.scan_time
    FROM attendance a
    JOIN events e ON a.event_id = e.event_id
    WHERE a.student_id = '$student_id'
    ORDER BY a.scan_time DESC
    LIMIT 1
")->fetch_assoc();



/* ATTENDANCE PROGRESS */
$attendancePercentage = 0;

if ($joinedEvents > 0) {
    $attendancePercentage = round(($attendanceCount / $joinedEvents) * 100);
}

/* NEXT REGISTERED EVENT */
$nextEvent = $conn->query("
    SELECT 
        e.event_id,
        e.event_name,
        e.event_date,
        e.event_time,
        e.venue,
        e.event_poster,
        e.status
    FROM event_registration er
    JOIN events e ON er.event_id = e.event_id
    WHERE er.student_id = '$student_id'
    AND e.status IN ('Upcoming','Ongoing')
    AND e.event_date >= CURDATE()
    ORDER BY e.event_date ASC, e.event_time ASC
    LIMIT 1
")->fetch_assoc();

/* CERTIFICATE READY COUNT - OPTIONAL */
$certificateReady = $conn->query("
    SELECT COUNT(*) AS total
    FROM event_registration
    WHERE student_id = '$student_id'
    AND certificate_status = 'Issued'
")->fetch_assoc()['total'];

?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Scan QR Attendance - PERSADA Student Portal</title>

<link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>
<script src="https://unpkg.com/html5-qrcode"></script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins', sans-serif;
}

:root{
    --body-color:#fff7ec;
    --sidebar-color:#ffffff;
    --primary-color:#ff6b4a;
    --secondary-color:#f6b73c;
    --primary-light:#fff1e8;
    --text-color:#667085;
    --title-color:#16254c;
    --card-color:#ffffff;
    --border-color:rgba(255,107,74,.14);
    --shadow:0 15px 35px rgba(0,0,0,.08);
    --tran:.25s ease;
}

body{
    min-height:100vh;
    background:var(--body-color);
    transition:var(--tran);
}

body.dark{
    --body-color:#18191a;
    --sidebar-color:#242526;
    --primary-light:#3a3b3c;
    --text-color:#ccc;
    --title-color:#ffffff;
    --card-color:#242526;
    --border-color:rgba(255,255,255,.08);
}

/* =========================
   SIDEBAR
========================= */
.sidebar{
    position:fixed;
    top:0;
    left:0;
    height:100%;
    width:260px;
    padding:14px;
    background:var(--sidebar-color);
    box-shadow:8px 0 30px rgba(0,0,0,.06);
    transition:width .25s ease;
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
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:22px;
    font-weight:800;
    margin-right:12px;
    box-shadow:0 10px 25px rgba(255,107,74,.22);
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
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    color:white;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    font-size:22px;
    transition:var(--tran);
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
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    box-shadow:0 12px 25px rgba(255,107,74,.18);
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
    background:#ddd;
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
.home{
    position:absolute;
    left:260px;
    width:calc(100% - 260px);
    min-height:100vh;
    padding:35px;
    background:
        radial-gradient(circle at top right, rgba(255,107,74,.12), transparent 28%),
        radial-gradient(circle at top left, rgba(246,183,60,.12), transparent 30%),
        var(--body-color);
    transition:left .25s ease, width .25s ease;
}

.sidebar.close ~ .home{
    left:88px;
    width:calc(100% - 88px);
}

/* PAGE HEADER */
.scan-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:28px;
}

.scan-header h1{
    color:var(--title-color);
    font-size:36px;
    font-weight:800;
    margin-bottom:6px;
}

.scan-header p{
    color:var(--text-color);
    font-size:15px;
}

.student-badge{
    background:var(--card-color);
    border:1px solid var(--border-color);
    border-radius:50px;
    padding:12px 18px;
    color:var(--primary-color);
    font-weight:800;
    display:flex;
    align-items:center;
    gap:8px;
    box-shadow:var(--shadow);
}

/* SUMMARY CARDS */
.scan-stats{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:22px;
    margin-bottom:30px;
}

.scan-stat-card{
    background:var(--card-color);
    border-radius:26px;
    padding:24px;
    box-shadow:var(--shadow);
    border:1px solid var(--border-color);
    display:flex;
    align-items:center;
    gap:16px;
    position:relative;
    overflow:hidden;
}

.scan-stat-card::after{
    content:"";
    position:absolute;
    width:110px;
    height:110px;
    right:-45px;
    bottom:-45px;
    background:rgba(255,107,74,.07);
    border-radius:50%;
}

.scan-stat-icon{
    width:58px;
    height:58px;
    border-radius:20px;
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:30px;
    flex-shrink:0;
}

.scan-stat-card h3{
    color:var(--title-color);
    font-size:30px;
    font-weight:800;
    line-height:1;
    margin-bottom:6px;
}

.scan-stat-card p{
    color:var(--text-color);
    font-size:14px;
    font-weight:600;
}

/* MAIN GRID */
.scan-layout{
    display:grid;
    grid-template-columns:minmax(0, 1.15fr) minmax(380px, .85fr);
    gap:26px;
    align-items:start;
}

/* SCANNER CARD */
.scanner-card{
    background:var(--card-color);
    border-radius:32px;
    padding:30px;
    box-shadow:var(--shadow);
    border:1px solid var(--border-color);
}

.card-title{
    display:flex;
    align-items:center;
    gap:14px;
    margin-bottom:22px;
}

.card-title i{
    width:50px;
    height:50px;
    border-radius:18px;
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:26px;
}

.card-title h2{
    color:var(--title-color);
    font-size:24px;
    font-weight:800;
}

.card-title p{
    color:var(--text-color);
    font-size:14px;
    margin-top:3px;
}

/* CAMERA BOX */
.scanner-box{
    background:#fffaf6;
    border:2px dashed rgba(255,107,74,.26);
    border-radius:28px;
    padding:20px;
    min-height:390px;
    display:flex;
    align-items:center;
    justify-content:center;
    position:relative;
    overflow:hidden;
}
.scan-left,
.scan-right{
    display:flex;
    flex-direction:column;
    gap:26px;
}
#reader{
    width:100%;
    max-width:520px;
    min-height:360px;
    display:none;
}

#reader video{
    width:100% !important;
    border-radius:22px;
}

.scanner-placeholder{
    text-align:center;
    max-width:420px;
    margin:auto;
}

.scanner-placeholder i{
    font-size:90px;
    color:#ff6b4a;
    margin-bottom:15px;
}

.scanner-placeholder h3{
    color:var(--title-color);
    font-size:24px;
    margin-bottom:8px;
}

.scanner-placeholder p{
    color:var(--text-color);
    line-height:1.7;
    font-size:14px;
}

/* SCANNER ACTIONS */
.scanner-actions{
    display:flex;
    gap:14px;
    margin-top:24px;
}

.start-btn,
.stop-btn{
    border:none;
    padding:14px 22px;
    border-radius:18px;
    font-weight:800;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    transition:.3s ease;
}

.start-btn{
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    color:white;
    box-shadow:0 12px 25px rgba(255,107,74,.22);
}

.stop-btn{
    background:#e5e7eb;
    color:#667085;
}

.start-btn:hover,
.stop-btn:hover{
    transform:translateY(-3px);
}

/* RESULT CARD */
.result-card{
    background:var(--card-color);
    border-radius:32px;
    padding:28px;
    box-shadow:var(--shadow);
    border:1px solid var(--border-color);
    margin-bottom:26px;
}

.result-empty{
    text-align:center;
    padding:30px 10px;
}

.result-empty i{
    font-size:70px;
    color:#ff6b4a;
    margin-bottom:12px;
}

.result-empty h3{
    color:var(--title-color);
    font-size:22px;
    margin-bottom:8px;
}

.result-empty p{
    color:var(--text-color);
    font-size:14px;
    line-height:1.6;
}

.result-success{
    display:none;
    background:linear-gradient(135deg,#ecfdf5,#fff7ed);
    border:1px solid rgba(34,197,94,.22);
    border-radius:24px;
    padding:22px;
}

.result-success.show{
    display:block;
}

.success-icon{
    width:58px;
    height:58px;
    border-radius:20px;
    background:#22c55e;
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:32px;
    margin-bottom:16px;
}

.result-success h3{
    color:#166534;
    font-size:22px;
    margin-bottom:8px;
}

.result-success p{
    color:#5f6b85;
    font-size:14px;
    line-height:1.7;
}

/* REGISTERED EVENTS */
.registered-card{
    background:var(--card-color);
    border-radius:32px;
    padding:28px;
    box-shadow:var(--shadow);
    border:1px solid var(--border-color);
}

.registered-list{
    display:flex;
    flex-direction:column;
    gap:14px;
}

.registered-item{
    background:#fffaf6;
    border:1px solid rgba(255,107,74,.14);
    border-radius:22px;
    padding:14px;
    display:flex;
    align-items:center;
    gap:14px;
}

.registered-item img,
.event-mini-placeholder{
    width:64px;
    height:64px;
    border-radius:18px;
    object-fit:cover;
    background:#fff1e8;
    flex-shrink:0;
}

.event-mini-placeholder{
    display:flex;
    align-items:center;
    justify-content:center;
    color:#ff6b4a;
    font-size:30px;
}

.registered-info{
    flex:1;
}

.registered-info h4{
    color:var(--title-color);
    font-size:15px;
    margin-bottom:5px;
}

.registered-info p{
    color:var(--text-color);
    font-size:12px;
    margin-bottom:3px;
}

.registered-info i{
    color:#ff6b4a;
    margin-right:5px;
}

/* HISTORY */
.history-card{
    background:var(--card-color);
    border-radius:32px;
    padding:28px;
    box-shadow:var(--shadow);
    border:1px solid var(--border-color);
    margin-top:26px;
}

.history-list{
    display:flex;
    flex-direction:column;
    gap:14px;
}

.history-item{
    display:flex;
    align-items:center;
    gap:14px;
    background:#fffaf6;
    border-radius:20px;
    padding:14px;
    border:1px solid rgba(255,107,74,.12);
}

.history-icon{
    width:46px;
    height:46px;
    border-radius:16px;
    background:#dcfce7;
    color:#16a34a;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:23px;
    flex-shrink:0;
}

.history-item strong{
    color:var(--title-color);
    font-size:14px;
}

.history-item p{
    color:var(--text-color);
    font-size:12px;
    margin-top:3px;
}

.empty-small{
    text-align:center;
    padding:30px 15px;
    background:#fffaf6;
    border-radius:22px;
    color:var(--text-color);
}

.empty-small i{
    display:block;
    font-size:50px;
    color:#ff6b4a;
    margin-bottom:10px;
}

/* =========================
   PROFESSIONAL UPGRADE
========================= */

.next-event-card,
.progress-card{
    background:var(--card-color);
    border-radius:32px;
    padding:28px;
    box-shadow:var(--shadow);
    border:1px solid var(--border-color);
    margin-top:26px;
    position:relative;
    overflow:hidden;
}

.next-event-card::after,
.progress-card::after{
    content:"";
    position:absolute;
    width:150px;
    height:150px;
    right:-65px;
    bottom:-65px;
    background:rgba(255,107,74,.07);
    border-radius:50%;
}

.next-event-box{
    display:flex;
    gap:16px;
    align-items:center;
    background:linear-gradient(135deg,#fffaf6,#ffffff);
    border:1px solid rgba(255,107,74,.14);
    border-radius:24px;
    padding:16px;
    position:relative;
    z-index:1;
}

.next-event-box img,
.next-event-placeholder{
    width:86px;
    height:86px;
    border-radius:22px;
    object-fit:cover;
    flex-shrink:0;
    background:#fff1e8;
}

.next-event-placeholder{
    display:flex;
    align-items:center;
    justify-content:center;
    color:#ff6b4a;
    font-size:36px;
}

.next-event-info{
    flex:1;
    min-width:0;
}

.next-event-info h4{
    color:var(--title-color);
    font-size:18px;
    font-weight:800;
    margin-bottom:8px;
}

.next-event-info p{
    color:var(--text-color);
    font-size:13px;
    margin-bottom:5px;
}

.next-event-info i{
    color:#ff6b4a;
    margin-right:6px;
}

.countdown-box{
    margin-top:14px;
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:10px;
}

.countdown-item{
    background:#fff1e8;
    border-radius:16px;
    padding:10px;
    text-align:center;
    border:1px solid rgba(255,107,74,.13);
}

.countdown-item strong{
    display:block;
    color:#16254c;
    font-size:18px;
    font-weight:800;
}

.countdown-item span{
    font-size:11px;
    color:#667085;
    font-weight:700;
}

/* SCANNER ANIMATION */
.scanner-box.scanning::before{
    content:"";
    position:absolute;
    left:40px;
    right:40px;
    height:4px;
    background:linear-gradient(90deg,transparent,#ff6b4a,transparent);
    border-radius:999px;
    animation:scanLine 1.8s linear infinite;
    z-index:5;
}

@keyframes scanLine{
    0%{
        top:45px;
        opacity:.25;
    }
    50%{
        opacity:1;
    }
    100%{
        top:calc(100% - 45px);
        opacity:.25;
    }
}

/* ATTENDANCE RESULT PREMIUM */
.result-success{
    background:linear-gradient(135deg,#f0fff4,#fffaf6);
    border:1px solid rgba(34,197,94,.25);
    border-radius:26px;
    padding:24px;
}

.result-success.show{
    display:block;
}

.result-success-header{
    display:flex;
    align-items:center;
    gap:16px;
    margin-bottom:20px;
}

.success-icon{
    width:64px;
    height:64px;
    border-radius:22px;
    background:linear-gradient(135deg,#22c55e,#4ade80);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:34px;
    margin-bottom:0;
    flex-shrink:0;
}

.result-success-header h3{
    color:#166534;
    font-size:22px;
    font-weight:800;
    margin-bottom:4px;
}

.result-success-header span{
    color:#16a34a;
    font-size:13px;
    font-weight:800;
    background:#dcfce7;
    padding:6px 12px;
    border-radius:999px;
}

.result-info-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:12px;
}

.result-info-item{
    background:white;
    border:1px solid rgba(34,197,94,.14);
    border-radius:18px;
    padding:14px 16px;
}

.result-info-item.full{
    grid-column:1 / -1;
}

.result-info-item span{
    display:block;
    color:#667085;
    font-size:12px;
    font-weight:700;
    margin-bottom:5px;
}

.result-info-item strong{
    color:#16254c;
    font-size:15px;
    font-weight:800;
}

/* REGISTERED EVENT BADGE */
.registered-item{
    transition:.25s ease;
}

.registered-item:hover{
    transform:translateY(-3px);
    box-shadow:0 10px 24px rgba(255,107,74,.10);
}

.registered-info-top{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin-bottom:5px;
}

.mini-status{
    font-size:10px;
    font-weight:800;
    padding:5px 9px;
    border-radius:999px;
    background:#dbeafe;
    color:#2563eb;
    white-space:nowrap;
}

.mini-status.ongoing{
    background:#dcfce7;
    color:#16a34a;
}

/* ATTENDANCE HISTORY PREMIUM */
.history-item{
    justify-content:space-between;
    transition:.25s ease;
}

.history-item:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 22px rgba(22,37,76,.06);
}

.history-card,
.next-event-card,
.progress-card{
    margin-top:0;
}

.scan-left,
.scan-right{
    display:flex;
    flex-direction:column;
    gap:26px;
}


.history-left{
    display:flex;
    align-items:center;
    gap:14px;
}

.history-badge{
    font-size:11px;
    font-weight:800;
    color:#16a34a;
    background:#dcfce7;
    padding:6px 10px;
    border-radius:999px;
}

/* PROGRESS CARD */
.progress-content{
    position:relative;
    z-index:1;
}

.progress-top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:12px;
}

.progress-top h4{
    color:var(--title-color);
    font-size:17px;
    font-weight:800;
}

.progress-top span{
    color:#ff6b4a;
    font-weight:800;
}

.progress-bar{
    width:100%;
    height:13px;
    background:#fff1e8;
    border-radius:999px;
    overflow:hidden;
    margin-bottom:12px;
}

.progress-fill{
    height:100%;
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    border-radius:999px;
    transition:.5s ease;
}

.progress-caption{
    color:var(--text-color);
    font-size:13px;
}

/* TOAST */
.toast{
    position:fixed;
    top:25px;
    right:30px;
    background:white;
    border:1px solid rgba(34,197,94,.25);
    box-shadow:0 20px 50px rgba(0,0,0,.14);
    border-radius:22px;
    padding:16px 18px;
    display:flex;
    align-items:center;
    gap:13px;
    z-index:99999;
    transform:translateX(130%);
    opacity:0;
    transition:.35s ease;
}

.toast.show{
    transform:translateX(0);
    opacity:1;
}

.toast-icon{
    width:44px;
    height:44px;
    border-radius:15px;
    background:#dcfce7;
    color:#16a34a;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:24px;
    flex-shrink:0;
}

.toast strong{
    display:block;
    color:#16254c;
    font-size:14px;
    margin-bottom:2px;
}

.toast span{
    color:#667085;
    font-size:12px;
}

/* RESPONSIVE EXTRA */
@media(max-width:900px){
    .result-info-grid{
        grid-template-columns:1fr;
    }

    .result-info-item.full{
        grid-column:auto;
    }

    .countdown-box{
        grid-template-columns:1fr;
    }

    .toast{
        left:18px;
        right:18px;
        top:18px;
    }
}


/* RESPONSIVE */
@media(max-width:1150px){
    .scan-layout{
        grid-template-columns:1fr;
    }

    .scan-stats{
        grid-template-columns:1fr;
    }

    .scan-header{
        flex-direction:column;
        align-items:flex-start;
        gap:14px;
    }
}

@media(max-width:700px){
    .home{
        padding:22px;
    }

    .scanner-actions{
        flex-direction:column;
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
    P
</div>

            <div class="logo-text">
                <span class="name">PERSADA</span>
                <span class="profession">Student Portal</span>
            </div>

        </div>

        <i class='bx bx-chevron-right toggle'></i>

    </header>

    <div class="menu-bar">

        <div class="menu">

            <ul class="menu-links">

                <li class="nav-link">
                    <a href="dashboard.php">
                        <i class='bx bx-home-alt icon'></i>
                        <span class="text">Dashboard</span>
                    </a>
                </li>

                <li class="nav-link">
                    <a href="Myprofile.php">
                        <i class='bx bx-user icon'></i>
                        <span class="text">My Profile</span>
                    </a>
                </li>

                <li class="nav-link">
                    <a href="Event_List.php">
                        <i class='bx bx-calendar-event icon'></i>
                        <span class="text">Event List</span>
                    </a>
                </li>

                <li class="nav-link">
                   <a href="User_Scan_QR.php" class="active">
                        <i class='bx bx-qr-scan icon'></i>
                        <span class="text">Scan QR</span>
                    </a>
                </li>

                <li class="nav-link">
                    <a href="participation.php">
                        <i class='bx bx-history icon'></i>
                        <span class="text">Participation</span>
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

<!-- MAIN PAGE -->
<section class="home">

    <!-- PAGE HEADER -->
    <div class="scan-header">

        <div>

            <h1>Scan QR Attendance</h1>

            <p>
                Scan the QR code provided by the event organizer
                to record your attendance instantly.
            </p>

        </div>

        <div class="student-badge">
            <i class='bx bx-user-circle'></i>
            <?php echo $studentName; ?>
        </div>

    </div>

    <!-- SUMMARY CARDS -->
    <div class="scan-stats">

        <div class="scan-stat-card">

            <div class="scan-stat-icon">
                <i class='bx bx-calendar-event'></i>
            </div>

            <div>
                <h3><?php echo $joinedEvents; ?></h3>
                <p>Registered Events</p>
            </div>

        </div>

        <div class="scan-stat-card">

            <div class="scan-stat-icon">
                <i class='bx bx-check-circle'></i>
            </div>

            <div>
                <h3><?php echo $attendanceCount; ?></h3>
                <p>Attendance Recorded</p>
            </div>

        </div>

        <div class="scan-stat-card">

            <div class="scan-stat-icon">
                <i class='bx bx-award'></i>
            </div>

            <div>
                <h3><?php echo $participationCount; ?></h3>
                <p>Total Participation</p>
            </div>

        </div>

    </div>
<!-- MAIN GRID -->
<div class="scan-layout">

    <!-- LEFT SIDE -->
    <div class="scan-left">

        <!-- SCANNER CARD -->
        <div class="scanner-card">

            <div class="card-title">
                <i class='bx bx-qr-scan'></i>
                <div>
                    <h2>Attendance Scanner</h2>
                    <p>Use your device camera to scan event QR code.</p>
                </div>
            </div>

            <div class="scanner-box" id="scannerBox">
                <div id="reader"></div>

                <div class="scanner-placeholder" id="scannerPlaceholder">
                    <i class='bx bx-qr-scan'></i>
                    <h3>Ready to Scan</h3>
                    <p>
                        Click Start Scanner and point your camera towards
                        the attendance QR code generated by the event administrator.
                    </p>
                </div>
            </div>

            <div class="scanner-actions">
                <button class="start-btn" onclick="startScanner()">
                    <i class='bx bx-camera'></i>
                    Start Scanner
                </button>

                <button class="stop-btn" onclick="stopScanner()">
                    <i class='bx bx-stop-circle'></i>
                    Stop Scanner
                </button>
            </div>

        </div>

        <!-- ATTENDANCE RESULT -->
        <div class="history-card">

            <div class="card-title">
                <i class='bx bx-check-shield'></i>
                <div>
                    <h2>Attendance Result</h2>
                    <p>Latest attendance verification.</p>
                </div>
            </div>

            <?php if($latestAttendance){ ?>

                <div class="result-success show" id="attendanceSuccess">

                    <div class="result-success-header">
                        <div class="success-icon">
                            <i class='bx bx-check'></i>
                        </div>

                        <div>
                            <span>Recorded Successfully</span>
                            <h3>Latest Attendance Recorded</h3>
                        </div>
                    </div>

                    <div class="result-info-grid" id="attendanceMessage">

                        <div class="result-info-item">
                            <span>Event</span>
                            <strong><?php echo $latestAttendance['event_name']; ?></strong>
                        </div>

                        <div class="result-info-item">
                            <span>Status</span>
                            <strong><?php echo $latestAttendance['attendance_status']; ?></strong>
                        </div>

                        <div class="result-info-item full">
                            <span>Scan Time</span>
                            <strong><?php echo date("d F Y, h:i A", strtotime($latestAttendance['scan_time'])); ?></strong>
                        </div>

                    </div>

                </div>

                <div class="result-empty" id="emptyResult" style="display:none;"></div>

            <?php } else { ?>

                <div class="result-empty" id="emptyResult">
                    <i class='bx bx-time-five'></i>
                    <h3>Waiting for Scan</h3>
                    <p>Scan a valid QR attendance code to record your attendance.</p>
                </div>

                <div class="result-success" id="attendanceSuccess">

                    <div class="result-success-header">
                        <div class="success-icon">
                            <i class='bx bx-check'></i>
                        </div>

                        <div>
                            <span>Recorded Successfully</span>
                            <h3>Attendance Successfully Recorded</h3>
                        </div>
                    </div>

                    <div class="result-info-grid" id="attendanceMessage"></div>

                </div>

            <?php } ?>

        </div>

        <!-- ATTENDANCE PROGRESS -->
        <div class="progress-card">

            <div class="card-title">
                <i class='bx bx-line-chart'></i>
                <div>
                    <h2>Attendance Progress</h2>
                    <p>Your attendance completion rate.</p>
                </div>
            </div>

            <div class="progress-content">

                <div class="progress-top">
                    <h4><?php echo $attendanceCount; ?> / <?php echo $joinedEvents; ?> Events Attended</h4>
                    <span><?php echo $attendancePercentage; ?>%</span>
                </div>

                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $attendancePercentage; ?>%;"></div>
                </div>

                <p class="progress-caption">
                    Keep joining and attending PERSADA activities to improve your participation record.
                </p>

            </div>

        </div>

    </div>

    <!-- RIGHT SIDE -->
    <div class="scan-right">

        <!-- REGISTERED EVENTS -->
        <div class="registered-card">

            <div class="card-title">
                <i class='bx bx-calendar-check'></i>
                <div>
                    <h2>My Registered Events</h2>
                    <p>Events available for attendance.</p>
                </div>
            </div>

            <div class="registered-list">

                <?php if($registeredEvents->num_rows > 0){ ?>

                    <?php while($event = $registeredEvents->fetch_assoc()){ ?>

                        <div class="registered-item">

                            <?php if(!empty($event['event_poster'])){ ?>
                                <img src="<?php echo $event['event_poster']; ?>" alt="Event Poster">
                            <?php } else { ?>
                                <div class="event-mini-placeholder">
                                    <i class='bx bx-calendar-event'></i>
                                </div>
                            <?php } ?>

                            <div class="registered-info">

                                <div class="registered-info-top">
                                    <h4><?php echo $event['event_name']; ?></h4>

                                    <span class="mini-status <?php echo strtolower($event['status'] ?? 'upcoming'); ?>">
                                        <?php echo $event['status'] ?? 'Upcoming'; ?>
                                    </span>
                                </div>

                                <p>
                                    <i class='bx bx-calendar'></i>
                                    <?php echo date("d M Y", strtotime($event['event_date'])); ?>
                                </p>

                                <p>
                                    <i class='bx bx-time'></i>
                                    <?php echo date("h:i A", strtotime($event['event_time'])); ?>
                                </p>

                                <p>
                                    <i class='bx bx-map'></i>
                                    <?php echo $event['venue']; ?>
                                </p>

                            </div>

                        </div>

                    <?php } ?>

                <?php } else { ?>

                    <div class="empty-small">
                        <i class='bx bx-calendar-x'></i>
                        No registered events found.
                    </div>

                <?php } ?>

            </div>

        </div>

        <!-- NEXT EVENT -->
        <div class="next-event-card">

            <div class="card-title">
                <i class='bx bx-calendar-star'></i>
                <div>
                    <h2>Next Event</h2>
                    <p>Your nearest registered activity.</p>
                </div>
            </div>

            <?php if($nextEvent){ ?>

                <div class="next-event-box">

                    <?php if(!empty($nextEvent['event_poster'])){ ?>
                        <img src="<?php echo $nextEvent['event_poster']; ?>" alt="Next Event">
                    <?php } else { ?>
                        <div class="next-event-placeholder">
                            <i class='bx bx-calendar-event'></i>
                        </div>
                    <?php } ?>

                    <div class="next-event-info">
                        <h4><?php echo $nextEvent['event_name']; ?></h4>

                        <p>
                            <i class='bx bx-calendar'></i>
                            <?php echo date("d F Y", strtotime($nextEvent['event_date'])); ?>
                        </p>

                        <p>
                            <i class='bx bx-time'></i>
                            <?php echo date("h:i A", strtotime($nextEvent['event_time'])); ?>
                        </p>

                        <p>
                            <i class='bx bx-map'></i>
                            <?php echo $nextEvent['venue']; ?>
                        </p>
                    </div>

                </div>

            <?php } else { ?>

                <div class="empty-small">
                    <i class='bx bx-calendar-x'></i>
                    No upcoming registered event.
                </div>

            <?php } ?>

        </div>

        <!-- ATTENDANCE HISTORY -->
        <div class="history-card">

            <div class="card-title">
                <i class='bx bx-history'></i>
                <div>
                    <h2>Attendance History</h2>
                    <p>Your latest attendance records.</p>
                </div>
            </div>

            <div class="history-list">

                <?php if($attendanceHistory->num_rows > 0){ ?>

                    <?php while($history = $attendanceHistory->fetch_assoc()){ ?>

                        <div class="history-item">

                            <div class="history-left">
                                <div class="history-icon">
                                    <i class='bx bx-check'></i>
                                </div>

                                <div>
                                    <strong><?php echo $history['event_name']; ?></strong>
                                    <p><?php echo date("d F Y, h:i A", strtotime($history['scan_time'])); ?></p>
                                </div>
                            </div>

                            <span class="history-badge">
                                <?php echo $history['attendance_status']; ?>
                            </span>

                        </div>

                    <?php } ?>

                <?php } else { ?>

                    <div class="empty-small">
                        <i class='bx bx-history'></i>
                        No attendance history found.
                    </div>

                <?php } ?>

            </div>

        </div>

    </div>

</div>

</section>
<!-- SUCCESS TOAST -->
<div class="toast" id="toastBox">
    <div class="toast-icon" id="toastIcon">
        <i class='bx bx-check'></i>
    </div>

    <div>
        <strong id="toastTitle">Attendance Recorded</strong>
        <span id="toastMessage">Your attendance has been recorded successfully.</span>
    </div>
</div>
<script>
const body = document.querySelector("body");
const sidebar = document.querySelector(".sidebar");
const toggle = document.querySelector(".toggle");
const modeSwitch = document.querySelector(".toggle-switch");
const modeText = document.querySelector(".mode-text");

let html5QrCode = null;
let isScanning = false;
let alreadyProcessing = false;

toggle.addEventListener("click", () => {
    sidebar.classList.toggle("close");
});

modeSwitch.addEventListener("click", () => {
    body.classList.toggle("dark");
    modeText.innerText = body.classList.contains("dark") ? "Light mode" : "Dark mode";
});

function showToast(type, title, message){

    const toastBox = document.getElementById("toastBox");
    const toastIcon = document.getElementById("toastIcon");
    const toastTitle = document.getElementById("toastTitle");
    const toastMessage = document.getElementById("toastMessage");

    toastBox.className = "toast show";

    if(type === "error"){
        toastBox.classList.add("error");
        toastIcon.innerHTML = "<i class='bx bx-x'></i>";
    }
    else if(type === "warning"){
        toastBox.classList.add("warning");
        toastIcon.innerHTML = "<i class='bx bx-error'></i>";
    }
    else{
        toastIcon.innerHTML = "<i class='bx bx-check'></i>";
    }

    toastTitle.innerText = title;
    toastMessage.innerText = message;

    setTimeout(() => {
        toastBox.className = "toast";
    }, 3500);
}

function startScanner(){

    if(isScanning) return;

    alreadyProcessing = false;

    document.getElementById("scannerPlaceholder").style.display = "none";
    document.getElementById("reader").style.display = "block";
    document.getElementById("scannerBox").classList.add("scanning");

    html5QrCode = new Html5Qrcode("reader");

    html5QrCode.start(
        { facingMode: "environment" },
        {
            fps: 10,
            qrbox: { width: 260, height: 260 }
        },
        async function(decodedText){

            if(alreadyProcessing) return;
            alreadyProcessing = true;

            await markAttendance(decodedText);
            await stopScanner();
        }
    ).then(() => {
        isScanning = true;
    }).catch(() => {
        showToast("error", "Camera Error", "Please allow camera permission.");
        document.getElementById("scannerPlaceholder").style.display = "block";
        document.getElementById("reader").style.display = "none";
        document.getElementById("scannerBox").classList.remove("scanning");
    });
}

async function stopScanner(){

    if(html5QrCode){
        try{
            const state = html5QrCode.getState();

            if(state === Html5QrcodeScannerState.SCANNING){
                await html5QrCode.stop();
            }

            html5QrCode.clear();

        }catch(error){
            console.log("Stop scanner error:", error);
        }
    }

    isScanning = false;

    document.getElementById("reader").style.display = "none";
    document.getElementById("scannerPlaceholder").style.display = "block";
    document.getElementById("scannerBox").classList.remove("scanning");
}

async function markAttendance(qrText){

    try{
        const response = await fetch(qrText);
        const data = await response.json();

        const emptyResult = document.getElementById("emptyResult");
        const attendanceSuccess = document.getElementById("attendanceSuccess");
        const attendanceMessage = document.getElementById("attendanceMessage");

        emptyResult.style.display = "none";
        attendanceSuccess.style.display = "block";
        attendanceSuccess.classList.add("show");

        if(data.status === "success"){

            attendanceSuccess.querySelector("h3").innerText = "Attendance Recorded Successfully";

            attendanceMessage.innerHTML =
                "<div class='attendance-detail-item'>" +
                    "<span>Event</span>" +
                    "<strong>" + data.event_name + "</strong>" +
                "</div>" +
                "<div class='attendance-detail-item'>" +
                    "<span>Status</span>" +
                    "<strong>Present</strong>" +
                "</div>" +
                "<div class='attendance-detail-item full'>" +
                    "<span>Scan Time</span>" +
                    "<strong>" + data.scan_time + "</strong>" +
                "</div>";

            showToast("success", "Attendance Recorded", data.event_name);
        }
        else if(data.status === "already"){

            attendanceSuccess.querySelector("h3").innerText = "Attendance Already Recorded";

            attendanceMessage.innerHTML =
                "<div class='attendance-detail-item'>" +
                    "<span>Event</span>" +
                    "<strong>" + data.event_name + "</strong>" +
                "</div>" +
                "<div class='attendance-detail-item'>" +
                    "<span>Status</span>" +
                    "<strong>Already Present</strong>" +
                "</div>" +
                "<div class='attendance-detail-item full'>" +
                    "<span>Scan Time</span>" +
                    "<strong>" + data.scan_time + "</strong>" +
                "</div>";

            showToast("warning", "Already Recorded", data.event_name);
        }
        else{

            attendanceSuccess.querySelector("h3").innerText = "Scan Failed";

            attendanceMessage.innerHTML =
                "<div class='attendance-detail-item full'>" +
                    "<span>Error Message</span>" +
                    "<strong>" + data.message + "</strong>" +
                "</div>";

            showToast("error", "Scan Failed", data.message);
        }

        attendanceSuccess.scrollIntoView({
            behavior: "smooth",
            block: "center"
        });

    }catch(error){
        showToast("error", "Invalid QR", "This QR response is not valid.");
        console.log(error);
    }
}
</script>
</body>
</html>