<?php
session_start();

$conn = new mysqli("localhost", "root", "", "persada_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$matric_number = strtoupper(trim($_POST['matric_number']));
$password = $_POST['password'];

$sql = "SELECT * FROM students WHERE matric_number = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $matric_number);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 1) {

    $student = $result->fetch_assoc();

    if (password_verify($password, $student['password'])) {

        $_SESSION['student_id'] = $student['id'];
        $_SESSION['student_name'] = $student['name'];
        $_SESSION['matric_number'] = $student['matric_number'];

        echo "<script>
            alert('Login successful!');
            window.location.href='dashboard.php';
        </script>";

    } else {
        echo "<script>
            alert('Incorrect password.');
            window.history.back();
        </script>";
    }

} else {
    echo "<script>
        alert('Matric number not found.');
        window.history.back();
    </script>";
}

$stmt->close();
$conn->close();
?>