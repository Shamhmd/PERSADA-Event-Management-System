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

if (!isset($_GET['attendance_id'])) {
    die("Invalid certificate request.");
}

$attendance_id = intval($_GET['attendance_id']);

$stmt = $conn->prepare("
    SELECT 
        a.attendance_id,
        a.student_id,
        a.attendance_status,
        a.scan_time,

        s.name,
        s.matric_number,
        s.faculty,

        e.event_name,
        e.event_category,
        e.event_date,
        e.event_time,
        e.venue,
        e.certificate_released

    FROM attendance a

    JOIN students s 
        ON a.student_id = s.id

    JOIN events e 
        ON a.event_id = e.event_id

    WHERE a.attendance_id = ?
    AND a.student_id = ?
");

$stmt->bind_param("ii", $attendance_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Certificate not found.");
}

$data = $result->fetch_assoc();

if ($data['attendance_status'] != "Present") {
    die("You are not eligible for this certificate.");
}

if ($data['certificate_released'] != "Yes") {
    die("Certificate has not been released by admin yet.");
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Certificate - <?php echo $data['event_name']; ?></title>

<style>
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Poppins:wght@400;500;600;700&display=swap');

body{
    margin:0;
    background:#f6f1e9;
    font-family:'Poppins', sans-serif;
}

@page{
    size: A4 landscape;
    margin: 0;
}

body{
    margin:0;
    background:#f6f1e9;
    font-family:'Poppins', sans-serif;
}

.certificate{
    width: 277mm;
    height: 190mm;
    margin: 10mm auto;
    background:#fffdf8;
    border:8px solid #f4b23f;
    padding:28px 45px;
    text-align:center;
    position:relative;
    box-shadow:0 20px 60px rgba(0,0,0,.18);
    overflow:hidden;
}

.certificate::before{
    content:"";
    position:absolute;
    inset:12px;
    border:2px solid #16254c;
}

.title{
    margin-top:20px;
    font-size:42px;
}

.subtitle{
    font-size:16px;
}

.name{
    margin:20px auto 8px;
    font-size:10px;
}

.text{
    font-size:16px;
    margin:12px auto;
}

.event-name{
    font-size:24px;
    margin:10px 0;
}

.details{
    margin-top:14px;
    font-size:14px;
    line-height:1.6;
}

.footer{
    margin-top:35px;
}

@media print{
    body{
        background:white;
    }

    .print-btn{
        display:none;
    }

    .certificate{
        margin:0;
        width:297mm;
        height:210mm;
        box-shadow:none;
        page-break-after:avoid;
    }
}

.certificate::before{
    content:"";
    position:absolute;
    inset:20px;
    border:3px solid #16254c;
}



.org{
    font-size:35px;
    font-weight:800;
    color:#16254c;
     margin-top:-40px;
}

.title{
    margin-top:-15px;
    font-family:'Playfair Display', serif;
    font-size:50px;
    color:#16254c;
}

.subtitle{
    font-size:20px;
    color:#667085;
    margin-top:12px;
}

.name{
    margin:35px auto 12px;
    font-family:'Playfair Display', serif;
    font-size:48px;
    color:#ff6b4a;
    border-bottom:2px solid #f4b23f;
    display:inline-block;
    padding:0 45px 8px;
}

.text{
    font-size:20px;
    color:#334155;
    line-height:1.8;
    max-width:850px;
    margin:20px auto;
}

.event-name{
    font-size:30px;
    font-weight:800;
    color:#16254c;
    margin:18px 0;
}

.details{
    margin-top:28px;
    font-size:17px;
    color:#475569;
    line-height:1.8;
}

.footer{
    margin-top:70px;
    display:flex;
    justify-content:space-between;
    align-items:end;
    padding:0 80px;
}

.sign{
    text-align:center;
    color:#16254c;
}

.sign-line{
    width:220px;
    border-top:2px solid #16254c;
    margin-bottom:10px;
}

.print-btn{
    display:block;
    margin:20px auto 40px;
    border:none;
    padding:14px 28px;
    border-radius:16px;
    background:linear-gradient(135deg,#ff6b4a,#f6b73c);
    color:white;
    font-weight:800;
    cursor:pointer;
    font-size:16px;
}


@media print{
    body{
        background:white;
    }

    .print-btn{
        display:none;
    }

    .certificate{
        margin:0;
        box-shadow:none;
        width:auto;
        min-height:720px;
    }
}

.logo{
    text-align:center;
    margin-top:-85px;
}

.logo img{
     margin-bottom:-40px;
    width:300px;
    height:300px;
    object-fit:contain;
}
</style>
</head>

<body>

<div class="certificate">

  <div class="logo">
    <img src="persada_logo.png" alt="PERSADA Logo">
</div>
    <div class="org">PERSADA UTHM</div>

    <div class="title">Certificate of Participation</div>

    <div class="subtitle">This certificate is proudly presented to</div>

    <div class="name">
        <?php echo strtoupper($data['name']); ?>
    </div>

    <div class="text">
        for successfully participating in the event
    </div>

    <div class="event-name">
        <?php echo $data['event_name']; ?>
    </div>

    <div class="details">
        Category: <?php echo $data['event_category']; ?><br>
        Date: <?php echo date("d F Y", strtotime($data['event_date'])); ?><br>
        Time: <?php echo date("h:i A", strtotime($data['event_time'])); ?><br>
        Venue: <?php echo $data['venue']; ?><br>
        Matric Number: <?php echo $data['matric_number']; ?>
    </div>

    <div class="footer">

        <div class="sign">
            <div class="sign-line"></div>
            PERSADA Administrator
        </div>

        <div class="sign">
            <div class="sign-line"></div>
            Event Organizer
        </div>

    </div>

</div>

<button class="print-btn" onclick="window.print()">
    Print / Save as PDF
</button>

</body>
</html>