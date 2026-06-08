<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PAMMS | PERSADA UTHM</title>

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

body{
    background:linear-gradient(to right,#d9a441,#efc76b);
    min-height:100vh;
    padding:35px;
}

.wrapper{
    max-width:1250px;
    margin:auto;
    background:linear-gradient(to bottom right,#f7efde,#f3e7d1,#ecdec2);
    border-radius:10px;
    overflow:hidden;
    box-shadow:0 20px 50px rgba(0,0,0,0.15);
    border:1px solid rgba(255,255,255,0.5);
}

/* NAVBAR */
.topbar{
    width:100%;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:18px 80px;
    border-bottom:1px solid rgba(0,0,0,0.05);
}

.brand img{
    width:200px;
    object-fit:contain;
}

nav{
    display:flex;
    align-items:center;
    gap:42px;
    margin-left:120px;
    margin-right:auto;
}


nav a{
    text-decoration:none;
    color:#18264d;
    font-size:21px;
    font-weight:600;
    position:relative;
    transition:.3s;
}
nav a::after{
    content:'';
    position:absolute;
    left:0;
    bottom:-8px;
    width:0;
    height:3px;
    background:#ff5b45;
    border-radius:50px;
    transition:.3s;
}
nav a:hover{
    color:#ff5b45;
}

nav a:hover::after{
    width:100%;
}
.login-nav-btn{
    background:linear-gradient(135deg,#ff5b45,#ff7a59);
    color:white;
    padding:14px 30px;
    border-radius:12px;
    text-decoration:none;
    font-weight:700;
    font-size:20px;
    margin-right:60px;
    display:flex;
    align-items:center;
    gap:10px;
    transition:all 0.35s ease;
    box-shadow:0 8px 20px rgba(255,91,69,0.28);
}

.login-nav-btn:hover{
    transform:translateY(-4px) scale(1.03);
    box-shadow:0 14px 28px rgba(255,91,69,0.40);
}

/* HERO */
.hero{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:30px 80px 70px;
    min-height:650px;
}

.left{
    width:55%;
    z-index:2;
    margin-bottom:80px;
    animation:slideLeft 1s ease forwards;
}

.left h1{
    font-size:52px;
    line-height:1.1;
    color:#18264d;
    margin-bottom:35px;
    max-width:650px;
}

.highlight{
    color:#9a7425;
    font-weight:650;
}

.description{
    color:#555;
    font-size:20px;
    line-height:1.8;
    max-width:720px;
}

.login-box{
    display:flex;
    gap:18px;
    flex-wrap:wrap;
    margin-top:20px;
}

.login-box a{
    text-decoration:none;
}

.explore-btn{
    background:linear-gradient(135deg,#ff5b45,#ff7a59);
    color:white;
    padding:14px 30px;
    border-radius:12px;
    text-decoration:none;
    font-weight:700;
    font-size:20px;
    display:inline-flex;
    align-items:center;
    gap:6px;
    transition:all 0.35s ease;
    box-shadow:0 8px 20px rgba(255,91,69,0.28);
}

.explore-btn:hover{
    transform:translateY(-4px) scale(1.03);
    box-shadow:0 14px 28px rgba(255,91,69,0.40);
}

.explore-icon{
    font-size:28px;
    display:flex;
    align-items:center;
}

/* HERO IMAGE */
.right{
    width:55%;
    display:flex;
    justify-content:center;
    align-items:center;
    position:relative;
}

.right::before{
    content:'';
    position:absolute;
    width:460px;
    height:460px;
    background:#e7c547;
    border-radius:50%;
    top:50%;
    left:58%;
    transform:translate(-50%,-50%);
    z-index:0;
    animation:floatCircle 4s ease-in-out infinite;
}

.right::after{
    content:'';
    position:absolute;
    width:600px;
    height:600px;
    border-radius:50%;
    background:rgba(231,197,71,0.18);
    top:50%;
    left:58%;
    transform:translate(-50%,-50%);
    z-index:0;
    animation:pulseCircle 4s ease-in-out infinite;
}

.team-card{
    width:680px;
    margin-top:-80px;
    margin-right:-77px;
    position:relative;
    z-index:2;
    animation:slideRight 1s ease forwards;
}

.team-card img{
    width:100%;
    height:auto;
    display:block;
    filter:drop-shadow(0 20px 30px rgba(0,0,0,0.15));
}

/* ABOUT */
.about{
    background:linear-gradient(135deg,#fffdf8,#fff3df);
    padding:90px 80px;
    display:grid;
    grid-template-columns:420px 1fr;
    gap:70px;
    align-items:center;
    position:relative;
    overflow:hidden;
    perspective:1200px;
}

.about::before{
    content:'';
    position:absolute;
    width:260px;
    height:260px;
    border-radius:50%;
    background:rgba(255,91,69,0.10);
    right:-80px;
    top:-80px;
}

.about::after{
    content:'PAMMS';
    position:absolute;
    right:55px;
    bottom:35px;
    font-size:85px;
    font-weight:900;
    color:rgba(22,37,76,0.04);
}

.about-image-card{
    position:relative;
    z-index:2;
    opacity:0;
    transform:translateX(-100px) rotateY(25deg) scale(0.85);
    filter:blur(15px);
    transition:all 1.2s cubic-bezier(.17,.84,.44,1);
}

.about-image-card img{
    width:100%;
    height:430px;
    object-fit:cover;
    border-radius:30px;
    box-shadow:0 30px 60px rgba(0,0,0,0.16);
    display:block;
}

.about-badge{
    position:absolute;
    bottom:22px;
    left:22px;
    background:white;
    padding:14px 20px;
    border-radius:18px;
    box-shadow:0 15px 35px rgba(0,0,0,0.15);
    font-weight:800;
    color:#16254c;
}

.about-text{
    position:relative;
    z-index:2;
    opacity:0;
    transform:translateX(100px) rotateY(-25deg) scale(0.85);
    filter:blur(15px);
    transition:all 1.2s cubic-bezier(.17,.84,.44,1);
}

.about-text span{
    color:#ff5b45;
    font-weight:800;
    letter-spacing:2px;
    font-size:20px;
}

.about-text h2{
    font-size:38px;
    color:#16254c;
    margin:18px 0 22px;
    line-height:1.18;
}

.about-text p{
    color:#5f6675;
    line-height:1.8;
    font-size:19px;
    font-weight:500;
    max-width:700px;
    margin-bottom:20px;
}

.about-stats{
    display:flex;
    gap:18px;
    margin-top:28px;
}

.stat-box{
    background:linear-gradient(135deg,#fff8ec,#f3e3c2);
    border:1px solid rgba(217,164,65,0.15);
    border-radius:22px;
    padding:22px 28px;
    box-shadow:0 12px 30px rgba(0,0,0,0.06);
    opacity:0;
    transform:translateY(50px) scale(0.8);
    filter:blur(10px);
    transition:all .8s cubic-bezier(.17,.84,.44,1);
}

.stat-box:hover{
    transform:translateY(-8px);
    background:linear-gradient(135deg,#ffe6d5,#fff3e0);
}

.stat-box h3{
    font-size:32px;
    margin-bottom:10px;
}

.stat-box p{
    margin:0;
    font-size:15px;
    font-weight:600;
    color:#555;
}

/* ABOUT ACTIVE ANIMATION */
.about.show .about-image-card,
.about.show .about-text{
    opacity:1;
    transform:translateX(0) rotateY(0) scale(1);
    filter:blur(0);
}

.about.show .stat-box{
    opacity:1;
    transform:translateY(0) scale(1);
    filter:blur(0);
}

.about.show .stat-box:nth-child(1){
    transition-delay:0.2s;
}

.about.show .stat-box:nth-child(2){
    transition-delay:0.4s;
}

.about.show .stat-box:nth-child(3){
    transition-delay:0.6s;
}

/* ANIMATION */
@keyframes slideLeft{
    from{
        opacity:0;
        transform:translateX(-60px);
    }
    to{
        opacity:1;
        transform:translateX(0);
    }
}

@keyframes slideRight{
    from{
        opacity:0;
        transform:translateX(60px);
    }
    to{
        opacity:1;
        transform:translateX(0);
    }
}

@keyframes floatCircle{
    0%,100%{
        transform:translate(-50%,-50%) scale(1);
    }
    50%{
        transform:translate(-50%,-53%) scale(1.03);
    }
}

@keyframes pulseCircle{
    0%,100%{
        transform:translate(-50%,-50%) scale(1);
        opacity:1;
    }
    50%{
        transform:translate(-50%,-50%) scale(1.05);
        opacity:0.7;
    }
}

/* EVENTS SECTION */
.events{
  background:
    linear-gradient(
        135deg,
        #fff9ef,
        #fff5e6,
        #fdf2dc
    );
    padding:95px 80px;
    position:relative;
    overflow:hidden;
}

.events::before{
    content:'EVENTS';
    position:absolute;
    right:45px;
    top:30px;
    font-size:90px;
    font-weight:900;
    color:rgba(22,37,76,0.04);
}

.events-header{
    text-align:center;
    max-width:760px;
    margin:0 auto 60px;
    position:relative;
    z-index:2;
}

.events-header span{
    color:#ff5b45;
    font-weight:900;
    letter-spacing:2px;
    font-size:20px;
}

.events-header h2{
    color:#16254c;
    font-size:38px;
    margin:16px 0;
}

.events-header p{
    color:#5f6675;
    font-size:18px;
    line-height:1.8;
}

.events-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:30px;
    position:relative;
    z-index:2;
}

.event-card{
    background:white;
    border-radius:30px;
    overflow:hidden;
    box-shadow:0 18px 45px rgba(0,0,0,0.10);
    transition:.4s ease;
}

.event-card:hover{
    transform:translateY(-12px);
    box-shadow:0 30px 65px rgba(0,0,0,0.16);
}



.event-image{
    height:230px;
    position:relative;
    overflow:hidden;
    border-bottom:4px solid #ff7a59;
}

.event-image img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
    transition:.6s ease;
}

.event-card:hover .event-image img{
    transform:scale(1.08);
}

.event-image::after{
    content:'';
    position:absolute;
    inset:0;
    background:linear-gradient(
        to top,
        rgba(0,0,0,0.55),
        rgba(0,0,0,0.05)
    );
}

.event-date{
    position:absolute;
    top:18px;
    left:18px;
    z-index:3;
    width:76px;
    height:76px;
    border-radius:22px;
    background:white;
    color:#ff5b45;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    box-shadow:0 12px 28px rgba(0,0,0,0.22);
}

.event-date h3{
    font-size:30px;
    line-height:1;
}

.event-date p{
    font-size:13px;
    font-weight:900;
}

.event-content{
    padding:28px;
    background:
        linear-gradient(135deg,#fffdf8,#fff1df);
    position:relative;
}

.event-content::before{
    content:'';
    position:absolute;
    width:120px;
    height:120px;
    border-radius:50%;
    background:rgba(255,91,69,0.08);
    right:-40px;
    bottom:-40px;
}

.event-content > *{
    position:relative;
    z-index:2;
}

.event-tag{
    display:inline-block;
       background: #FFF4D8;
color: #B8860B;
    padding:8px 14px;
    border-radius:999px;
    font-size:19px;
    font-weight:900;
    margin-bottom:16px;
}

.event-content h3{
    color:#16254c;
    font-size:24px;
    line-height:1.3;
    margin-bottom:14px;
}

.event-content p{
    color:#626978;
    font-size:15px;
    line-height:1.7;
    margin-bottom:16px;
}

.event-info p{
    margin-bottom:8px;
    font-weight:700;
}

.event-btn{
    display:inline-flex;
    margin-top:10px;
    background:linear-gradient(135deg,#ff5b45,#ff7a59);
    color:white;
    padding:13px 24px;
    border-radius:14px;
    text-decoration:none;
    font-weight:900;
    box-shadow:0 12px 28px rgba(255,91,69,0.28);
    transition:.35s ease;
}

.event-btn:hover{
    transform:translateY(-4px);
    box-shadow:0 18px 35px rgba(255,91,69,0.38);
}

/* EVENTS SCROLL ANIMATION */
.event-card{
    opacity:0;
    transform:translateY(80px) rotate(-6deg) scale(0.88);
    filter:blur(12px);
    transition:
        opacity 0.9s ease,
        transform 0.9s cubic-bezier(.18,.89,.32,1.28),
        filter 0.9s ease;
}

.events.show .event-card{
    opacity:1;
    transform:translateY(0) rotate(0) scale(1);
    filter:blur(0);
}

.events.show .event-card:nth-child(1){
    transition-delay:0.1s;
}

.events.show .event-card:nth-child(2){
    transition-delay:0.28s;
}

.events.show .event-card:nth-child(3){
    transition-delay:0.46s;
}

/* Image zoom reveal */
.event-image img{
    transform:scale(1.18);
    transition:transform 1.2s ease;
}

.events.show .event-image img{
    transform:scale(1);
}

/* Date badge pop */
.event-date{
    transform:scale(0);
    transition:transform 0.6s cubic-bezier(.18,.89,.32,1.28);
}

.events.show .event-date{
    transform:scale(1);
}

.events.show .event-card:nth-child(1) .event-date{
    transition-delay:0.35s;
}

.events.show .event-card:nth-child(2) .event-date{
    transition-delay:0.55s;
}

.events.show .event-card:nth-child(3) .event-date{
    transition-delay:0.75s;
}

/* BLOG SECTION */

/* BLOG SECTION */
.blog{
    background:linear-gradient(135deg,#fffaf3,#fff5e8,#fdf1dc);
    padding:95px 80px;
    position:relative;
    overflow:hidden;
}

.blog::before{
    content:'BLOG';
    position:absolute;
    top:25px;
    left:40px;
    font-size:95px;
    font-weight:900;
    color:rgba(22,37,76,0.04);
}

.blog-header{
    text-align:center;
    max-width:760px;
    margin:0 auto 60px;
    position:relative;
    z-index:2;
}

.blog-header span{
    color:#ff5b45;
    font-size:20px;
    font-weight:900;
    letter-spacing:2px;
}

.blog-header h2{
    font-size:38px;
    color:#16254c;
    margin:14px 0;
}

.blog-header p{
    color:#666;
    line-height:1.8;
    font-size:17px;
}

.blog-layout{
    display:flex;
    flex-direction:column;
    gap:28px;
    position:relative;
    z-index:2;
}

.blog-card{
    display:grid;
    grid-template-columns:450px 1fr;
    background:linear-gradient(135deg,#fffdf8,#fff5e8,#fdf1dc);
    border-radius:28px;
    padding:25px;
    gap:28px;
    align-items:center;
    box-shadow:0 18px 45px rgba(0,0,0,0.10);
    border:1px solid rgba(217,164,65,0.16);
    transition:.4s ease;

    opacity:0;
    transform:perspective(1200px) rotateX(12deg) translateY(90px) scale(.94);
    transform-origin:top center;
    filter:blur(10px);
}

.blog-card:hover{
    transform:translateY(-8px);
    box-shadow:0 30px 65px rgba(0,0,0,0.16);
}

.blog-card img{
    width:80%;
    height:420px;
    object-fit:cover;
    object-position:top center;
    background:#f8f5ee;
    border-radius:22px;
    transform:scale(.92);
    transition:transform 1.1s cubic-bezier(.16,1,.3,1);
}

.blog-content{
    padding:10px 20px 10px 0;
    opacity:0;
    transform:translateX(35px);
    transition:
        opacity .8s ease,
        transform .8s cubic-bezier(.16,1,.3,1);
}

.blog-tag{
    display:inline-block;
    background:#FFF4D8;
    color:#B8860B;
    padding:9px 16px;
    border-radius:999px;
    font-size:19px;
    font-weight:900;
    margin-bottom:16px;
}

.blog-content h3{
    color:#16254c;
    font-size:28px;
    line-height:1.35;
    margin-bottom:14px;
}

.blog-content p{
    color:#626978;
    font-size:15px;
    line-height:1.8;
    margin-bottom:18px;
}

.blog-meta{
    display:flex;
    gap:16px;
    flex-wrap:wrap;
    color:#9a7425;
    font-weight:800;
    font-size:14px;
    margin-bottom:22px;
}

.blog-btn{
    display:inline-flex;
    background:linear-gradient(135deg,#ff5b45,#ff7a59);
    color:white;
    padding:13px 24px;
    border-radius:14px;
    text-decoration:none;
    font-weight:900;
    box-shadow:0 12px 28px rgba(255,91,69,0.28);
    transition:.35s ease;
}

.blog-btn:hover{
    transform:translateY(-4px);
    box-shadow:0 18px 35px rgba(255,91,69,0.38);
}

/* BLOG SCROLL ANIMATION */
.blog.show .blog-card{
    opacity:1;
    transform:perspective(1200px) rotateX(0) translateY(0) scale(1);
    filter:blur(0);
}

.blog.show .blog-card:nth-child(1){
    transition-delay:.1s;
}

.blog.show .blog-card:nth-child(2){
    transition-delay:.32s;
}

.blog.show .blog-card:nth-child(3){
    transition-delay:.54s;
}

.blog.show .blog-card img{
    transform:scale(1);
}

.blog.show .blog-content{
    opacity:1;
    transform:translateX(0);
}

.blog.show .blog-card:nth-child(1) .blog-content{
    transition-delay:.35s;
}

.blog.show .blog-card:nth-child(2) .blog-content{
    transition-delay:.55s;
}

.blog.show .blog-card:nth-child(3) .blog-content{
    transition-delay:.75s;
}

/* CONTACT SECTION */
.contact{
    min-height:620px;
    background:#fffdf8;
    display:grid;
    grid-template-columns:1fr 1fr;
    position:relative;
    overflow:hidden;
}

/* LEFT FORM CARD */
.contact-form-area{
    margin:55px 0 55px 55px;
    padding:45px;
    position:relative;
    z-index:3;

    background:
        radial-gradient(circle at top left,
        rgba(255,106,74,.13),
        transparent 35%),

        radial-gradient(circle at bottom right,
        rgba(22,37,76,.08),
        transparent 35%),

        linear-gradient(135deg,#ffffff,#fffaf3,#fff6ee);

    border-radius:35px;
    box-shadow:0 25px 65px rgba(0,0,0,0.11);
    border:1px solid rgba(255,255,255,0.8);
    overflow:hidden;
}

/* top accent line */
.contact-form-area::before{
    content:'';
    position:absolute;
    top:0;
    left:0;
    width:100%;
    height:6px;
    background:linear-gradient(90deg,#ff5b45,#ff9d5c);
}

/* soft decoration circle */
.contact-form-area::after{
    content:'';
    position:absolute;
    width:180px;
    height:180px;
    border-radius:50%;
    background:rgba(255,91,69,.06);
    top:-70px;
    right:-70px;
}

.contact-form-area span,
.contact-form-area h2,
.contact-form-area p,
.contact-form-area form{
    position:relative;
    z-index:2;
}

.contact-form-area span{
    color:#ff5b45;
    font-size:18px;
    font-weight:900;
    letter-spacing:2px;
}

.contact-form-area h2{
    color:#16254c;
    font-size:38px;
    line-height:1.2;
    margin:14px 0;
}

.contact-form-area p{
    color:#626978;
    font-size:16px;
    line-height:1.8;
    margin-bottom:25px;
}

/* FORM */
.contact-form-area form{
    background:transparent;
    padding:0;
    box-shadow:none;
    border:none;
}

.form-row{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:15px;
}

.contact-form-area input,
.contact-form-area textarea{
    width:100%;
    border:2px solid transparent;
    outline:none;
    background:#F8F9FC;
    padding:16px 18px;
    border-radius:16px;
    margin-bottom:15px;
    font-size:15px;
    color:#16254c;
    font-weight:700;
    box-shadow:0 8px 22px rgba(0,0,0,0.04);
    transition:.3s ease;
}

.contact-form-area input:focus,
.contact-form-area textarea:focus{
    background:#ffffff;
    border-color:#ff6b4a;
    box-shadow:0 0 0 4px rgba(255,91,69,.16);
}

.contact-form-area textarea{
    height:135px;
    resize:none;
}

.contact-form-area button{
    border:none;
    background:linear-gradient(135deg,#ff5b45,#ff7a59);
    color:white;
    padding:16px 38px;
    border-radius:18px;
    font-size:17px;
    font-weight:900;
    cursor:pointer;
    box-shadow:0 18px 35px rgba(255,91,69,.35);
    transition:.35s ease;
}

.contact-form-area button:hover{
    transform:translateY(-4px);
    box-shadow:0 24px 45px rgba(255,91,69,.45);
}

/* RIGHT IMAGE AREA */
.contact-image-area{
    position:relative;
    overflow:hidden;
}

.contact-image-area::before{
    content:'';
    position:absolute;
    left:-120px;
    top:0;
    width:260px;
    height:100%;
    background:#fffdf8;
    z-index:2;
    border-radius:0 50% 50% 0;
}

.contact-image-area img{
    width:100%;
    height:100%;
    object-fit:cover;
    object-position:85% center;
    transition:none;
}

/* DOTTED CURVE */
.contact::after{
    content:'';
    position:absolute;
    width:260px;
    height:430px;
    border-left:2px dashed rgba(22,37,76,.18);
    border-radius:50%;
    left:50%;
    top:70px;
    z-index:4;
}

html{
    scroll-behavior:smooth;
}

/* RESPONSIVE */
@media(max-width:1000px){
    body{
        padding:15px;
    }

    .topbar{
        flex-direction:column;
        gap:22px;
        padding:25px;
    }

    nav{
        margin:0;
        gap:20px;
        flex-wrap:wrap;
        justify-content:center;
    }

    .hero{
        flex-direction:column;
        padding:40px 25px;
        text-align:center;
    }

    .left,
    .right{
        width:100%;
    }

    .left{
        margin-bottom:40px;
    }

    .left h1{
        font-size:42px;
    }

    .description{
        margin:auto;
    }

    .login-box{
        justify-content:center;
    }

    .team-card{
        width:100%;
        margin:0;
    }

    .about{
        grid-template-columns:1fr;
        padding:60px 25px;
        gap:40px;
        text-align:center;
    }

    .about-image-card img{
        height:360px;
    }

    .about-stats{
        flex-direction:column;
    }
}


</style>
</head>

<body>

<div class="wrapper">

    <!-- TOP NAVBAR -->
    <div class="topbar">

        <div class="brand">
            <img src="persada_logo.png" alt="PERSADA Logo">
        </div>

    <nav>
    <a href="#home">Home</a>
    <a href="#about">About</a>
    <a href="#events">Events</a>
    <a href="#blog">Blog</a>
    <a href="#contact">Contact</a>
</nav>

     <a href="Login.php" class="login-nav-btn">
    <span class="login-icon">🔐</span>
    Login
</a>

    </div>

    <!-- HERO SECTION -->
    <section class="hero" id="home">

        <div class="left">
<h1>
Empowering Student<br>

<span class="highlight">
Activities & Leadership
</span><br>


</h1>
<p class="description">
The official platform for activities, leadership,
and<br> student participation in PERSADA UTHM.
</p>
<div class="login-box">
  
</div>

        </div>

        <div class="right">
    <div class="team-card">
        <img src="persada.png" alt="PERSADA UTHM">
    </div>
</div>

    </section>

 

    <!-- ABOUT -->
 <section class="about" id="about">

    <div class="about-image-card">
        <img src="about_persada.png" alt="PERSADA Activity">
        <div class="about-badge">PERSADA UTHM</div>
    </div>

   <div class="about-text">

    <span>ABOUT PERSADA UTHM</span>

    <h2>
        Building Future Leaders Through
        Activities & Engagement
    </h2>

    <p>
        🎓 PERSADA UTHM empowers students through leadership development,
community engagement, and impactful campus activities.
    </p>

    <p>
        🚀 PAMMS provides a centralized platform for managing memberships,
events, attendance records, and student participation efficiently.
    </p>

    <div class="about-stats">

        <div class="stat-box">
            <h3>👥</h3>
            <p>Membership Management</p>
        </div>

        <div class="stat-box">
            <h3>📅</h3>
            <p>Event Management</p>
        </div>

        <div class="stat-box">
            <h3>📲</h3>
            <p>QR Attendance</p>
        </div>

    </div>

</div>
    </section>

<!-- EVENTS -->
<section class="events" id="events">

    <div class="events-header">
        <span>UPCOMING EVENTS</span>
        <h2>Explore PERSADA Activities</h2>
        <p>
            Discover upcoming programs, leadership activities,
            and community events organized by PERSADA UTHM.
        </p>
    </div>

    <div class="events-grid">

        <div class="event-card">
            <div class="event-image">
                <img src="Student_Leadership.png" alt="Student Leadership Workshop">
                <div class="event-date">
                    <h3>15</h3>
                    <p>JUN</p>
                </div>
            </div>

            <div class="event-content">
                <span class="event-tag">Leadership</span>

                <h3>Student Leadership Workshop</h3>

                <p>
                    A leadership program designed to improve communication,
                    teamwork, and student management skills.
                </p>

                <div class="event-info">
                    <p>📍 Dewan Kuliah Utama</p>
                    <p>⏰ 9:00 AM - 1:00 PM</p>
                </div>

                <a href="#" class="event-btn">View Details</a>
            </div>
        </div>


        <div class="event-card">
            <div class="event-image">
                <img src="Community_Service_Program.png" alt="Community Service Program">
                <div class="event-date">
                    <h3>22</h3>
                    <p>JUN</p>
                </div>
            </div>

            <div class="event-content">
                <span class="event-tag">Community</span>

                <h3>Community Service Program</h3>

                <p>
                    Join PERSADA members in a meaningful community
                    engagement activity around the campus area.
                </p>

                <div class="event-info">
                    <p>📍 UTHM Campus</p>
                    <p>⏰ 8:30 AM - 12:00 PM</p>
                </div>

                <a href="#" class="event-btn">View Details</a>
            </div>
        </div>


        <div class="event-card">
            <div class="event-image">
                <img src="Academic Talk & Sharing Session.png" alt="Academic Talk and Sharing Session">
                <div class="event-date">
                    <h3>30</h3>
                    <p>JUN</p>
                </div>
            </div>

            <div class="event-content">
                <span class="event-tag">Academic</span>

                <h3>Academic Talk & Sharing Session</h3>

                <p>
                    A knowledge-sharing session with invited speakers
                    to support student academic and career development.
                </p>

                <div class="event-info">
                    <p>📍 Seminar Room</p>
                    <p>⏰ 10:00 AM - 12:00 PM</p>
                </div>

                <a href="#" class="event-btn">View Details</a>
            </div>
        </div>

    </div>

</section>

<!-- BLOG -->
<section class="blog" id="blog">

    <div class="blog-header">
        <span>LATEST BLOG</span>
        <h2>PERSADA Stories & Updates</h2>
        <p>
            Explore activity highlights, committee stories, and updates from the
            PERSADA UTHM community.
        </p>
    </div>

    <div class="blog-layout">

        <div class="blog-card">
            <img src="Run_for_wellness.png" alt="Run For Wellness 5KM">

            <div class="blog-content">
                <span class="blog-tag">Sports & Wellness</span>

                <h3>Over 500 Students Joined the Run For Wellness 5KM Challenge</h3>

                <p>
                    The event successfully encouraged students to embrace a healthier lifestyle
                    while strengthening community spirit through participation in recreational activities.
                </p>

                <div class="blog-meta">
                    <span>📅 12 June 2026</span>
                    <span>👤 PERSADA UTHM</span>
                </div>

                <a href="#" class="blog-btn">Read More</a>
            </div>
        </div>

        <div class="blog-card">
           <img src="esports.png" class="tall-poster" alt="">

            <div class="blog-content">
                <span class="blog-tag">Esports Tournament</span>

                <h3>PERSADA X FESTKON Mobile Legends Tournament Returns in 2025</h3>

                <p>
                    The tournament provides a platform for students to showcase their gaming skills,
                    strategic thinking, teamwork, and competitive spirit.
                </p>

                <div class="blog-meta">
                    <span>📅 6 December 2025</span>
                    <span>🎮 Esports Event</span>
                </div>

                <a href="#" class="blog-btn">Read More</a>
            </div>
        </div>

        <div class="blog-card">
            <img src="Ihya.png" alt="Ihya Ramadan">

            <div class="blog-content">
                <span class="blog-tag">Community Outreach</span>

                <h3>Ihya’ Ramadan Strengthens Community Spirit and Volunteerism</h3>

                <p>
                    The program brought together students and local communities through meaningful
                    Ramadan activities, fostering compassion and social responsibility.
                </p>

                <div class="blog-meta">
                    <span>📅 13 March 2026</span>
                    <span>🤝 Community Service</span>
                </div>

                <a href="#" class="blog-btn">Read More</a>
            </div>
        </div>

    </div>

</section>
<!-- CONTACT -->
<section class="contact" id="contact">

    <div class="contact-form-area">
        <span>CONTACT US</span>

        <h2>Let’s Connect with PERSADA.</h2>

        <p>
            Have any questions about membership, activities, or PAMMS?
            Send us a message and our committee will assist you.
        </p>

        <form>
            <div class="form-row">
                <input type="text" placeholder="First name">
                <input type="text" placeholder="Last name">
            </div>

            <input type="email" placeholder="Email address">

            <textarea placeholder="Message"></textarea>

            <button type="submit">Send Message</button>
        </form>
    </div>

    <div class="contact-image-area">
        <img src="uthm.png" alt="PERSADA UTHM">
    </div>



</section>

</div>
<!-- TUTUP WRAPPER DEKAT SINI -->


<script>

const aboutSection = document.querySelector('.about');
const eventsSection = document.querySelector('.events');
const blogSection = document.querySelector('.blog');

const observer = new IntersectionObserver((entries)=>{
    entries.forEach(entry=>{
        if(entry.isIntersecting){
            entry.target.classList.add('show');
        }
    });
},{
    threshold:0.3
});

observer.observe(aboutSection);
observer.observe(eventsSection);
observer.observe(blogSection);

</script>
</body>
</html>