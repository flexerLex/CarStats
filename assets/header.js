fetch('Templates/header.html')
    .then((response) => response.text())
    .then((html) => {
        document.getElementById('header-placeholder').innerHTML = html;

        // Dynamic highlighting of active link
        const currentPage = window.location.pathname.split('/').pop();
        document.querySelectorAll('.site-header__nav-link').forEach(link => {
            const linkPage = link.getAttribute('href');
            if (linkPage === currentPage) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    })
    .catch((error) => console.error('Error loading header:', error));