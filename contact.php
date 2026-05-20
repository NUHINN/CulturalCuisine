<?php
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $name = clean_text($_POST['name'] ?? '', 80);
    $email = clean_text($_POST['email'] ?? '', 120);
    $message = clean_long_text($_POST['message'] ?? '', 3000);

    if ($name === '' || $email === '' || $message === '') {
        flash('danger', 'All fields are required.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('danger', 'Please enter a valid email address.');
    } else {
        $smtpUser = getenv('BREVO_SMTP_USER') ?: '';
        $smtpKey = getenv('BREVO_SMTP_KEY') ?: '';
        $fromEmail = getenv('CONTACT_FROM_EMAIL') ?: '';
        $recipientEmail = getenv('CONTACT_TO_EMAIL') ?: '';

        if ($smtpUser && $smtpKey && $fromEmail && $recipientEmail) {
            require_once __DIR__ . '/phpmailer/Exception.php';
            require_once __DIR__ . '/phpmailer/PHPMailer.php';
            require_once __DIR__ . '/phpmailer/SMTP.php';

            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp-relay.brevo.com';
                $mail->SMTPAuth = true;
                $mail->Username = $smtpUser;
                $mail->Password = $smtpKey;
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->setFrom($fromEmail, APP_NAME);
                $mail->addAddress($recipientEmail);
                $mail->addReplyTo($email, $name);
                $mail->isHTML(false);
                $mail->Subject = 'New Cultural Cuisine Explorer message';
                $mail->Body = "Name: {$name}\nEmail: {$email}\n\n{$message}\n";
                $mail->send();
                flash('success', 'Your message was sent successfully.');
            } catch (\PHPMailer\PHPMailer\Exception $exception) {
                error_log('Contact email failed: ' . $mail->ErrorInfo);
                flash('danger', 'Could not send the message right now.');
            }
        } else {
            flash('info', 'Message validated. Configure SMTP environment variables to send email from XAMPP.');
        }
    }

    redirect('contact.php');
}

$pageTitle = 'Contact | ' . APP_NAME;
require __DIR__ . '/header.php';
?>

<div class="page-shell">
    <section class="content-card mx-auto" style="max-width: 720px;">
        <span class="eyebrow"><i class="fa-solid fa-envelope"></i> Contact</span>
        <h1 class="section-title mt-2">Send a message</h1>
        <p class="section-copy">For security, SMTP credentials are read from environment variables instead of being stored in the source code.</p>

        <form method="post" class="mt-4">
            <?php echo csrf_field(); ?>
            <div class="mb-3">
                <label class="form-label" for="name">Your name</label>
                <input class="form-control" id="name" name="name" maxlength="80" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="email">Your email</label>
                <input class="form-control" id="email" name="email" type="email" maxlength="120" required>
            </div>
            <div class="mb-4">
                <label class="form-label" for="message">Message</label>
                <textarea class="form-control" id="message" name="message" rows="6" required></textarea>
            </div>
            <button class="btn btn-primary" type="submit">Send message</button>
        </form>
    </section>
</div>

<?php require __DIR__ . '/footer.php'; ?>
