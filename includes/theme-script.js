// Theme Management Script - Matches React ThemeContext EXACTLY
const themes = {
    light: { name: 'Light', icon: '☀️' },
    dark: { name: 'Dark', icon: '🌙' },
    blue: { name: 'Ocean Blue', icon: '🌊' },
    purple: { name: 'Royal Purple', icon: '💜' },
    green: { name: 'Forest Green', icon: '🌿' },
    orange: { name: 'Sunset Orange', icon: '🧡' }
};

// Load saved theme
function loadTheme() {
    const savedTheme = localStorage.getItem('church-theme') || 'light';
    applyTheme(savedTheme);
}

// Apply theme
function applyTheme(theme) {
    const root = document.documentElement;
    
    // Remove all theme classes
    root.classList.remove('light', 'dark', 'theme-blue', 'theme-purple', 'theme-green', 'theme-orange');
    
    // Add the new theme class
    if (theme === 'dark') {
        root.classList.add('dark');
    } else if (theme !== 'light') {
        root.classList.add(`theme-${theme}`);
    }
    
    // Update button
    const themeIcon = document.getElementById('theme-icon');
    const themeName = document.getElementById('theme-name');
    if (themeIcon) themeIcon.textContent = themes[theme].icon;
    if (themeName) themeName.textContent = themes[theme].name;
    
    // Update active state in menu
    document.querySelectorAll('.theme-option').forEach(option => {
        if (option.dataset.theme === theme) {
            option.style.background = 'var(--primary)';
            option.style.color = 'var(--primary-foreground)';
        } else {
            option.style.background = 'transparent';
            option.style.color = 'var(--foreground)';
        }
    });
    
    // Save to localStorage
    localStorage.setItem('church-theme', theme);
}

// Change theme
function changeTheme(theme) {
    applyTheme(theme);
    toggleThemeMenu();
}

// Toggle theme menu
function toggleThemeMenu() {
    const menu = document.getElementById('theme-menu');
    if (menu) {
        menu.classList.toggle('hidden');
    }
}

// Close theme menu when clicking outside
document.addEventListener('click', function(event) {
    const menu = document.getElementById('theme-menu');
    const button = document.getElementById('theme-button');
    
    if (menu && button && !menu.contains(event.target) && !button.contains(event.target)) {
        menu.classList.add('hidden');
    }
});

// Initialize theme on page load
document.addEventListener('DOMContentLoaded', loadTheme);