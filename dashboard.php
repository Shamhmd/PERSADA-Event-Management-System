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

$studentQuery = $conn->prepare("SELECT * FROM students WHERE id = ?");
$studentQuery->bind_param("i", $student_id);
$studentQuery->execute();
$student = $studentQuery->get_result()->fetch_assoc();

$events = $conn->query("
    SELECT * FROM events 
    WHERE status IN ('Upcoming','Ongoing')
    ORDER BY event_date ASC
");

$history = $conn->query("
    SELECT 
        e.event_name, 
        e.event_date, 
        e.venue, 
        e.event_poster,
        er.registration_date
    FROM event_registration er
    JOIN events e ON er.event_id = e.event_id
    WHERE er.student_id = '$student_id'
    ORDER BY er.registration_date DESC
");

if (isset($_POST['join_event'])) {
    $event_id = $_POST['event_id'];

    $checkJoin = $conn->prepare("
        SELECT * FROM event_registration 
        WHERE student_id = ? AND event_id = ?
    ");
    $checkJoin->bind_param("ii", $student_id, $event_id);
    $checkJoin->execute();
    $checkResult = $checkJoin->get_result();

    if ($checkResult->num_rows > 0) {
        echo "<script>alert('You have already joined this event.'); window.location='dashboard.php#events';</script>";
        exit();
    } else {
        $join = $conn->prepare("
            INSERT INTO event_registration (student_id, event_id)
            VALUES (?, ?)
        ");
        $join->bind_param("ii", $student_id, $event_id);

        if ($join->execute()) {
            echo "<script>alert('Event joined successfully!'); window.location='dashboard.php#events';</script>";
            exit();
        }
    }
}


/* PARTICIPATION SNAPSHOT DATA */
$totalJoined = $history->num_rows;

$attendedQuery = $conn->query("
    SELECT COUNT(*) AS total
    FROM attendance
    WHERE student_id = '$student_id'
    AND attendance_status = 'Present'
");
$totalAttended = $attendedQuery->fetch_assoc()['total'];

$attendanceRate = 0;
if ($totalJoined > 0) {
    $attendanceRate = round(($totalAttended / $totalJoined) * 100);
}

$latestParticipation = $conn->query("
    SELECT 
        e.event_name,
        e.event_date,
        e.venue,
        e.event_poster,
        er.registration_date
    FROM event_registration er
    JOIN events e ON er.event_id = e.event_id
    WHERE er.student_id = '$student_id'
    ORDER BY er.registration_date DESC
    LIMIT 1
")->fetch_assoc();

$achievementTitle = "New Member";
$achievementDesc = "Join more events to unlock achievements.";

if ($totalJoined >= 3 && $attendanceRate >= 70) {
    $achievementTitle = "Active Member";
    $achievementDesc = "Great job! You are actively joining PERSADA events.";
} elseif ($totalJoined >= 1) {
    $achievementTitle = "First Step";
    $achievementDesc = "You have started your participation journey.";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PERSADA Student Dashboard</title>
    <link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

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
    --primary-light:#fff1e8;
    --text-color:#667085;
    --title-color:#16254c;
    --card-color:#ffffff;
    --shadow:0 15px 35px rgba(0,0,0,.08);
    --tran:all .3s ease;
}

body{
    min-height:100vh;
    background:var(--body-color);
    transition:var(--tran);
}

body.dark{
    --body-color:#18191a;
    --sidebar-color:#242526;
    --primary-color:#ff8a5c;
    --primary-light:#3a3b3c;
    --text-color:#ccc;
    --title-color:#ffffff;
    --card-color:#242526;
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
    box-shadow:8px 0 30px rgba(0,0,0,.06);
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
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:22px;
    font-weight:700;
    margin-right:12px;
}

.logo-text{
    display:flex;
    flex-direction:column;
}

.logo-text .name{
    font-size:20px;
    font-weight:700;
    color:var(--title-color);
}

.logo-text .profession{
    font-size:13px;
    color:var(--text-color);
}

.sidebar.close .text{
    opacity:0;
}

.toggle{
    position:absolute;
    top:50%;
    right:-27px;
    transform:translateY(-50%) rotate(180deg);
    width:28px;
    height:28px;
    background:var(--primary-color);
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
    font-weight:600;
    color:var(--text-color);
    white-space:nowrap;
    transition:var(--tran);
}

.sidebar li a:hover{
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
}

.sidebar li a:hover .icon,
.sidebar li a:hover .text{
    color:white;
}

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

/* MAIN */
.home{
    position:absolute;
    left:260px;
    width:calc(100% - 260px);
    min-height:100vh;
    padding:35px;
    background:var(--body-color);
    transition:var(--tran);
}

.sidebar.close ~ .home{
    left:88px;
    width:calc(100% - 88px);
}

.dashboard-header{
    padding:0 10px;
    margin-bottom:30px;
    background:none;
    box-shadow:none;
}

body.dark .dashboard-header{
    background:var(--card-color);
}

.dashboard-header h1{
    font-size:32px;
    font-weight:700;
    color:#16254c;
    margin-bottom:10px;
}

.dashboard-header p{
    color:var(--text-color);
    margin-top:8px;
}

.current-date{
    font-size:18px;
    color:#8b6f5c;
    font-weight:500;
}

.current-date i{
    color:#ff6b4a;
    font-size:18px;
}




/* CARDS */
.dashboard-cards{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:22px;
    margin-bottom:35px;
}

.card{
    background:var(--card-color);
    padding:25px;
    border-radius:24px;
    box-shadow:var(--shadow);
    border:1px solid rgba(255,107,74,.12);
}

.dashboard-cards{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:24px;
    margin-bottom:35px;
}

.stat-card{
    min-height:145px;
    padding:20px;
    border-radius:20px;
    color:white;
    box-shadow:0 18px 38px rgba(0,0,0,.12);
    position:relative;
    overflow:hidden;
    transition:.3s ease;
}

.stat-card:hover{
    transform:translateY(-8px);
}

.stat-card::after{
    content:"";
    position:absolute;
    width:100px;
    height:100px;
    right:-30px;
    bottom:-35px;
    border-radius:50%;
    background:rgba(255,255,255,.12);
   
}

.stat-card.purple{
    background:linear-gradient(135deg,#6f2d8f,#8e44ad);
}

.stat-card.cyan{
    background:linear-gradient(135deg,#75d0d1,#9be1dc);
}

.stat-card.orange{
    background:linear-gradient(135deg,#ff6b4a,#ff9966);
}

.stat-top{
    display:flex;
    justify-content:space-between;
    align-items:center;
  margin-bottom:15px;
}

.stat-icon{
    width:40px;
    height:40px;
    font-size:30px;
    border-radius:18px;
    display:flex;
    justify-content:center;
    align-items:center;
    
}

.stat-card h3{
    font-size:20px;
    margin-bottom:10px;
    line-height:1.2;
    
}

.stat-card p{
    font-size:14px;
    opacity:.9;
    margin-bottom:12px;
}

.progress-line{
    width:100%;
    height:7px;
    background:rgba(255,255,255,.28);
    border-radius:20px;
    overflow:hidden;
}

.progress-line span{
    display:block;
    height:100%;
    background:white;
    border-radius:20px;
}

/* SECTION */
.section{
    background:var(--card-color);
    border-radius:28px;
    padding:30px;
    box-shadow:var(--shadow);
    margin-bottom:35px;
}

.section h2{
    color:var(--title-color);
    margin-bottom:20px;
}

/* PROFILE */
.dashboard-layout{
    display:grid;
    grid-template-columns:1fr 390px;
    gap:18px;
    margin-bottom:35px;
}

.dashboard-right{
    width:100%;
    margin-top:-145px;
    margin-left: 26px;
}

.side-profile-card{
    background:#EBE6D3;
    border-radius:28px;
    padding:30px;
    box-shadow:0 15px 35px rgba(0,0,0,.08);
    position:sticky;
    top:25px;
    min-height:720px;

    text-align:center;
}

.side-profile-card h2{
    color:#16254c;
    font-size:24px;
    margin-bottom:5px;
}

.progress-text{
    color:#ff6b4a;
    font-size:15px;
    font-weight:600;
}

.avatar{
    width:120px;
    height:120px;
    border-radius:50%;
    overflow:hidden;
    border:6px solid #fff;
    box-shadow:0 5px 15px rgba(0,0,0,.1);

    margin:20px auto;
    display:flex;
    justify-content:center;
    align-items:center;
}
.avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
    object-position:center;
    display:block;
}

.profile-ring{
    width:100px;
    height:100px;
    border-radius:50%;
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    color:white;
    font-size:40px;
    font-weight:700;
    display:flex;
    justify-content:center;
    align-items:center;
    border:6px solid #fff1e8;
}

.side-profile-card h3{
    text-align:center;
    color:#16254c;
}

.profile-email{
    text-align:center;
    color:#888;
    font-size:13px;
    margin-top:4px;
    margin-bottom:25px;
}

.profile-menu{
    display:flex;
    flex-direction:column;
    gap:15px;
}

.profile-menu-item{
    display:flex;
    align-items:center;
    gap:15px;
    background:#fff7ef;
    padding:14px;
    border-radius:16px;
}

.profile-menu-item i:first-child{
    width:40px;
    height:40px;
    border-radius:50%;
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:20px;
}

.profile-menu-item div{
    flex:1;
}

.profile-menu-item strong{
    display:block;
    color:#16254c;
    font-size:14px;
}

.profile-menu-item span{
    font-size:12px;
    color:#777;
}

.arrow{
    color:#aaa;
    font-size:22px;
}

.profile-extra{
    margin-top:28px;
}

.profile-section-title{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:14px;
}

.profile-section-title span{
    color:#16254c;
    font-size:17px;
    font-weight:700;
}

.profile-section-title a{
    color:#ff6b4a;
    font-size:12px;
    font-weight:600;
    text-decoration:none;
}

.mini-action{
    display:flex;
    align-items:center;
    gap:14px;
    margin-bottom:14px;
    padding:14px;
    border-radius:18px;
    background:#fff7ef;
}

.mini-action i{
    width:42px;
    height:42px;
    border-radius:50%;
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:20px;
}

.mini-action strong,
.membership-box strong{
    display:block;
    color:#16254c;
    font-size:14px;
}

.mini-action span,
.membership-box span{
    color:#856E5D;
    font-size:12px;
}

.team-title{
    margin-top:24px;
}

.membership-box{
    display:flex;
    align-items:center;
    gap:14px;
    background:#fff1e8;
    padding:16px;
    border-radius:20px;
}

.membership-box i{
    width:44px;
    height:44px;
    border-radius:50%;
    background:#ff6b4a;
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:22px;
}

/* EVENTS */
.event-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:22px;
}

.event-card{
    background:var(--primary-light);
    padding:24px;
    border-radius:24px;
    transition:var(--tran);
}

.event-card:hover{
    transform:translateY(-8px);
    box-shadow:0 18px 38px rgba(255,107,74,.18);
}

.event-card h3{
    color:var(--title-color);
    margin-bottom:12px;
}

.event-card p{
    color:var(--text-color);
    font-size:14px;
    margin-bottom:8px;
}

.status{
    display:inline-block;
    background:#fff;
    color:#ff6b4a;
    padding:6px 14px;
    border-radius:20px;
    font-size:12px;
    font-weight:700;
    margin-bottom:12px;
}

.join-btn{
    display:inline-block;
    margin-top:14px;
    padding:12px 22px;
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    color:white;
    border-radius:30px;
    text-decoration:none;
    font-weight:700;
}

/* QR */
.qr-box{
    text-align:center;
    padding:30px;
    background:var(--primary-light);
    border-radius:22px;
}

.qr-box i{
    font-size:70px;
    color:var(--primary-color);
}

.qr-box p{
    color:var(--text-color);
    margin-top:10px;
}

/* HISTORY */
table{
    width:100%;
    border-collapse:collapse;
}

th, td{
    padding:14px;
    text-align:left;
    border-bottom:1px solid rgba(0,0,0,.08);
    color:var(--text-color);
}

th{
    color:var(--title-color);
}


/* ===========================
   MY EVENT LIST
=========================== */

.my-event-section{
    margin-top:10px;
    margin-bottom:35px;
}

.section-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
}

.section-header h2{
    font-size:28px;
    color:#16254c;
    margin-bottom:5px;
}

.section-header p{
    color:#856E5D;
    font-size:14px;
}

.section-header a{
    color:#ff6b4a;
    font-weight:600;
    text-decoration:none;
}

.section-header a:hover{
    text-decoration:underline;
}

.my-event-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));
    gap:22px;
}

.my-event-card{
    background:#ffffff;
    border-radius:26px;
    padding:18px;
    display:flex;
    gap:18px;
    align-items:center;
    box-shadow:0 16px 35px rgba(0,0,0,.07);
    border:1px solid rgba(255,107,74,.12);
    transition:.25s ease;
}



.event-placeholder{
    width:100%;
    height:100%;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#ff6b4a;
    font-size:45px;
}

.my-event-card{
    align-items:center;
}


.event-badge{
    display:inline-block;
    background:#fff1e8;
    color:#ff6b4a;
    padding:6px 14px;
    border-radius:20px;
    font-size:12px;
    font-weight:700;
    margin-bottom:15px;
}

.my-event-card:hover{
    transform:translateY(-5px);
    box-shadow:0 20px 45px rgba(255,107,74,.14);
}
.event-image{
    width:125px;
    height:125px;
    min-width:125px;
    border-radius:22px;
    overflow:hidden;
    background:#fff1e8;
    margin:0;
}

.event-image img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
}

.event-content{
    flex:1;
    padding:0;
}

.event-badge{
    display:inline-flex;
    background:#fff1e8;
    color:#ff6b4a;
    padding:6px 13px;
    border-radius:999px;
    font-size:11px;
    font-weight:800;
    margin-bottom:10px;
}

.event-content h3{
    color:#16254c;
    font-size:20px;
    line-height:1.25;
    margin-bottom:10px;
}

.event-content p{
    color:#667085;
    font-size:13px;
    margin-bottom:7px;
}

.event-content i{
    color:#ff6b4a;
    margin-right:7px;
}
.event-actions{
    display:flex;
    gap:10px;
    margin-top:12px;
    flex-wrap:wrap;
}

.scan-btn,
.detail-btn{
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:10px 16px;
    border-radius:14px;
    text-decoration:none;
    font-size:13px;
    font-weight:800;
}

.scan-btn{
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    color:white;
}

.detail-btn{
    background:#fff1e8;
    color:#ff6b4a;
}

.scan-btn i,
.detail-btn i{
    color:inherit !important;
    margin-right:0 !important;
    font-size:16px;
}

.scan-btn,
.detail-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    padding:10px 16px;
    border-radius:14px;
    text-decoration:none;
    font-size:13px;
    font-weight:800;
}

@media(max-width:650px){
    .my-event-card{
        flex-direction:column;
        align-items:flex-start;
    }

    .event-image{
        width:100%;
        height:180px;
    }
}


.scan-btn:hover{
    opacity:.9;
}

.empty-event{
    grid-column:1 / -1;
    background:#ffffff;
    border-radius:24px;
    text-align:center;
    padding:60px;
    box-shadow:0 15px 35px rgba(0,0,0,.08);
}

.empty-event i{
    font-size:70px;
    color:#ff6b4a;
    margin-bottom:15px;
}

.empty-event h3{
    color:#16254c;
    margin-bottom:10px;
}

.empty-event p{
    color:#667085;
    margin-bottom:20px;
}

.empty-event a{
    display:inline-block;
    padding:12px 24px;
    border-radius:30px;
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    color:white;
    text-decoration:none;
    font-weight:600;
}


   
.event-mini-top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:8px;
}

.event-mini-top span{
    color:#856E5D;
    font-size:13px;
    font-weight:600;
}

.event-mini-top i{
    color:#16254c;
    font-size:22px;
}

.event-progress-mini h4{
    color:#16254c;
    font-size:18px;
    margin-bottom:16px;
}

.mini-detail{
    display:flex;
    align-items:center;
    gap:10px;
    margin-bottom:10px;
}

.mini-detail p{
    margin:0;
    color:#667085;
    font-size:14px;
}

.dot{
    width:10px;
    height:10px;
    border-radius:50%;
}

.dot.purple{
    background:#8e44ad;
}

.dot.blue{
    background:#75d0d1;
}

.dot.orange{
    background:#ff6b4a;
}

.no-progress{
    grid-column:1 / -1;
    text-align:center;
    padding:30px;
    background:#fff7ef;
    border-radius:22px;
}


.participation-snapshot{
    background:#ffffff;
    border-radius:30px;
    padding:34px;
    margin-top:35px;
    box-shadow:0 18px 45px rgba(0,0,0,.08);
    border:1px solid rgba(255,107,74,.12);
}

.snapshot-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:20px;
    margin-bottom:28px;
}

.snapshot-label{
    display:inline-flex;
    padding:7px 14px;
    border-radius:999px;
    background:#fff1e8;
    color:#ff6b4a;
    font-size:12px;
    font-weight:800;
    margin-bottom:10px;
}

.snapshot-header h2{
    color:#16254c;
    font-size:30px;
    font-weight:800;
    margin-bottom:6px;
}

.snapshot-header p{
    color:#856E5D;
    font-size:14px;
}

.view-participation-btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:13px 20px;
    border-radius:18px;
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    color:white;
    text-decoration:none;
    font-weight:800;
    box-shadow:0 12px 28px rgba(255,107,74,.22);
    white-space:nowrap;
}

.snapshot-grid{
    display:grid;
    grid-template-columns:1.4fr .8fr .8fr;
    gap:22px;
}

.snapshot-main-card,
.snapshot-side-card{
    background:linear-gradient(135deg,#fffaf6,#ffffff);
    border:1px solid rgba(255,107,74,.14);
    border-radius:26px;
    padding:24px;
    min-height:230px;
}

.snapshot-card-title{
    display:flex;
    align-items:center;
    gap:14px;
    margin-bottom:22px;
}

.snapshot-card-title i{
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

.snapshot-card-title h3{
    color:#16254c;
    font-size:20px;
    font-weight:800;
}

.snapshot-card-title p{
    color:#667085;
    font-size:13px;
}

.latest-event-preview{
    display:flex;
    align-items:center;
    gap:18px;
}

.latest-event-img{
    width:125px;
    height:125px;
    border-radius:24px;
    background:#fff1e8;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
    flex-shrink:0;
}

.latest-event-img img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.latest-event-img i{
    font-size:44px;
    color:#ff6b4a;
}

.event-mini-badge{
    display:inline-flex;
    padding:6px 12px;
    border-radius:999px;
    background:#fff1e8;
    color:#ff6b4a;
    font-size:11px;
    font-weight:800;
    margin-bottom:8px;
}

.latest-event-info h4{
    color:#16254c;
    font-size:21px;
    font-weight:800;
    margin-bottom:10px;
}

.latest-event-info p{
    color:#667085;
    font-size:13px;
    margin-bottom:7px;
}

.latest-event-info i{
    color:#ff6b4a;
    margin-right:6px;
}

.mini-progress-number{
    color:#16254c;
    font-size:44px;
    font-weight:900;
    margin-bottom:15px;
}

.mini-progress-bar{
    width:100%;
    height:12px;
    border-radius:999px;
    background:#fff1e8;
    overflow:hidden;
    margin-bottom:12px;
}

.mini-progress-bar span{
    display:block;
    height:100%;
    border-radius:999px;
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
}

.mini-progress-text{
    color:#667085;
    font-size:14px;
    font-weight:600;
}

.achievement-preview{
    text-align:center;
}

.achievement-icon{
    width:74px;
    height:74px;
    margin:8px auto 15px;
    border-radius:24px;
    background:#fff1e8;
    color:#ff6b4a;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:40px;
}

.achievement-preview h4{
    color:#16254c;
    font-size:22px;
    font-weight:800;
    margin-bottom:8px;
}

.achievement-preview p{
    color:#667085;
    font-size:14px;
    line-height:1.6;
}

.snapshot-empty{
    text-align:center;
    padding:25px;
    color:#667085;
}

.snapshot-empty i{
    font-size:55px;
    color:#ff6b4a;
    margin-bottom:12px;
}

.snapshot-empty h4{
    color:#16254c;
    font-size:20px;
    margin-bottom:6px;
}

@media(max-width:1200px){
    .snapshot-grid{
        grid-template-columns:1fr;
    }

    .snapshot-header{
        flex-direction:column;
        align-items:flex-start;
    }

    .latest-event-preview{
        flex-direction:column;
        align-items:flex-start;
    }
}





.no-progress i{
    font-size:45px;
    color:#ff6b4a;
}

@media(max-width:1000px){
    .event-progress-wrapper{
        grid-template-columns:1fr;
    }
}

@media(max-width:1200px){

    .dashboard-layout{
        grid-template-columns:1fr;
    }

    .side-profile-card{
        position:relative;
        top:0;
    }

}



@media(max-width:1000px){
    .dashboard-cards,
    .event-grid,
    .profile-grid{
        grid-template-columns:1fr;
    }
}
</style>
</head>

<body>

<nav class="sidebar close">
    <header>
        <div class="image-text">
            <div class="logo-box">P</div>

            <div class="text logo-text">
                <span class="name">PERSADA</span>
                <span class="profession">Student Portal</span>
            </div>
        </div>

        <i class='bx bx-chevron-right toggle'></i>
    </header>

    <div class="menu-bar">
        <div class="menu">
            <ul class="menu-links">
                <li><a href="#dashboard"><i class='bx bx-home-alt icon'></i><span class="text">Dashboard</span></a></li>
                <li><a href="Myprofile.php"><i class='bx bx-user icon'></i><span class="text">My Profile</span></a></li>
                <li><a href="Event_List.php"><i class='bx bx-calendar-event icon'></i><span class="text">Event List</span></a></li>
              
                <li><a href="User_Scan_QR.php"><i class='bx bx-qr-scan icon'></i><span class="text">Scan QR</span></a></li>
                <li><a href="participation.php"><i class='bx bx-history icon'></i><span class="text">Participation</span></a></li>
            </ul>
        </div>

        <div class="bottom-content">
            <li><a href="Login.php"><i class='bx bx-log-out icon'></i><span class="text">Logout</span></a></li>

            <li class="mode">
                <div class="sun-moon">
                    <i class='bx bx-moon icon moon'></i>
                </div>
                <span class="mode-text text">Dark mode</span>

                <div class="toggle-switch">
                    <span class="switch"></span>
                </div>
            </li>
        </div>
    </div>
</nav>

<section class="home" id="dashboard">

  <div class="dashboard-header">
    <h1>Hello, <?php echo $student['name']; ?> 👋</h1>

    <div class="current-date">
        <?php
        date_default_timezone_set('Asia/Kuala_Lumpur');
        echo "Today is " . date('l, d F Y');
        ?>
    </div>
</div>

<div class="dashboard-layout">

    <!-- LEFT SIDE -->
    <div class="dashboard-left">

        <div class="dashboard-cards">

            <div class="stat-card purple">
                <div class="stat-top">
                    <span class="stat-icon">
                        <i class='bx bx-calendar-check'></i>
                    </span>
                </div>

                <h3>Registered<br>Events</h3>
                <p><?php echo $history->num_rows; ?> events joined</p>

                <div class="progress-line">
                    <span style="width:65%"></span>
                </div>
            </div>

            <div class="stat-card cyan">
                <div class="stat-top">
                    <span class="stat-icon">
                        <i class='bx bx-calendar-event'></i>
                    </span>
                </div>

                <h3>Available<br>Events</h3>
                <p><?php echo $events->num_rows; ?> events open</p>

                <div class="progress-line">
                    <span style="width:45%"></span>
                </div>
            </div>

          <div class="stat-card orange">
    <div class="stat-top">
        <span class="stat-icon">
            <i class='bx bx-user-check'></i>
        </span>
    </div>

                <h3>Attendance</h3>
    <p><?php echo $history->num_rows; ?> events attended</p>

              <div class="progress-line">
        <span style="width:75%"></span>
    </div>
</div>

        </div>

        <!-- MY EVENT LIST -->
        <div class="my-event-section">

            <div class="section-header">
                <div>
                    <h2>My Event List</h2>
                    <p>Activities that you have registered or joined.</p>
                </div>

               
            </div>

            <div class="my-event-grid">

                <?php
                $history->data_seek(0);

                if ($history->num_rows > 0) {
                    while($myEvent = $history->fetch_assoc()) {
                ?>

                    <div class="my-event-card">

                       <div class="event-image">
    <?php if (!empty($myEvent['event_poster'])) { ?>
        <img src="<?php echo $myEvent['event_poster']; ?>" alt="PERSADA Event">
    <?php } else { ?>
        <div class="event-placeholder">
            <i class='bx bx-calendar-event'></i>
        </div>
    <?php } ?>
</div>

                        <div class="event-content">
                            <span class="event-badge">Registered</span>

                            <h3><?php echo $myEvent['event_name']; ?></h3>

                            <p>
                                <i class='bx bx-calendar'></i>
                                <?php echo $myEvent['event_date']; ?>
                            </p>

                            <p>
                                <i class='bx bx-map'></i>
                                <?php echo $myEvent['venue']; ?>
                            </p>

                            
<div class="event-actions">
    <a href="User_Scan_QR.php" class="scan-btn">
        <i class='bx bx-qr-scan'></i>
        Scan QR
    </a>

    <a href="participation.php" class="detail-btn">
        <i class='bx bx-history'></i>
        Record
    </a>
</div>





                        </div>

                    </div>

                <?php
                    }
                } else {
                ?>

                    <div class="empty-event">
                        <i class='bx bx-calendar-x'></i>

                        <h3>No event joined yet</h3>

                        <p>
                            Browse available events and join your first PERSADA activity.
                        </p>

                        <a href="#events">Explore Events</a>
                    </div>

                <?php } ?>

            </div>

        </div>

    </div>

    <!-- RIGHT SIDE PROFILE -->
    <div class="dashboard-right">

        <div class="side-profile-card">

            <h2>My Profile</h2>
            <p class="progress-text">PERSADA Student Member</p>

           <div class="avatar">
    <?php if (!empty($student['profile_picture'])) { ?>
        <img src="<?php echo $student['profile_picture']; ?>" alt="Profile Picture">
    <?php } else { ?>
        <?php echo strtoupper(substr($student['name'],0,1)); ?>
    <?php } ?>
</div>

            <h3><?php echo $student['name']; ?></h3>

            <p class="profile-email">
                <?php echo $student['email']; ?>
            </p>

            <div class="profile-menu">

                <div class="profile-menu-item">
                    <i class='bx bx-id-card'></i>

                    <div>
                        <strong>Matric Number</strong>
                        <span><?php echo $student['matric_number']; ?></span>
                    </div>

                    <i class='bx bx-chevron-right arrow'></i>
                </div>

                <div class="profile-menu-item">
                    <i class='bx bx-phone'></i>

                    <div>
                        <strong>Phone Number</strong>
                        <span><?php echo $student['phone_number']; ?></span>
                    </div>

                    <i class='bx bx-chevron-right arrow'></i>
                </div>

                <div class="profile-menu-item">
                    <i class='bx bx-buildings'></i>

                    <div>
                        <strong>Faculty</strong>
                        <span><?php echo $student['faculty']; ?></span>
                    </div>

                    <i class='bx bx-chevron-right arrow'></i>
                </div>

            </div>

            <!-- EXTRA PROFILE CONTENT -->
            <div class="profile-extra">

                <div class="profile-section-title">
                    <span>Today</span>
                    <a href="#events">View All</a>
                </div>

                <div class="mini-action">
                    <i class='bx bx-calendar-event'></i>

                    <div>
                        <strong>Upcoming Events</strong>
                        <span><?php echo $events->num_rows; ?> events available</span>
                    </div>
                </div>

               

                <div class="mini-action">
                    <i class='bx bx-history'></i>

                    <div>
                        <strong>Participation</strong>
                        <span><?php echo $history->num_rows; ?> registered events</span>
                    </div>
                </div>

               
                

            </div>

        </div>

    </div>

</div>

   
<div class="participation-snapshot" id="history">

    <div class="snapshot-header">
        <div>
            <span class="snapshot-label">Participation Snapshot</span>
            <h2>My Activity Overview</h2>
            <p>Quick preview of your latest PERSADA participation progress.</p>
        </div>

        <a href="participation.php" class="view-participation-btn">
            View Full Participation
            <i class='bx bx-right-arrow-alt'></i>
        </a>
    </div>

    <div class="snapshot-grid">

        <div class="snapshot-main-card">

            <div class="snapshot-card-title">
                <i class='bx bx-calendar-check'></i>
                <div>
                    <h3>Latest Participation</h3>
                    <p>Your most recent registered activity.</p>
                </div>
            </div>

            <?php if($latestParticipation){ ?>

                <div class="latest-event-preview">

                    <div class="latest-event-img">
                        <?php if(!empty($latestParticipation['event_poster'])){ ?>
                            <img src="<?php echo $latestParticipation['event_poster']; ?>" alt="Latest Event">
                        <?php } else { ?>
                            <i class='bx bx-calendar-event'></i>
                        <?php } ?>
                    </div>

                    <div class="latest-event-info">
                        <span class="event-mini-badge">Latest Event</span>
                        <h4><?php echo $latestParticipation['event_name']; ?></h4>

                        <p>
                            <i class='bx bx-calendar'></i>
                            <?php echo date("d M Y", strtotime($latestParticipation['event_date'])); ?>
                        </p>

                        <p>
                            <i class='bx bx-map'></i>
                            <?php echo $latestParticipation['venue']; ?>
                        </p>

                        <p>
                            <i class='bx bx-check-circle'></i>
                            Registered on <?php echo date("d M Y", strtotime($latestParticipation['registration_date'])); ?>
                        </p>
                    </div>

                </div>

            <?php } else { ?>

                <div class="snapshot-empty">
                    <i class='bx bx-calendar-x'></i>
                    <h4>No participation yet</h4>
                    <p>Join your first event to start building your participation record.</p>
                </div>

            <?php } ?>

        </div>

        <div class="snapshot-side-card">

            <div class="snapshot-card-title">
                <i class='bx bx-line-chart'></i>
                <div>
                    <h3>Attendance Rate</h3>
                    <p>Mini progress summary.</p>
                </div>
            </div>

            <div class="mini-progress-number">
                <?php echo $attendanceRate; ?>%
            </div>

            <div class="mini-progress-bar">
                <span style="width: <?php echo $attendanceRate; ?>%;"></span>
            </div>

            <p class="mini-progress-text">
                <?php echo $totalAttended; ?> out of <?php echo $totalJoined; ?> events attended.
            </p>

        </div>

        <div class="snapshot-side-card achievement-preview">

            <div class="snapshot-card-title">
                <i class='bx bx-medal'></i>
                <div>
                    <h3>Achievement</h3>
                    <p>Your current participation badge.</p>
                </div>
            </div>

            <div class="achievement-icon">
                <i class='bx bx-trophy'></i>
            </div>

            <h4><?php echo $achievementTitle; ?></h4>
            <p><?php echo $achievementDesc; ?></p>

        </div>

    </div>

</div>

</section>

<script>
const body = document.querySelector('body');
const sidebar = body.querySelector('.sidebar');
const toggle = body.querySelector('.toggle');
const modeSwitch = body.querySelector('.toggle-switch');
const modeText = body.querySelector('.mode-text');

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