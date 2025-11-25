
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login</title>
   <script src="particles.min.js"></script>
    <link rel="icon" href="assets/elevate.jpg" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        
/* ================================
   🔥 PREMIUM LOGIN PAGE UPGRADE 🔥
   ================================ */

/* Background */
body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #6a11cb, #2575fc);
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
}

/* Particles Layer */
#particles-js {
    position: absolute;
    width: 100%;
    height: 100%;
    z-index: 0;
}

/* Login Form Container */
form {
    width: 100%;
    max-width: 420px;
    padding: 35px;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(18px) saturate(160%);
    -webkit-backdrop-filter: blur(18px) saturate(160%);
    border: 1px solid rgba(255, 255, 255, 0.25);

    box-shadow:
        0 12px 35px rgba(0,0,0,0.25),
        inset 0 0 12px rgba(255,255,255,0.12);

    z-index: 2;
    animation: fadeIn 0.8s ease-out forwards;
    transform: translateY(20px);
}

/* Fade Animation */
@keyframes fadeIn {
    to {
        opacity: 1;
        transform: translateY(0px);
    }
}

/* Form Title */
form h2 {
    text-align: center;
    margin-bottom: 25px;
    font-size: 2rem;
    font-weight: 800;
    letter-spacing: 1px;
    color: #ffffff;
    text-shadow: 0 3px 10px rgba(0,0,0,0.3);
}

/* Labels */
form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #f0f0f0;
}

/* Inputs */
form input {
    width: 100%;
    padding: 14px 16px;
    border-radius: 12px;
    border: none;
    background: rgba(255,255,255,0.85);
    font-size: 0.95rem;
    outline: none;
    transition: 0.25s ease;
}

form input:focus {
    background: white;
    box-shadow: 0 0 0 3px rgba(121, 68, 255, 0.45);
}

/* Buttons */
form button {
    width: 100%;
    margin-top: 10px;
    padding: 14px 16px;
    font-size: 1rem;
    font-weight: bold;
    border-radius: 12px;
    background: linear-gradient(135deg, #2575fc, #6a11cb);
    border: none;
    color: white;
    cursor: pointer;
    transition: 0.3s ease;
}

form button:hover {
    transform: translateY(-3px);
    box-shadow: 0px 8px 20px rgba(0,0,0,0.25);
}

/* Back Button */
form a.btn.delete-btn {
    background: rgba(255,255,255,0.25);
    text-align: center;
    padding: 12px;
    display: block;
    border-radius: 12px;
    margin-top: 15px;
    font-weight: 600;
    backdrop-filter: blur(6px);
    color: #fff !important;
    transition: 0.3s ease;
}

form a.btn.delete-btn:hover {
    background: rgba(255,255,255,0.4);
}

/* Auth Links */
form a {
    text-align: center;
    display: block;
    margin-top: 12px;
    color: #e6e6e6;
    font-size: 0.9rem;
    transition: 0.25s;
}

form a:hover {
    color: #ffffff;
    text-decoration: underline;
}

/* Error Box */
.error {
    padding: 12px;
    margin-bottom: 15px;
    color: #ff6e6e;
    font-weight: bold;
    text-align: center;
    background: rgba(255,0,0,0.1);
    border-radius: 12px;
    border: 1px solid rgba(255,0,0,0.3);
}

/* Mobile Optimization */
@media (max-width: 480px) {
    form {
        padding: 25px;
        border-radius: 16px;
    }

    form h2 {
        font-size: 1.7rem;
    }
}
    </style>
</head>
<body style="height: 100vh;">
<div id="particles-js"></div>
    
    <!-- Display Error Messages -->
    
    <form action="login.php" method="POST" >
    <h2>Login</h2>
    <?php
session_start();
include 'includes/db.php'; // Database connection

$error = ''; // Initialize error variable

if (isset($_POST['login'])) {
    $email = htmlspecialchars(trim($_POST['email']));
    $password = trim($_POST['password']);

    // Fetch user details
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Check if the account is deactivated
        if ($user['is_active'] == 0) {
            // Calculate time difference since deactivation
            $deactivatedAt = new DateTime($user['deactivated_at']);
            $now = new DateTime();
            $interval = $deactivatedAt->diff($now);

            if ($interval->days > 7) {
                
                 // Delete from the `follows` table (both follower_id and following_id)
        $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? OR following_id = ?");
        $stmt->execute([$user['id'], $user['id']]);

        // Delete from the `notifications` table (both user_id and sender_id)
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ? OR sender_id = ?");
        $stmt->execute([$user['id'], $user['id']]);

        // Delete from the `messages` table (both sender_id and receiver_id)
        $stmt = $pdo->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?");
        $stmt->execute([$user['id'], $user['id']]);

         // Delete from the `messages` table (both sender_id and receiver_id)
         $stmt = $pdo->prepare("DELETE FROM comment_likes WHERE user_id = ?");
         $stmt->execute([$user['id']]);

        // Delete from all other tables using `user_id`
        $tables = [
            'bookmarks', 'comments',  'comment_replies',
             'group_members', 'group_posts', 
            'group_post_comments', 'group_post_likes',
            'likes', 'post_views', 
            'reports', 'todos'
        ];
        foreach ($tables as $table) {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE user_id = ?");
            $stmt->execute([$user['id']]);
        }

        // Delete the user from the `users` table
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);

                $error = 'Your account has been deleted due to inactivity after deactivation.';
            } else {
                // Reactivate the account if within the grace period
                $reactivateStmt = $pdo->prepare("UPDATE users SET is_active = 1, deactivated_at = NULL WHERE id = ?");
                $reactivateStmt->execute([$user['id']]);

                $_SESSION['user_id'] = $user['id'];
                $error = 'Your account has been reactivated. Welcome back!';
            }
        } else {
            // Check the password
            if (password_verify($password, $user['password'])) {
                // Password is correct
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['image'] = $user['profile_picture'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['is_admin'] = $user['is_admin'];

                if ($user['first_login']) {
                    // First-time login welcome message
                    $_SESSION['welcome_message'] = "Welcome to Elevate, " . htmlspecialchars($user['name']) . "! Ready to share your next big idea?";
                    
                    // Mark first login as complete
                    $update = $pdo->prepare("UPDATE users SET first_login = 0 WHERE id = ?");
                    $update->execute([$user['id']]);
                } else {
                    // Welcome back message with a random tip
                    $tips = [
                        "Tip: Stay consistent. Even small progress adds up over time!",
                        "Tip: Engage with others—collaboration sparks creativity.",
                        "Tip: Bookmark your favorite posts to revisit ideas anytime.",
                        "Tip: Take short breaks to boost productivity!",
                        "Tip: Reflect on your posts to see how far you've grown."
                    ];
                    $randomTip = $tips[array_rand($tips)];
            
                    $_SESSION['welcome_back_message'] = "Welcome back, " . htmlspecialchars($user['name']) . "! " . $randomTip;
                }

                header("Location: admin_dashboard.php"); // Redirect to dashboard
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        }
    } else {
        $error = 'No account found with this email.';
    }
}
?>
    <?php if (!empty($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>

    

    
    
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" placeholder="Enter your email" required><br><br>

    <label for="password">Password:</label>
    <input type="password" id="password" name="password" placeholder="Enter your password" required><br><br>

    <button type="submit" name="login">Login</button>
    <a href="index.html" style="width: 100%" class="btn delete-btn">Go Back</a>
    <a href="register.php">Don't have an account? Register now</a>
        
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

