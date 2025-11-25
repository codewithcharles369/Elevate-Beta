let start = 0; // Starting point for posts
const limit = 2; // Number of posts to load at a time
const postsContainer = document.getElementById('posts-container');
const loadMoreButton = document.getElementById('load-more');
const commentForm = document.getElementById('comment-form');
if (commentForm) {
    commentForm.addEventListener('submit', function (e) {
        e.preventDefault(); // Prevent the page reload

        const formData = new FormData(commentForm);

        fetch('php/add_comment.php', {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Optionally update the comments section without reloading
                const commentSection = document.querySelector('section'); // Select the section that contains comments
                const newComment = document.createElement('div');
                newComment.classList.add('comment');
                newComment.innerHTML = `
                    <p><strong>You:</strong></p>
                    <p>${data.comment}</p> <!-- Adjust this based on the server's response -->
                    <small>Posted just now</small>
                    <hr>
                `;
                commentSection.appendChild(newComment);
                // Optionally clear the form
                document.getElementById('comment').value = '';
            } else {
                alert(data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    });
}
// Function to fetch posts
function fetchPosts() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `php/fetch_posts.php?start=${start}`, true);
    xhr.onload = function () {
        if (this.status === 200) {
            const posts = JSON.parse(this.responseText);

            // Check if posts exist
            if (posts.length > 0) {
                posts.forEach(post => {
                    const blogCard = document.createElement('div');
                    blogCard.className = 'blog-card';
                    blogCard.innerHTML = `
                        <img src="uploads/${post.image}" alt="${post.image}">
                        
                    `;
                    const blogContent = document.createElement('div');
                    blogContent.className = 'blog-content';
                    blogContent.innerHTML = `
                        <h2>${post.title}</h2>
                        <p>${post.content.substring(0, 150)}...</p>
                        <a href="single_post.php?id=${post.id}" class="btn">Read More</a>
                    `;
                    postsContainer.appendChild(blogCard);
                    blogCard.appendChild(blogContent);
                });

                // Increment start for the next fetch
                start += limit;
            } else {
                loadMoreButton.style.display = 'none';
            }
        }
    };
    xhr.send();
}

// Initial fetch
fetchPosts();

// Load more posts on button click
loadMoreButton.addEventListener('click', fetchPosts);

