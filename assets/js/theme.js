const themeToggleBtn = document.getElementById('themeToggleBtn');
const themeStylesheet = document.getElementById('themeStylesheet');

if (!themeToggleBtn || !themeStylesheet) {
    console.error('Theme toggle elements not found');
} else {
    // Get the current stylesheet path to determine base path
    const getCurrentPath = () => themeStylesheet.getAttribute('href');

    const togglePath = (currentPath, newFile) => {
        // Replace the filename (light.css or dark.css) while keeping the path
        return currentPath.replace(/(light|dark)\.css$/, newFile);
    };

    // Check for stored theme preference
    if (localStorage.getItem('theme') === 'dark') {
        themeStylesheet.href = togglePath(getCurrentPath(), 'dark.css');
        themeToggleBtn.textContent = 'Light Theme';
    }

    themeToggleBtn.addEventListener('click', () => {
        const currentPath = getCurrentPath();

        // Toggle between themes
        if (currentPath.includes('light.css')) {
            themeStylesheet.href = togglePath(currentPath, 'dark.css');
            themeToggleBtn.textContent = 'Light Theme';
            localStorage.setItem('theme', 'dark');
        } else {
            themeStylesheet.href = togglePath(currentPath, 'light.css');
            themeToggleBtn.textContent = 'Dark Theme';
            localStorage.setItem('theme', 'light');
        }
    });
}
