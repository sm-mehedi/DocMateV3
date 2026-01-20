<?php
session_start();
require "../app/config/database.php";
require "../app/models/User.php";
require "../app/models/Patient.php";

$errors = [];
$formData = []; // To preserve form data on error

if($_POST){
    // Sanitize and validate inputs
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $emergency = trim($_POST['emergency'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $nid = trim($_POST['nid'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $health_issues = $_POST['health_issues'] ?? [];
    $other_conditions = trim($_POST['other_conditions'] ?? '');
    
    // Preserve form data for repopulation
    $formData = [
        'name' => htmlspecialchars($name),
        'phone' => htmlspecialchars($phone),
        'emergency' => htmlspecialchars($emergency),
        'address' => htmlspecialchars($address),
        'nid' => htmlspecialchars($nid),
        'email' => htmlspecialchars($email),
        'other_conditions' => htmlspecialchars($other_conditions)
    ];
    
    // Validation Rules
    
    // Name: Only letters, spaces, and basic punctuation
    if (empty($name)) {
        $errors['name'] = "Name is required";
    } elseif (!preg_match("/^[a-zA-Z\s\.\-']{2,100}$/", $name)) {
        $errors['name'] = "Name can only contain letters, spaces, dots, hyphens and apostrophes";
    }
    
    // Phone: Bangladeshi format (01XXXXXXXXX)
    if (empty($phone)) {
        $errors['phone'] = "Phone number is required";
    } elseif (!preg_match("/^01[3-9]\d{8}$/", $phone)) {
        $errors['phone'] = "Please enter a valid Bangladeshi phone number (01XXXXXXXXX)";
    }
    
    // Emergency: Same as phone validation
    if (empty($emergency)) {
        $errors['emergency'] = "Emergency contact is required";
    } elseif (!preg_match("/^01[3-9]\d{8}$/", $emergency)) {
        $errors['emergency'] = "Please enter a valid Bangladeshi emergency number (01XXXXXXXXX)";
    }
    
    // Address: Basic length check
    if (empty($address)) {
        $errors['address'] = "Address is required";
    } elseif (strlen($address) < 5 || strlen($address) > 200) {
        $errors['address'] = "Address must be between 5 and 200 characters";
    }
    
    // NID: 10-17 digits (Bangladeshi NID format)
    if (empty($nid)) {
        $errors['nid'] = "NID is required";
    } elseif (!preg_match("/^\d{10,17}$/", $nid)) {
        $errors['nid'] = "NID must be 10-17 digits";
    }
    
    // Email: Valid email format
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address";
    } elseif (strlen($email) > 100) {
        $errors['email'] = "Email is too long (max 100 characters)";
    }
    
    // Password: At least 6 characters
    if (empty($password)) {
        $errors['password'] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors['password'] = "Password must be at least 6 characters";
    }
    
    // Confirm Password
    if (empty($confirm_password)) {
        $errors['confirm_password'] = "Please confirm your password";
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match";
    }
    
    // Health Issues: Validate selected options (prevent injection)
    $valid_health_issues = [
        'diabetes', 'hypertension', 'hypotension', 'heart_disease', 'cholesterol',
        'thyroid', 'kidney_disease', 'liver_disease', 'asthma', 'copd',
        'bronchitis', 'sleep_apnea', 'drug_allergy', 'food_allergy', 'dust_allergy',
        'pollen_allergy', 'latex_allergy', 'gerd', 'ibs', 'colitis',
        'crohns', 'migraine', 'epilepsy', 'parkinsons', 'multiple_sclerosis',
        'arthritis', 'osteoporosis', 'back_pain', 'fibromyalgia', 'depression',
        'anxiety', 'bipolar', 'ptsd', 'hiv_aids', 'cancer', 'autoimmune',
        'stroke', 'heart_attack', 'pregnancy', 'smoking', 'alcohol', 'obesity'
    ];
    
    $sanitized_health_issues = [];
    foreach ($health_issues as $issue) {
        if (in_array($issue, $valid_health_issues)) {
            $sanitized_health_issues[] = $issue;
        }
    }
    
    // Other Conditions: Sanitize
    if (!empty($other_conditions)) {
        $other_conditions = substr(htmlspecialchars($other_conditions, ENT_QUOTES, 'UTF-8'), 0, 500);
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        $user = new User($conn);
        
        if(!$user->create($email, $password, 'patient')){
            $errors['email'] = "Email already exists!";
        } else {
            $patient = new Patient($conn);
            
            // Prepare data for Patient model
            $patientData = [
                'name' => $name,
                'phone' => $phone,
                'address' => $address,
                'health_issues' => $sanitized_health_issues,
                'emergency' => $emergency,
                'nid' => $nid
            ];
            
            // If other conditions specified, add to health issues
            if (!empty($other_conditions)) {
                // You might want to add an 'other_conditions' column to patients table
                // For now, we'll store it in health_issues
                $patientData['health_issues'][] = 'other:' . substr($other_conditions, 0, 100);
            }
            
            if($patient->create($conn->lastInsertId(), $patientData)){
                header("Location: login.php");
                exit;
            } else {
                $errors['general'] = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Patient Registration</title>
    <link rel="stylesheet" href="../public/assets/css/signP.css">
    
</head>
<body>

<div class="signup-container">
    <h2>Patient Registration</h2>

    <?php if(isset($errors['general'])): ?>
        <div class="error-message"><?= htmlspecialchars($errors['general']) ?></div>
    <?php endif; ?>

    <form method="post" id="patientSignupForm">
        <!-- Personal Information -->
        <div class="form-group">
            <label class="required">Full Name</label>
            <input type="text" name="name" value="<?= $formData['name'] ?? '' ?>" 
                   placeholder="Your full name" required
                   class="<?= isset($errors['name']) ? 'error-field' : '' ?>">
            <?php if(isset($errors['name'])): ?>
                <span class="field-error"><?= htmlspecialchars($errors['name']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="required">Phone Number</label>
                <input type="text" name="phone" value="<?= $formData['phone'] ?? '' ?>" 
                       placeholder="01XXXXXXXXX" required
                       class="<?= isset($errors['phone']) ? 'error-field' : '' ?>">
                <?php if(isset($errors['phone'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['phone']) ?></span>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label class="required">Emergency Contact</label>
                <input type="text" name="emergency" value="<?= $formData['emergency'] ?? '' ?>" 
                       placeholder="Emergency phone number" required
                       class="<?= isset($errors['emergency']) ? 'error-field' : '' ?>">
                <?php if(isset($errors['emergency'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['emergency']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label class="required">Address</label>
            <input type="text" name="address" value="<?= $formData['address'] ?? '' ?>" 
                   placeholder="Your complete address" required
                   class="<?= isset($errors['address']) ? 'error-field' : '' ?>">
            <?php if(isset($errors['address'])): ?>
                <span class="field-error"><?= htmlspecialchars($errors['address']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="required">National ID (NID)</label>
                <input type="text" name="nid" value="<?= $formData['nid'] ?? '' ?>" 
                       placeholder="Your NID number" required
                       class="<?= isset($errors['nid']) ? 'error-field' : '' ?>">
                <?php if(isset($errors['nid'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['nid']) ?></span>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label class="required">Email</label>
                <input type="email" name="email" value="<?= $formData['email'] ?? '' ?>" 
                       placeholder="your@email.com" required
                       class="<?= isset($errors['email']) ? 'error-field' : '' ?>">
                <?php if(isset($errors['email'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['email']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pre-existing Medical Conditions -->
        <div class="checkbox-section">
            <label><strong>Pre-existing Medical Conditions & Diagnoses</strong></label>
            <p style="font-size: 13px; color: #666; margin: 5px 0 15px 0;">
                Select all that apply. This helps doctors provide better care.
            </p>

            <div class="checkbox-group">
                <!-- All the same checkbox categories as before -->
                <!-- Chronic Diseases -->
                <div class="condition-category">
                    <h4>Chronic Diseases</h4>
                    <label><input type="checkbox" name="health_issues[]" value="diabetes"> Diabetes (Type 1/2)</label>
                    <label><input type="checkbox" name="health_issues[]" value="hypertension"> Hypertension (High BP)</label>
                    <label><input type="checkbox" name="health_issues[]" value="hypotension"> Hypotension (Low BP)</label>
                    <label><input type="checkbox" name="health_issues[]" value="heart_disease"> Heart Disease</label>
                    <label><input type="checkbox" name="health_issues[]" value="cholesterol"> High Cholesterol</label>
                    <label><input type="checkbox" name="health_issues[]" value="thyroid"> Thyroid Disorder</label>
                    <label><input type="checkbox" name="health_issues[]" value="kidney_disease"> Kidney Disease</label>
                    <label><input type="checkbox" name="health_issues[]" value="liver_disease"> Liver Disease</label>
                </div>

                <!-- Respiratory Conditions -->
                <div class="condition-category">
                    <h4>Respiratory Conditions</h4>
                    <label><input type="checkbox" name="health_issues[]" value="asthma"> Asthma</label>
                    <label><input type="checkbox" name="health_issues[]" value="copd"> COPD</label>
                    <label><input type="checkbox" name="health_issues[]" value="bronchitis"> Chronic Bronchitis</label>
                    <label><input type="checkbox" name="health_issues[]" value="sleep_apnea"> Sleep Apnea</label>
                </div>

                <!-- All other categories remain the same... -->
                <!-- [Include all other checkbox categories from your original code] -->
            </div>

            <!-- Other conditions not listed -->
            <div class="other-conditions">
                <label>Other Conditions (Specify)</label>
                <textarea name="other_conditions" placeholder="List any other medical conditions, surgeries, or important health information not mentioned above..."><?= $formData['other_conditions'] ?? '' ?></textarea>
            </div>
        </div>

        <!-- Account Security -->
        <div class="form-row">
            <div class="form-group">
                <label class="required">Password</label>
                <input type="password" name="password" required minlength="6"
                       class="<?= isset($errors['password']) ? 'error-field' : '' ?>">
                <?php if(isset($errors['password'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['password']) ?></span>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label class="required">Confirm Password</label>
                <input type="password" name="confirm_password" required
                       class="<?= isset($errors['confirm_password']) ? 'error-field' : '' ?>">
                <?php if(isset($errors['confirm_password'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['confirm_password']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <button type="submit">Register as Patient</button>
    </form>

    <div class="back-link">
        <a href="login.php">← Back to Login</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('patientSignupForm');
    const phoneInput = document.querySelector('input[name="phone"]');
    const emergencyInput = document.querySelector('input[name="emergency"]');
    
    // Phone number formatting and validation
    function formatPhoneNumber(input) {
        let value = input.value.replace(/\D/g, '');
        if (value.length > 0 && !value.startsWith('01')) {
            value = '01' + value.substring(2);
        }
        if (value.length > 11) value = value.substring(0, 11);
        input.value = value;
    }
    
    phoneInput.addEventListener('input', function() {
        formatPhoneNumber(this);
        validatePhoneNumber(this);
    });
    
    emergencyInput.addEventListener('input', function() {
        formatPhoneNumber(this);
        validatePhoneNumber(this);
    });
    
    function validatePhoneNumber(input) {
        const phoneRegex = /^01[3-9]\d{8}$/;
        if (input.value && !phoneRegex.test(input.value)) {
            input.style.borderColor = '#e74c3c';
        } else {
            input.style.borderColor = '#ddd';
        }
    }
    
    // Real-time password match validation
    const password = document.querySelector('input[name="password"]');
    const confirmPassword = document.querySelector('input[name="confirm_password"]');
    
    [password, confirmPassword].forEach(input => {
        input.addEventListener('input', function() {
            if (password.value && confirmPassword.value) {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.style.borderColor = '#e74c3c';
                } else {
                    confirmPassword.style.borderColor = '#2ecc71';
                }
            }
        });
    });
});
</script>

</body>
</html>