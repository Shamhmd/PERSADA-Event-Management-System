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

/* SEARCH */
$search = "";

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

/* TOTAL CARDS */
$totalMembers = $conn->query("
    SELECT COUNT(*) AS total 
    FROM students
")->fetch_assoc()['total'];

$activeMembers = $conn->query("
    SELECT COUNT(*) AS total 
    FROM students 
    WHERE status = 'Active'
")->fetch_assoc()['total'];

$pendingMembers = $conn->query("
    SELECT COUNT(*) AS total 
    FROM students 
    WHERE status = 'Pending'
")->fetch_assoc()['total'];

/* MEMBER LIST WITH JOINED EVENTS + ATTENDANCE */
if (!empty($search)) {

    $keyword = "%" . $search . "%";

    $stmt = $conn->prepare("
        SELECT 
            s.*,

            COUNT(DISTINCT er.event_id) AS joined_events,

            COUNT(DISTINCT CASE 
                WHEN a.attendance_status = 'Present' THEN a.event_id 
            END) AS attended_events

        FROM students s

        LEFT JOIN event_registration er
            ON s.id = er.student_id

        LEFT JOIN attendance a
            ON s.id = a.student_id
            AND er.event_id = a.event_id

        WHERE s.name LIKE ?
        OR s.matric_number LIKE ?
        OR s.email LIKE ?
        OR s.phone_number LIKE ?
        OR s.faculty LIKE ?

        GROUP BY s.id
        ORDER BY s.name ASC
    ");

    $stmt->bind_param(
        "sssss",
        $keyword,
        $keyword,
        $keyword,
        $keyword,
        $keyword
    );

    $stmt->execute();
    $members = $stmt->get_result();

} else {

    $members = $conn->query("
        SELECT 
            s.*,

            COUNT(DISTINCT er.event_id) AS joined_events,

            COUNT(DISTINCT CASE 
                WHEN a.attendance_status = 'Present' THEN a.event_id 
            END) AS attended_events

        FROM students s

        LEFT JOIN event_registration er
            ON s.id = er.student_id

        LEFT JOIN attendance a
            ON s.id = a.student_id
            AND er.event_id = a.event_id

        GROUP BY s.id
        ORDER BY s.name ASC
    ");
}

/* UPDATE MEMBER */
if (isset($_POST['update_member'])) {

    $student_id = $_POST['student_id'];
    $name = $_POST['name'];
    $matric_number = $_POST['matric_number'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $faculty = $_POST['faculty'];
    $status = $_POST['status'];
    $old_profile_picture = $_POST['old_profile_picture'];

    $profile_picture = $old_profile_picture;

    if (!empty($_FILES['profile_picture']['name'])) {

        $folder = "uploads/profile/";

        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES['profile_picture']['name']);
        $targetFile = $folder . $fileName;

        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
            $profile_picture = $targetFile;
        }
    }

    $stmt = $conn->prepare("
        UPDATE students
        SET 
            name = ?,
            matric_number = ?,
            email = ?,
            phone_number = ?,
            faculty = ?,
            status = ?,
            profile_picture = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "sssssssi",
        $name,
        $matric_number,
        $email,
        $phone_number,
        $faculty,
        $status,
        $profile_picture,
        $student_id
    );

    $stmt->execute();

    echo "<script>
        alert('Member updated successfully.');
        window.location='member_management.php';
    </script>";
    exit();
}

/* DELETE MEMBER */
if (isset($_POST['delete_member'])) {

    $student_id = intval($_POST['student_id']);

    $conn->query("DELETE FROM attendance WHERE student_id = $student_id");
    $conn->query("DELETE FROM event_registration WHERE student_id = $student_id");

    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();

    echo "<script>
        alert('Member deleted successfully.');
        window.location='member_management.php';
    </script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Member Management - PERSADA Admin</title>
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

/* MEMBER PAGE */
.member-summary{
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

.member-card{
    background:var(--card-color);
    border-radius:30px;
    padding:30px;
    box-shadow:var(--shadow);
    border:1px solid var(--border-color);
}

.member-top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
}

.member-top h2{
    color:var(--title-color);
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

.search-box button{
    border:none;
    padding:13px 20px;
    border-radius:16px;
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    color:white;
    font-weight:700;
    cursor:pointer;
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

.member-info{
    display:flex;
    align-items:center;
    gap:12px;
}

.member-avatar{
    width:42px;
    height:42px;
    border-radius:50%;
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:800;
    overflow:hidden;
    flex-shrink:0;
}

.member-avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
    object-position:center;
    display:block;
}

.member-info strong{
    color:var(--title-color);
}

.status{
    padding:6px 13px;
    border-radius:20px;
    background:#dcfce7;
    color:#16a34a;
    font-size:12px;
    font-weight:800;
}

.action-btn{
    text-decoration:none;
    padding:8px 12px;
    border-radius:12px;
    font-size:13px;
    font-weight:700;
    margin-right:6px;
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

.empty-row{
    text-align:center;
    padding:30px;
    color:var(--text-color);
}
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
      width:1000px;
    max-width:95%;
    background:#ffffff;
    border-radius:30px;
    padding:32px;
    position:relative;
    box-shadow:0 25px 55px rgba(15,23,42,.25);
    animation:popUp .25s ease;
}

.large-modal{
    width:850px;
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

.view-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:16px;
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

.form-grid{
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
.form-group select{
    width:100%;
    padding:13px 15px;
    border-radius:15px;
    border:1px solid #dbeafe;
    outline:none;
}

.form-group input:focus,
.form-group select:focus{
    border-color:#2563eb;
    box-shadow:0 0 0 4px rgba(37,99,235,.12);
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
.action-group{
    display:flex;
    gap:8px;
}

.action-btn{
    border:none;
    padding:9px 14px;
    border-radius:14px;
    font-size:13px;
    font-weight:800;
    cursor:pointer;
    display:flex;
    align-items:center;
    gap:6px;
    transition:.25s ease;
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

@keyframes modalShow{

from{
    transform:translateY(20px);
    opacity:0;
}

to{
    transform:translateY(0);
    opacity:1;
}

}
@media(max-width:1000px){
    .member-summary{
        grid-template-columns:1fr;
    }

    .member-top{
        flex-direction:column;
        align-items:flex-start;
        gap:15px;
    }

    .search-box{
        width:100%;
    }

    .search-box input{
        width:100%;
    }
}

    .member-card{
        overflow-x:auto;
        min-width:1350px;
    }



}

.member-card{
    overflow-x:auto;
}

.member-card table{
    min-width:1450px;
}
.joined-badge{
    display:inline-flex;
    align-items:center;
    padding:7px 13px;
    border-radius:999px;
    background:#eff6ff;
    color:#2563eb;
    font-size:13px;
    font-weight:800;
}

.attendance-mini strong{
    display:block;
    color:var(--title-color);
    font-size:13px;
    margin-bottom:6px;
}

.attendance-mini-bar{
    width:85px;
    height:7px;
    background:#e5e7eb;
    border-radius:999px;
    overflow:hidden;
}

.attendance-mini-fill{
    height:100%;
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    border-radius:999px;
}

.member-detail-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:16px;
}

.member-detail-box{
    background:#eff6ff;
    border:1px solid rgba(37,99,235,.12);
    border-radius:18px;
    padding:16px;
}

.member-detail-box span{
    display:block;
    color:#64748b;
    font-size:13px;
    margin-bottom:6px;
}

.member-detail-box strong{
    color:#0f172a;
    font-size:15px;
}

.member-detail-box.full{
    grid-column:1 / -1;
}

.member-profile-head{
    display:flex;
    align-items:center;
    gap:16px;
    padding:18px;
    background:linear-gradient(135deg,#eff6ff,#ecfeff);
    border-radius:22px;
    margin-bottom:20px;
}

.member-profile-img{
    width:70px;
    height:70px;
    border-radius:50%;
    overflow:hidden;
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:26px;
    font-weight:800;
}

.member-profile-img img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.member-profile-head h3{
    color:#0f172a;
    font-size:22px;
    margin-bottom:4px;
}

.member-profile-head p{
    color:#64748b;
    font-size:14px;
}


.member-profile-head{
    display:flex;
    align-items:center;
    gap:18px;
    padding:22px;
    background:linear-gradient(135deg,#eff6ff,#ecfeff);
    border-radius:24px;
    margin-bottom:22px;
}

.member-profile-img{
    width:82px;
    height:82px;
    border-radius:50%;
    overflow:hidden;
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:30px;
    font-weight:800;
    flex-shrink:0;
}

.member-profile-img img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.member-detail-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:16px;
}

.member-detail-box{
    background:#f8fafc;
    border:1px solid #dbeafe;
    border-radius:18px;
    padding:16px;
}

.member-detail-box.full{
    grid-column:1 / -1;
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
                <li><a href="member_management.php" class="active"><i class='bx bx-group icon'></i><span class="text">Members</span></a></li>
                <li><a href="event_management.php"><i class='bx bx-calendar-event icon'></i><span class="text">Events</span></a></li>
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
            <h1 class="page-title">Member Management</h1>
            <p class="page-subtitle">View, search and manage PERSADA student members.</p>
        </div>

        <div class="admin-badge">
            <i class='bx bx-user-circle'></i>
            <?php echo $adminName; ?>
        </div>
    </div>

    <div class="member-summary">
        <div class="summary-card">
            <i class='bx bx-group'></i>
            <h3><?php echo $totalMembers; ?></h3>
            <p>Total Members</p>
        </div>

        <div class="summary-card">
            <i class='bx bx-user-check'></i>
            <h3><?php echo $totalMembers; ?></h3>
            <p>Active Members</p>
        </div>

        <div class="summary-card">
            <i class='bx bx-time-five'></i>
            <h3>0</h3>
            <p>Pending Approval</p>
        </div>
    </div>

    <div class="member-card">

        <div class="member-top">
            <h2>Registered Members</h2>

            <form class="search-box" method="GET">
                <input type="text" name="search" placeholder="Search name, matric or email..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">
                    <i class='bx bx-search'></i>
                    Search
                </button>
            </form>
        </div>

        <table>
            <tr>
               <th>Member</th>
<th>Matric No</th>
<th>Email</th>
<th>Phone</th>
<th>Faculty</th>
<th>Joined Events</th>
<th>Attendance</th>
<th>Status</th>
<th>Action</th>
            </tr>

            <?php if ($members->num_rows > 0) { ?>
                <?php while($row = $members->fetch_assoc()) { ?>
                    <tr>
                        <td>
                            <div class="member-avatar">
    <?php if (!empty($row['profile_picture'])) { ?>
        <img src="<?php echo $row['profile_picture']; ?>" alt="Profile Picture">
    <?php } else { ?>
        <?php echo strtoupper(substr($row['name'],0,1)); ?>
    <?php } ?>
</div>
                        </td>

                        <td><?php echo $row['matric_number']; ?></td>
                        <td><?php echo $row['email']; ?></td>
                        <td><?php echo $row['phone_number']; ?></td>
                        <td><?php echo $row['faculty']; ?></td>


<td>
    <span class="joined-badge">
        <?php echo $row['joined_events']; ?> events
    </span>
</td>

<td>
    <div class="attendance-mini">
        <strong>
            <?php echo $row['attended_events']; ?> / <?php echo $row['joined_events']; ?>
        </strong>

        <?php
            $attendancePercent = 0;

            if ($row['joined_events'] > 0) {
                $attendancePercent = round(($row['attended_events'] / $row['joined_events']) * 100);
            }
        ?>

        <div class="attendance-mini-bar">
            <div 
                class="attendance-mini-fill" 
                style="width: <?php echo $attendancePercent; ?>%;">
            </div>
        </div>
    </div>
</td>





                        <td>
                            <span class="status">Active</span>
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

        <button type="button" class="action-btn delete-btn"
            onclick="openDeleteModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['name']); ?>')">
            <i class="bx bx-trash"></i> Delete
        </button>
    </div>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="9" class="empty-row">No members found.</td>
                </tr>
            <?php } ?>
        </table>

    </div>


<div class="modal" id="viewModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('viewModal')">&times;</button>
        <h2>Member Details</h2>

        <div class="view-grid" id="viewContent"></div>
    </div>
</div>

<div class="modal" id="editModal">
    <div class="modal-content large-modal">
        <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        <h2>Edit Member</h2>

      <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="student_id" id="edit_id">
    <input type="hidden" name="old_profile_picture" id="edit_old_profile_picture">
<input type="hidden" name="status" id="edit_status">

            <div class="form-grid">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>

                <div class="form-group">
                    <label>Matric Number</label>
                    <input type="text" name="matric_number" id="edit_matric_number" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone_number" id="edit_phone_number" required>
                </div>

                <div class="form-group">
                    <label>Faculty</label>
                   <select name="faculty" id="edit_faculty" onchange="updateProgrammeOptions()">
                        <option value="FSKTM">FSKTM</option>
                        <option value="FKEE">FKEE</option>
                        <option value="FKMP">FKMP</option>
                        <option value="FKAAB">FKAAB</option>
                        <option value="FPTV">FPTV</option>
                        <option value="FPTP">FPTP</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" id="edit_gender">
                        <option value="">Select Gender</option>
                        <option value="Female">Female</option>
                        <option value="Male">Male</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="date_of_birth" id="edit_date_of_birth">
                </div>

                <div class="form-group">
                    <label>Programme</label>
                    <select name="programme" id="edit_programme">
    <option value="">Select Faculty First</option>
</select>
                </div>

                <div class="form-group">
                    <label>Year of Study</label>
                    <select name="year_of_study" id="edit_year_of_study">
                        <option value="">Select Year</option>
                        <option value="Year 1">Year 1</option>
                        <option value="Year 2">Year 2</option>
                        <option value="Year 3">Year 3</option>
                        <option value="Year 4">Year 4</option>
                    </select>
                </div>
            </div>




            <div class="form-group">
    <label>Profile Picture</label>
    <input type="file" name="profile_picture" accept="image/*">
</div>

            <div class="modal-actions">
                <button type="button" class="cancel-modal-btn" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" name="update_member" class="save-modal-btn">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="deleteModal">
    <div class="modal-content delete-modal">
        <div class="delete-icon">
            <i class="bx bx-trash"></i>
        </div>

        <h2>Delete Member?</h2>
        <p id="deleteText"></p>

        <form method="POST">
    <input type="hidden" name="student_id" id="delete_id">

            <div class="modal-actions center">
                <button type="button" class="cancel-modal-btn" onclick="closeModal('deleteModal')">Cancel</button>
                <button type="submit" name="delete_member" class="delete-modal-btn">Delete Member</button>
            </div>
        </form>
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

function openViewModal(member){

    const content = document.getElementById("viewContent");

    let profileImage = "";

    if(member.profile_picture && member.profile_picture !== ""){
        profileImage = `<img src="${member.profile_picture}" alt="Profile">`;
    }else{
        profileImage = (member.name || "A").charAt(0).toUpperCase();
    }

    let joinedEvents = parseInt(member.joined_events || 0);
    let attendedEvents = parseInt(member.attended_events || 0);

    let percent = 0;

    if(joinedEvents > 0){
        percent = Math.round((attendedEvents / joinedEvents) * 100);
    }

    content.innerHTML = `
        <div class="member-profile-head">
            <div class="member-profile-img">
                ${profileImage}
            </div>

            <div>
                <h3>${member.name || "-"}</h3>
                <p>${member.matric_number || "-"} • ${member.faculty || "-"}</p>
            </div>
        </div>

        <div class="member-detail-grid">

            <div class="member-detail-box">
                <span>Email</span>
                <strong>${member.email || "-"}</strong>
            </div>

            <div class="member-detail-box">
                <span>Phone Number</span>
                <strong>${member.phone_number || "-"}</strong>
            </div>

            <div class="member-detail-box">
                <span>Faculty</span>
                <strong>${member.faculty || "-"}</strong>
            </div>

            <div class="member-detail-box">
                <span>Status</span>
                <strong>${member.status || "Active"}</strong>
            </div>

            <div class="member-detail-box">
                <span>Joined Events</span>
                <strong>${joinedEvents} events</strong>
            </div>

            <div class="member-detail-box">
                <span>Attendance</span>
                <strong>${attendedEvents} / ${joinedEvents} present</strong>
            </div>

            <div class="member-detail-box full">
                <span>Attendance Rate</span>
                <strong>${percent}% attendance rate</strong>
            </div>

        </div>
    `;

    document.getElementById("viewModal").classList.add("show");
}
const programmeList = {
    "FSKTM": [
        "BIW - Bachelor of Computer Science (Web Technology)",
        "BIP - Bachelor of Computer Science (Software Engineering)",
        "BIS - Bachelor of Information Security",
        "BIM - Bachelor of Multimedia Computing"
    ],

    "FKEE": [
        "BEE - Bachelor of Electrical Engineering",
        "BEP - Bachelor of Electronic Engineering",
        "BET - Bachelor of Telecommunication Engineering"
    ],

    "FKMP": [
        "BMM - Bachelor of Mechanical Engineering",
        "BMP - Bachelor of Manufacturing Engineering",
        "BMA - Bachelor of Automotive Engineering"
    ],

    "FKAAB": [
        "BFC - Bachelor of Civil Engineering",
        "BFF - Bachelor of Construction Management"
    ],

    "FPTV": [
        "BBV - Bachelor of Vocational Education",
        "BBD - Bachelor of Design and Technology"
    ],

    "FPTP": [
        "BPP - Bachelor of Technology Management",
        "BPA - Bachelor of Real Estate Management"
    ]
};

function updateProgrammeOptions(selectedProgramme = ""){
    const faculty = document.getElementById("edit_faculty").value;
    const programmeSelect = document.getElementById("edit_programme");

    programmeSelect.innerHTML = `<option value="">Select Programme</option>`;

    if(programmeList[faculty]){
        programmeList[faculty].forEach(programme => {
            const option = document.createElement("option");
            option.value = programme;
            option.textContent = programme;

            if(programme === selectedProgramme){
                option.selected = true;
            }

            programmeSelect.appendChild(option);
        });
    }
}
function openEditModal(member){
    document.getElementById("edit_id").value = member.id || "";
    document.getElementById("edit_name").value = member.name || "";
    document.getElementById("edit_matric_number").value = member.matric_number || "";
    document.getElementById("edit_email").value = member.email || "";
    document.getElementById("edit_phone_number").value = member.phone_number || "";
    document.getElementById("edit_faculty").value = member.faculty || "";
    document.getElementById("edit_gender").value = member.gender || "";
    document.getElementById("edit_date_of_birth").value = member.date_of_birth || "";
   updateProgrammeOptions(member.programme || "");
    document.getElementById("edit_year_of_study").value = member.year_of_study || "";

    document.getElementById("editModal").classList.add("show");
    document.getElementById("edit_old_profile_picture").value = member.profile_picture || "";
document.getElementById("edit_status").value = member.status || "Active";
}

function openDeleteModal(id, name){
    document.getElementById("delete_id").value = id;
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







</script>

</body>
</html>