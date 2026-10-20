<?php
// Registration page. Steps (from the system flowchart):
//   1. Validate the staff ID against the staff list (and check it is unused)
//   2. Validate the other fields and the passport photo
//   3. Save the account as INACTIVE - the admin must activate it
require_once 'includes/auth.php';

$questions = [
    'In what city were you born?',
    'What is the name of your first school?',
    'What is your mother\'s maiden name?',
    'What was the name of your first pet?',
];

$errors = [];
$form = ['fname' => '', 'gender' => '', 'address' => '', 'pn' => '', 'email' => '', 'staffid' => '', 'secq' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    foreach ($form as $key => $value) {
        $form[$key] = trim($_POST[$key] ?? '');
    }
    $form['staffid'] = strtoupper($form['staffid']);
    $pass  = $_POST['pass'] ?? '';
    $pass2 = $_POST['pass2'] ?? '';
    $seca  = strtolower(trim($_POST['seca'] ?? ''));

    // 1. Staff ID validation
    if (!db_one('SELECT staffid FROM staff WHERE staffid = ?', 's', [$form['staffid']])) {
        $errors[] = 'Staff ID not recognised. Use the ID on your IMT staff card, e.g. IMT/STF/0106.';
    } elseif (db_one('SELECT id FROM register WHERE staffid = ?', 's', [$form['staffid']])) {
        $errors[] = 'An account already exists for this staff ID.';
    }

    // 2. Other fields
    if ($form['fname'] === '') $errors[] = 'Full name is required.';
    if (!in_array($form['gender'], ['Male', 'Female'], true)) $errors[] = 'Please select your gender.';
    if ($form['address'] === '') $errors[] = 'Address is required.';
    if (!preg_match('/^0[789][01]\d{8}$/', $form['pn'])) {
        $errors[] = 'Phone number must be 11 digits, e.g. 08031234567.';
    } elseif (db_one('SELECT id FROM register WHERE pn = ?', 's', [$form['pn']])) {
        $errors[] = 'This phone number is already registered.';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid e-mail address.';
    if (strlen($pass) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($pass !== $pass2) $errors[] = 'The two passwords do not match.';
    if (!in_array($form['secq'], $questions, true)) $errors[] = 'Please choose a security question.';
    if ($seca === '') $errors[] = 'Please answer the security question.';

    // Passport photo: must be a real JPG/PNG image, max 2 MB
    $photo = '';
    if (empty($_FILES['photo']['name'])) {
        $errors[] = 'Please upload a passport photograph.';
    } elseif ($_FILES['photo']['error'] !== UPLOAD_ERR_OK || $_FILES['photo']['size'] > 2 * 1024 * 1024) {
        $errors[] = 'Photo upload failed. Use a JPG or PNG image under 2 MB.';
    } else {
        $info = @getimagesize($_FILES['photo']['tmp_name']);
        $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png'];
        if (!$info || !isset($allowed[$info[2]])) {
            $errors[] = 'The photo must be a JPG or PNG image.';
        } else {
            // Random file name so users cannot overwrite files or upload scripts
            $photo = bin2hex(random_bytes(8)) . '.' . $allowed[$info[2]];
        }
    }

    // 3. Save
    if (!$errors) {
        move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/uploads/' . $photo);
        db_exec(
            "INSERT INTO register (fname, gender, address, photo, pn, pass, email, staffid, role, active, secq, seca)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'user', 0, ?, ?)",
            'ssssssssss',
            [$form['fname'], $form['gender'], $form['address'], $photo, $form['pn'],
             password_hash($pass, PASSWORD_DEFAULT), $form['email'], $form['staffid'],
             $form['secq'], password_hash($seca, PASSWORD_DEFAULT)]
        );
        flash('success', 'Registration successful. Your account will be usable once the administrator activates it.');
        redirect('index.php');
    }
}

$page_title = 'Register';
include 'includes/header.php';
?>
<div class="auth-box wide">
    <h1>Create an account</h1>
    <?php if ($errors): ?>
        <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="card grid-2">
        <?= csrf_field() ?>
        <label>Staff ID
            <input name="staffid" value="<?= e($form['staffid']) ?>" placeholder="IMT/STF/0106" required>
        </label>
        <label>Full name
            <input name="fname" value="<?= e($form['fname']) ?>" required>
        </label>
        <label>Gender
            <select name="gender" required>
                <option value="">-- select --</option>
                <?php foreach (['Male', 'Female'] as $g): ?>
                    <option <?= $form['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Phone number (used to log in)
            <input type="tel" name="pn" value="<?= e($form['pn']) ?>" placeholder="08031234567" required>
        </label>
        <label>E-mail
            <input type="email" name="email" value="<?= e($form['email']) ?>" required>
        </label>
        <label>Address
            <input name="address" value="<?= e($form['address']) ?>" required>
        </label>
        <label>Password
            <input type="password" name="pass" minlength="6" required>
        </label>
        <label>Confirm password
            <input type="password" name="pass2" minlength="6" required>
        </label>
        <label>Security question
            <select name="secq" required>
                <option value="">-- select --</option>
                <?php foreach ($questions as $q): ?>
                    <option <?= $form['secq'] === $q ? 'selected' : '' ?>><?= e($q) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Answer
            <input name="seca" required>
        </label>
        <label>Passport photograph (JPG/PNG, max 2 MB)
            <input type="file" name="photo" id="photo" accept="image/jpeg,image/png" required>
        </label>
        <div><img id="photo-preview" class="photo-preview" alt="" hidden></div>
        <div class="span-2">
            <button type="submit" class="btn">Register</button>
            <span class="muted">Already registered? <a href="index.php">Login</a></span>
        </div>
    </form>
</div>
<?php include 'includes/footer.php'; ?>
