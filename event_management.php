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

/* RELEASE CERTIFICATE */
if (isset($_GET['release_certificate'])) {
    $event_id = intval($_GET['release_certificate']);

    $stmt = $conn->prepare("
        UPDATE events 
        SET certificate_released = 'Yes'
        WHERE event_id = ?
    ");

    $stmt->bind_param("i", $event_id);
    $stmt->execute();

    echo "<script>alert('Certificate released successfully.'); window.location='event_management.php';</script>";
    exit();
}


$adminName = $_SESSION['admin_name'];

/* CREATE EVENT */
if (isset($_POST['create_event'])) {
    $event_name = $_POST['event_name'];
    $event_category = $_POST['event_category'];
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $venue = $_POST['venue'];
    $description = $_POST['description'];
    $max_participants = $_POST['max_participants'];
    $registration_deadline = $_POST['registration_deadline'];
$today = date('Y-m-d');

if ($event_date > $today) {
    $status = "Upcoming";
} elseif ($event_date == $today) {
    $status = "Ongoing";
} else {
    $status = "Completed";
}



    $created_by = $adminName;

    $event_poster = "";

    if (!empty($_FILES['event_poster']['name'])) {
        $folder = "uploads/events/";

        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES['event_poster']['name']);
        $targetFile = $folder . $fileName;

        if (move_uploaded_file($_FILES['event_poster']['tmp_name'], $targetFile)) {
            $event_poster = $targetFile;
        }
    }

    $stmt = $conn->prepare("
        INSERT INTO events 
        (event_name, event_category, event_date, event_time, venue, description, event_poster, max_participants, registration_deadline, created_by, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sssssssisss",
        $event_name,
        $event_category,
        $event_date,
        $event_time,
        $venue,
        $description,
        $event_poster,
        $max_participants,
        $registration_deadline,
        $created_by,
        $status
    );

    $stmt->execute();

    echo "<script>alert('Event created successfully.'); window.location='event_management.php';</script>";
    exit();
}

/* UPDATE EVENT */
if (isset($_POST['update_event'])) {
    $event_id = $_POST['event_id'];
    $event_name = $_POST['event_name'];
    $event_category = $_POST['event_category'];
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $venue = $_POST['venue'];
    $description = $_POST['description'];
    $max_participants = $_POST['max_participants'];
    $registration_deadline = $_POST['registration_deadline'];
   $today = date('Y-m-d');

if ($event_date > $today) {
    $status = "Upcoming";
} elseif ($event_date == $today) {
    $status = "Ongoing";
} else {
    $status = "Completed";
}
    $old_poster = $_POST['old_poster'];

    $event_poster = $old_poster;

    if (!empty($_FILES['event_poster']['name'])) {
        $folder = "uploads/events/";

        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES['event_poster']['name']);
        $targetFile = $folder . $fileName;

        if (move_uploaded_file($_FILES['event_poster']['tmp_name'], $targetFile)) {
            $event_poster = $targetFile;
        }
    }

    $stmt = $conn->prepare("
        UPDATE events 
        SET event_name=?, event_category=?, event_date=?, event_time=?, venue=?, description=?, event_poster=?, max_participants=?, registration_deadline=?, status=?
        WHERE event_id=?
    ");

    $stmt->bind_param(
        "sssssssissi",
        $event_name,
        $event_category,
        $event_date,
        $event_time,
        $venue,
        $description,
        $event_poster,
        $max_participants,
        $registration_deadline,
        $status,
        $event_id
    );

    $stmt->execute();

    echo "<script>alert('Event updated successfully.'); window.location='event_management.php';</script>";
    exit();
}

/* DELETE EVENT */
if (isset($_POST['delete_event'])) {
    $event_id = $_POST['event_id'];

    $stmt = $conn->prepare("DELETE FROM events WHERE event_id=?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();

    echo "<script>alert('Event deleted successfully.'); window.location='event_management.php';</script>";
    exit();
}

/* SEARCH EVENT */
$search = "";

if (isset($_GET['search'])) {
    $search = $_GET['search'];

    $stmt = $conn->prepare("
        SELECT 
            e.*,
            COUNT(er.registration_id) AS current_participants
        FROM events e
        LEFT JOIN event_registration er 
            ON e.event_id = er.event_id
        WHERE e.event_name LIKE ?
        OR e.event_category LIKE ?
        OR e.venue LIKE ?
        OR e.status LIKE ?
        GROUP BY e.event_id
        ORDER BY e.event_date ASC
    ");

    $keyword = "%" . $search . "%";
    $stmt->bind_param("ssss", $keyword, $keyword, $keyword, $keyword);
    $stmt->execute();
    $events = $stmt->get_result();

} else {
    $events = $conn->query("
        SELECT 
            e.*,
            COUNT(er.registration_id) AS current_participants
        FROM events e
        LEFT JOIN event_registration er 
            ON e.event_id = er.event_id
        GROUP BY e.event_id
        ORDER BY e.event_date ASC
    ");
}

/* TOP CARDS */
$totalEvents = $conn->query("SELECT COUNT(*) AS total FROM events")->fetch_assoc()['total'];
$upcomingEvents = $conn->query("SELECT COUNT(*) AS total FROM events WHERE status='Upcoming'")->fetch_assoc()['total'];
$completedEvents = $conn->query("SELECT COUNT(*) AS total FROM events WHERE status='Completed'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Event Management - PERSADA Admin</title>
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
}

.admin-badge{
    background:var(--card-color);
    padding:12px 18px;
    border-radius:50px;
    color:var(--primary-color);
    font-weight:700;
    box-shadow:var(--shadow);
    border:1px solid var(--border-color);
}

/* EVENT SUMMARY */
.event-summary{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:22px;
    margin-bottom:28px;
}

.summary-card{
    background:var(--card-color);
    border-radius:26px;
    padding:25px;
    box-shadow:var(--shadow);
    border:1px solid var(--border-color);
    transition:.3s;
}

.summary-card:hover{
    transform:translateY(-6px);
}

.summary-card i{
    width:55px;
    height:55px;
    border-radius:18px;
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:28px;
    margin-bottom:15px;
}

.summary-card h3{
    font-size:30px;
    color:var(--title-color);
}

.summary-card p{
    color:var(--text-color);
    font-weight:600;
}

/* EVENT CARD */
.event-card{
    background:var(--card-color);
    border-radius:30px;
    padding:30px;
    box-shadow:var(--shadow);
    border:1px solid var(--border-color);
     min-width:1450px;

}

.event-top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
}

.event-top h2{
    color:var(--title-color);
}

.toolbar{
    display:flex;
    gap:12px;
    align-items:center;
}

.search-box{
    display:flex;
    gap:10px;
}

.search-box input{
    width:280px;
    padding:13px 18px;
    border-radius:16px;
    border:1px solid var(--border-color);
    outline:none;
    color:var(--title-color);
}

.search-box button,
.add-event-btn{
    border:none;
    padding:13px 20px;
    border-radius:16px;
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    color:white;
    font-weight:700;
    cursor:pointer;
    display:flex;
    align-items:center;
    gap:7px;
}

.add-event-btn{
    box-shadow:0 12px 25px rgba(37,99,235,.25);
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
    border-bottom:1px solid rgba(0,0,0,.05);
    font-size:14px;
}

.event-info{
    display:flex;
    align-items:center;
    gap:12px;
}

.event-poster{
    width:58px;
    height:58px;
    border-radius:18px;
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:24px;
    overflow:hidden;
    flex-shrink:0;
}

.event-poster img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.event-info strong{
    color:var(--title-color);
    display:block;
}

.event-info span{
    font-size:12px;
    color:var(--text-color);
}

.status{
    padding:6px 13px;
    border-radius:20px;
    font-size:12px;
    font-weight:800;
}

.upcoming{
    background:#dbeafe;
    color:#2563eb;
}

.completed{
    background:#dcfce7;
    color:#16a34a;
}

.cancelled{
    background:#fee2e2;
    color:#dc2626;
}

.action-group{
     display:flex;
    gap:7px;
    align-items:center;
    white-space:nowrap;
}

.action-btn{
     border:none;
    padding:8px 11px;
    border-radius:12px;
    font-size:12px;
    font-weight:800;
    cursor:pointer;
    display:flex;
    align-items:center;
    gap:5px;
    transition:.25s ease;
}

.action-btn i{
    font-size:15px;
}
.view-btn{
    background:#eff6ff;
    color:#2563eb;
}

.edit-btn{
    background:#ecfeff;
    color:#0891b2;
}

.delete-btn{
    background:#fee2e2;
    color:#dc2626;
}

.action-btn:hover{
    transform:translateY(-3px);
    box-shadow:0 10px 20px rgba(15,23,42,.12);
}

.empty-row{
    text-align:center;
    padding:30px;
    color:var(--text-color);
}

/* MODAL */
.modal{
    position:fixed;
    inset:0;
    background:rgba(15,23,42,.55);
    backdrop-filter:blur(8px);
    display:none;
    justify-content:center;
    align-items:center;
    z-index:9999;
}

.modal.show{
    display:flex;
}

.modal-content{
    width:700px;
    max-width:95%;
    max-height:90vh;
    overflow-y:auto;
    background:#ffffff;
    border-radius:30px;
    padding:32px;
    position:relative;
    box-shadow:0 25px 55px rgba(15,23,42,.25);
    animation:popUp .25s ease;
}

.large-modal{
    width:900px;
}


@keyframes popUp{
    from{
        transform:translateY(20px) scale(.96);
        opacity:0;
    }
    to{
        transform:translateY(0) scale(1);
        opacity:1;
    }
}

.modal-close{
    position:absolute;
    top:18px;
    right:20px;
    width:38px;
    height:38px;
    border:none;
    border-radius:50%;
    background:#eff6ff;
    color:#2563eb;
    font-size:24px;
    cursor:pointer;
}

.modal-content h2{
    color:#0f172a;
    font-size:28px;
    margin-bottom:22px;
}

.form-grid,
.view-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:16px;
}

.form-group label{
    display:block;
    color:#0f172a;
    font-weight:700;
    margin-bottom:7px;
}

.form-group input,
.form-group select,
.form-group textarea{
    width:100%;
    padding:13px 15px;
    border-radius:15px;
    border:1px solid #dbeafe;
    outline:none;
}

.form-group textarea{
    min-height:110px;
    resize:vertical;
}

.form-group.full{
    grid-column:1 / -1;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus{
    border-color:#2563eb;
    box-shadow:0 0 0 4px rgba(37,99,235,.12);
}

.view-box{
    background:#eff6ff;
    border:1px solid rgba(37,99,235,.12);
    padding:18px;
    border-radius:18px;
}

.view-box span{
    display:block;
    color:#64748b;
    font-size:13px;
    margin-bottom:6px;
}

.view-box strong{
    color:#0f172a;
    font-size:15px;
}

.modal-actions{
    margin-top:24px;
    display:flex;
    justify-content:flex-end;
    gap:12px;
}

.modal-actions.center{
    justify-content:center;
}

.cancel-modal-btn,
.save-modal-btn,
.delete-modal-btn{
    border:none;
    padding:13px 22px;
    border-radius:15px;
    font-weight:800;
    cursor:pointer;
}

.cancel-modal-btn{
    background:#e5e7eb;
    color:#0f172a;
}

.save-modal-btn{
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    color:white;
}

.delete-modal{
    text-align:center;
    width:480px;
}

.delete-icon{
    width:78px;
    height:78px;
    margin:0 auto 18px;
    border-radius:24px;
    background:#fee2e2;
    color:#dc2626;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:38px;
}

.delete-modal p{
    color:#64748b;
    margin-bottom:12px;
}

.delete-modal-btn{
    background:#dc2626;
    color:white;
}

@media(max-width:1000px){
    .event-summary{
        grid-template-columns:1fr;
    }

    .event-top{
        flex-direction:column;
        align-items:flex-start;
        gap:15px;
    }

    .toolbar{
        flex-direction:column;
        align-items:flex-start;
        width:100%;
    }

    .search-box input{
        width:100%;
    }

    .event-card{
        overflow-x:auto;
    }

    .form-grid,
    .view-grid{
        grid-template-columns:1fr;
    }
}


.ongoing{
    background:#dcfce7;
    color:#16a34a;

}

.upcoming{
    background:#dbeafe;
    color:#2563eb;
}

.completed{
    background:#ecfccb;
    color:#65a30d;
}

.cancelled{
    background:#fee2e2;
    color:#dc2626;
}

.capacity-box strong{
    color:var(--title-color);
    font-size:13px;
}

.capacity-bar{
    width:90px;
    height:7px;
    background:#e5e7eb;
    border-radius:999px;
    overflow:hidden;
    margin-top:7px;
}

.capacity-fill{
    height:100%;
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    border-radius:999px;
}

.participants-btn{
    background:#f5f3ff;
    color:#7c3aed;
    padding:8px 10px;
}

.participants-btn:hover{
    box-shadow:0 10px 20px rgba(124,58,237,.15);
}

.participant-summary{
    background:#eff6ff;
    border:1px solid rgba(37,99,235,.12);
    border-radius:20px;
    padding:18px;
    margin-bottom:18px;
}

.participant-summary h3{
    color:#0f172a;
    margin-bottom:6px;
}

.participant-summary p{
    color:#64748b;
    font-size:14px;
}


.certificate-released,
.certificate-pending{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:8px 14px;
    border-radius:30px;
    font-size:12px;
    font-weight:800;
    white-space:nowrap;
}

.certificate-released{
    background:#dcfce7;
    color:#16a34a;
}

.certificate-pending{
    background:#fef3c7;
    color:#d97706;
}

.release-btn{
    background:#dcfce7;
    color:#16a34a;
    text-decoration:none;
}

.release-btn:hover{
    background:#bbf7d0;
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
                <li><a href="admin_dashboard.php"><i class='bx bx-home-alt icon'></i><span class="text">Dashboard</span></a></li>
                <li><a href="member_management.php"><i class='bx bx-group icon'></i><span class="text">Members</span></a></li>
                <li><a href="event_management.php" class="active"><i class='bx bx-calendar-event icon'></i><span class="text">Events</span></a></li>
                <li><a href="attendance_management.php"><i class='bx bx-qr-scan icon'></i><span class="text">Attendance</span></a></li>
                <li><a href="report_management.php"><i class='bx bx-bar-chart-alt-2 icon'></i><span class="text">Reports</span></a></li>
             
            </ul>
        </div>

        <div class="bottom-content">
            <li><a href="Login.php"><i class='bx bx-log-out icon'></i><span class="text">Logout</span></a></li>

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
            <h1 class="page-title">Event Management</h1>
            <p class="page-subtitle">Create, view, update and manage PERSADA activities.</p>
        </div>

        <div class="admin-badge">
            <i class='bx bx-user-circle'></i>
            <?php echo $adminName; ?>
        </div>
    </div>

    <div class="event-summary">
        <div class="summary-card">
            <i class='bx bx-calendar-event'></i>
            <h3><?php echo $totalEvents; ?></h3>
            <p>Total Events</p>
        </div>

        <div class="summary-card">
            <i class='bx bx-time-five'></i>
            <h3><?php echo $upcomingEvents; ?></h3>
            <p>Upcoming Events</p>
        </div>

        <div class="summary-card">
            <i class='bx bx-check-circle'></i>
            <h3><?php echo $completedEvents; ?></h3>
            <p>Completed Events</p>
        </div>
    </div>

    <div class="event-card">

        <div class="event-top">
            <h2>Event List</h2>

            <div class="toolbar">
                <form class="search-box" method="GET">
                    <input type="text" name="search" placeholder="Search event..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">
                        <i class='bx bx-search'></i>
                        Search
                    </button>
                </form>

                <button class="add-event-btn" onclick="openAddModal()">
                    <i class='bx bx-plus'></i>
                    Add New Event
                </button>
            </div>
        </div>

        <table>
            <tr>
               <th>Event</th>
<th>Category</th>
<th>Date</th>
<th>Time</th>
<th>Venue</th>
<th>Capacity</th>
<th>Deadline</th>
<th>Status</th>
<th>Certificate</th>
<th>Action</th>
            </tr>

            <?php if ($events->num_rows > 0) { ?>
                <?php while($row = $events->fetch_assoc()) { ?>
                    <tr>
                        <td>
                            <div class="event-info">
                                <div class="event-poster">
                                    <?php if (!empty($row['event_poster'])) { ?>
                                        <img src="<?php echo $row['event_poster']; ?>" alt="Event Poster">
                                    <?php } else { ?>
                                        <i class='bx bx-calendar'></i>
                                    <?php } ?>
                                </div>

                                <div>
                                    <strong><?php echo $row['event_name']; ?></strong>
                                    <span>Created by <?php echo $row['created_by'] ?? '-'; ?></span>
                                </div>
                            </div>
                        </td>

                        <td><?php echo $row['event_category'] ?? '-'; ?></td>
                        <td><?php echo $row['event_date']; ?></td>
                        <td><?php echo date("h:i A", strtotime($row['event_time'])); ?></td>
                        <td><?php echo $row['venue']; ?></td>
<td>
    <div class="capacity-box">
        <strong>
            <?php echo $row['current_participants']; ?> / <?php echo $row['max_participants']; ?>
        </strong>

        <?php
            $capacityPercent = 0;
            if ($row['max_participants'] > 0) {
                $capacityPercent = round(($row['current_participants'] / $row['max_participants']) * 100);
            }
        ?>

        <div class="capacity-bar">
            <div class="capacity-fill" style="width: <?php echo $capacityPercent; ?>%;"></div>
        </div>
    </div>
</td>

<td>
    <?php echo !empty($row['registration_deadline']) ? date("d M Y", strtotime($row['registration_deadline'])) : "-"; ?>
</td>

<?php
$status = $row['status'];
$statusClass = strtolower($status);
?>

<td>
    <span class="status <?php echo $statusClass; ?>">
        <?php echo $status; ?>
    </span>
</td>

<td>
    <?php if(($row['certificate_released'] ?? 'No') == 'Yes'){ ?>
        <span class="certificate-released">
            <i class='bx bx-check-circle'></i>
            Released
        </span>
    <?php } else { ?>
        <span class="certificate-pending">
            <i class='bx bx-time-five'></i>
            Pending
        </span>
    <?php } ?>
</td>

<td>
    <div class="action-group">
        <button type="button" class="action-btn view-btn"
            onclick='openViewModal(<?php echo json_encode($row); ?>)'>
            <i class="bx bx-show"></i> View
        </button>

        <button type="button" class="action-btn edit-btn"
            onclick='openEditModal(<?php echo json_encode($row); ?>)'>
            <i class="bx bx-edit"></i> Edit
        </button>

        <?php if(($row['certificate_released'] ?? 'No') == 'No'){ ?>
            <a href="?release_certificate=<?php echo $row['event_id']; ?>"
               class="action-btn release-btn"
               onclick="return confirm('Release certificate for this event?');">
                <i class='bx bx-award'></i> Release
            </a>
        <?php } ?>

        <button type="button" class="action-btn delete-btn"
            onclick="openDeleteModal(<?php echo $row['event_id']; ?>, '<?php echo addslashes($row['event_name']); ?>')">
            <i class="bx bx-trash"></i> Delete
        </button>
    </div>
</td>

                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="10" class="empty-row">No events found.</td>
                </tr>
            <?php } ?>
        </table>

    </div>

</div>

<!-- ADD MODAL -->
<div class="modal" id="addModal">
    <div class="modal-content large-modal">
        <button class="modal-close" onclick="closeModal('addModal')">&times;</button>

        <h2>Add New Event</h2>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">

                <div class="form-group">
                    <label>Event Name</label>
                    <input type="text" name="event_name" required>
                </div>

                <div class="form-group">
                    <label>Event Category</label>
                    <select name="event_category" required>
                        <option value="">Select Category</option>
                        <option value="Leadership">Leadership</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Seminar">Seminar</option>
                        <option value="Volunteer">Volunteer</option>
                        <option value="Sports">Sports</option>
                        <option value="Academic">Academic</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Event Date</label>
                    <input type="date" name="event_date" required>
                </div>

                <div class="form-group">
                    <label>Event Time</label>
                    <input type="time" name="event_time" required>
                </div>

                <div class="form-group">
                    <label>Venue</label>
                    <input type="text" name="venue" required>
                </div>

                <div class="form-group">
                    <label>Maximum Participants</label>
                    <input type="number" name="max_participants" min="0" value="0">
                </div>

                <div class="form-group">
                    <label>Registration Deadline</label>
                    <input type="date" name="registration_deadline">
                </div>

               

                <div class="form-group full">
                    <label>Description</label>
                    <textarea name="description" required></textarea>
                </div>

                <div class="form-group full">
                    <label>Event Poster</label>
                    <input type="file" name="event_poster" accept="image/*">
                </div>

            </div>

            <div class="modal-actions">
                <button type="button" class="cancel-modal-btn" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" name="create_event" class="save-modal-btn">Create Event</button>
            </div>
        </form>
    </div>
</div>

<!-- VIEW MODAL -->
<div class="modal" id="viewModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('viewModal')">&times;</button>
        <h2>Event Details</h2>
        <div class="view-grid" id="viewContent"></div>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal" id="editModal">
    <div class="modal-content large-modal">
        <button class="modal-close" onclick="closeModal('editModal')">&times;</button>

        <h2>Edit Event</h2>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="event_id" id="edit_event_id">
            <input type="hidden" name="old_poster" id="edit_old_poster">

            <div class="form-grid">

                <div class="form-group">
                    <label>Event Name</label>
                    <input type="text" name="event_name" id="edit_event_name" required>
                </div>

                <div class="form-group">
                    <label>Event Category</label>
                    <select name="event_category" id="edit_event_category" required>
                        <option value="Leadership">Leadership</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Seminar">Seminar</option>
                        <option value="Volunteer">Volunteer</option>
                        <option value="Sports">Sports</option>
                        <option value="Academic">Academic</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Event Date</label>
                    <input type="date" name="event_date" id="edit_event_date" required>
                </div>

                <div class="form-group">
                    <label>Event Time</label>
                    <input type="time" name="event_time" id="edit_event_time" required>
                </div>

                <div class="form-group">
                    <label>Venue</label>
                    <input type="text" name="venue" id="edit_venue" required>
                </div>

                <div class="form-group">
                    <label>Maximum Participants</label>
                    <input type="number" name="max_participants" id="edit_max_participants" min="0">
                </div>

                <div class="form-group">
                    <label>Registration Deadline</label>
                    <input type="date" name="registration_deadline" id="edit_registration_deadline">
                </div>

               

                <div class="form-group full">
                    <label>Description</label>
                    <textarea name="description" id="edit_description" required></textarea>
                </div>

                <div class="form-group full">
                    <label>Change Event Poster</label>
                    <input type="file" name="event_poster" accept="image/*">
                </div>

            </div>

            <div class="modal-actions">
                <button type="button" class="cancel-modal-btn" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" name="update_event" class="save-modal-btn">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE MODAL -->
<div class="modal" id="deleteModal">
    <div class="modal-content delete-modal">
        <div class="delete-icon">
            <i class="bx bx-trash"></i>
        </div>

        <h2>Delete Event?</h2>
        <p id="deleteText"></p>

        <form method="POST">
            <input type="hidden" name="event_id" id="delete_event_id">

            <div class="modal-actions center">
                <button type="button" class="cancel-modal-btn" onclick="closeModal('deleteModal')">Cancel</button>
                <button type="submit" name="delete_event" class="delete-modal-btn">Delete Event</button>
            </div>
        </form>
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

function openAddModal(){
    document.getElementById("addModal").classList.add("show");
}

function openViewModal(event){
    const content = document.getElementById("viewContent");

    content.innerHTML = `
        <div class="view-box"><span>Event Name</span><strong>${event.event_name || '-'}</strong></div>
        <div class="view-box"><span>Category</span><strong>${event.event_category || '-'}</strong></div>
        <div class="view-box"><span>Date</span><strong>${event.event_date || '-'}</strong></div>
        <div class="view-box"><span>Time</span><strong>${event.event_time || '-'}</strong></div>
        <div class="view-box"><span>Venue</span><strong>${event.venue || '-'}</strong></div>
        <div class="view-box"><span>Max Participants</span><strong>${event.max_participants || '0'}</strong></div>
        <div class="view-box"><span>Registration Deadline</span><strong>${event.registration_deadline || '-'}</strong></div>
        <div class="view-box"><span>Status</span><strong>${event.status || '-'}</strong></div>
        <div class="view-box"><span>Created By</span><strong>${event.created_by || '-'}</strong></div>
        <div class="view-box"><span>Created At</span><strong>${event.created_at || '-'}</strong></div>
        <div class="view-box" style="grid-column:1 / -1;"><span>Description</span><strong>${event.description || '-'}</strong></div>
    `;

    document.getElementById("viewModal").classList.add("show");
}

function openEditModal(event){
    document.getElementById("edit_event_id").value = event.event_id || "";
    document.getElementById("edit_event_name").value = event.event_name || "";
    document.getElementById("edit_event_category").value = event.event_category || "";
    document.getElementById("edit_event_date").value = event.event_date || "";
    document.getElementById("edit_event_time").value = event.event_time || "";
    document.getElementById("edit_venue").value = event.venue || "";
    document.getElementById("edit_max_participants").value = event.max_participants || "0";
    document.getElementById("edit_registration_deadline").value = event.registration_deadline || "";
   
    document.getElementById("edit_description").value = event.description || "";
    document.getElementById("edit_old_poster").value = event.event_poster || "";

    document.getElementById("editModal").classList.add("show");
}

function openDeleteModal(id, name){
    document.getElementById("delete_event_id").value = id;

    document.getElementById("deleteText").innerHTML =
        `Are you sure you want to delete <strong>${name}</strong>? This action cannot be undone.`;

    document.getElementById("deleteModal").classList.add("show");
}

function closeModal(id){
    document.getElementById(id).classList.remove("show");
}

window.addEventListener("click", function(e){
    document.querySelectorAll(".modal").forEach(modal => {
        if(e.target === modal){
            modal.classList.remove("show");
        }
    });
});


function openParticipantsModal(event){

    document.getElementById("participantEventName").innerText = event.event_name || "-";

    document.getElementById("participantCapacity").innerText =
        (event.current_participants || 0) + " / " + (event.max_participants || 0) + " participants registered";

    document.getElementById("participantContent").innerHTML = `
        <div class="view-box">
            <span>Current Participants</span>
            <strong>${event.current_participants || 0}</strong>
        </div>

        <div class="view-box">
            <span>Maximum Participants</span>
            <strong>${event.max_participants || 0}</strong>
        </div>

        <div class="view-box">
            <span>Registration Deadline</span>
            <strong>${event.registration_deadline || '-'}</strong>
        </div>

        <div class="view-box">
            <span>Status</span>
            <strong>${event.status || '-'}</strong>
        </div>
    `;

    
}





</script>

</body>
</html>