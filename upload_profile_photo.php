<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    echo "failed";
    exit();
}

$conn = new mysqli("localhost", "root", "", "persada_db");

if ($conn->connect_error) {
    echo "failed";
    exit();
}

$student_id = $_SESSION['student_id'];

if (isset($_FILES['profile_picture'])) {

    $folder = "uploads/profile/";

    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    $filename = time() . "_" . basename($_FILES['profile_picture']['name']);
    $filepath = $folder . $filename;

    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $filepath)) {

        $update = $conn->prepare("UPDATE students SET profile_picture=? WHERE id=?");
        $update->bind_param("si", $filepath, $student_id);

        if ($update->execute()) {
            echo "success";
        } else {
            echo "failed";
        }

    } else {
        echo "failed";
    }
}
?>