<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration</title>
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        /* Form Container */
form {
    width: 100%;
    background: rgba(255, 255, 255, 0.1);
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

/* Form Header */
h2 {
    text-align: center;
    margin-bottom: 20px;
    font-weight: 700;
    color: #fff;
}

/* Input Fields */
form label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    font-size: 14px;
}

form input {
    width: 100%;
    padding: 12px 15px;
    margin-bottom: 15px;
    border: none;
    border-radius: 5px;
    font-size: 14px;
}

form select {
    width: 100%;
    padding: 16px 20px;
    border: none;
    border-radius: 4px;
    background-color: #f1f1f1;
}

 
form button {
    width: 100%;
    padding: 12px 15px;
    border: none;
    border-radius: 5px;
    background: #2575fc;
    color: white;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
}



form button:hover {
    background: #1e63d4;
}

/* Links */
form a {
    display: block;
    margin-top: 15px;
    text-align: center;
    color: #ddd;
    font-size: 14px;
    text-decoration: none;
}

form a:hover {
    color: white;
    text-decoration: underline;
}

/* Error Message */
.error {
    color: red;
    font-size: 14px;
    margin-bottom: 15px;
    text-align: center;
}

/* Textarea */
form textarea {
    width: 100%;
    padding: 12px 15px;
    border: none;
    border-radius: 5px;
    font-size: 14px;
    resize: vertical;
    margin-bottom: 15px;
}
    </style>
</head>
<body style="height: 100vh;">

    <form action="register.php" method="POST"  style="max-width: 400px;">
        <h2>Register</h2>
        <label for="name">Name</label>
        <input type="text" id="name" name="name" placeholder="Enter your name" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Enter your email" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter your password" required>

        <!-- Hidden field to set the role as admin -->
        <input type="hidden" name="role" value="Admin">

        <button type="submit" name="register">Sign Up</button>
        <a href="login.php">Already have an account? Login here</a>
        
        <?php
        include 'includes/db.php'; // Database connection

        if (isset($_POST['register'])) {
            $name = htmlspecialchars(trim($_POST['name']));
            $email = htmlspecialchars(trim($_POST['email']));
            $password = password_hash(trim($_POST['password']), PASSWORD_BCRYPT); // Encrypt password
            $role = htmlspecialchars(trim($_POST['role'])); // Get the role from the hidden field

            // Check if email already exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0) {
                echo "Email is already registered!";
            } else {
                // Insert user into the database
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$name, $email, $password, $role])) {
                    echo "Registration successful! <a href='login.php'>Login here</a>";
                    header("Location: admin_edit_profile.php");
                    exit();
                } else {
                    echo "An error occurred. Please try again.";
                }
            }
        }
        ?>
    </form>
</body>
</html>

