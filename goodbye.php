<?php
session_start();
$goodbyeMessage = isset($_SESSION['goodbye_message']) ? $_SESSION['goodbye_message'] : "Goodbye! See you soon.";

// Clear the session after retrieving the message
session_unset();
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Goodbye from Elevate</title>
   <script src="particles.min.js"></script>
    <style>
       /* General Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
}

/* Body */
body {
    font-family: 'Roboto', sans-serif;
    background: linear-gradient(to bottom, #2d2a4a, #1e1c39);
    color: #fff;
    overflow-x: hidden;
}



#particles-js {
    position: absolute;
    width: 100%;
    height: 100%;
    display: flex;
    top: 0;
    left: 0;
    z-index: 0; /* Behind other elements */
    background: linear-gradient(to bottom right,#6a11cb, #2575fc); /* Fallback */
}
        h1 {
            position: relative;
    z-index: 1; /* Above particles */
    align-items: center;
    justify-content: center;
    padding: 100px 50px;
    color: white;
    min-height: 100vh;
    overflow: hidden;
    display: flex;
    text-align: left;
    font-size: 4rem;
    font-weight: bold;
    line-height: 1.2;
        }

        @keyframes blinkCursor {
            0%, 100% { border-color: transparent; }
            50% { border-color: #FFD700; }
        }
    </style>
</head>
<body>
<div id="particles-js"></div>
    <h1 id="goodbyeText"></h1>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const message = "<?php echo addslashes($goodbyeMessage); ?>";
            const goodbyeText = document.getElementById("goodbyeText");
            let index = 0;

            // Typewriter effect
            function typeWriter() {
                if (index < message.length) {
                    goodbyeText.textContent += message.charAt(index);
                    index++;
                    setTimeout(typeWriter, 100); // Typing speed (sync with voice)
                }
            }

            // Start both typewriter and voice together
            typeWriter();

            const utterance = new SpeechSynthesisUtterance(message);
            utterance.lang = "en-US";
            utterance.rate = 1;   // Normal speaking speed
            utterance.pitch = 1.1; // Slightly cheerful tone

            speechSynthesis.speak(utterance);

            // Redirect after the message is read aloud
            utterance.onend = function () {
                window.location.href = "login.php";
            };
        });
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