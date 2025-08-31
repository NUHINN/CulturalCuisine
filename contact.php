<?php
// contact.php — PHPMailer + Brevo SMTP (labels hard-fixed, orange gradient)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/phpmailer/Exception.php';
require __DIR__ . '/phpmailer/PHPMailer.php';
require __DIR__ . '/phpmailer/SMTP.php';

$success = '';
$error   = '';

// ====== Brevo SMTP creds ======
$BREVO_USER = '95ede7001@smtp-brevo.com';
$BREVO_KEY  = 'vV03d2tZNaEHFwhD';

// ====== Verified sender in Brevo ======
$VERIFIED_FROM_EMAIL = 'nuhin.islam44@gmail.com';
$VERIFIED_FROM_NAME  = 'Website Contact';

// ====== Recipient (BRACU inbox) ======
$RECIPIENT_EMAIL = 'nuhin.islam@g.bracu.ac.bd';
$RECIPIENT_NAME  = 'Nuh';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $message === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $mail = new PHPMailer(true);

        try {
            // SMTP config (Brevo)
            $mail->isSMTP();
            $mail->Host       = 'smtp-relay.brevo.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $BREVO_USER;
            $mail->Password   = $BREVO_KEY;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // From must be verified in Brevo
            $mail->setFrom($VERIFIED_FROM_EMAIL, $VERIFIED_FROM_NAME);

            // To
            $mail->addAddress($RECIPIENT_EMAIL, $RECIPIENT_NAME);

            // Reply-to visitor
            if ($email) {
              $mail->addReplyTo($email, $name ?: 'Website Visitor');
            }

            // Content
            $mail->isHTML(false);
            $mail->Subject = "New Contact Message from $name";
            $mail->Body    = "You received a new message from your website:\n\n"
                           . "Name: $name\n"
                           . "Email: $email\n\n"
                           . "Message:\n$message\n";

            $mail->send();
            $success = '✅ Your message has been sent successfully!';
        } catch (Exception $e) {
            $error = '❌ Could not send message. SMTP error: ' . htmlspecialchars($mail->ErrorInfo);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Contact Us</title>
  <link rel="stylesheet" href="style.css" />
  <style>
    /* Page background: orange gradient to match homepage/search */
    body {
      margin: 0;
      padding: 0;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background: linear-gradient(135deg, #ff9966, #ff5e62);
      font-family: "Poppins", sans-serif;
    }

    /* Card */
    .contact-card {
      width: 100%;
      max-width: 520px;
      padding: 2.25rem;
      border-radius: 18px;
      background: rgba(255, 255, 255, 0.94);
      backdrop-filter: blur(10px);
      box-shadow: 0 20px 45px rgba(0,0,0,0.22);
      animation: fadeIn 0.6s ease;
    }

    .contact-card h1 {
      text-align: center;
      margin: 0 0 1.25rem 0;
      color: #ff5e62;
      font-size: 30px;
      font-weight: 700;
      letter-spacing: 0.3px;
    }

    /* Messages */
    .msg {
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 16px;
      font-size: 14px;
      text-align: center;
      font-weight: 500;
    }
    .ok { background:#e8f7ef; border:1px solid #b6ebc5; color:#2e7d32; }
    .err{ background:#fdeaea; border:1px solid #f5bcbc; color:#c62828; }

    /* Field wrapper ensures our overrides win */
    .field {
      margin-bottom: 18px;
    }

    /* Hard reset any floating/absolute label styles from global CSS */
    .contact-card .field > label {
      position: static !important;
      display: block !important;
      float: none !important;
      transform: none !important;
      top: auto !important;
      left: auto !important;
      margin: 0 0 6px 2px !important;  /* small gap above the input */
      padding: 0 !important;
      font-size: 14px !important;
      font-weight: 500 !important;
      line-height: 1.2 !important;
      color: #444 !important;
      pointer-events: auto !important;
      opacity: 1 !important;
    }

    /* Inputs */
    .contact-card .field > input,
    .contact-card .field > textarea {
      width: 100%;
      padding: 12px;
      border: 1px solid #d2d2d2 !important;
      border-radius: 10px;
      font-size: 14px;
      outline: none;
      background: #fff !important;     /* solid bg so labels never overlap */
      box-shadow: none !important;
      transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;
    }
    .contact-card .field > input::placeholder,
    .contact-card .field > textarea::placeholder {
      color: #999;
    }
    .contact-card .field > input:focus,
    .contact-card .field > textarea:focus {
      border-color: #ff5e62 !important;
      box-shadow: 0 0 6px rgba(255, 94, 98, 0.55) !important;
      transform: translateY(-1px);
    }

    /* Kill any sibling-selector floating-label animations from global CSS */
    .contact-card .field > input:focus ~ label,
    .contact-card .field > input:not(:placeholder-shown) ~ label,
    .contact-card .field > textarea:focus ~ label,
    .contact-card .field > textarea:not(:placeholder-shown) ~ label {
      transform: none !important;
      top: auto !important;
      font-size: 14px !important;
      color: #444 !important;
    }

    /* Button */
    button {
      width: 100%;
      background: linear-gradient(45deg, #ff9966, #ff5e62);
      color: #fff;
      padding: 14px;
      font-size: 16px;
      font-weight: 600;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    button:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 18px rgba(0,0,0,0.22);
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-14px); }
      to   { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>
  <div class="contact-card">
    <h1>📩 Contact Us</h1>

    <?php if (!empty($success)): ?>
      <div class="msg ok"><?= $success ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
      <div class="msg err"><?= $error ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <div class="field">
        <label for="name">Your Name</label>
        <input id="name" type="text" name="name" placeholder="Enter your name" required />
      </div>

      <div class="field">
        <label for="email">Your Email</label>
        <input id="email" type="email" name="email" placeholder="Enter your email" required />
      </div>

      <div class="field">
        <label for="message">Message</label>
        <textarea id="message" name="message" rows="6" placeholder="Write your message here..." required></textarea>
      </div>

      <button type="submit">Send Message</button>
    </form>
  </div>
</body>
</html>
