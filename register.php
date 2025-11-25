<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
   <script src="particles.min.js"></script>
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<style>
/* ===================================
   🌟 PREMIUM REGISTER PAGE UI UPGRADE
   =================================== */

body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #6a11cb, #2575fc);
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    overflow: hidden;
    color: #fff;
}

/* Particle Background Layer */
#particles-js {
    position: absolute;
    width: 100%;
    height: 100%;
    z-index: 0;
}

/* Form Container */
form {
    width: 100%;
    max-width: 450px;
    padding: 35px;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.10);
    backdrop-filter: blur(18px) saturate(180%);
    -webkit-backdrop-filter: blur(18px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.25);

    box-shadow:
        0 12px 35px rgba(0,0,0,0.25),
        inset 0 0 12px rgba(255,255,255,0.15);

    animation: formFade 0.7s ease-out forwards;
    opacity: 0;
    transform: translateY(25px);
    position: relative;
    z-index: 1;
}

/* Fade animation */
@keyframes formFade {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Page Title */
form h2 {
    text-align: center;
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 25px;
    color: #ffffff;
    text-shadow: 0 3px 10px rgba(0,0,0,0.3);
}

/* Labels */
form label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    color: #eeeeee;
    font-size: 0.9rem;
}

/* Inputs */
form input {
    width: 100%;
    padding: 14px 16px;
    margin-bottom: 16px;
    border-radius: 12px;
    border: none;
    font-size: 0.95rem;
    background: rgba(255,255,255,0.85);
    transition: 0.25s;
    outline: none;
}

form input:focus {
    background: white;
    box-shadow: 0 0 0 3px rgba(121, 68, 255, 0.45);
}

/* Submit Button */
form button {
    width: 100%;
    padding: 14px 16px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #2575fc, #6a11cb);
    color: #fff;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    margin-top: 10px;
    transition: 0.3s ease;
}

form button:hover {
    transform: translateY(-3px);
    box-shadow: 0px 8px 20px rgba(0,0,0,0.25);
}

/* Back Button */
form a.btn.delete-btn {
    display: block;
    text-align: center;
    padding: 12px;
    margin-top: 15px;
    border-radius: 12px;
    background: rgba(255,255,255,0.2);
    color: white !important;
    font-weight: 600;
    backdrop-filter: blur(6px);
    transition: 0.25s;
}

form a.btn.delete-btn:hover {
    background: rgba(255,255,255,0.35);
}

/* Links */
form a {
    display: block;
    margin-top: 10px;
    text-align: center;
    color: #e0e0e0;
    font-size: 0.9rem;
    transition: 0.25s ease;
}

form a:hover {
    color: #ffffff;
    text-decoration: underline;
}

/* Error message */
.error {
    background: rgba(255, 0, 0, 0.15);
    border: 1px solid rgba(255, 0, 0, 0.4);
    border-radius: 12px;
    padding: 12px;
    color: #ff9a9a;
    font-weight: 600;
    text-align: center;
    margin-bottom: 10px;
}

/* Responsive Adjustments */
@media (max-width: 480px) {
    form {
        padding: 28px;
        border-radius: 16px;
    }

    form h2 {
        font-size: 1.8rem;
    }
}

</style>
<body style="">
<div id="particles-js"></div>
    <form action="register.php" method="POST"  >
    <h2>Register</h2>
    <p style="text-align: center"><?php
include 'includes/db.php'; // Database connection

if (isset($_POST['register'])) {
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password = password_hash(trim($_POST['password']), PASSWORD_BCRYPT); // Encrypt password

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        echo "Email is already registered!";
    } else {
        // Insert user into the database
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        if ($stmt->execute([$name, $email, $password])) {
            echo "Registration successful! <a href='login.php'>Login here</a>";
            exit();
        } else {
            echo "An error occurred. Please try again.";
        }
    }
}
?></p>
       
        <label for="name">Name</label>
        <input type="text" id="name" name="name" placeholder="Enter your name" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Enter your email" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter your password" required>

        <button type="submit" name="register">Sign Up</button>
        <a href="index.html" style="width: 100%" class="btn delete-btn">Go Back</a>
        <a href="login.php">Already have an account? Login here</a>

    </form>
    <script>
        particlesJS("particles-js", {
            particles: {
                number: {
                    value: 80,
                    density: { enable: true, value_area: 800 }
                },
                color: { value: "#ffffff" },
                shape: {
                    type: "circle",
                    stroke: { width: 0, color: "#000000" },
                    polygon: { nb_sides: 5 }
                },
                opacity: {
                    value: 0.5,
                    random: false,
                    anim: { enable: false, speed: 1, opacity_min: 0.1, sync: false }
                },
                size: {
                    value: 3,
                    random: true,
                    anim: { enable: false, speed: 40, size_min: 0.1, sync: false }
                },
                line_linked: {
                    enable: true,
                    distance: 150,
                    color: "#ffffff",
                    opacity: 0.4,
                    width: 1
                },
                move: {
                    enable: true,
                    speed: 6,
                    direction: "none",
                    random: false,
                    straight: false,
                    out_mode: "out",
                    bounce: false,
                    attract: { enable: false, rotateX: 600, rotateY: 1200 }
                }
            },
            interactivity: {
                detect_on: "canvas",
                events: {
                    onhover: { enable: true, mode: "repulse" },
                    onclick: { enable: true, mode: "push" },
                    resize: true
                },
                modes: {
                    grab: { distance: 400, line_linked: { opacity: 1 } },
                    bubble: { distance: 400, size: 40, duration: 2, opacity: 8, speed: 3 },
                    repulse: { distance: 200, duration: 0.4 },
                    push: { particles_nb: 4 },
                    remove: { particles_nb: 2 }
                }
            },
            retina_detect: true
        });
        
    </script>
</body>
</html>

