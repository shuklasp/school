<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Shiksha Vidyala - Register</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="register-body">
    <div class="register-container">
        <div class="register-box">
            <img src="logo.png" alt="Virtual Shiksha Vidyala Logo" class="logo">
            <h1>Register</h1>
            <form>
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm-password">Confirm Password:</label>
                    <input type="password" id="confirm-password" name="confirm-password" required>
                </div>
                <button type="submit" class="submit-btn">Register</button>
                <p>Already have an account? <a href="signin.html">Sign in here</a></p>
            </form>
        </div>
    </div>
</body>
</html>