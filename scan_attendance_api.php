<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

function sendResponse($status, $message, $extra = []) {
    echo json_encode(array_merge([
        "status" => $status,
        "message" => $message
    ], $extra));
    exit();
}

$conn = new mysqli("localhost", "root", "", "persada_db");

if ($conn->connect_error) {
    sendResponse("error", "Database connection failed.");
}

if (!isset($_SESSION['student_id'])) {
    sendResponse("error", "Please login first.");
}

$student_id = (int) $_SESSION['student_id'];

if (!isset($_GET['token']) || trim($_GET['token']) === "") {
    sendResponse("error", "Invalid QR code.");
}

$token = trim($_GET['token']);

/* CHECK TOKEN */
$eventStmt = $conn->prepare("
    SELECT event_id, event_name
    FROM events
    WHERE qr_token = ?
    LIMIT 1
");

if (!$eventStmt) {
    sendResponse("error", "Event query failed.");
}

$eventStmt->bind_param("s", $token);
$eventStmt->execute();
$eventResult = $eventStmt->get_result();

if ($eventResult->num_rows === 0) {
    sendResponse("error", "Invalid or expired QR code.");
}

$event = $eventResult->fetch_assoc();
$event_id = (int) $event['event_id'];

/* CHECK REGISTERED EVENT */
$checkRegister = $conn->prepare("
    SELECT registration_id
    FROM event_registration
    WHERE student_id = ?
    AND event_id = ?
    LIMIT 1
");

if (!$checkRegister) {
    sendResponse("error", "Registration query failed.");
}

$checkRegister->bind_param("ii", $student_id, $event_id);
$checkRegister->execute();
$registerResult = $checkRegister->get_result();

if ($registerResult->num_rows === 0) {
    sendResponse("error", "You are not registered for this event.");
}

/* CHECK EXISTING ATTENDANCE */
$checkAttendance = $conn->prepare("
    SELECT attendance_id, scan_time
    FROM attendance
    WHERE student_id = ?
    AND event_id = ?
    LIMIT 1
");

if (!$checkAttendance) {
    sendResponse("error", "Attendance query failed.");
}

$checkAttendance->bind_param("ii", $student_id, $event_id);
$checkAttendance->execute();
$attendanceResult = $checkAttendance->get_result();

if ($attendanceResult->num_rows > 0) {
    $old = $attendanceResult->fetch_assoc();

    sendResponse("already", "Attendance already recorded.", [
        "event_name" => $event['event_name'],
        "scan_time" => $old['scan_time']
    ]);
}

/* INSERT ATTENDANCE */
$insert = $conn->prepare("
    INSERT INTO attendance
    (student_id, event_id, attendance_status, scan_time)
    VALUES (?, ?, 'Present', NOW())
");

if (!$insert) {
    sendResponse("error", "Insert attendance query failed.");
}

$insert->bind_param("ii", $student_id, $event_id);

if ($insert->execute()) {
    sendResponse("success", "Attendance recorded successfully.", [
        "event_name" => $event['event_name'],
        "scan_time" => date("Y-m-d H:i:s")
    ]);
}

sendResponse("error", "Failed to record attendance.");