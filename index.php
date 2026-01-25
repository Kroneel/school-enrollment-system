<?php
// Page title shown in browser tab
$pageTitle = "Home - Koro High School";

// Include common header (navbar + CSS)
include "partials/header.php";
?>

<!-- HERO SECTION (background image applied via CSS .hero class) -->
<header class="hero d-flex align-items-center">
  <div class="container hero-content text-white py-5">
    <div class="row">
      <div class="col-lg-7">

        <h1 class="display-5 fw-bold">
          Welcome to Koro High School Portal
        </h1>

        <p class="lead mt-3">
          A simple and secure portal for teachers to register students
          and access student profiles quickly.
        </p>

        <!-- Action buttons -->
        <div class="d-flex gap-2 mt-4">
          <a href="login.php" class="btn btn-primary btn-lg">
            Administrator Login
          </a>
          
        </div>

              <div class="d-flex gap-1 mt-1">
          <a href="student_login.php" class="btn btn-primary btn-lg">
            Student Login
          </a>
          
        </div>

      </div>
    </div>
  </div>
</header>

<!-- INFORMATION SECTION -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="row g-3">

      <!-- About School -->
      <div class="col-md-4">
        <div class="card shadow-sm h-100">
          <div class="card-body">
            <h5 class="fw-bold">About Koro High School</h5>
            <p class="mb-0 text-muted" style="text-align: justify;">
              Koro High School is committed to providing quality education in a safe, respectful, and 
              inclusive environment that reflects the strong values of the Fijian community. The school strives
               to develop disciplined, confident, and responsible students by promoting academic excellence, good
                character, and teamwork. Koro High School has achieved positive results in national examinations 
                and encourages active participation in sports, cultural activities, and community programs. 
                With a growing focus on ICT and digital learning, the school integrates technology into teaching 
                and administration to enhance learning outcomes and prepare students with practical skills for further 
                education and future careers.
            </p>
          </div>
        </div>
      </div>

      <!-- Latest News -->
      <div class="col-md-4">
        <div class="card shadow-sm h-100">
          <div class="card-body">
                <h5 class="fw-bold">Latest News</h5>

                            <div id="newsCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000">
                            <div class="carousel-inner rounded">

                                <!-- Slide 1 -->
                                <div class="carousel-item active">
                                <img src="assets/images/news/news1.jpg" class="d-block w-100" alt="Fiji News">
                                <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-2">
                                    <p class="mb-0 small">
                                    National education updates and important announcements across Fiji.
                                    </p>
                                </div>
                                </div>

                                <!-- Slide 2 -->
                                <div class="carousel-item">
                                <img src="assets/images/news/news2.jpg" class="d-block w-100" alt="Community News">
                                <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-2">
                                    <p class="mb-0 small">
                                    Community, school, and public interest news.
                                    </p>
                                </div>
                                </div>

                                <!-- Slide 3 -->
                                <div class="carousel-item">
                                <img src="assets/images/news/news3.jpg" class="d-block w-100" alt="FijiVillage">
                                <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-2">
                                    <p class="mb-0 small">
                                    Access trusted local news from FijiVillage.
                                    </p>
                                </div>
                                </div>

                            </div>
                            </div>

                            <a href="https://www.fijivillage.com"
                            target="_blank"
                            class="btn btn-sm btn-outline-primary mt-2">
                            View more on FijiVillage
                            </a>

                            <p class="small text-muted mt-2 mb-0">
                            External news source: FijiVillage
                            </p>

          </div>
        </div>
      </div>
        <!-- Quick Access -->
<div class="col-md-4">
  <div class="card shadow-sm">
    <div class="card-body">
      <h5 class="fw-bold">Quick Access</h5>
      <p class="mb-3 text-muted">
        Quick tools and information to support daily school activities and planning.
      </p>
    <?php include "weather_widget.php"; ?>

    </div>
  </div>
</div>

    </div>
  </div>
</section>
<?php
// Include common footer (local JS + closing tags)
include "partials/footer.php";
?>
