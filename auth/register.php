<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    redirect('/user/dashboard.php');
}

$ref = isset($_GET['ref']) ? sanitize($_GET['ref']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    $phone = sanitize($_POST['phone']);
    $email = sanitize($_POST['email']);
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $referral_code = sanitize($_POST['referral_code']);

    $errors = [];

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ? OR username = ?");
    $stmt->execute([$email, $phone, $username]);
    if ($stmt->fetch()) {
        $errors[] = "Email, Phone or Username already exists.";
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $user_ref_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

        // Find sponsor
        $sponsor_id = null;
        if (!empty($referral_code)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE ref_code = ?");
            $stmt->execute([$referral_code]);
            $sponsor = $stmt->fetch();
            if ($sponsor) {
                $sponsor_id = $sponsor['id'];
            }
        }

        $otp = generate_otp();
        $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        try {
            $stmt = $pdo->prepare("INSERT INTO users (phone, email, username, password, ref_code, referred_by, otp_hash, otp_expiry) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$phone, $email, $username, $hashed_password, $user_ref_code, $sponsor_id, $otp_hash, $otp_expiry]);

            $user_id = $pdo->lastInsertId();
            $_SESSION['temp_user_id'] = $user_id;

            // Send OTP email
            $subject = "Verify your account";
            $message = "Your OTP for verification is: <b>$otp</b>. It expires in 10 minutes.";
            send_email($email, $subject, $message);

            redirect('/auth/verify.php');
        } catch (PDOException $e) {
            $errors[] = "Registration failed. Please try again.";
        }
    }

    if (!empty($errors)) {
        foreach ($errors as $error) {
            set_flash_message('danger', $error);
        }
    }
}

$page_title = "Register";
include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <div class="card p-4">
            <h3 class="text-center mb-4">Create Account</h3>

            <form id="regForm" method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <!-- One "tab" for each step in the form: -->
                <div class="tab" style="display: block;">
                    <h5>1. Phone Number</h5>
                    <div class="mb-3">
                        <input type="tel" class="form-control" name="phone" placeholder="Phone Number" required>
                    </div>
                </div>

                <div class="tab" style="display: none;">
                    <h5>2. Email Address</h5>
                    <div class="mb-3">
                        <input type="email" class="form-control" name="email" placeholder="Email Address" required>
                    </div>
                </div>

                <div class="tab" style="display: none;">
                    <h5>3. Username</h5>
                    <div class="mb-3">
                        <input type="text" class="form-control" name="username" placeholder="Username" required>
                    </div>
                </div>

                <div class="tab" style="display: none;">
                    <h5>4. Password</h5>
                    <div class="mb-3">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    </div>
                </div>

                <div class="tab" style="display: none;">
                    <h5>5. Confirm & Submit</h5>
                    <div class="mb-3">
                        <input type="password" class="form-control" name="confirm_password" placeholder="Confirm Password" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" class="form-control" name="referral_code" placeholder="Referral Code (Optional)" value="<?php echo $ref; ?>">
                    </div>
                </div>

                <div style="overflow:auto;">
                    <div style="float:right;">
                        <button type="button" class="btn btn-secondary" id="prevBtn" onclick="nextPrev(-1)">Previous</button>
                        <button type="button" class="btn btn-primary" id="nextBtn" onclick="nextPrev(1)">Next</button>
                    </div>
                </div>

                <!-- Circles which indicates the steps of the form: -->
                <div style="text-align:center;margin-top:40px;">
                    <span class="step"></span>
                    <span class="step"></span>
                    <span class="step"></span>
                    <span class="step"></span>
                    <span class="step"></span>
                </div>
            </form>

            <div class="text-center mt-3">
                Already have an account? <a href="/auth/login.php">Login</a>
            </div>
        </div>
    </div>
</div>

<style>
/* Mark input boxes that gets an error on validation: */
input.invalid {
  background-color: #ffdddd;
}

/* Make circles that indicate the steps of the form: */
.step {
  height: 15px;
  width: 15px;
  margin: 0 2px;
  background-color: #bbbbbb;
  border: none;
  border-radius: 50%;
  display: inline-block;
  opacity: 0.5;
}

.step.active {
  opacity: 1;
}

/* Mark the steps that are finished and valid: */
.step.finish {
  background-color: #04AA6D;
}
</style>

<script>
var currentTab = 0; // Current tab is set to be the first tab (0)
showTab(currentTab); // Display the current tab

function showTab(n) {
  // This function will display the specified tab of the form...
  var x = document.getElementsByClassName("tab");
  x[n].style.display = "block";
  //... and fix the Previous/Next buttons:
  if (n == 0) {
    document.getElementById("prevBtn").style.display = "none";
  } else {
    document.getElementById("prevBtn").style.display = "inline";
  }
  if (n == (x.length - 1)) {
    document.getElementById("nextBtn").innerHTML = "Submit";
  } else {
    document.getElementById("nextBtn").innerHTML = "Next";
  }
  //... and run a function that will display the correct step indicator:
  fixStepIndicator(n)
}

function nextPrev(n) {
  // This function will figure out which tab to display
  var x = document.getElementsByClassName("tab");
  // Exit the function if any field in the current tab is invalid:
  if (n == 1 && !validateForm()) return false;
  // Hide the current tab:
  x[currentTab].style.display = "none";
  // Increase or decrease the current tab by 1:
  currentTab = currentTab + n;
  // if you have reached the end of the form...
  if (currentTab >= x.length) {
    // ... the form gets submitted:
    document.getElementById("regForm").submit();
    return false;
  }
  // Otherwise, display the correct tab:
  showTab(currentTab);
}

function validateForm() {
  // This function deals with validation of the form fields
  var x, y, i, valid = true;
  x = document.getElementsByClassName("tab");
  y = x[currentTab].getElementsByTagName("input");
  // A loop that checks every input field in the current tab:
  for (i = 0; i < y.length; i++) {
    // If a field is empty...
    if (y[i].value == "" && y[i].required) {
      // add an "invalid" class to the field:
      y[i].className += " invalid";
      // and set the current valid status to false
      valid = false;
    }
  }
  // If the valid status is true, mark the step as finished and valid:
  if (valid) {
    document.getElementsByClassName("step")[currentTab].className += " finish";
  }
  return valid; // return the valid status
}

function fixStepIndicator(n) {
  // This function removes the "active" class of all steps...
  var i, x = document.getElementsByClassName("step");
  for (i = 0; i < x.length; i++) {
    x[i].className = x[i].className.replace(" active", "");
  }
  //... and adds the "active" class on the current step:
  x[n].className += " active";
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
