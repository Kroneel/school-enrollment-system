<?php
$pageTitle = "About Us - Koro High School";
include "partials/header.php";
?>

<div class="container my-4">

    <!-- Page Heading -->
    <h2 class="mb-3">About Koro High School</h2>
    <p class="text-muted mb-4">
        Welcome to Koro High School. This student enrollment portal was created to simplify
        the process of applying for enrollment, tracking application status, and improving
        communication between students and the school administration.
    </p>

    <!-- Row: About School + Google Map -->
    <div class="row g-3">

        <!-- School Information Card -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">School Information</h5>

                    <p class="mb-1"><strong>School Name:</strong> Koro High School</p>
                    <p class="mb-1"><strong>Address:</strong> Laucala Bay</p>
                    <p class="mb-1"><strong>Phone:</strong> (679) 1234567</p>
                    <p class="mb-1"><strong>Email:</strong> info@korohighschool.ac.fj</p>
                    <p class="mb-0"><strong>Office Hours:</strong> Monday – Friday, 8:00 AM – 4:30 PM</p>

                </div>
            </div>
        </div>

        <!-- Google Map Card -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Find Us on the Map</h5>

                    <div class="ratio ratio-16x9">
                        <!-- Replace with actual school coordinates -->
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3904.8872880793197!2d178.44502577538523!3d-18.148041085302275!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x6e1bddd1d33b500d%3A0x151b26830981d6a1!2sThe%20University%20of%20the%20South%20Pacific!5e1!3m2!1sen!2sus!4v1768864511961!5m2!1sen!2sus" 
                            width="600" 
                            height="450" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>

                </div>
            </div>
        </div>

    </div> <!-- /row -->

    <section class="py-5 bg-light">
  <div class="container">

    <h2 class="text-center mb-4 fw-bold">Meet the Developers</h2>
    <p class="text-center text-muted mb-5">
      Our development team designed and built this enrollment system with a focus on security,
      efficiency, and user-friendly experience.
    </p>

    <!-- TOP DEVELOPER -->
    <div class="row justify-content-center mb-5">
        <div class="col-md-4 text-center">
        <img src="assets/images/dev2.jpg" 
             class="developer-photo mb-3" 
             alt="Developer Photo">

        <h5 class="fw-bold">Jennis Prasad</h5>
        <hr class="dev-line mx-auto">
        <p class="text-muted small">
          Frontend Designer • UI/UX • Responsive Layouts
        </p>
      </div>                   

    </div>

    <!-- TWO DEVELOPERS BELOW -->
    <div class="row justify-content-center g-5">
            <div class="col-md-4 text-center">
        
        <img src="assets/images/dev1.jpg" 
             class="developer-photo mb-3" 
             alt="Developer Photo">

        <h5 class="fw-bold">Roneel Kumar</h5>
        <hr class="dev-line mx-auto">
        <p class="text-muted small">
          Lead Developer • Backend & Security • System Architecture
        </p>
      </div>                      
   

      <div class="col-md-4 text-center">
        <img src="assets/images/dev3.jpg" 
             class="developer-photo mb-3" 
             alt="Developer Photo">

        <h5 class="fw-bold">Shivnay Chand</h5>
        <hr class="dev-line mx-auto">
        <p class="text-muted small">
          Database Engineer • PHP Integration • Optimization
        </p>
      </div>

    </div>

  </div>
</section>

</div>

<?php include "partials/footer.php"; ?>
