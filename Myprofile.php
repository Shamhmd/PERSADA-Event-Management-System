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

$eventCount = $conn->query("
    SELECT COUNT(*) AS total 
    FROM event_registration 
    WHERE student_id = '$student_id'
")->fetch_assoc()['total'];

if (isset($_POST['change_password'])) {

    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        echo "<script>alert('New password and confirm password do not match.');</script>";
    } else {

        $check = $conn->prepare("SELECT password FROM students WHERE id=?");
        $check->bind_param("i", $student_id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();

        if (password_verify($current_password, $row['password'])) {

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $updatePass = $conn->prepare("UPDATE students SET password=? WHERE id=?");
            $updatePass->bind_param("si", $hashed_password, $student_id);

            if ($updatePass->execute()) {
                echo "<script>alert('Password updated successfully.'); window.location='Myprofile.php';</script>";
                exit();
            }

        } else {
            echo "<script>alert('Current password is incorrect.');</script>";
        }
    }
}
if (isset($_POST['update_profile'])) {

    $name = $_POST['name'];
    $matric_number = $_POST['matric_number'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $gender = $_POST['gender'];
    $date_of_birth = $_POST['date_of_birth'];
    $faculty = $_POST['faculty'];
    $programme = $_POST['programme'];
    $year_of_study = $_POST['year_of_study'];

    $profile_picture = $student['profile_picture'] ?? "";

    if (!empty($_FILES['profile_picture']['name'])) {
        $uploadDir = "uploads/profile/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES['profile_picture']['name']);
        $targetFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
            $profile_picture = $targetFile;
        }
    }

    $update = $conn->prepare("
        UPDATE students 
        SET name=?, matric_number=?, email=?, phone_number=?, gender=?, date_of_birth=?, faculty=?, programme=?, year_of_study=?, profile_picture=?
        WHERE id=?
    ");

    $update->bind_param(
        "ssssssssssi",
        $name,
        $matric_number,
        $email,
        $phone_number,
        $gender,
        $date_of_birth,
        $faculty,
        $programme,
        $year_of_study,
        $profile_picture,
        $student_id
    );

    if ($update->execute()) {
        echo "<script>alert('Profile updated successfully!'); window.location='Myprofile.php';</script>";
        exit();
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile - PERSADA</title>
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

.sidebar li a:hover,
.sidebar li a.active{
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
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

.page-title{
    color:var(--title-color);
    font-size:32px;
    margin-bottom:25px;
}

/* PROFILE */
.profile-hero{
    background:var(--card-color);
    border-radius:26px;
    padding:28px;
    display:flex;
    align-items:center;
    gap:25px;
    box-shadow:var(--shadow);
    margin-bottom:28px;
}

.avatar{
    width:120px;
    height:120px;
    border-radius:50%;
    overflow:hidden;
    border:6px solid #fff;
    box-shadow:0 5px 15px rgba(0,0,0,.1);
}
.avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
    object-position:center;
}
.profile-hero h2{
    color:var(--title-color);
    font-size:26px;
}

.profile-hero p{
    color:#ff6b4a;
    font-weight:700;
    margin:5px 0;
}

.profile-hero span{
    color:var(--text-color);
}

.profile-card{
    background:var(--card-color);
    border-radius:26px;
    padding:28px;
    box-shadow:var(--shadow);
    margin-bottom:28px;
}

.card-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    border-bottom:1px solid rgba(0,0,0,.08);
    padding-bottom:16px;
    margin-bottom:25px;
}

.card-header h3{
    color:var(--title-color);
    font-size:21px;
}

.edit-btn{
    border:none;
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    color:white;
    padding:10px 18px;
    border-radius:12px;
    font-weight:700;
    cursor:pointer;
    font-size: 16px;
}

.info-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:18px;
}

.info-box{
    background:#fff7ef;
    border:1px solid rgba(255,107,74,.12);
    border-radius:20px;
    padding:18px;
    display:flex;
    align-items:flex-start;
    gap:14px;
    transition:.3s ease;
}

.info-box:hover{
    transform:translateY(-5px);
    box-shadow:0 14px 28px rgba(255,107,74,.12);
}

.info-icon{
    width:42px;
    height:42px;
    border-radius:14px;
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:20px;
    flex-shrink:0;
}

.info-box span{
    display:block;
    color:#667085;
    font-size:16px;
    margin-bottom:5px;
}

.info-box strong{
    color:#16254c;
    font-size:15px;
    line-height:1.4;
}

.summary-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:20px;
}

.summary-card{
    background:var(--primary-light);
    border-radius:22px;
    padding:22px;
    text-align:center;
}

.summary-card i{
    font-size:35px;
    color:#ff6b4a;
    margin-bottom:10px;
}

.summary-card h3{
    color:var(--title-color);
    font-size:24px;
}

.summary-card p{
    color:var(--text-color);
    font-size:13px;
}



.modal{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.45);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:999;
    padding:20px;
}

.modal-box{
    background:var(--card-color);
    width:850px;
    max-width:95%;
    max-height:90vh;
    overflow-y:auto;
    border-radius:26px;
    padding:32px;
    box-shadow:0 25px 60px rgba(0,0,0,.18);
}

.modal-box h2{
    color:var(--title-color);
    margin-bottom:6px;
    font-size:28px;
}

.modal-subtitle{
    color:var(--text-color);
    margin-bottom:24px;
    font-size:14px;
}

.modal-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:18px 20px;
}

.form-group{
    display:flex;
    flex-direction:column;
}

.form-group label{
    color:var(--title-color);
    font-size:13px;
    font-weight:600;
    margin-bottom:8px;
}

.form-group input,
.form-group select{
    width:100%;
    padding:14px 16px;
    border-radius:14px;
    border:1px solid #e5e7eb;
    outline:none;
    font-size:14px;
    background:#fff;
    color:#16254c;
}

.form-group input:focus,
.form-group select:focus{
    border-color:#ff6b4a;
    box-shadow:0 0 0 4px rgba(255,107,74,.12);
}

.form-group input:disabled{
    background:#f3f4f6;
    color:#888;
    cursor:not-allowed;
}

.modal-actions{
    display:flex;
    justify-content:flex-end;
    gap:12px;
    margin-top:28px;
}
.password-modal-box{
    width:620px;
    padding:34px;
}

.password-modal-header{
    display:flex;
    align-items:center;
    gap:18px;
    margin-bottom:28px;
}

.password-header-icon{
    width:62px;
    height:62px;
    border-radius:20px;
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:30px;
    flex-shrink:0;
}

.password-modal-header h2{
    color:#16254c;
    font-size:30px;
    margin-bottom:4px;
}

.password-modal-header p{
    color:#667085;
    font-size:14px;
}

.password-field{
    margin-bottom:18px;
}

.password-field label{
    display:block;
    color:#16254c;
    font-size:14px;
    font-weight:700;
    margin-bottom:8px;
}

.password-input-wrap{
    position:relative;
}

.password-input-wrap input{
    width:100%;
    height:56px;
    border:1px solid #e5e7eb;
    border-radius:16px;
    padding:0 52px 0 18px;
    outline:none;
    font-size:15px;
    color:#16254c;
    transition:.3s ease;
}

.password-input-wrap input:focus{
    border-color:#ff6b4a;
    box-shadow:0 0 0 4px rgba(255,107,74,.12);
}

.eye-icon{
    position:absolute;
    right:18px;
    top:50%;
    transform:translateY(-50%);
    font-size:22px;
    color:#888;
    cursor:pointer;
}

.password-requirements{
    background:#fff7ef;
    border:1px solid rgba(255,107,74,.14);
    border-radius:18px;
    padding:16px 18px;
    margin-bottom:18px;
}

.password-requirements p{
    font-size:13px;
    color:#ff5b45;
    margin-bottom:6px;
}

.password-requirements p.valid{
    color:#28a745;
}

#matchText{
    display:block;
    margin-top:8px;
    font-size:13px;
    font-weight:600;
}

#updatePasswordBtn:disabled{
    opacity:.45;
    cursor:not-allowed;
}
.cancel-btn,
.save-btn{
    border:none;
    padding:13px 24px;
    border-radius:14px;
    font-weight:700;
    cursor:pointer;
}

.cancel-btn{
    background:#eee;
    color:#333;
}

.save-btn{
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    color:white;
}


.membership-card{
    position:relative;
    overflow:hidden;
    background:linear-gradient(135deg,#ffffff,#fff7ef);
}

.membership-card::after{
    content:"";
    position:absolute;
    width:190px;
    height:190px;
    right:-70px;
    bottom:-80px;
    background:rgba(255,107,74,.08);
    border-radius:50%;
}

.membership-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    border-bottom:1px solid rgba(0,0,0,.08);
    padding-bottom:18px;
    margin-bottom:26px;
    position:relative;
    z-index:1;
}

.membership-header h3{
    color:#16254c;
    font-size:22px;
    margin-bottom:4px;
}

.membership-header p{
    color:#667085;
    font-size:16px;
}

.member-badge{
    background:#fff1e8;
    color:#ff6b4a;
    padding:10px 18px;
    border-radius:30px;
    font-size:16px;
    font-weight:700;
}

.membership-grid.two-columns{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:22px;
    position:relative;
    z-index:1;
}

.membership-item.premium{
    background:#ffffff;
    border:1px solid rgba(255,107,74,.15);
    border-radius:24px;
    padding:24px;
    display:flex;
    align-items:center;
    gap:18px;
    box-shadow:0 12px 28px rgba(255,107,74,.08);
    transition:.3s ease;
}

.membership-item.premium:hover{
    transform:translateY(-6px);
    box-shadow:0 18px 38px rgba(255,107,74,.16);
}

.membership-icon{
    width:58px;
    height:58px;
    border-radius:18px;
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:28px;
    flex-shrink:0;
}

.membership-item span{
    display:block;
    color:#667085;
    font-size:16px;
    margin-bottom:5px;
}

.membership-item strong{
    display:block;
    color:#16254c;
    font-size:20px;
    margin-bottom:4px;
}

.membership-item small{
    color:#856E5D;
    font-size:16px;
}


.account-settings-card{
    background:#fff;
    border-radius:30px;
    padding:35px;
    margin-top:35px;
    box-shadow:0 8px 30px rgba(0,0,0,.05);
}

.section-header h2{
    color:#0d2a66;
    font-size:22px;
    font-weight:700;
    margin-bottom:5px;
}

.section-header p{
    color:#7a7a7a;
    margin-bottom:30px;
}

.settings-grid{
    display:flex;
    flex-direction:column;
    gap:20px;
}

.setting-item{
    display:flex;
    align-items:center;
    justify-content:space-between;
    background:#faf4ef;
    border:1px solid #f4d8c7;
    border-radius:20px;
    padding:20px 25px;
}

.setting-icon{
    width:60px;
    height:60px;
    border-radius:18px;
    background:linear-gradient(135deg,#ff6f4d,#f5b13d);
    display:flex;
    align-items:center;
    justify-content:center;
    color:#fff;
    font-size:26px;
}

.setting-content{
    flex:1;
    margin-left:20px;
}

.setting-content h4{
    color:#0d2a66;
    font-size:20px;
    margin-bottom:4px;
}

.setting-content p{
    color:#777;
    font-size:16px;
}

.setting-btn{
    background:linear-gradient(135deg,#ff6f4d,#f5b13d);
    color:#fff;
    border:none;
    border-radius:12px;
    padding:12px 22px;
    font-weight:600;
    cursor:pointer;
    font-size: 14px;
}
.notification-switch{
    position:relative;
    display:inline-block;
    width:62px;
    height:34px;
}

.notification-switch input{
    opacity:0;
    width:0;
    height:0;
}

.notification-slider{
    position:absolute;
    inset:0;
    background:#d9d9d9;
    border-radius:50px;
    cursor:pointer;
    transition:.3s;
}

.notification-slider::before{
    content:"";
    position:absolute;
    width:26px;
    height:26px;
    left:4px;
    top:4px;
    background:white;
    border-radius:50%;
    transition:.3s;
    box-shadow:0 3px 8px rgba(0,0,0,.15);
}

.notification-switch input:checked + .notification-slider{
    background:linear-gradient(135deg,#ff6f4d,#f5b13d);
}

.notification-switch input:checked + .notification-slider::before{
    transform:translateX(28px);
}
.switch{
    position:relative;
    display:inline-block;
    width:58px;
    height:30px;
}

.switch input{
    opacity:0;
    width:0;
    height:0;
}

.slider{
    position:absolute;
    cursor:pointer;
    top:0;
    left:0;
    right:0;
    bottom:0;
    background:#ccc;
    transition:.4s;
    border-radius:30px;
}

.slider:before{
    position:absolute;
    content:"";
    height:22px;
    width:22px;
    left:4px;
    bottom:4px;
    background:white;
    transition:.4s;
    border-radius:50%;
}

input:checked + .slider{
    background:#ff8a4c;
}

input:checked + .slider:before{
    transform:translateX(28px);
}

@media(max-width:900px){
    .membership-grid.two-columns{
        grid-template-columns:1fr;
    }

    .membership-header{
        flex-direction:column;
        align-items:flex-start;
        gap:12px;
    }
}

@media(max-width:900px){
    .membership-grid{
        grid-template-columns:1fr;
    }
}








@media(max-width:700px){
    .modal-grid{
        grid-template-columns:1fr;
    }
}









@media(max-width:1000px){
    .info-grid,
    .summary-grid{
        grid-template-columns:1fr;
    }

    .profile-hero{
        flex-direction:column;
        text-align:center;
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
                <li><a href="dashboard.php"><i class='bx bx-home-alt icon'></i><span class="text">Dashboard</span></a></li>
                <li><a href="Myprofile.php" class="active"><i class='bx bx-user icon'></i><span class="text">My Profile</span></a></li>
                <li><a href="Event_List.php"><i class='bx bx-calendar-event icon'></i><span class="text">Event List</span></a></li>
                
                <li><a href="User_Scan_QR.php"><i class='bx bx-qr-scan icon'></i><span class="text">Scan QR</span></a></li>
                <li><a href="participation.php"><i class='bx bx-history icon'></i><span class="text">Participation</span></a></li>
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

    <h1 class="page-title">My Profile</h1>

    <div class="profile-hero">
        <div class="avatar">
    <?php if (!empty($student['profile_picture'])) { ?>
        <img src="<?php echo $student['profile_picture']; ?>" alt="Profile Picture">
    <?php } else { ?>
        <?php echo strtoupper(substr($student['name'],0,1)); ?>
    <?php } ?>
</div>

        <div>
            <h2><?php echo $student['name']; ?></h2>
            <p>PERSADA Student Member</p>
            <span><?php echo $student['matric_number']; ?> • <?php echo $student['faculty']; ?></span>
        </div>
    </div>

    <div class="profile-card">
        <div class="card-header">
            <h3>Personal Information</h3>
           <button class="edit-btn" onclick="openEditModal()" type="button">
    Edit <i class='bx bx-edit'></i>
</button>
        </div>

        <div class="info-grid">
           <div class="info-box">
    <div class="info-icon"><i class='bx bx-user'></i></div>
    <div>
        <span>Full Name</span>
        <strong><?php echo $student['name']; ?></strong>
    </div>
</div>

            <div class="info-box">
                 <div class="info-icon"><i class=' bx bx-id-card'></i></div>

                <span>Matric Number</span>
                <strong><?php echo $student['matric_number']; ?></strong>
            </div>

            <div class="info-box">
                 <div class="info-icon"><i class='bx bx-envelope'></i></div>
                <span>Email Address</span>
                <strong><?php echo $student['email']; ?></strong>
            </div>

            <div class="info-box">
                 <div class="info-icon"><i class='bx bx-phone'></i></div>
                <span>Phone Number</span>
                <strong><?php echo $student['phone_number']; ?></strong>
            </div>


<div class="info-box">
     <div class="info-icon"><i class='bx bx-male-female'></i></div>
    <span>Gender</span>
    <strong><?php echo $student['gender'] ?? '-'; ?></strong>
</div>

<div class="info-box">
     <div class="info-icon"><i class='bx bx-calendar'></i></div>
    <span>Date of Birth</span>
    <strong>
        <?php 
        echo !empty($student['date_of_birth']) 
            ? date("d F Y", strtotime($student['date_of_birth'])) 
            : '-'; 
        ?>
    </strong>
</div>



            <div class="info-box">
                <div class="info-icon"><i class='bx bx-buildings'></i></div>
                <span>Faculty</span>
                <strong><?php echo $student['faculty']; ?></strong>
            </div>


<div class="info-box">
    <div class="info-icon"><i class='bx bx-book-open'></i></div>
    <span>Programme</span>
    <strong><?php echo $student['programme'] ?? '-'; ?></strong>
</div>

<div class="info-box">
    <div class="info-icon"><i class='bx bx-layer'></i></div>
    <span>Year of Study</span>
    <strong><?php echo $student['year_of_study'] ?? '-'; ?></strong>
</div>







            <div class="info-box">
<div class="info-icon"><i class='bx bx-badge-check'></i></div>

                <span>User Role</span>
                <strong>Student Member</strong>
            </div>
        </div>
















    </div>

<div class="profile-card membership-card">
    <div class="membership-header">
        <div>
            <h3>Membership Information</h3>
            <p>Your PERSADA membership record and registration status.</p>
        </div>

        <span class="member-badge">Student Member</span>
    </div>

    <div class="membership-grid two-columns">

        <div class="membership-item premium">
            <div class="membership-icon">
                <i class='bx bx-badge-check'></i>
            </div>

            <div>
                <span>Membership Status</span>
                <strong>Active Member</strong>
                <small>Your student membership is currently active.</small>
            </div>
        </div>

        <div class="membership-item premium">
            <div class="membership-icon">
                <i class='bx bx-calendar'></i>
            </div>

            <div>
                <span>Member Since</span>
                <strong><?php echo date("d F Y", strtotime($student['created_at'])); ?></strong>
                <small>Registered date in PERSADA Student Portal.</small>
            </div>
        </div>

    </div>
</div>

   
<div class="account-settings-card">
    <div class="section-header">
        <h2>Account Settings</h2>
        <p>Manage your account and notification preferences.</p>
    </div>

    <div class="settings-grid">

        <div class="setting-item">
            <div class="setting-icon">
                <i class='bx bx-lock-alt'></i>
            </div>

            <div class="setting-content">
                <h4>Change Password</h4>
                <p>Update your account password securely.</p>
            </div>

            <button type="button" class="setting-btn" onclick="openPasswordModal()">
                Change
            </button>
        </div>

        <div class="setting-item">
            <div class="setting-icon">
                <i class='bx bx-camera'></i>
            </div>

            <div class="setting-content">
                <h4>Upload Profile Photo</h4>
                <p>Change your profile picture.</p>
            </div>

            <input type="file" id="quickPhotoUpload" accept="image/*" style="display:none;">

            <button type="button" class="setting-btn" onclick="document.getElementById('quickPhotoUpload').click();">
                Upload
            </button>
        </div>
<div class="setting-item">

    <div class="setting-icon">
        <i class='bx bx-bell'></i>
    </div>

    <div class="setting-content">
        <h4>Notifications</h4>
        <p>Receive event reminders and announcements.</p>
    </div>

    <label class="notification-switch">
        <input type="checkbox" checked>
        <span class="notification-slider"></span>
    </label>

</div>
    </div>


        


<div class="modal" id="editModal">
    <div class="modal-box">
        <h2>Edit Personal Information</h2>
        <p class="modal-subtitle">Update your student profile details below.</p>

      <form method="POST" enctype="multipart/form-data">
            <div class="modal-grid">

                <div class="form-group">
                    <label>Full Name</label>
                    <input 
                        type="text" 
                        name="name" 
                        value="<?php echo $student['name']; ?>" 
                        placeholder="Enter full name"
                        required>
                </div>

                <div class="form-group">
                    <label>Matric Number</label>
                    <input 
                        type="text" 
                        name="matric_number" 
                        value="<?php echo $student['matric_number']; ?>" 
                        placeholder="Example: CI240059"
                        required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input 
                        type="email" 
                        name="email" 
                        value="<?php echo $student['email']; ?>" 
                        placeholder="Enter email address"
                        required>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input 
                        type="text" 
                        name="phone_number" 
                        value="<?php echo $student['phone_number']; ?>" 
                        placeholder="Example: 0166780186"
                        required>
                </div>

                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Female" <?php if(($student['gender'] ?? '') == 'Female') echo 'selected'; ?>>Female</option>
                        <option value="Male" <?php if(($student['gender'] ?? '') == 'Male') echo 'selected'; ?>>Male</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Date of Birth</label>
                    <input 
                        type="date" 
                        name="date_of_birth" 
                        value="<?php echo $student['date_of_birth'] ?? ''; ?>">
                </div>

                <div class="form-group">
                    <label>Faculty</label>
                    <select name="faculty" id="facultySelect" required>
                        <option value="">Select Faculty</option>
                        <option value="FSKTM" <?php if(($student['faculty'] ?? '') == 'FSKTM') echo 'selected'; ?>>
                            FSKTM - Faculty of Computer Science and Information Technology
                        </option>
                        <option value="FKAAB" <?php if(($student['faculty'] ?? '') == 'FKAAB') echo 'selected'; ?>>
                            FKAAB - Faculty of Civil Engineering and Built Environment
                        </option>
                        <option value="FKEE" <?php if(($student['faculty'] ?? '') == 'FKEE') echo 'selected'; ?>>
                            FKEE - Faculty of Electrical and Electronic Engineering
                        </option>
                        <option value="FKMP" <?php if(($student['faculty'] ?? '') == 'FKMP') echo 'selected'; ?>>
                            FKMP - Faculty of Mechanical and Manufacturing Engineering
                        </option>
                        <option value="FPTP" <?php if(($student['faculty'] ?? '') == 'FPTP') echo 'selected'; ?>>
                            FPTP - Faculty of Technology Management and Business
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Programme</label>
                    <select name="programme" id="programmeSelect" required>
                        <option value="">Select Programme</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Year of Study</label>
                    <select name="year_of_study" required>
                        <option value="">Select Year</option>
                        <option value="Year 1" <?php if(($student['year_of_study'] ?? '') == 'Year 1') echo 'selected'; ?>>Year 1</option>
                        <option value="Year 2" <?php if(($student['year_of_study'] ?? '') == 'Year 2') echo 'selected'; ?>>Year 2</option>
                        <option value="Year 3" <?php if(($student['year_of_study'] ?? '') == 'Year 3') echo 'selected'; ?>>Year 3</option>
                        <option value="Year 4" <?php if(($student['year_of_study'] ?? '') == 'Year 4') echo 'selected'; ?>>Year 4</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>User Role</label>
                    <input type="text" value="Student Member" disabled>
                </div>

            </div>



<div class="form-group">
    <label>Profile Picture</label>
    <input type="file" name="profile_picture" accept="image/*">
</div>




            <div class="modal-actions">
                <button type="button" onclick="closeEditModal()" class="cancel-btn">Cancel</button>
                <button type="submit" name="update_profile" class="save-btn">Save Changes</button>
            </div>
        </form>
    </div>
</div>
<div class="modal" id="passwordModal">
    <div class="modal-box password-modal-box">

        <div class="password-modal-header">
            <div class="password-header-icon">
                <i class='bx bx-lock-alt'></i>
            </div>

            <div>
                <h2>Change Password</h2>
                <p>Create a strong password to protect your PERSADA account.</p>
            </div>
        </div>

        <form method="POST">

            <div class="password-field">
                <label>Current Password</label>
                <div class="password-input-wrap">
                    <input type="password" name="current_password" id="currentPassword" required>
                    <i class='bx bx-show eye-icon' onclick="togglePassword('currentPassword', this)"></i>
                </div>
            </div>

            <div class="password-field">
                <label>New Password</label>
                <div class="password-input-wrap">
                    <input type="password" name="new_password" id="newPassword" required oninput="checkNewPassword()">
                    <i class='bx bx-show eye-icon' onclick="togglePassword('newPassword', this)"></i>
                </div>
            </div>

            <div class="password-requirements">
                <p id="lengthReq">✖ At least 8 characters</p>
                <p id="upperReq">✖ At least 1 uppercase letter</p>
                <p id="lowerReq">✖ At least 1 lowercase letter</p>
                <p id="numberReq">✖ At least 1 number</p>
                <p id="specialReq">✖ At least 1 special character</p>
            </div>

            <div class="password-field">
                <label>Confirm Password</label>
                <div class="password-input-wrap">
                    <input type="password" name="confirm_password" id="confirmPassword" required oninput="checkNewPassword()">
                    <i class='bx bx-show eye-icon' onclick="togglePassword('confirmPassword', this)"></i>
                </div>
                <small id="matchText"></small>
            </div>

            <div class="modal-actions">
                <button type="button" class="cancel-btn" onclick="closePasswordModal()">Cancel</button>
                <button type="submit" name="change_password" id="updatePasswordBtn" class="save-btn" disabled>
                    Update Password
                </button>
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



function openEditModal(){
    document.getElementById("editModal").style.display = "flex";
}

function closeEditModal(){
    document.getElementById("editModal").style.display = "none";
}

const facultySelect = document.getElementById("facultySelect");
const programmeSelect = document.getElementById("programmeSelect");

const programmes = {
    FSKTM: [
        "BIW - Bachelor of Computer Science (Web Technology)",
        "BIP - Bachelor of Computer Science (Software Engineering)",
        "BIS - Bachelor of Information Technology",
        "BIM - Bachelor of Multimedia Computing",
        "BIT - Bachelor of Computer Science (Information Security)"
    ],
    FKAAB: [
        "BFF - Bachelor of Civil Engineering",
        "BFC - Bachelor of Civil Engineering Technology"
    ],
    FKEE: [
        "BEV - Bachelor of Electrical Engineering",
        "BEE - Bachelor of Electronic Engineering"
    ],
    FKMP: [
        "BDM - Bachelor of Mechanical Engineering",
        "BDA - Bachelor of Manufacturing Engineering"
    ],
    FPTP: [
        "BPC - Bachelor of Technology Management",
        "BPA - Bachelor of Business Administration"
    ],







};

const selectedProgramme = "<?php echo $student['programme'] ?? ''; ?>";

function loadProgrammes(){
    const selectedFaculty = facultySelect.value;
    programmeSelect.innerHTML = '<option value="">Select Programme</option>';

    if(programmes[selectedFaculty]){
        programmes[selectedFaculty].forEach(programme => {
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

facultySelect.addEventListener("change", loadProgrammes);
loadProgrammes();



document.getElementById("quickPhotoUpload")
.addEventListener("change", function(){

    if(this.files.length > 0){

        let formData = new FormData();
        formData.append("profile_picture", this.files[0]);
        formData.append("upload_photo", "1");

        fetch("upload_profile_photo.php",{
            method:"POST",
            body:formData
        })
        .then(response => response.text())
        .then(data => {

            if(data === "success"){
                alert("Profile picture updated!");
                location.reload();
            }else{
                alert("Upload failed.");
            }

        });

    }

});


function openPasswordModal(){
    document.getElementById("passwordModal").style.display = "flex";
}

function closePasswordModal(){
    document.getElementById("passwordModal").style.display = "none";
}


function togglePassword(inputId, icon){
    const input = document.getElementById(inputId);

    if(input.type === "password"){
        input.type = "text";
        icon.classList.remove("bx-show");
        icon.classList.add("bx-hide");
    }else{
        input.type = "password";
        icon.classList.remove("bx-hide");
        icon.classList.add("bx-show");
    }
}

function checkNewPassword(){
    const newPassword = document.getElementById("newPassword").value;
    const confirmPassword = document.getElementById("confirmPassword").value;
    const updateBtn = document.getElementById("updatePasswordBtn");
    const matchText = document.getElementById("matchText");

    const lengthValid = newPassword.length >= 8;
    const upperValid = /[A-Z]/.test(newPassword);
    const lowerValid = /[a-z]/.test(newPassword);
    const numberValid = /[0-9]/.test(newPassword);
    const specialValid = /[!@#$%^&*]/.test(newPassword);

    updateRequirement("lengthReq", lengthValid, "At least 8 characters");
    updateRequirement("upperReq", upperValid, "At least 1 uppercase letter");
    updateRequirement("lowerReq", lowerValid, "At least 1 lowercase letter");
    updateRequirement("numberReq", numberValid, "At least 1 number");
    updateRequirement("specialReq", specialValid, "At least 1 special character");

    const passwordMatch = newPassword === confirmPassword && confirmPassword !== "";

    if(confirmPassword === ""){
        matchText.innerHTML = "";
    }else if(passwordMatch){
        matchText.innerHTML = "✓ Passwords match";
        matchText.style.color = "#28a745";
    }else{
        matchText.innerHTML = "✖ Passwords do not match";
        matchText.style.color = "#ff5b45";
    }

    updateBtn.disabled = !(lengthValid && upperValid && lowerValid && numberValid && specialValid && passwordMatch);
}

function updateRequirement(id, valid, text){
    const element = document.getElementById(id);

    if(valid){
        element.innerHTML = "✓ " + text;
        element.classList.add("valid");
    }else{
        element.innerHTML = "✖ " + text;
        element.classList.remove("valid");
    }
}
</script>

</body>
</html>