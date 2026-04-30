<div class="relative inline-block">
    <button
        onclick="toggleThemeMenu()"
        id="theme-button"
        class="flex items-center gap-2 px-3 py-2 rounded-lg transition-colors"
        style="background: var(--card); border: 1px solid var(--border);"
        onmouseover="this.style.background='var(--accent)';"
        onmouseout="this.style.background='var(--card)';"
    >
        <span id="theme-icon">☀️</span>
        <span class="hidden sm:inline" id="theme-name">Light</span>
    </button>

    <div id="theme-menu" class="hidden absolute right-0 mt-2 w-48 rounded-lg shadow-lg z-20" style="background: var(--card); border: 1px solid var(--border);">
        <div class="p-2">
            <div class="text-sm p-2" style="color: var(--muted-foreground); font-weight: var(--font-weight-medium);">Choose Theme</div>
            <button onclick="changeTheme('light')" data-theme="light" class="theme-option w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-colors" style="color: var(--foreground);" onmouseover="this.style.background='var(--accent)';" onmouseout="this.style.background='transparent';">
                <span>☀️</span>
                <span>Light</span>
            </button>
            <button onclick="changeTheme('dark')" data-theme="dark" class="theme-option w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-colors" style="color: var(--foreground);" onmouseover="this.style.background='var(--accent)';" onmouseout="this.style.background='transparent';">
                <span>🌙</span>
                <span>Dark</span>
            </button>
            <button onclick="changeTheme('blue')" data-theme="blue" class="theme-option w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-colors" style="color: var(--foreground);" onmouseover="this.style.background='var(--accent)';" onmouseout="this.style.background='transparent';">
                <span>🌊</span>
                <span>Ocean Blue</span>
            </button>
            <button onclick="changeTheme('purple')" data-theme="purple" class="theme-option w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-colors" style="color: var(--foreground);" onmouseover="this.style.background='var(--accent)';" onmouseout="this.style.background='transparent';">
                <span>💜</span>
                <span>Royal Purple</span>
            </button>
            <button onclick="changeTheme('green')" data-theme="green" class="theme-option w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-colors" style="color: var(--foreground);" onmouseover="this.style.background='var(--accent)';" onmouseout="this.style.background='transparent';">
                <span>🌿</span>
                <span>Forest Green</span>
            </button>
            <button onclick="changeTheme('orange')" data-theme="orange" class="theme-option w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-colors" style="color: var(--foreground);" onmouseover="this.style.background='var(--accent)';" onmouseout="this.style.background='transparent';">
                <span>🧡</span>
                <span>Sunset Orange</span>
            </button>
        </div>
    </div>
</div>