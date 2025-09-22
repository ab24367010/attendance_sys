const themeToggleBtn = document.getElementById('themeToggleBtn');
        const themeStylesheet = document.getElementById('themeStylesheet');

        // Check for stored theme preference
        if (localStorage.getItem('theme') === 'dark') {
            themeStylesheet.href = 'public/css/style-dark.css';
            themeToggleBtn.textContent = 'Light Theme';
        }

        themeToggleBtn.addEventListener('click', () => {
            // Toggle between themes
            if (themeStylesheet.href.includes('style-light.css')) {
                themeStylesheet.href = 'public/css/style-dark.css';
                themeToggleBtn.textContent = 'Light Theme';
                localStorage.setItem('theme', 'dark');
            } else {
                themeStylesheet.href = 'public/css/style-light.css';
                themeToggleBtn.textContent = 'Dark Theme';
                localStorage.setItem('theme', 'light');
            }
        });