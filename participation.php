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

/* STUDENT INFO */
$stmt = $conn->prepare("
    SELECT *
    FROM students
    WHERE id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

$studentName = $student['name'] ?? "Student";

/* TOTAL JOINED */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM event_registration
    WHERE student_id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$totalJoined = $stmt->get_result()->fetch_assoc()['total'];

/* TOTAL ATTENDED */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM attendance
    WHERE student_id = ?
    AND attendance_status = 'Present'
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$totalAttended = $stmt->get_result()->fetch_assoc()['total'];

$attendanceRate = 0;

if ($totalJoined > 0) {
    $attendanceRate = round(($totalAttended / $totalJoined) * 100);
}

/* FAVOURITE CATEGORY */
$stmt = $conn->prepare("
    SELECT e.event_category, COUNT(*) AS total
    FROM event_registration er
    JOIN events e ON er.event_id = e.event_id
    WHERE er.student_id = ?
    GROUP BY e.event_category
    ORDER BY total DESC
    LIMIT 1
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$favResult = $stmt->get_result()->fetch_assoc();

$favCategory = $favResult['event_category'] ?? "N/A";

/* NEXT EVENT */
$stmt = $conn->prepare("
    SELECT e.*
    FROM event_registration er
    JOIN events e ON er.event_id = e.event_id
    WHERE er.student_id = ?
    AND e.event_date >= CURDATE()
    ORDER BY e.event_date ASC, e.event_time ASC
    LIMIT 1
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$nextEvent = $stmt->get_result()->fetch_assoc();

/* PARTICIPATION RECORDS */
$stmt = $conn->prepare("
    SELECT 
        e.event_name,
        e.event_category,
        e.event_date,
        e.event_time,
        e.venue,
        e.event_poster,
        e.certificate_released,
        a.attendance_status,
        a.attendance_id,
        a.scan_time
    FROM event_registration er
    JOIN events e 
        ON er.event_id = e.event_id
    LEFT JOIN attendance a 
        ON er.event_id = a.event_id
        AND er.student_id = a.student_id
    WHERE er.student_id = ?
    ORDER BY e.event_date DESC
");

$stmt->bind_param("i", $student_id);
$stmt->execute();
$records = $stmt->get_result();

/* TIMELINE */
$stmt = $conn->prepare("
    SELECT 
        e.event_name,
        e.event_category,
        e.event_date,
        a.attendance_status,
        a.scan_time
    FROM attendance a
    JOIN events e ON a.event_id = e.event_id
    WHERE a.student_id = ?
    ORDER BY a.scan_time DESC
    LIMIT 6
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$timeline = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Participation - PERSADA Student Portal</title>

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
    --body-color:#fff7ec;
    --sidebar-color:#ffffff;
    --primary-color:#ff6b4a;
    --secondary-color:#f6b73c;
    --primary-light:#fff1e8;
    --text-color:#667085;
    --title-color:#16254c;
    --card-color:#ffffff;
    --shadow:0 15px 35px rgba(0,0,0,.08);
    --soft-border:rgba(255,107,74,.14);
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
    --primary-light:#3a3b3c;
    --text-color:#ccc;
    --title-color:#ffffff;
    --card-color:#242526;
    --soft-border:rgba(255,255,255,.08);
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
    box-shadow:0 8px 20px rgba(255,107,74,.28);
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
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    box-shadow:0 12px 25px rgba(255,107,74,.18);
}

.sidebar li a:hover .icon,
.sidebar li a:hover .text,
.sidebar li a.active .icon,
.sidebar li a.active .text{
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
.main{
    margin-left:260px;
    padding:35px;
    min-height:100vh;
    background:
        radial-gradient(circle at top right, rgba(255,107,74,.12), transparent 28%),
        radial-gradient(circle at top left, rgba(246,183,60,.12), transparent 30%),
        var(--body-color);
    transition:var(--tran);
}

.sidebar.close ~ .main{
    margin-left:88px;
}

/* HERO */
.participation-hero{
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    border-radius:32px;
    padding:36px;
    color:white;
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
    box-shadow:0 22px 45px rgba(255,107,74,.25);
    position:relative;
    overflow:hidden;
}

.participation-hero::after{
    content:"";
    position:absolute;
    width:260px;
    height:260px;
    right:-90px;
    bottom:-100px;
    background:rgba(255,255,255,.15);
    border-radius:50%;
}

.participation-hero h1{
    font-size:38px;
    font-weight:800;
    margin-bottom:8px;
}

.participation-hero p{
    max-width:700px;
    line-height:1.7;
    font-size:15px;
}

.hero-icon{
    width:95px;
    height:95px;
    border-radius:28px;
    background:rgba(255,255,255,.2);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:50px;
    z-index:1;
}

/* SUMMARY */
.summary-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:22px;
    margin-bottom:30px;
}

.summary-card{
    background:var(--card-color);
    border-radius:26px;
    padding:24px;
    box-shadow:var(--shadow);
    border:1px solid var(--soft-border);
    display:flex;
    align-items:center;
    gap:18px;
    transition:.3s ease;
}

.summary-card:hover{
    transform:translateY(-7px);
    box-shadow:0 20px 42px rgba(255,107,74,.14);
}

.summary-icon{
    width:60px;
    height:60px;
    border-radius:20px;
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:30px;
}

.summary-card h3{
    color:var(--title-color);
    font-size:28px;
    margin-bottom:5px;
}

.summary-card p{
    color:var(--text-color);
    font-size:14px;
    font-weight:600;
}

/* LAYOUT */
.content-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:26px;
    margin-bottom:30px;
}

.card{
    background:var(--card-color);
    border-radius:30px;
    padding:28px;
    box-shadow:var(--shadow);
    border:1px solid var(--soft-border);
}

.section-title{
    display:flex;
    align-items:center;
    gap:14px;
    margin-bottom:20px;
}

.section-title i{
    width:54px;
    height:54px;
    border-radius:18px;
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:28px;
}

.section-title h2{
    color:var(--title-color);
    font-size:24px;
    font-weight:800;
}

.section-title p{
    color:var(--text-color);
    font-size:14px;
}

/* ACHIEVEMENTS */
.achievement-list{
    display:grid;
    grid-template-columns:1fr;
    gap:14px;
}

.achievement-item{
    padding:16px;
    border-radius:20px;
    background:#fffaf6;
    border:1px solid var(--soft-border);
    display:flex;
    align-items:center;
    gap:14px;
    color:var(--title-color);
    font-weight:800;
}

.achievement-item span{
    width:42px;
    height:42px;
    border-radius:15px;
    background:var(--primary-light);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:22px;
}

/* PROGRESS */
.progress-info{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:12px;
    color:var(--title-color);
    font-weight:800;
}

.progress-bar{
    width:100%;
    height:14px;
    border-radius:999px;
    background:#eee;
    overflow:hidden;
}

.progress-fill{
    height:100%;
    width:0;
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    border-radius:999px;
    transition:.6s ease;
}

.progress-note{
    margin-top:12px;
    color:var(--text-color);
    font-size:14px;
}

/* NEXT EVENT */
.next-event-box{
    display:flex;
    gap:18px;
    align-items:center;
    padding:18px;
    border-radius:24px;
    background:#fffaf6;
    border:1px solid var(--soft-border);
}

.next-event-box img{
    width:110px;
    height:110px;
    border-radius:20px;
    object-fit:cover;
}

.next-event-info h3{
    color:var(--title-color);
    margin-bottom:8px;
}

.next-event-info p{
    color:var(--text-color);
    margin-bottom:6px;
}

.countdown{
    margin-top:10px;
    display:inline-flex;
    padding:9px 14px;
    border-radius:999px;
    background:var(--primary-light);
    color:var(--primary-color);
    font-weight:800;
}

/* TIMELINE */
.timeline-list{
    position:relative;
    padding-left:10px;
}

.timeline-item{
    display:flex;
    gap:16px;
    margin-bottom:18px;
}

.timeline-dot{
    width:16px;
    height:16px;
    border-radius:50%;
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    margin-top:8px;
    flex-shrink:0;
}

.timeline-content{
    flex:1;
    background:#fffaf6;
    border:1px solid var(--soft-border);
    padding:16px;
    border-radius:20px;
}

.timeline-content h4{
    color:var(--title-color);
    margin-bottom:5px;
}

.timeline-content p{
    color:var(--text-color);
    font-size:13px;
}

.timeline-status{
    display:inline-flex;
    margin-top:8px;
    padding:6px 12px;
    border-radius:999px;
    background:#dcfce7;
    color:#16a34a;
    font-weight:800;
    font-size:12px;
}

/* RECORD TABLE */
.record-card{
    background:var(--card-color);
    border-radius:30px;
    padding:28px;
    box-shadow:var(--shadow);
    border:1px solid var(--soft-border);
}

.table-wrapper{
    overflow-x:auto;
}

table{
    width:100%;
    min-width:900px;
    border-collapse:collapse;
}

th{
    text-align:left;
    padding:16px;
    color:var(--title-color);
    font-size:14px;
    border-bottom:1px solid var(--soft-border);
}

td{
    padding:16px;
    color:var(--text-color);
    border-bottom:1px solid rgba(0,0,0,.05);
    font-size:14px;
}

.event-cell{
    display:flex;
    align-items:center;
    gap:12px;
}

.event-cell img{
    width:55px;
    height:55px;
    border-radius:14px;
    object-fit:cover;
}

.event-cell strong{
    color:var(--title-color);
}

.status-present{
    background:#dcfce7;
    color:#16a34a;
    padding:7px 13px;
    border-radius:999px;
    font-weight:800;
    font-size:12px;
}

.status-absent{
    background:#fee2e2;
    color:#dc2626;
    padding:7px 13px;
    border-radius:999px;
    font-weight:800;
    font-size:12px;
}

.certificate-btn{
    border:none;
    padding:9px 14px;
    border-radius:14px;
    font-weight:800;
    cursor:pointer;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    gap:7px;
    white-space:nowrap;
}

.certificate-btn.eligible{
    background:#dcfce7;
    color:#16a34a;
}

.certificate-btn.coming-soon{
    background:#dbeafe;
    color:#2563eb;
    cursor:not-allowed;
}

.certificate-btn.disabled{
    background:#fee2e2;
    color:#dc2626;
    cursor:not-allowed;
}

.empty{
    text-align:center;
    padding:35px;
    color:var(--text-color);
}

/* RESPONSIVE */
@media(max-width:1200px){
    .summary-grid{
        grid-template-columns:repeat(2,1fr);
    }

    .content-grid{
        grid-template-columns:1fr;
    }
}

@media(max-width:800px){
    .main{
        padding:22px;
    }

    .summary-grid{
        grid-template-columns:1fr;
    }

    .participation-hero{
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

                <li>
                    <a href="dashboard.php">
                        <i class='bx bx-home-alt icon'></i>
                        <span class="text">Dashboard</span>
                    </a>
                </li>

                <li>
                    <a href="Myprofile.php">
                        <i class='bx bx-user icon'></i>
                        <span class="text">My Profile</span>
                    </a>
                </li>

                <li>
                    <a href="Event_List.php">
                        <i class='bx bx-calendar-event icon'></i>
                        <span class="text">Event List</span>
                    </a>
                </li>

                <li>
                    <a href="User_Scan_QR.php">
                        <i class='bx bx-qr-scan icon'></i>
                        <span class="text">Scan QR</span>
                    </a>
                </li>

                <li>
                    <a href="participation.php" class="active">
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
                <i class='bx bx-moon icon moon'></i>
                <span class="mode-text text">Dark mode</span>

                <div class="toggle-switch">
                    <span class="switch"></span>
                </div>
            </li>

        </div>
    </div>
</nav>

<section class="main">

    <div class="participation-hero">
        <div>
            <h1>My Participation</h1>
            <p>
                Track your PERSADA activity involvement, attendance record,
                achievement progress and event participation history.
            </p>
        </div>

        <div class="hero-icon">
            <i class='bx bx-trophy'></i>
        </div>
    </div>

    <div class="summary-grid">

        <div class="summary-card">
            <div class="summary-icon">
                <i class='bx bx-calendar-check'></i>
            </div>
            <div>
                <h3><?php echo $totalJoined; ?></h3>
                <p>Joined Events</p>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-icon">
                <i class='bx bx-check-circle'></i>
            </div>
            <div>
                <h3><?php echo $totalAttended; ?></h3>
                <p>Attended Events</p>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-icon">
                <i class='bx bx-line-chart'></i>
            </div>
            <div>
                <h3><?php echo $attendanceRate; ?>%</h3>
                <p>Attendance Rate</p>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-icon">
                <i class='bx bx-star'></i>
            </div>
            <div>
                <h3><?php echo $favCategory; ?></h3>
                <p>Favourite Category</p>
            </div>
        </div>

    </div>

    <div class="content-grid">

        <div class="card">
            <div class="section-title">
                <i class='bx bx-medal'></i>
                <div>
                    <h2>Achievements</h2>
                    <p>Your participation milestones.</p>
                </div>
            </div>

            <div class="achievement-list">

                <?php if($totalJoined >= 1){ ?>
                    <div class="achievement-item">
                        <span>🏅</span>
                        First Event Joined
                    </div>
                <?php } ?>

                <?php if($attendanceRate == 100 && $totalJoined > 0){ ?>
                    <div class="achievement-item">
                        <span>⭐</span>
                        Perfect Attendance
                    </div>
                <?php } ?>

                <?php if($totalJoined >= 3){ ?>
                    <div class="achievement-item">
                        <span>🏆</span>
                        Active PERSADA Member
                    </div>
                <?php } ?>

                <?php if($totalJoined == 0){ ?>
                    <div class="achievement-item">
                        <span>📌</span>
                        Join your first event to unlock achievements.
                    </div>
                <?php } ?>

            </div>
        </div>

        <div class="card">
            <div class="section-title">
                <i class='bx bx-bar-chart-alt'></i>
                <div>
                    <h2>Attendance Progress</h2>
                    <p>Your overall attendance completion rate.</p>
                </div>
            </div>

            <div class="progress-info">
                <span><?php echo $totalAttended; ?> / <?php echo $totalJoined; ?> Events Attended</span>
                <span><?php echo $attendanceRate; ?>%</span>
            </div>

            <div class="progress-bar">
                <div class="progress-fill" style="width:<?php echo $attendanceRate; ?>%;"></div>
            </div>

            <p class="progress-note">
                Keep joining and attending PERSADA activities to improve your record.
            </p>
        </div>

    </div>

    <div class="content-grid">

        <div class="card">
            <div class="section-title">
                <i class='bx bx-calendar-star'></i>
                <div>
                    <h2>Next Registered Event</h2>
                    <p>Your nearest upcoming activity.</p>
                </div>
            </div>

            <?php if($nextEvent){ ?>
                <div class="next-event-box">
                    <?php if(!empty($nextEvent['event_poster'])){ ?>
                        <img src="<?php echo $nextEvent['event_poster']; ?>">
                    <?php } else { ?>
                        <img src="https://via.placeholder.com/120x120?text=Event">
                    <?php } ?>

                    <div class="next-event-info">
                        <h3><?php echo $nextEvent['event_name']; ?></h3>
                        <p><i class='bx bx-calendar'></i> <?php echo date("d M Y", strtotime($nextEvent['event_date'])); ?></p>
                        <p><i class='bx bx-time'></i> <?php echo date("h:i A", strtotime($nextEvent['event_time'])); ?></p>
                        <p><i class='bx bx-map'></i> <?php echo $nextEvent['venue']; ?></p>

                        <div class="countdown" id="countdown">
                            Calculating...
                        </div>
                    </div>
                </div>
            <?php } else { ?>
                <div class="empty">
                    No upcoming registered event.
                </div>
            <?php } ?>
        </div>

        <div class="card">
            <div class="section-title">
                <i class='bx bx-history'></i>
                <div>
                    <h2>Participation Timeline</h2>
                    <p>Your latest attendance activities.</p>
                </div>
            </div>

            <div class="timeline-list">

                <?php if($timeline->num_rows > 0){ ?>
                    <?php while($t = $timeline->fetch_assoc()){ ?>
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>

                            <div class="timeline-content">
                                <h4><?php echo $t['event_name']; ?></h4>
                                <p>
                                    <?php echo date("d M Y h:i A", strtotime($t['scan_time'])); ?>
                                </p>

                                <span class="timeline-status">
                                    <?php echo $t['attendance_status']; ?>
                                </span>
                            </div>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <div class="empty">
                        No attendance timeline yet.
                    </div>
                <?php } ?>

            </div>
        </div>

    </div>

    <div class="record-card">
        <div class="section-title">
            <i class='bx bx-spreadsheet'></i>
            <div>
                <h2>Participation Records</h2>
                <p>Complete list of your registered events and attendance status.</p>
            </div>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Venue</th>
                        <th>Status</th>
                        <th>Certificate</th>
                    </tr>
                </thead>

                <tbody>
                <?php if($records->num_rows > 0){ ?>
                    <?php while($row = $records->fetch_assoc()){ ?>
                        <tr>
                            <td>
                                <div class="event-cell">
                                    <?php if(!empty($row['event_poster'])){ ?>
                                        <img src="<?php echo $row['event_poster']; ?>">
                                    <?php } else { ?>
                                        <img src="https://via.placeholder.com/60x60?text=Event">
                                    <?php } ?>

                                    <div>
                                        <strong><?php echo $row['event_name']; ?></strong>
                                    </div>
                                </div>
                            </td>

                            <td><?php echo $row['event_category']; ?></td>

                            <td><?php echo date("d M Y", strtotime($row['event_date'])); ?></td>

                            <td><?php echo date("h:i A", strtotime($row['event_time'])); ?></td>

                            <td><?php echo $row['venue']; ?></td>

                           <td>
    <?php if(($row['attendance_status'] ?? '') == "Present"){ ?>
        <span class="status-present">Present</span>
    <?php } else { ?>
        <span class="status-absent">Absent</span>
    <?php } ?>
</td>

<td>
    <?php if(
        ($row['attendance_status'] ?? '') == "Present"
        && ($row['certificate_released'] ?? 'No') == "Yes"
    ){ ?>

        <a href="download_certificate.php?attendance_id=<?php echo $row['attendance_id']; ?>"
           class="certificate-btn eligible">
            <i class='bx bx-download'></i>
            Download
        </a>

    <?php } elseif(
        ($row['attendance_status'] ?? '') == "Present"
        && ($row['certificate_released'] ?? 'No') == "No"
    ){ ?>

        <button class="certificate-btn coming-soon" disabled>
            <i class='bx bx-time-five'></i>
            Pending
        </button>

    <?php } else { ?>

        <button class="certificate-btn disabled" disabled>
            <i class='bx bx-lock-alt'></i>
            Not Eligible
        </button>

    <?php } ?>
</td>
   </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="7" class="empty">
                            No participation record found.
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

</section>

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

<?php if($nextEvent){ ?>
const targetDate = new Date("<?php echo $nextEvent['event_date'] . ' ' . $nextEvent['event_time']; ?>").getTime();

function updateCountdown(){
    const now = new Date().getTime();
    const distance = targetDate - now;

    if(distance <= 0){
        document.getElementById("countdown").innerHTML = "Event Started";
        return;
    }

    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));

    document.getElementById("countdown").innerHTML =
        days + " Days • " + hours + " Hours • " + minutes + " Minutes";
}

updateCountdown();
setInterval(updateCountdown, 1000);
<?php } ?>
</script>

</body>
</html>