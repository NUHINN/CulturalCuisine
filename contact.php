
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(120deg, #ffcc00, #ff9900); /* Gradient background */
            background-size: cover;
            background-attachment: fixed;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 50px auto;
            background: #fff;
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 32px;
            color: #333;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            font-size: 16px;
            margin-bottom: 5px;
            font-weight: 500;
        }

        input, textarea {
            font-size: 16px;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 100%;
            box-sizing: border-box;
        }

        textarea {
            resize: none;
            height: 100px;
        }

        button {
            font-size: 16px;
            padding: 10px;
            background-color: #333;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #555;
        }

        .message {
            font-size: 14px;
            text-align: center;
            margin-bottom: 20px;
        }

        .success {
            color: green;
        }

        .error {
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Contact Us</h1>

        <?php if (isset($success)): ?>
            <div class="message success"><?= $success; ?></div>
        <?php elseif (isset($error)): ?>
            <div class="message error"><?= $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" placeholder="Your name" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" placeholder="Your email" required>

            <label for="subject">Subject:</label>
            <input type="text" id="subject" name="subject" placeholder="Subject" required>

            <label for="message">Message:</label>
            <textarea id="message" name="message" placeholder="Write your message here" required></textarea>

            <button type="submit">Send Message</button>
        </form>
    </div>
</body>
</html>
