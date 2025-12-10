fetch('../../Templates/header.html')
    .then((response) => response.text())
    .then((html) => {

        document.getElementById('header-placeholder').innerHTML = html;

        const currentPath = window.location.pathname; 
        const currentFileName = currentPath.split('/').pop(); 
        
        document.querySelectorAll('.site-header__nav-link').forEach(link => {
            const linkHref = link.getAttribute('href'); 
          
            const linkFileName = linkHref.split('/').pop();

            if (linkFileName === currentFileName && currentFileName !== "") {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
        
        const navToggle = document.getElementById('site-header__nav-toggle');
        const overlay = document.querySelector('.site-header__overlay');
        const navLinks = document.querySelectorAll('.site-header__nav-link');

        if (overlay) {
            overlay.addEventListener('click', () => {
                navToggle.checked = false;
            });
        }
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                navToggle.checked = false;
            });
        });

    })
    .catch((error) => console.error('Error loading header:', error));