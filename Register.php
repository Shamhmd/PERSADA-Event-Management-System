<?php
$conn = new mysqli("localhost", "root", "", "persada_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$name = $_POST['name'];
$matric_number = strtoupper($_POST['matric_number']);
$email = $_POST['email'];
$phone_number = $_POST['phone_number'];
$faculty = $_POST['faculty'];
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

if ($password !== $confirm_password) {
    echo "<script>alert('Password and Confirm Password do not match.'); window.history.back();</script>";
    exit();
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO students 
(name, matric_number, email, phone_number, faculty, password)
VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "ssssss",
    $name,
    $matric_number,
    $email,
    $phone_number,
    $faculty,
    $hashed_password
);

if ($stmt->execute()) {
    echo "<script>alert('Registration successful!'); window.location.href='Login.php';</script>";
} else {
    echo "<script>alert('Registration failed. Matric number or email may already exist.'); window.history.back();</script>";
}

$stmt->close();
$conn->close();
?>

