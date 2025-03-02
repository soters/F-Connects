<!DOCTYPE html>
<html>

<head>
  <!-- Basic -->
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <!-- Mobile Metas -->
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <!-- Site Metas -->
  <meta name="keywords" content="" />
  <meta name="description" content="" />
  <meta name="author" content="" />
  <link rel="icon" href="images/favicon.png" type="image/gif" />
  <style>
    html {
      scroll-behavior: smooth;
    }
  </style>

  <link rel="shortcut icon" href="../assets/images/F-Connect.ico" type="image/x-icon" />
  <title>F - Connect</title>
  <link rel="stylesheet" type="text/css" href="css/bootstrap.css" />
  <link href="https://fonts.googleapis.com/css?family=Poppins:400,600,700&display=swap" rel="stylesheet" />
  <link href="css/style.css" rel="stylesheet" />
  <link href="css/responsive.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

</head>

<body>

  <!-- header section strats -->
  <header class="header_section">
    <div class="container-fluid">
      <nav class="navbar navbar-expand-lg custom_nav-container">
        <div class="" id="">
          <button class="close-btn" onclick="location.href='../../kiosk/kiosk-index.php'">×</button>
          <!-- <div class="custom_menu-btn">
            <button onclick="openNav()">
              <span class="s-1"> </span>
              <span class="s-2"> </span>
              <span class="s-3"> </span>
            </button>
            <div id="myNav" class="overlay">
              <div class="overlay-content">
                <a href="#home">Home</a>
                <a href="#about">About</a>
                <a href="#gallery">Gallery</a>
                <a href="#service">Service</a>
                <a href="#blog">Blog</a>
                <a href="#contact">Contact</a>
              </div>
            </div>
          </div> -->
        </div>
      </nav>
    </div>
  </header>
  <!-- end header section -->

  <!-- slider section -->
  <section id="home" class="slider_section position-relative">
    <div id="customCarousel1" class="carousel slide" data-ride="carousel">
      <div class="carousel-inner">
        <div class="carousel-item active">
          <div class="img_container">
            <div class="img-box">
              <img src="images/BG1.jpg" class="" alt="...">
            </div>
          </div>
        </div>
        <div class="carousel-item">
          <div class="img_container">
            <div class="img-box">
              <img src="images/BG7.jpg" class="" alt="...">
            </div>
          </div>
        </div>
        <div class="carousel-item">
          <div class="img_container">
            <div class="img-box">
              <img src="images/BG8.jpg" class="" alt="...">
            </div>
          </div>
        </div>
      </div>
      <div class="carousel_btn_box">
        <a class="carousel-control-prev" href="#customCarousel1" role="button" data-slide="prev">
          <i class="bi bi-arrow-left"></i>
          <span class="sr-only">Previous</span>
        </a>
        <a class="carousel-control-next" href="#customCarousel1" role="button" data-slide="next">
          <i class="bi bi-arrow-right"></i>
          <span class="sr-only">Next</span>
        </a>
      </div>
    </div>
    <div class="detail-box">
      <div class="col-md-8 col-lg-6 mx-auto">
        <div class="inner_detail-box">
          <h1 class="ttl">
            Welcome to <br>
            F-Connect Kiosk User Manual
          </h1>
          <p>
            Welcome to the F-CONNECT Kiosk System, designed for efficient student-faculty engagement through RFID-based
            attendance logging, department overview, and appointment scheduling. This manual will guide you through each
            step to ensure a seamless experience.
          </p>
          <div>
            <a href="#about" class="slider-link">
              Let's Get Started
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- end slider section -->

  <!-- about section -->

  <section id="about" class="about_section layout_padding ">
    <div class="container">
      <div class="row">
        <!-- <div class="col-md-6">
          <div class="img-box">
            <img src="images/about-img.jpg" alt="">
          </div>
        </div> -->
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">
              <h2>
                Tap Your RFID Card
              </h2><br>
            </div>
            <li>Place your RFID card on the kiosk scanner.</li>
            <li>The system will identify whether you are a <a href="#faculty"
                style="font-weight: bold; color: blue; text-decoration: none; background: none; border: none; padding: 0;">faculty
                member</a> or a <a href="#student"
                style="font-weight: bold; color: blue; text-decoration: none; background: none; border: none; padding: 0;">student</a>
              and display the appropriate options.</li>

          </div>
        </div>
      </div>
    </div>
    <div class="image-box">
      <img src="images/Main.png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
  </section>

  <section id="faculty" class="about_section layout_padding ">
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">
              <h2>
                For Faculty Members
              </h2><br>
              <p>Choose between <a href="#option1"
                  style="font-weight: bold; color: orangered; text-decoration: none;background: none; border: none; padding: 0;">Time
                  In/Out</a> and <a href="#option2"
                  style="font-weight: bold; color: orangered; text-decoration: none;background: none; border: none; padding: 0;">Department
                  Overview</a></p>
              <h3 id=option1 style="font-weight: bold;">
                Option 1: Attendance Logging
              </h3><br>

              <li>Select Time In/Time Out from the main menu.</li>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="image-box">
      <img src="images/FACULTY/Screenshot (175).png" alt="time in"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">
              <li>The system will prompt you to take a photo for verification.</li>

              <!-- <ol>The system will prompt you to take a photo for verification.</ol>
              <ol>If it is your first time using the system, reminders will be displayed:</ol>
                <li>Ensure the area is well-lit.</li>
                <li>Make sure your face is clearly visible.</li>
                <li>Avoid wearing sunglasses.</li> -->
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="image-box">
      <img src="images/FACULTY/Screenshot (177).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">
              <li>If it is your first time using the system, reminders will be displayed:</li>
              <ul>
                <li>Ensure the area is well-lit.</li>
                <li>Make sure your face is clearly visible.</li>
                <li>Avoid wearing sunglasses.</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="image-box">
      <img src="images/FACULTY/Screenshot (178).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="image-box">
      <img src="images/FACULTY/Screenshot (179).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="image-box">
      <img src="images/FACULTY/Screenshot (181).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">
              <li>First-Time Users: Facial Registration</li>
              <ul>
                <li>Capture or upload three clear images of your face.</li>
                <li>If not satisfied, you may retake the images.</li>
                <li>Click Save to register your facial data successfully.
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="image-box">
      <img src="images/FACULTY/Screenshot (182).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="image-box">
      <img src="images/FACULTY/Screenshot (183).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="image-box">
      <img src="images/FACULTY/Screenshot (184).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">
              <li>If it is your first time using the system, reminders will be displayed:</li>
              <ul>
                <li>Ensure the area is well-lit.</li>
                <li>Make sure your face is clearly visible.</li>
                <li>Avoid wearing sunglasses.</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="image-box">
      <img src="images/FACULTY/Screenshot (178).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="image-box">
      <img src="images/FACULTY/Screenshot (179).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="image-box">
      <img src="images/FACULTY/Screenshot (181).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">
              <li>Logging Attendance</li>
              <ul>
                <li>Position your face in front of the camera.</li>
                <li>If the system does not detect your face, adjust your position and try again.</li>
                <li>Once recognized, the system will record your attendance.</li>
                <li>Click Confirm to complete the process.</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="image-box">
      <img src="images/FACULTY/Screenshot (186).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="image-box">
      <img src="images/FACULTY/Screenshot (187).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="image-box">
      <img src="images/FACULTY/Screenshot (188).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">
              <h3 id=option2 style="font-weight: bold;">Option 2: Department Overview</h3>
              <li>Select Department Overview from the main menu.</li>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="image-box">
      <img src="images/FACULTY/Screenshot (171).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">
              <li>The system will display the organizational chart of the department.</li>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="image-box">
      <img src="images/FACULTY/Screenshot (172).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">
              <li>Use the available controls to:</li>
              <ul>
                <li>Zoom, drag, or collapse sections for better navigation.</li>
                <li>Click on any faculty member to view their schedule and daily appointments.</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="image-box">
      <img src="images/FACULTY/Screenshot (173).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="image-box">
      <img src="images/FACULTY/Screenshot (174).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
  </section>

  <section id="student" class="about_section layout_padding ">
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">
              <h2>
                For Students
              </h2><br>
              <p>Choose among <a href="#option3"
                  style="font-weight: bold; color: red; text-decoration: none; background: none; border: none; padding: 0;">Department
                  Overview</a>, <a href="#option4"
                  style="font-weight: bold; color: red; text-decoration: none; background: none; border: none; padding: 0;">Book
                  of Appointment</a>, Or <a href="#option5"
                  style="font-weight: bold; color: red; text-decoration: none; background: none; border: none; padding: 0;">Manage
                  Appointments</a></p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="image-box">
      <img src="images/STUDENT/Screenshot (190).png" alt="time in"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">
              <h3 id=option3 style="font-weight: bold;">Option 1: Department Overview</h3>
              <li>Select Department Overview from the main menu.</li>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="image-box">
      <img src="images/STUDENT/Screenshot (191).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">

              <li>Displays the IT department's organizational chart, similar to the faculty’s view.</li>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="image-box">
      <img src="images/STUDENT/Screenshot (192).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="image-box">
      <img src="images/STUDENT/Screenshot (193).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">

              <li>Clicking on a faculty member will show:</li>
              <ul>
                <li>Their schedule.</li>
                <li>Class details and location map.</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="image-box">
      <img src="images/STUDENT/Screenshot (194).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="image-box">
      <img src="images/STUDENT/Screenshot (195).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="image-box">
      <img src="images/STUDENT/Screenshot (196).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="image-box">
      <img src="images/STUDENT/Screenshot (197).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="image-box">
      <img src="images/STUDENT/Screenshot (198).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="image-box">
      <img src="images/STUDENT/Screenshot (199).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="image-box">
      <img src="images/STUDENT/Screenshot (200).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">
              <h3 id=option4 style="font-weight: bold;">Option 2: Book an Appointment</h3>
              <li>Select Book Appointment from the main menu.</li>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="image-box">
      <img src="images/STUDENT/Screenshot (201).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">

              <li>Tap your RFID card to display your student information (student number, email, section).</li>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="image-box">
      <img src="images/STUDENT/Screenshot (202).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="image-box">
      <img src="images/STUDENT/Screenshot (203).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">

              <li>Click Next and choose a faculty member for the appointment.

              </li>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="image-box">
      <img src="images/STUDENT/Screenshot (204).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="image-box">
      <img src="images/STUDENT/Screenshot (205).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">

              <li>Select a date and time (availability may vary).</li>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="image-box">
      <img src="images/STUDENT/Screenshot (206).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">

              <li>Pick an appointment agenda (meeting purpose).</li>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="image-box">
      <img src="images/STUDENT/Screenshot (207).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">

              <li>The system will display all entered details for review.</li>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="image-box">
      <img src="images/STUDENT/Screenshot (208).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">

              <li>Click Next to confirm, and an appointment code will be generated.</li>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="image-box">
      <img src="images/STUDENT/Screenshot (209).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">
              <h3 id=option5 style="font-weight: bold;">Option 3: Manage Appointments</h3>
              <li>Select Manage Appointment from the main menu.</li>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="image-box">
      <img src="images/STUDENT/Screenshot (210).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">
              <li>Tap your RFID card to access your list of scheduled appointments.</li>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="image-box">
      <img src="images/STUDENT/Screenshot (211).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">
              <li>You can review, reschedule, or cancel an appointment if needed</li>
              <br>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="image-box">
      <img src="images/STUDENT/Screenshot (212).png" alt="RFID Card Scanning"
        style="display: block; margin: 20px auto; width: 60%; max-width: 600px; height: auto; border-radius: 10px;">
    </div>
    <a href="#" class="back-to-top">
      <i class="bi bi-arrow-up"></i>
    </a>

  </section>

  <!-- Info Section -->
  <section class="info_section ">
    <div class="container">
      <h4>
        <a href="index.html" class="navbar-brand m-0 p-0">
          <span>
            F-Connect
          </span>
        </a>
      </h4>
      <p class="mb-0">
        This manual is designed to help you navigate the F-CONNECT Kiosk System with ease. If you have any feedback or
        questions, feel free to reach out. Happy using!
      </p>
    </div>
  </section>
  <!-- End Info Section -->

  <style>
    .back-to-top {
      position: fixed;
      right: 20px;
      bottom: 20px;
      background-color: rgb(42, 87, 135);
      width: 60px;
      color: white;
      padding: 10px 15px;
      border-radius: 5px;
      font-size: 24px;
      text-align: center;
      display: flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
    }

    .arrow-up {
      border-left: 15px solid transparent;
      border-right: 15px solid transparent;
      border-bottom: 20px solid white;
      /* Arrow shape */
    }

    .back-to-top:hover {
      background-color: rgb(56, 112, 172);
    }

    .close-btn {
      position: fixed;
      top: 20px;
      right: 20px;
      width: 50px;
      /* Circular shape */
      height: 50px;
      background-color: red;
      color: white;
      border: none;
      border-radius: 50%;
      font-size: 24px;
      font-weight: bold;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
    }

    .close-btn:hover {
      background-color: darkred;
    }
  </style>

  <script src="js/jquery-3.4.1.min.js"></script>
  <script src="js/bootstrap.js"></script>

  <script>
    document.querySelectorAll('.overlay-content a').forEach(anchor => {
      anchor.addEventListener('click', function (event) {
        event.preventDefault();
        const targetId = this.getAttribute('href').substring(1);
        document.getElementById(targetId).scrollIntoView({ behavior: 'smooth' });
      });
    });

    function closePage() {
      if (window.history.length > 1) {
        window.history.back(); // Go back if possible
      } else {
        window.open('', '_self').close(); // Only works if opened via JS
        alert("Your browser does not allow this page to be closed automatically. Please close it manually.");
      }
    }
  </script>
</body>

</html>