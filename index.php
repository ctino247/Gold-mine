<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('/user/dashboard.php');
}

// Simple check to see if onboarding should be shown
// In a real app, this could be a cookie or local storage check
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - RecruitPlatform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            overflow: hidden;
            background-color: #fff;
        }
        .onboarding-container {
            height: 100%;
            display: flex;
            transition: transform 0.5s ease;
        }
        .slide {
            min-width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            text-align: center;
        }
        .slide img {
            max-width: 80%;
            margin-bottom: 30px;
        }
        .slide h2 {
            font-weight: bold;
            color: #333;
        }
        .slide p {
            color: #777;
            font-size: 18px;
        }
        .controls {
            position: fixed;
            bottom: 50px;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .indicators {
            display: flex;
            margin-bottom: 20px;
        }
        .dot {
            height: 10px;
            width: 10px;
            background-color: #bbb;
            border-radius: 50%;
            display: inline-block;
            margin: 0 5px;
        }
        .dot.active {
            background-color: #0d6efd;
            width: 25px;
            border-radius: 5px;
        }
        .btn-get-started {
            display: none;
            width: 80%;
        }
    </style>
</head>
<body>

<div class="onboarding-container" id="slider">
    <div class="slide">
        <div class="mb-4">
            <i class="fas fa-hand-wave fa-5x text-primary"></i>
        </div>
        <h2>Welcome</h2>
        <p>Your journey to professional recruitment and referrals starts here.</p>
    </div>
    <div class="slide">
        <div class="mb-4">
            <i class="fas fa-chart-line fa-5x text-success"></i>
        </div>
        <h2>How earnings work</h2>
        <p>Purchase contracts, refer friends, and earn commissions on every successful placement.</p>
    </div>
    <div class="slide">
        <div class="mb-4">
            <i class="fas fa-user-plus fa-5x text-info"></i>
        </div>
        <h2>Create Account</h2>
        <p>Join our community today and start earning rewards for your network.</p>
    </div>
</div>

<div class="controls">
    <div class="indicators">
        <span class="dot active"></span>
        <span class="dot"></span>
        <span class="dot"></span>
    </div>
    <button class="btn btn-primary btn-lg rounded-pill px-5" id="nextBtn">Next</button>
    <a href="/auth/register.php" class="btn btn-primary btn-lg rounded-pill px-5 btn-get-started" id="startBtn">Get Started</a>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
<script>
    const slider = document.getElementById('slider');
    const nextBtn = document.getElementById('nextBtn');
    const startBtn = document.getElementById('startBtn');
    const dots = document.querySelectorAll('.dot');
    let currentSlide = 0;
    const totalSlides = 3;

    nextBtn.addEventListener('click', () => {
        if (currentSlide < totalSlides - 1) {
            currentSlide++;
            updateSlider();
        }
    });

    function updateSlider() {
        slider.style.transform = `translateX(-${currentSlide * 100}%)`;
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === currentSlide);
        });

        if (currentSlide === totalSlides - 1) {
            nextBtn.style.display = 'none';
            startBtn.style.display = 'block';
        } else {
            nextBtn.style.display = 'block';
            startBtn.style.display = 'none';
        }
    }

    // Touch support
    let startX = 0;
    slider.addEventListener('touchstart', (e) => {
        startX = e.touches[0].clientX;
    });

    slider.addEventListener('touchend', (e) => {
        const endX = e.changedTouches[0].clientX;
        if (startX - endX > 50) {
            // Swipe left
            if (currentSlide < totalSlides - 1) {
                currentSlide++;
                updateSlider();
            }
        } else if (endX - startX > 50) {
            // Swipe right
            if (currentSlide > 0) {
                currentSlide--;
                updateSlider();
            }
        }
    });
</script>
</body>
</html>
