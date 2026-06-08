<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: Login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "persada_db");

$id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();

if (!$member) {
    echo "<script>alert('Member not found.'); window.location='member_management.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>View Member</title>
<link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>

<style>
body{
    font-family:Poppins, sans-serif;
    background:#f4f7fb;
    padding:40px;
}

.card{
    max-width:900px;
    margin:auto;
    background:white;
    padding:35px;
    border-radius:28px;
    box-shadow:0 18px 40px rgba(15,23,42,.08);
}

h1{
    color:#0f172a;
}

.info-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:20px;
    margin-top:25px;
}

.info-box{
    background:#eff6ff;
    padding:20px;
    border-radius:18px;
}

.info-box span{
    color:#64748b;
    font-size:13px;
}

.info-box strong{
    display:block;
    color:#0f172a;
    margin-top:6px;
}

.back-btn{
    display:inline-block;
    margin-top:25px;
    padding:12px 20px;
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    color:white;
    border-radius:16px;
    text-decoration:none;
    font-weight:700;
}
</style>
</head>

<body>

<div class="card">
    <h1>Member Details</h1>

    <div class="info-grid">
        <div class="info-box">
            <span>Full Name</span>
            <strong><?php echo $member['name']; ?></strong>
        </div>

        <div class="info-box">
            <span>Matric Number</span>
            <strong><?php echo $member['matric_number']; ?></strong>
        </div>

        <div class="info-box">
            <span>Email</span>
            <strong><?php echo $member['email']; ?></strong>
        </div>

        <div class="info-box">
            <span>Phone Number</span>
            <strong><?php echo $member['phone_number']; ?></strong>
        </div>

        <div class="info-box">
            <span>Faculty</span>
            <strong><?php echo $member['faculty']; ?></strong>
        </div>

        <div class="info-box">
            <span>Gender</span>
            <strong><?php echo $member['gender'] ?? '-'; ?></strong>
        </div>

        <div class="info-box">
            <span>Date of Birth</span>
            <strong><?php echo $member['date_of_birth'] ?? '-'; ?></strong>
        </div>

        <div class="info-box">
            <span>Programme</span>
            <strong><?php echo $member['programme'] ?? '-'; ?></strong>
        </div>

        <div class="info-box">
            <span>Year of Study</span>
            <strong><?php echo $member['year_of_study'] ?? '-'; ?></strong>
        </div>

        <div class="info-box">
            <span>Registered On</span>
            <strong><?php echo $member['created_at']; ?></strong>
        </div>
    </div>

    <a href="member_management.php" class="back-btn">Back to Members</a>
</div>

</body>
</html>