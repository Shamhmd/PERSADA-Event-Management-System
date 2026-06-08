<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <title>LOGIN & REGISTER</title>
</head>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap');

*{
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Montserrat', sans-serif;
}

body{
    background-image: url('LOGIN.png');
    background-size: 100% 100%;
    background-repeat: no-repeat;
    background-position: center center;
    background-color: #0f172a;

    min-height: 100vh;
    height: 100vh;

    display: flex;
    align-items: center;
    justify-content: center;

    overflow: hidden;
}
body::before{
    content:"";
    position:fixed;
    inset:0;

    background:rgba(0,0,0,0.35);

    z-index:-1;
}
.container{
     background: rgba(255,255,255,0.88);

    border: 1px solid rgba(255,255,255,0.6);

    border-radius: 30px;

    box-shadow:
        0 20px 50px rgba(0,0,0,0.18);

    position: relative;
    overflow: hidden;

    width: 900px;
    max-width: 100%;
     min-height:650px;

    backdrop-filter: none;
    -webkit-backdrop-filter: none;
}
.container select{
    background:#eeeeee;
    border:none;
    margin:8px 0;
    padding:12px 16px;
    font-size:14px;
    border-radius:10px;
    width:100%;
    outline:none;
    color:#333;
    cursor:pointer;
}
.container p{
    font-size: 14px;
    line-height: 20px;
    letter-spacing: 0.3px;
    margin: 20px 0;
}

.container span{
    font-size: 12px;
}

.container a{
    color: #333;
    font-size: 13px;
    text-decoration: none;
    margin: 15px 0 10px;
}


/* PREMIUM BUTTON */
.container button{
    min-width:150px;
    padding:13px 42px;
    border:none;
    border-radius:50px;
    background:linear-gradient(135deg,#ff5b45,#ff8a5c,#f6b73c);
    color:white;
    font-size:13px;
    font-weight:800;
    letter-spacing:1px;
    text-transform:uppercase;
    cursor:pointer;
    position:relative;
    overflow:hidden;
    box-shadow:0 14px 30px rgba(255,91,69,.35);
    transition:all .35s ease;
}

.container button::before{
    content:'';
    position:absolute;
    top:0;
    left:-100%;
    width:100%;
    height:100%;
    background:linear-gradient(
        120deg,
        transparent,
        rgba(255,255,255,.45),
        transparent
    );
    transition:.7s;
}

.container button:hover{
    transform:translateY(-5px) scale(1.04);
    box-shadow:0 22px 45px rgba(255,91,69,.48);
}

.container button:hover::before{
    left:100%;
}

.container button.hidden{
    background:rgba(255,255,255,.18);
    border:2px solid rgba(255,255,255,.85);
    color:white;
    backdrop-filter:blur(8px);
    box-shadow:0 12px 28px rgba(0,0,0,.12);
}

.container button.hidden:hover{
    background:white;
    color:#ff5b45;
    transform:translateY(-5px) scale(1.05);
}

.container form{
    background:rgba(255,255,255,0.88);
    display:flex;
    align-items:center;
    justify-content:center;
    flex-direction:column;
    padding:0 40px;
    height:100%;
}

.sign-in h1,
.sign-up h1{
    color:#111;
}

.sign-in span,
.sign-up span,
.sign-in a,
.sign-up a{
    color:#333;
}
.container input,
.container select{
    width:100%;
    margin:9px 0;
    padding:15px 18px;

    background:rgba(255,255,255,0.82);
    border:1.5px solid rgba(255,107,74,0.15);

    border-radius:16px;

    color:#16254c;
    font-size:14px;
    font-weight:500;

    outline:none;

    box-shadow:
        0 8px 20px rgba(0,0,0,0.05),
        inset 0 1px 0 rgba(255,255,255,0.8);

    transition:all .3s ease;
}

.container input::placeholder{
    color:#8a8a8a;
}

.container input:focus,
.container select:focus{
    background:#ffffff;
    border-color:#ff6b4a;

    box-shadow:
        0 0 0 4px rgba(255,107,74,0.14),
        0 12px 28px rgba(255,107,74,0.12);

    transform:translateY(-2px);
}

.form-container{
    position: absolute;
    top: 0;
    height: 100%;
    transition: all 0.6s ease-in-out;
}

.sign-in{
    left: 0;
    width: 50%;
    z-index: 2;
}

.container.active .sign-in{
    transform: translateX(100%);
    opacity: 0;
    visibility: hidden;
}

.sign-up{
    left: 0;
    width: 50%;
    opacity: 0;
    visibility: hidden;
    z-index: 1;
}

.container.active .sign-up{
    transform: translateX(100%);
    opacity: 1;
    visibility: visible;
    z-index: 5;
}
.register-subtitle{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:6px;

    background:linear-gradient(135deg,#fff3e8,#ffe6d5);
    color:#ff5b45 !important;

    padding:8px 18px;
    border-radius:50px;

    font-size:13px !important;
    font-weight:700;
    letter-spacing:.3px;

    margin:8px 0 14px;

    border:1px solid rgba(255,91,69,.18);
    box-shadow:0 8px 20px rgba(255,91,69,.12);
}

.password-wrapper{
    position:relative;
    width:100%;
}

.password-wrapper input{
    width:100%;
    padding-right:50px;
}

.password-wrapper i{
    position:absolute;
    right:14px;
    top:50%;
    transform:translateY(-50%);

    background:rgba(255,107,74,0.10);
    color:#ff6b4a;

    width:30px;
    height:30px;
    border-radius:50%;

    display:flex;
    align-items:center;
    justify-content:center;

    cursor:pointer;
    font-size:14px;
    transition:.3s;
    z-index:5;
}

.password-wrapper i:hover{
    background:#ff6b4a;
    color:white;
}

.password-requirements{
    width:100%;
    margin-top:5px;
    margin-bottom:10px;
    padding-left:5px;
}

.password-requirements p{
    margin:3px 0 !important;
    font-size:11px !important;
    color:#777;
    text-align:left;
}

.password-requirements .valid{
    color:#28a745;
    font-weight:600;
}

.password-requirements .invalid{
    color:#dc3545;
}


.password-hint{
    display:none;
    width:100%;
    margin-top:3px;
    margin-bottom:8px;
    text-align:left;
    font-size:11px;
    line-height:1.5;
    color:#ff5b45;
    font-weight:600;
}

.password-wrapper:focus-within + .password-hint{
    display:block;
}

#passwordMatch{
    width:100%;
    text-align:left;
    font-size:11px;
    margin-bottom:8px;
    font-weight:700;
}

button:disabled{
    opacity:0.5;
    cursor:not-allowed;
    transform:none !important;
}

.login-subtitle{
    display:inline-flex;
    align-items:center;
    gap:8px;

    margin:10px 0 20px;

    padding:8px 18px;

    background:linear-gradient(
        135deg,
        #fff3e8,
        #ffe6d5
    );

    color:#ff6b4a !important;

    border:1px solid rgba(255,107,74,0.2);

    border-radius:50px;

    font-size:13px !important;
    font-weight:700;

    letter-spacing:.3px;

    box-shadow:
        0 8px 20px rgba(255,107,74,0.12);
}

.login-subtitle i{
    font-size:14px;
}



.sign-in a{
    color:#ff6b4a;
    font-size:14px;
    font-weight:600;
    margin-top:18px;
    position:relative;
    transition:.3s;
}

.sign-in a::after{
    content:'';
    position:absolute;
    left:0;
    bottom:-3px;

    width:0;
    height:2px;

    background:#ff6b4a;

    transition:.3s;
}

.sign-in a:hover{
    color:#ff5b45;
}

.sign-in a:hover::after{
    width:100%;
}


.admin-login-link{
    margin-top:15px;
}

.admin-login-link a{
    color:#16254c;
    font-size:14px;
    font-weight:700;
    text-decoration:none;

    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;

    transition:.3s;
}

.admin-login-link a:hover{
    color:#ff6b4a;
}


.admin-sign-in{
    left:0;
    width:50%;
    opacity:0;
    visibility:hidden;
    z-index:1;
}

.container.admin-mode .sign-in{
    opacity:0;
    visibility:hidden;
}

.container.admin-mode .admin-sign-in{
    opacity:1;
    visibility:visible;
    z-index:5;
}
#backToStudentLogin{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;

    margin:18px 0 12px;

    padding:12px 22px;

    border-radius:50px;

    background:#fff7f3;

    border:1px solid rgba(255,107,74,.18);

    color:#5f6475;

    font-size:14px;
    font-weight:700;

    transition:.35s ease;

    text-decoration:none;

    box-shadow:
        0 8px 20px rgba(0,0,0,.04);
}

#backToStudentLogin:hover{
    background:linear-gradient(
        135deg,
        #ff6b4a,
        #f6b73c
    );

    color:white;

    transform:translateY(-3px);

    box-shadow:
        0 14px 28px rgba(255,107,74,.28);
}

#backToStudentLogin i{
    font-size:13px;
}
.home-btn{
    position:absolute;
    top:25px;
    left:25px;

    display:flex;
    align-items:center;
    gap:8px;

    padding:10px 18px;

    background:white;

    border:1px solid rgba(255,107,74,.15);

    border-radius:50px;

    text-decoration:none;

    color:#16254c;
    font-weight:700;
    font-size:14px;

    box-shadow:0 8px 20px rgba(0,0,0,.08);

    transition:.3s;
    z-index:9999;
}

.home-btn:hover{
    background:linear-gradient(
        135deg,
        #ff6b4a,
        #f6b73c
    );

    color:white;

    transform:translateY(-3px);

    box-shadow:0 15px 30px rgba(255,107,74,.25);
}
@keyframes move{
    0%, 49.99%{
        opacity: 0;
        z-index: 1;
    }
    50%, 100%{
        opacity: 1;
        z-index: 5;
    }
}

.social-icons{
    margin: 20px 0;
}

.social-icons a{
    border:1px solid #ccc;
    color:#222;
    background:white;
    border-radius:10px;
    display:inline-flex;
    justify-content:center;
    align-items:center;
    margin:0 4px;
    width:42px;
    height:42px;
}

.toggle-container{
    position: absolute;
    top: 0;
    left: 50%;
    width: 50%;
    height: 100%;
    overflow: hidden;
    transition: all 0.6s ease-in-out;
    border-radius: 150px 0 0 100px;
    z-index: 1000;
}

.container.active .toggle-container{
    transform: translateX(-100%);
    border-radius: 0 150px 100px 0;
}

.toggle{
    
    height: 100%;
        background: linear-gradient(
        135deg,
        #FF6B4A 0%,
        #FF8A65 35%,
        #F6B73C 100%
    );
    color: #fff;
    position: relative;
    left: -100%;
    height: 100%;
    width: 200%;
    transform: translateX(0);
    transition: all 0.6s ease-in-out;
}

.container.active .toggle{
    transform: translateX(50%);
}
.toggle-panel h1{
    color: #FFF1D6;
}
.toggle-panel p{
    color: rgba(255,248,240,0.92);
}
.toggle-panel{
    position: absolute;
    width: 50%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    padding: 0 30px;
    text-align: center;
    top: 0;
    transform: translateX(0);
    transition: all 0.6s ease-in-out;
}

.toggle-left{
    transform: translateX(-200%);
}

.container.active .toggle-left{
    transform: translateX(0);
}

.toggle-right{
    right: 0;
    transform: translateX(0);
}

.container.active .toggle-right{
    transform: translateX(200%);
}
</style>
<body>

   <div class="container" id="container">
    <a href="Home_Page.php" class="home-btn">
    <i class="fa-solid fa-house"></i>
    Home
</a>

    <!-- REGISTER -->
    <div class="form-container sign-up">
        <form action="Register.php" method="POST">

            <h1>Create Account</h1>

         
<span class="register-subtitle">
     Student Membership Registration
</span>

            <input type="text" name="name" placeholder="Full Name" required>

            <input type="text" name="matric_number" placeholder="Matric Number" required>

            <input type="email" name="email" placeholder="Email Address" required>

            <input type="tel" name="phone_number" placeholder="Phone Number" required>

            <select name="faculty" required>
                <option value="">Select Faculty</option>
                <option value="FSKTM">FSKTM(Faculty of Science Computer and Information Technology)</option>
                <option value="FKEE">FKEE(Faculty of Electric and Electronic Engineering)</option>
                <option value="FKMP">FKMP(Faculty of Mechanical and Manufacturing Engineering)</option>
                <option value="FKAAB">FKAAB(Faculty of Civil Engineering and Built Environment)</option>
                <option value="FPTV">FPTV(Faculty of Technical and Vocational Education)</option>
                <option value="FPTP">FPTP(Faculty of Technology Management and Business)</option>
                <option value="FPIK">FAST(Faculty of Applied Science and Technology)</option>
                <option value="PPUU">FTK(Faculty of Engineering Technology)</option>
            </select>

     <div class="password-wrapper">
    <input type="password"
           id="registerPassword"
           name="password"
           placeholder="Password"
           minlength="8"
           pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*]).{8,}"
           required>

    <i class="fa-solid fa-eye"
       onclick="togglePassword('registerPassword', this)">
    </i>
</div>

<small class="password-hint" id="passwordHint">
    Password must contain at least 8 characters, 1 uppercase letter, 1 lowercase letter, 1 number and 1 special character.
</small>

<div class="password-wrapper">
    <input type="password"
           id="confirmPassword"
           name="confirm_password"
           placeholder="Confirm Password"
           required>

    <i class="fa-solid fa-eye"
       onclick="togglePassword('confirmPassword', this)">
    </i>
</div>

<small id="passwordMatch"></small>
<button type="submit" id="registerBtn" disabled>Register</button>

        </form>
    </div>

    <!-- LOGIN -->
    <div class="form-container sign-in">
        <form action="login_process.php" method="POST">

            <h1>Login</h1>

         
          <span class="login-subtitle">
    <i class="fa-solid fa-id-card"></i>
    Login using your matric number
</span>

            <input type="text"name="matric_number"placeholder="Matric Number"required>

          <div class="password-wrapper">
    <input type="password"
           id="loginPassword"
           name="password"
           placeholder="Password"
           required>

    <i class="fa-solid fa-eye"
       onclick="togglePassword('loginPassword', this)">
    </i>
</div>

            <a href="#">Forget Your Password?</a>

            <button type="submit">Login</button>



<div class="admin-login-link">
    <a href="#" id="showAdminLogin">
        <i class="fa-solid fa-user-shield"></i>
        Login as Administrator
    </a>
</div>


        </form>
    </div>

    <div class="form-container admin-sign-in">
    <form action="admin_login_process.php" method="POST">

        <h1>Admin Login</h1>

        <span class="login-subtitle">
            <i class="fa-solid fa-user-shield"></i>
            Login using admin account
        </span>

        <input type="text" name="username" placeholder="Admin Username" required>

        <div class="password-wrapper">
            <input type="password"
                   id="adminPassword"
                   name="password"
                   placeholder="Password"
                   required>

            <i class="fa-solid fa-eye"
               onclick="togglePassword('adminPassword', this)">
            </i>
        </div>

       <a href="#" id="backToStudentLogin">
    <i class="fa-solid fa-arrow-left"></i>
    Return to Student Portal
</a>

        <button type="submit">Login Admin</button>

    </form>
</div>

    <!-- TOGGLE PANEL -->
    <div class="toggle-container">
        <div class="toggle">

            <div class="toggle-panel toggle-left">
                <h1>Welcome Back!</h1>
                <p>Enter your personal details to use all of site features</p>
                <button class="hidden" id="login" type="button">Login</button>
            </div>

            <div class="toggle-panel toggle-right" id="toggleRightPanel">

    <h1 id="toggleTitle">Join PERSADA</h1>

    <p id="toggleDescription">
        Register now and explore exciting events,
        leadership programs and student activities.
    </p>

    <button class="hidden" id="register" type="button">
        Register
    </button>

</div>

        </div>
    </div>

</div>

    <script>
const container = document.getElementById("container");
const registerSlideBtn = document.getElementById("register");
const loginSlideBtn = document.getElementById("login");

const registerPassword = document.getElementById("registerPassword");
const confirmPassword = document.getElementById("confirmPassword");
const passwordHint = document.getElementById("passwordHint");
const passwordMatch = document.getElementById("passwordMatch");
const registerSubmitBtn = document.getElementById("registerBtn");

const matricPattern = /^[A-Z]{2}\d{6}$/;

/* SLIDE ANIMATION */
registerSlideBtn.addEventListener("click", () => {
    container.classList.add("active");
});

loginSlideBtn.addEventListener("click", () => {
    container.classList.remove("active");
});

/* SHOW / HIDE PASSWORD */
function togglePassword(inputId, icon) {
    const input = document.getElementById(inputId);

    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}

/* CHECK PASSWORD REQUIREMENT */
function checkPasswordRequirement() {
    const password = registerPassword.value;

    return (
        password.length >= 8 &&
        /[A-Z]/.test(password) &&
        /[a-z]/.test(password) &&
        /[0-9]/.test(password) &&
        /[!@#$%^&*]/.test(password)
    );
}

/* VALIDATE REGISTER FORM */
function validateRegisterForm() {
    const passwordValid = checkPasswordRequirement();
    const passwordSame =
        registerPassword.value === confirmPassword.value &&
        confirmPassword.value !== "";

    if (passwordValid) {
        passwordHint.innerHTML = "✅ Password requirement passed.";
        passwordHint.style.color = "#28a745";
    } else {
        passwordHint.innerHTML =
            "❌ Password must contain at least 8 characters, 1 uppercase letter, 1 lowercase letter, 1 number and 1 special character.";
        passwordHint.style.color = "#ff5b45";
    }

    if (confirmPassword.value === "") {
        passwordMatch.innerHTML = "";
    } else if (passwordSame) {
        passwordMatch.innerHTML = "✅ Passwords match.";
        passwordMatch.style.color = "#28a745";
    } else {
        passwordMatch.innerHTML = "❌ Passwords do not match.";
        passwordMatch.style.color = "#ff5b45";
    }

    registerSubmitBtn.disabled = !(passwordValid && passwordSame);
}


/* CHECK WHEN USER TYPES */
const allRegisterFields = document.querySelectorAll(
    ".sign-up input, .sign-up select"
);

allRegisterFields.forEach((field) => {
    field.addEventListener("input", validateRegisterForm);
    field.addEventListener("change", validateRegisterForm);
});

/* RUN ON PAGE LOAD */
validateRegisterForm();


const showAdminLogin = document.getElementById("showAdminLogin");
const backToStudentLogin = document.getElementById("backToStudentLogin");

showAdminLogin.addEventListener("click", (e) => {
    e.preventDefault();

    container.classList.add("admin-mode");

    toggleTitle.innerHTML = "PERSADA Administration";

    toggleDescription.innerHTML = `
        Secure access for committee members and system administrators.<br><br>
        • Manage Members<br>
        • Manage Events<br>
        • Attendance Monitoring<br>
        • Generate Reports
    `;

    registerSlideBtn.style.display = "none";
});

backToStudentLogin.addEventListener("click", (e) => {
    e.preventDefault();

    container.classList.remove("admin-mode");

    toggleTitle.innerHTML = "Join PERSADA";

    toggleDescription.innerHTML =
        "Register now and explore exciting events, leadership programs and student activities.";

    registerSlideBtn.style.display = "inline-block";
});

const toggleTitle = document.getElementById("toggleTitle");
const toggleDescription = document.getElementById("toggleDescription");

    </script>
</body>

</html>