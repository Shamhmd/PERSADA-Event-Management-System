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

/* GET STUDENT INFO */
$studentQuery = $conn->prepare("SELECT * FROM students WHERE id = ?");
$studentQuery->bind_param("i", $student_id);
$studentQuery->execute();
$student = $studentQuery->get_result()->fetch_assoc();

/* JOIN EVENT PROCESS */
if (isset($_POST['join_event'])) {

    $event_id = $_POST['event_id'];

    $check = $conn->prepare("
        SELECT registration_id 
        FROM event_registration 
        WHERE student_id = ? AND event_id = ?
    ");
    $check->bind_param("ii", $student_id, $event_id);
    $check->execute();
    $checkResult = $check->get_result();

    if ($checkResult->num_rows > 0) {
        echo "<script>
            alert('You have already joined this event.');
            window.location='Event_List.php';
        </script>";
        exit();
    }

    $join = $conn->prepare("
        INSERT INTO event_registration 
        (student_id, event_id, attendance_status, certificate_status)
        VALUES (?, ?, 'Absent', 'Not Issued')
    ");
    $join->bind_param("ii", $student_id, $event_id);

    if ($join->execute()) {
        echo "<script>
            alert('Event joined successfully!');
            window.location='Event_List.php';
        </script>";
        exit();
    } else {
        echo "<script>
            alert('Failed to join event. Please try again.');
        </script>";
    }
}

/* SEARCH */
$search = "";

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

/* EVENT STATISTICS */
$totalEvents = $conn->query("
    SELECT COUNT(*) AS total 
    FROM events 
    WHERE status IN ('Upcoming','Ongoing')
")->fetch_assoc()['total'];

$openEvents = $conn->query("
    SELECT COUNT(*) AS total 
    FROM events 
    WHERE status = 'Upcoming'
")->fetch_assoc()['total'];

$joinedEvents = $conn->query("
    SELECT COUNT(*) AS total 
    FROM event_registration 
    WHERE student_id = '$student_id'
")->fetch_assoc()['total'];

/* AVAILABLE EVENTS QUERY */

if (!empty($search)) {

    $availableEvents = $conn->prepare("
        SELECT 
            e.*,

            CASE 
                WHEN er.registration_id IS NOT NULL THEN 1
                ELSE 0
            END AS already_joined,

            (
                SELECT COUNT(*)
                FROM event_registration er2
                WHERE er2.event_id = e.event_id
            ) AS current_participants

        FROM events e

        LEFT JOIN event_registration er 
            ON e.event_id = er.event_id 
            AND er.student_id = ?

        WHERE e.status IN ('Upcoming','Ongoing')
        AND (
            e.event_name LIKE ?
            OR e.event_category LIKE ?
            OR e.venue LIKE ?
        )

        ORDER BY e.event_date ASC
    ");

    $keyword = "%" . $search . "%";
    $availableEvents->bind_param("isss", $student_id, $keyword, $keyword, $keyword);
    $availableEvents->execute();
    $eventsResult = $availableEvents->get_result();

} else {

    $availableEvents = $conn->prepare("
        SELECT 
            e.*,

            CASE 
                WHEN er.registration_id IS NOT NULL THEN 1
                ELSE 0
            END AS already_joined,

            (
                SELECT COUNT(*)
                FROM event_registration er2
                WHERE er2.event_id = e.event_id
            ) AS current_participants

        FROM events e

        LEFT JOIN event_registration er 
            ON e.event_id = er.event_id 
            AND er.student_id = ?

        WHERE e.status IN ('Upcoming','Ongoing')

        ORDER BY e.event_date ASC
    ");

    $availableEvents->bind_param("i", $student_id);
    $availableEvents->execute();
    $eventsResult = $availableEvents->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Event List - PERSADA Student Portal</title>

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

/* DARK MODE TOGGLE */
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

/* =========================
   CLEAN PAGE HEADER
========================= */
.event-header{
    margin-bottom:24px;
}

.event-header h1{
    color:var(--title-color);
    font-size:36px;
    font-weight:800;
    margin-bottom:6px;
}

.event-header p{
    color:var(--text-color);
    font-size:15px;
}

/* =========================
   COMPACT STATS
========================= */
.event-stats{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:18px;
    margin-bottom:24px;
}

.stat-card{
    background:var(--card-color);
    border-radius:24px;
    padding:20px 22px;
    box-shadow:0 12px 28px rgba(0,0,0,.06);
    border:1px solid var(--soft-border);
    display:flex;
    align-items:center;
    gap:16px;
}

.stat-icon{
    width:52px;
    height:52px;
    border-radius:16px;
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:27px;
    flex-shrink:0;
}

.stat-card h3{
    color:var(--title-color);
    font-size:28px;
    line-height:1;
    margin-bottom:5px;
}

.stat-card p{
    color:var(--text-color);
    font-size:13px;
    font-weight:600;
}

/* =========================
   SEARCH BAR
========================= */
.event-toolbar{
    background:var(--card-color);
    border-radius:24px;
    padding:18px;
    box-shadow:0 12px 28px rgba(0,0,0,.06);
    border:1px solid var(--soft-border);
    margin-bottom:28px;
}

.search-form{
    width:100%;
    display:flex;
    align-items:center;
    gap:12px;
}

.search-input-wrap{
    flex:1;
    position:relative;
}

.search-input-wrap i{
    position:absolute;
    left:18px;
    top:50%;
    transform:translateY(-50%);
    color:#ff6b4a;
    font-size:22px;
}

.search-input-wrap input{
    width:100%;
    padding:15px 18px 15px 50px;
    border-radius:18px;
    border:1px solid var(--soft-border);
    background:#fffaf6;
    outline:none;
    color:#16254c;
    font-size:14px;
}

.search-input-wrap input:focus{
    border-color:#ff6b4a;
    box-shadow:0 0 0 4px rgba(255,107,74,.12);
}

.search-btn,
.clear-btn{
    border:none;
    padding:14px 22px;
    border-radius:16px;
    font-weight:800;
    cursor:pointer;
    text-decoration:none;
    display:flex;
    align-items:center;
    gap:8px;
}

.search-btn{
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    color:white;
}

.clear-btn{
    background:#fff1e8;
    color:#ff6b4a;
}

/* =========================
   EVENT GRID / CARDS
========================= */
.event-grid{
    display:grid;
   grid-template-columns:repeat(auto-fit, minmax(360px, 1fr));
    gap:28px;
}

.event-card{
    background:rgba(255,255,255,.96);
    border-radius:30px;
    padding:0;
    overflow:hidden;
    display:flex;
    flex-direction:column;
    border:1px solid rgba(255,107,74,.16);
    box-shadow:0 18px 45px rgba(22,37,76,.08);
    transition:.3s ease;
}

.event-card:hover{
    transform:translateY(-6px);
    box-shadow:0 24px 55px rgba(255,107,74,.18);
}

.event-poster-box{
    width:100%;
    height:260px;
    min-height:260px;
    border-radius:0;
    overflow:hidden;
    background:#fff1e8;
    position:relative;
}

.event-poster-box img{
    width:100%;
    height:100%;
    object-fit:cover;
    object-position:center;
    display:block;
}

.status-badge{
    position:absolute;
    top:14px;
    left:14px;
    padding:7px 14px;
    border-radius:30px;
    font-size:15px;
    font-weight:800;
    background:#dbeafe;
    color:#2563eb;
}

.status-badge.ongoing{
    background:#dcfce7;
    color:#16a34a;
}



.card-content{
    padding:24px;
}

.event-category{
    width:max-content;
    padding:8px 15px;
    border-radius:999px;
    background:linear-gradient(135deg,#fff1e8,#fffaf6);
    color:#ff6b4a;
    font-size:13px;
    font-weight:800;
    border:1px solid rgba(255,107,74,.18);
    margin-bottom:16px;
}

.card-content h3{
    font-size:22px;
    font-weight:800;
    margin-top:16px;
}

.event-meta{
    display:grid;
    grid-template-columns:1fr;
    gap:12px;
    margin-bottom:22px;
}

.meta-item{
    background:linear-gradient(135deg,#fffaf6,#ffffff);
    border:1px solid rgba(255,107,74,.14);
    border-radius:18px;
    padding:13px 15px;
    display:flex;
    align-items:center;
    gap:13px;
    color:#5f6b85;
    font-size:14px;
    font-weight:600;
    box-shadow:0 8px 18px rgba(22,37,76,.035);
}

.meta-item i{
    width:38px;
    height:38px;
    min-width:38px;
    border-radius:14px;
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:19px;
    box-shadow:0 8px 18px rgba(255,107,74,.18);
}

.card-actions{
    margin-top:auto;
    display:flex;
    gap:12px;
    justify-content:space-between;
    padding-top:18px;
    border-top:1px solid rgba(255,107,74,.12);
}

.details-btn,
.join-btn,
.joined-btn{
    border:none;
    padding:12px 18px;
    border-radius:16px;
    font-size:14px;
    font-weight:800;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    transition:.25s ease;
}

.details-btn{
    background:#fff1e8;
    color:#ff6b4a;
}

.details-btn:hover{
    background:#ff6b4a;
    color:white;
}

.joined-btn{
    background:#eef1f6;
    color:#667085;
    cursor:not-allowed;
    border:1px solid #d7dce5;
}
}
@media(max-width:900px){
    .event-card{
        grid-template-columns:1fr;
    }

    .event-meta{
        grid-template-columns:1fr;
    }

    .card-actions{
        flex-direction:column;
    }
}
/* =========================
   EVENT DETAILS MODAL
========================= */
.event-modal{
    position:fixed;
    inset:0;
    background:rgba(15,23,42,.62);
    backdrop-filter:blur(10px);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:9999;
    padding:25px;
}

.event-modal.show{
    display:flex;
}

.event-modal-box{
    width:980px;
    max-width:96%;
    max-height:92vh;
    overflow-y:auto;
    background:#ffffff;
    border-radius:34px;
    box-shadow:0 35px 90px rgba(0,0,0,.28);
    position:relative;
    animation:modalPop .28s ease;
}

.modal-close{
    position:absolute;
    top:18px;
    right:18px;
    width:46px;
    height:46px;
    border:none;
    border-radius:50%;
    background:#fff1e8;
    color:#ff6b4a;
    font-size:30px;
    cursor:pointer;
    z-index:5;
}

.modal-poster{
    width:100%;
    height:360px;
    background:#fff1e8;
    overflow:hidden;
    border-radius:34px 34px 0 0;
}

.modal-poster img{
    width:100%;
    height:100%;
    object-fit:contain;
    object-position:center;
    background:#fff1e8;
}

.modal-body{
    padding:34px;
}

.modal-top{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:20px;
    margin-bottom:26px;
}

.modal-status{
    display:inline-flex;
    padding:9px 18px;
    border-radius:30px;
    background:#dbeafe;
    color:#2563eb;
    font-size:13px;
    font-weight:800;
    margin-bottom:12px;
}

.modal-body h2{
    color:#16254c;
    font-size:34px;
    font-weight:800;
}

.modal-category{
    background:#fff1e8;
    color:#ff6b4a;
    padding:10px 18px;
    border-radius:30px;
    font-weight:800;
    border:1px solid rgba(255,107,74,.16);
}

.modal-section{
    background:#fffaf6;
    border:1px solid rgba(255,107,74,.14);
    border-radius:24px;
    padding:24px;
    margin-bottom:20px;
}

.modal-section h3{
    color:#16254c;
    font-size:20px;
    margin-bottom:12px;
}

.modal-desc{
    color:#5f6b85;
    line-height:1.8;
    font-size:15px;
}

.modal-info-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:16px;
}

.modal-info-item{
    background:white;
    border-radius:20px;
    padding:18px;
    display:flex;
    align-items:center;
    gap:14px;
    border:1px solid rgba(255,107,74,.12);
}

.modal-info-item i{
    width:48px;
    height:48px;
    border-radius:16px;
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:24px;
}

.modal-info-item span{
    color:#856E5D;
    font-size:13px;
}

.modal-info-item strong{
    color:#16254c;
    font-size:16px;
}

.notes-list{
    list-style:none;
    display:grid;
    gap:10px;
}

.notes-list li{
    color:#5f6b85;
    background:white;
    padding:13px 16px;
    border-radius:16px;
    border:1px solid rgba(255,107,74,.12);
}

.notes-list li i{
    color:#ff6b4a;
    margin-right:8px;
}

.benefit-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:12px;
}

.benefit-item{
    background:white;
    padding:16px;
    border-radius:18px;
    text-align:center;
    border:1px solid rgba(255,107,74,.12);
    color:#16254c;
    font-weight:800;
}

.benefit-item i{
    display:block;
    font-size:28px;
    color:#ff6b4a;
    margin-bottom:8px;
}

@media(max-width:800px){
    .modal-info-grid,
    .benefit-grid{
        grid-template-columns:1fr;
    }

    .modal-poster{
        height:260px;
    }
}
/* =========================
   RESPONSIVE
========================= */
@media(max-width:1200px){
    .event-grid{
        grid-template-columns:repeat(2,1fr);
    }
}

@media(max-width:900px){
    .event-stats,
    .event-grid{
        grid-template-columns:1fr;
    }

    .event-hero{
        flex-direction:column;
        align-items:flex-start;
        gap:22px;
    }

    .event-toolbar{
        flex-direction:column;
        align-items:stretch;
    }

    .search-form{
        flex-direction:column;
        align-items:stretch;
    }

    .card-actions{
        flex-direction:column;
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
                    <a href="Event_List.php" class="active">
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
                <i class='bx bx-moon icon moon'></i>
                <span class="mode-text text">Dark mode</span>

                <div class="toggle-switch">
                    <span class="switch"></span>
                </div>
            </li>

        </div>
    </div>
</nav>

<main class="main">

    <!-- HERO -->
    <section class="event-header">
    <h1>Available Events</h1>
    <p>Discover and join upcoming PERSADA activities.</p>
</section>

    <!-- STAT CARDS -->
    <section class="event-stats">

        <div class="stat-card">
            <div class="stat-icon">
                <i class='bx bx-calendar-event'></i>
            </div>

            <div>
                <h3><?php echo $totalEvents; ?></h3>
                <p>Total Available Events</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class='bx bx-time-five'></i>
            </div>

            <div>
                <h3><?php echo $openEvents; ?></h3>
                <p>Open for Registration</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class='bx bx-check-circle'></i>
            </div>

            <div>
                <h3><?php echo $joinedEvents; ?></h3>
                <p>My Joined Events</p>
            </div>
        </div>

    </section>

    <!-- TOOLBAR -->
    <section class="event-toolbar">

        <form method="GET" class="search-form">
            <div class="search-input-wrap">
                <i class='bx bx-search'></i>

                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search event name, category or venue..."
                    value="<?php echo htmlspecialchars($search); ?>">
            </div>

            <button type="submit" class="search-btn">
                <i class='bx bx-search-alt'></i>
                Search
            </button>

            <?php if (!empty($search)) { ?>
                <a href="Event_List.php" class="clear-btn">
                    <i class='bx bx-x'></i>
                    Clear
                </a>
            <?php } ?>
        </form>

    </section>

    <!-- EVENT GRID -->
    <section class="event-grid">

        <?php if ($eventsResult->num_rows > 0) { ?>

            <?php while ($event = $eventsResult->fetch_assoc()) { ?>

                <?php
                    $statusClass = strtolower($event['status']);

                    $eventJson = htmlspecialchars(
                        json_encode($event),
                        ENT_QUOTES,
                        'UTF-8'
                    );
                ?>

                <div class="event-card">

                    <div class="event-poster-box">

                        <?php if (!empty($event['event_poster'])) { ?>
                            <img src="<?php echo $event['event_poster']; ?>" alt="Event Poster">
                        <?php } else { ?>
                            <div class="poster-placeholder">
                                <i class='bx bx-calendar-event'></i>
                            </div>
                        <?php } ?>

                        <span class="status-badge <?php echo $statusClass; ?>">
                            <i class='bx bx-radio-circle-marked'></i>
                            <?php echo $event['status']; ?>
                        </span>

                    </div>

                    <div class="card-content">

                        <span class="event-category">
                            <?php echo !empty($event['event_category']) ? $event['event_category'] : 'PERSADA Event'; ?>
                        </span>

                        <h3><?php echo $event['event_name']; ?></h3>

                        <div class="event-meta">

                            <div class="meta-item">
                                <i class='bx bx-calendar'></i>
                                <span><?php echo date("d F Y", strtotime($event['event_date'])); ?></span>
                            </div>

                            <div class="meta-item">
                                <i class='bx bx-time'></i>
                                <span><?php echo date("h:i A", strtotime($event['event_time'])); ?></span>
                            </div>

                            <div class="meta-item">
                                <i class='bx bx-map'></i>
                                <span><?php echo $event['venue']; ?></span>
                            </div>

                            <?php if (!empty($event['registration_deadline'])) { ?>
                                <div class="meta-item">
                                    <i class='bx bx-calendar-exclamation'></i>
                                    <span>
                                        Register before 
                                        <?php echo date("d F Y", strtotime($event['registration_deadline'])); ?>
                                    </span>
                                </div>
                            <?php } ?>

                            <?php if (!empty($event['max_participants'])) { ?>
                                <div class="meta-item">
                                    <i class='bx bx-group'></i>
                                    <span>
                                        Max Participants:
                                        <?php echo $event['max_participants']; ?>
                                    </span>
                                </div>
                            <?php } ?>

                        </div>

                       <!-- Description only shown in View Details modal -->

                        <div class="card-actions">

                            <button 
                                type="button" 
                                class="details-btn"
                                onclick='openEventModal(<?php echo $eventJson; ?>)'>
                                <i class='bx bx-show'></i>
                                View Details
                            </button>

                            <?php if ($event['already_joined'] == 1) { ?>

                                <button type="button" class="joined-btn" disabled>
                                    <i class='bx bx-check'></i>
                                    Joined
                                </button>

                            <?php } else { ?>

                                <form method="POST" style="flex:1;">
                                    <input 
                                        type="hidden" 
                                        name="event_id" 
                                        value="<?php echo $event['event_id']; ?>">

                                    <button 
                                        type="submit" 
                                        name="join_event" 
                                        class="join-btn"
                                        onclick="return confirm('Are you sure you want to join this event?');">
                                        <i class='bx bx-plus-circle'></i>
                                        Join Event
                                    </button>
                                </form>

                            <?php } ?>

                        </div>

                    </div>

                </div>

            <?php } ?>

        <?php } else { ?>

            <div class="empty-event">
                <i class='bx bx-calendar-x'></i>
                <h3>No Available Events</h3>

                <?php if (!empty($search)) { ?>
                    <p>No event found for "<?php echo htmlspecialchars($search); ?>". Try another keyword.</p>
                <?php } else { ?>
                    <p>There are no upcoming PERSADA events at the moment.</p>
                <?php } ?>
            </div>

        <?php } ?>

    </section>

</main>



<!-- EVENT DETAILS MODAL -->
<div class="event-modal" id="eventModal">
    <div class="event-modal-box">

        <button type="button" class="modal-close" onclick="closeEventModal()">
            &times;
        </button>

        <div class="modal-poster" id="modalPoster"></div>

        <div class="modal-body">

            <div class="modal-top">
                <div>
                    <span class="modal-status" id="modalStatus">Upcoming</span>
                    <h2 id="modalTitle">Event Title</h2>
                </div>

                <div class="modal-category" id="modalCategory">Category</div>
            </div>

            <div class="modal-section">
                <h3>About Event</h3>
                <p class="modal-desc" id="modalDescription">Event description.</p>
            </div>

            <div class="modal-section">
                <h3>Event Information</h3>

                <div class="modal-info-grid">

                    <div class="modal-info-item">
                        <i class='bx bx-group'></i>
                        <div>
                            <span>Participants</span>
                            <strong id="modalParticipants">0 / 0 registered</strong>
                        </div>
                    </div>

                    <div class="modal-info-item">
                        <i class='bx bx-user-pin'></i>
                        <div>
                            <span>Organizer</span>
                            <strong id="modalOrganizer">PERSADA UTHM</strong>
                        </div>
                    </div>

                    <div class="modal-info-item">
                        <i class='bx bx-phone'></i>
                        <div>
                            <span>Contact Person</span>
                            <strong id="modalContact">PERSADA Committee</strong>
                        </div>
                    </div>

                    <div class="modal-info-item">
                        <i class='bx bx-calendar-check'></i>
                        <div>
                            <span>Registration Status</span>
                            <strong id="modalRegisterStatus">Open</strong>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-section">
                <h3>Important Notes</h3>

                <ul class="notes-list">
                    <li><i class='bx bx-check-circle'></i>Please arrive 15 minutes before the event starts.</li>
                    <li><i class='bx bx-check-circle'></i>Bring your matric card for verification.</li>
                    <li><i class='bx bx-check-circle'></i>Only registered participants are allowed to join.</li>
                    <li><i class='bx bx-check-circle'></i>Late participants may lose their slot.</li>
                </ul>
            </div>

            <div class="modal-section">
                <h3>Event Benefits</h3>

                <div class="benefit-grid">
                    <div class="benefit-item">
                        <i class='bx bx-award'></i>
                       SMAP
                    </div>

                    <div class="benefit-item">
                        <i class='bx bx-certification'></i>
                        Certificate
                    </div>

                    <div class="benefit-item">
                        <i class='bx bx-group'></i>
                        Networking
                    </div>

                    <div class="benefit-item">
                        <i class='bx bx-star'></i>
                        Experience
                    </div>
                </div>
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

/* FORMAT DATE */
function formatDate(dateString){
    if(!dateString){
        return "-";
    }

    const date = new Date(dateString);
    return date.toLocaleDateString("en-MY", {
        day:"2-digit",
        month:"long",
        year:"numeric"
    });
}

/* FORMAT TIME */
function formatTime(timeString){
    if(!timeString){
        return "-";
    }

    const [hour, minute] = timeString.split(":");
    const date = new Date();
    date.setHours(hour);
    date.setMinutes(minute);

    return date.toLocaleTimeString("en-MY", {
        hour:"2-digit",
        minute:"2-digit",
        hour12:true
    });
}

/* OPEN EVENT DETAILS MODAL */
function openEventModal(event){

    const modal = document.getElementById("eventModal");
    const modalPoster = document.getElementById("modalPoster");
    const modalStatus = document.getElementById("modalStatus");

    if(event.event_poster && event.event_poster !== ""){
        modalPoster.innerHTML = `<img src="${event.event_poster}" alt="Event Poster">`;
    }else{
        modalPoster.innerHTML = `
            <div class="modal-placeholder">
                <i class='bx bx-calendar-event'></i>
            </div>
        `;
    }

    modalStatus.innerText = event.status || "Upcoming";
    modalStatus.className = "modal-status " + (event.status ? event.status.toLowerCase() : "upcoming");

    document.getElementById("modalTitle").innerText = event.event_name || "-";
    document.getElementById("modalCategory").innerText = event.event_category || "PERSADA Event";
    document.getElementById("modalDescription").innerText = event.description || "No description provided.";

    let current = parseInt(event.current_participants) || 0;
let max = parseInt(event.max_participants) || 0;
    document.getElementById("modalParticipants").innerText =
        max > 0 ? current + " / " + max + " registered" : current + " registered";

    document.getElementById("modalOrganizer").innerText = event.created_by || "PERSADA UTHM";
    document.getElementById("modalContact").innerText = "PERSADA Committee";

    if(max > 0 && current >= max){
        document.getElementById("modalRegisterStatus").innerText = "Full";
    }else{
        document.getElementById("modalRegisterStatus").innerText = "Open for Registration";
    }

    modal.classList.add("show");
}
/* CLOSE EVENT DETAILS MODAL */
function closeEventModal(){
    document.getElementById("eventModal").classList.remove("show");
}

/* CLOSE MODAL WHEN CLICK OUTSIDE */
window.addEventListener("click", function(e){
    const modal = document.getElementById("eventModal");

    if(e.target === modal){
        closeEventModal();
    }
});
</script>

</body>
</html>