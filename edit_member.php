<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: Login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "persada_db");

$id = $_GET['id'];

if (isset($_POST['update_member'])) {

    $name = $_POST['name'];
    $matric_number = $_POST['matric_number'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $faculty = $_POST['faculty'];
    $gender = $_POST['gender'];
    $date_of_birth = $_POST['date_of_birth'];
    $programme = $_POST['programme'];
    $year_of_study = $_POST['year_of_study'];

    $stmt = $conn->prepare("
        UPDATE students 
        SET name=?, matric_number=?, email=?, phone_number=?, faculty=?, gender=?, date_of_birth=?, programme=?, year_of_study=?
        WHERE id=?
    ");

    $stmt->bind_param(
        "sssssssssi",
        $name,
        $matric_number,
        $email,
        $phone_number,
        $faculty,
        $gender,
        $date_of_birth,
        $programme,
        $year_of_study,
        $id
    );

    if ($stmt->execute()) {
        echo "<script>alert('Member updated successfully.'); window.location='member_management.php';</script>";
        exit();
    }
}

$stmt = $conn->prepare("SELECT * FROM students WHERE id=?");
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
<title>Edit Member</title>

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
    margin-bottom:25px;
}

.form-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:18px;
}

.form-group label{
    display:block;
    margin-bottom:7px;
    color:#64748b;
    font-weight:600;
}

input, select{
    width:100%;
    padding:14px;
    border-radius:14px;
    border:1px solid #dbeafe;
    outline:none;
}

input:focus, select:focus{
    border-color:#2563eb;
}

.actions{
    margin-top:25px;
    display:flex;
    gap:12px;
}

.save-btn{
    border:none;
    padding:13px 24px;
    background:linear-gradient(135deg,#2563eb,#06b6d4);
    color:white;
    border-radius:16px;
    font-weight:700;
    cursor:pointer;
}

.cancel-btn{
    padding:13px 24px;
    background:#e5e7eb;
    color:#0f172a;
    border-radius:16px;
    text-decoration:none;
    font-weight:700;
}
</style>
</head>

<body>

<div class="card">
    <h1>Edit Member</h1>

    <form method="POST">

        <div class="form-grid">

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" value="<?php echo $member['name']; ?>" required>
            </div>

            <div class="form-group">
                <label>Matric Number</label>
                <input type="text" name="matric_number" value="<?php echo $member['matric_number']; ?>" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo $member['email']; ?>" required>
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone_number" value="<?php echo $member['phone_number']; ?>" required>
            </div>

            <div class="form-group">
                <label>Faculty</label>
                <select name="faculty" required>
                    <option value="FSKTM" <?php if($member['faculty']=="FSKTM") echo "selected"; ?>>FSKTM</option>
                    <option value="FKEE" <?php if($member['faculty']=="FKEE") echo "selected"; ?>>FKEE</option>
                    <option value="FKMP" <?php if($member['faculty']=="FKMP") echo "selected"; ?>>FKMP</option>
                    <option value="FKAAB" <?php if($member['faculty']=="FKAAB") echo "selected"; ?>>FKAAB</option>
                    <option value="FPTV" <?php if($member['faculty']=="FPTV") echo "selected"; ?>>FPTV</option>
                    <option value="FPTP" <?php if($member['faculty']=="FPTP") echo "selected"; ?>>FPTP</option>
                </select>
            </div>

            <div class="form-group">
                <label>Gender</label>
                <select name="gender">
                    <option value="">Select Gender</option>
                    <option value="Female" <?php if(($member['gender'] ?? '')=="Female") echo "selected"; ?>>Female</option>
                    <option value="Male" <?php if(($member['gender'] ?? '')=="Male") echo "selected"; ?>>Male</option>
                </select>
            </div>

            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="date_of_birth" value="<?php echo $member['date_of_birth'] ?? ''; ?>">
            </div>

            <div class="form-group">
                <label>Programme</label>
                <select name="programme">
                    <option value="">Select Programme</option>
                    <option value="BIW - Bachelor of Computer Science (Web Technology)" <?php if(($member['programme'] ?? '')=="BIW - Bachelor of Computer Science (Web Technology)") echo "selected"; ?>>BIW - Web Technology</option>
                    <option value="BIP - Bachelor of Computer Science (Software Engineering)" <?php if(($member['programme'] ?? '')=="BIP - Bachelor of Computer Science (Software Engineering)") echo "selected"; ?>>BIP - Software Engineering</option>
                    <option value="BIS - Bachelor of Information Security" <?php if(($member['programme'] ?? '')=="BIS - Bachelor of Information Security") echo "selected"; ?>>BIS - Information Security</option>
                    <option value="BIM - Bachelor of Multimedia Computing" <?php if(($member['programme'] ?? '')=="BIM - Bachelor of Multimedia Computing") echo "selected"; ?>>BIM - Multimedia</option>
                </select>
            </div>

            <div class="form-group">
                <label>Year of Study</label>
                <select name="year_of_study">
                    <option value="">Select Year</option>
                    <option value="Year 1" <?php if(($member['year_of_study'] ?? '')=="Year 1") echo "selected"; ?>>Year 1</option>
                    <option value="Year 2" <?php if(($member['year_of_study'] ?? '')=="Year 2") echo "selected"; ?>>Year 2</option>
                    <option value="Year 3" <?php if(($member['year_of_study'] ?? '')=="Year 3") echo "selected"; ?>>Year 3</option>
                    <option value="Year 4" <?php if(($member['year_of_study'] ?? '')=="Year 4") echo "selected"; ?>>Year 4</option>
                </select>
            </div>

        </div>

        <div class="actions">
            <button type="submit" name="update_member" class="save-btn">Save Changes</button>
            <a href="member_management.php" class="cancel-btn">Cancel</a>
        </div>

    </form>
</div>

</body>
</html>