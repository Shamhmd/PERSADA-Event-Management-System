<?php
session_start();

$conn = new mysqli("localhost", "root", "", "persada_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['student_id'])) {
    header("Location: Login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("Invalid QR code.");
}

$token = $_GET['token'];

$eventStmt = $conn->prepare("
    SELECT event_id, event_name 
    FROM events 
    WHERE qr_token = ?
");

$eventStmt->bind_param("s", $token);
$eventStmt->execute();
$eventResult = $eventStmt->get_result();

if ($eventResult->num_rows == 0) {
    die("Invalid or expired QR code.");
}

$event = $eventResult->fetch_assoc();
$event_id = $event['event_id'];

$checkRegister = $conn->prepare("
    SELECT registration_id 
    FROM event_registration 
    WHERE student_id = ? AND event_id = ?
");

$checkRegister->bind_param("ii", $student_id, $event_id);
$checkRegister->execute();
$registerResult = $checkRegister->get_result();

if ($registerResult->num_rows == 0) {
    die("You are not registered for this event.");
}

$checkAttendance = $conn->prepare("
    SELECT attendance_id 
    FROM attendance 
    WHERE student_id = ? AND event_id = ?
");

$checkAttendance->bind_param("ii", $student_id, $event_id);
$checkAttendance->execute();
$attendanceResult = $checkAttendance->get_result();

if ($attendanceResult->num_rows > 0) {
    echo "<script>
        alert('Your attendance has already been recorded.');
        window.location='dashboard.php';
    </script>";
    exit();
}

$insert = $conn->prepare("
    INSERT INTO attendance 
    (student_id, event_id, attendance_status, scan_time)
    VALUES (?, ?, 'Present', NOW())
");

$insert->bind_param("ii", $student_id, $event_id);

if ($insert->execute()) {
    echo "<script>
        alert('Attendance recorded successfully for " . addslashes($event['event_name']) . "!');
        window.location='dashboard.php';
    </script>";
    exit();
} else {
    die("Failed to record attendance.");
}
?>