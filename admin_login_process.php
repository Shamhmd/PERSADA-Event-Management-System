<?php
session_start();

$conn = new mysqli("localhost", "root", "", "persada_db");

$username = $_POST['username'];
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();

    if (password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_name'] = $admin['full_name'];

        header("Location: admin_dashboard.php");
        exit();
    }
}

echo "<script>
alert('Invalid admin username or password.');
window.location='Login.php';
</script>";
?>