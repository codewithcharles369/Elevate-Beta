document.getElementById('register-form').addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('register_action.php', {
        method: 'POST',
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            window.location.href = 'login.php';
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error:', error));
});

document.getElementById('login-form').addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('login_action.php', {
        method: 'POST',
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            window.location.href = 'dashboard.php';
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error:', error));
});