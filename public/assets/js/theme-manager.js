
class ThemeManager {
    constructor() {
        this.currentTheme = this.loadTheme();
        this.themes = {
            'light': {
                name: '浅色主题',
                color: '#0d6efd',
                description: '经典浅色主题'
            },
            'dark': {
                name: '深色主题',
                color: '#1a1d23',
                description: '护眼深色主题'
            },
            'blue': {
                name: '海洋蓝',
                color: '#1e40af',
                description: '清新海洋蓝主题'
            },
            'green': {
                name: '森林绿',
                color: '#059669',
                description: '自然森林绿主题'
            },
            'purple': {
                name: '典雅紫',
                color: '#7c3aed',
                description: '优雅紫色主题'
            },
            'orange': {
                name: '活力橙',
                color: '#ea580c',
                description: '活力橙色主题'
            }
        };

        this.init();
    }

    init() {
        this.applyTheme(this.currentTheme);
        this.createThemeSelector();
        this.bindEvents();


        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            mediaQuery.addListener(() => {
                if (this.currentTheme === 'auto') {
                    this.applySystemTheme();
                }
            });
        }
    }

    loadTheme() {
        return localStorage.getItem('theme') || 'light';
    }

    saveTheme(theme) {
        localStorage.setItem('theme', theme);
        this.currentTheme = theme;
    }

    applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);


        document.body.style.transition = 'all 0.3s ease';


        this.forceStyleRefresh();


        this.updateMetaThemeColor(theme);


        this.dispatchThemeChangeEvent(theme);


        setTimeout(() => {
            this.forceStyleRefresh();
        }, 50);
    }

    forceStyleRefresh() {

        document.body.classList.remove('bg-light', 'bg-dark', 'text-dark', 'text-light');


        const style = document.createElement('style');
        style.textContent = `
            * {
                color: var(--text-primary) !important;
            }
            .navbar * {
                color: var(--navbar-text) !important;
            }
            .card * {
                color: var(--text-primary) !important;
            }
            .table * {
                color: var(--text-primary) !important;
            }
            .form-control, .form-select {
                background-color: var(--card-bg) !important;
                color: var(--text-primary) !important;
                border-color: var(--border-color) !important;
            }
            .btn:not(.btn-primary):not(.btn-success):not(.btn-danger):not(.btn-warning):not(.btn-info) {
                color: var(--text-primary) !important;
            }
        `;
        document.head.appendChild(style);


        setTimeout(() => {
            if (style.parentNode) {
                style.parentNode.removeChild(style);
            }
        }, 100);


        document.body.classList.add('theme-transitioning');


        requestAnimationFrame(() => {

            document.body.offsetHeight;


            document.body.classList.remove('theme-transitioning');


            this.updateElementStyles();
        });
    }

    updateElementStyles() {

        const textElements = document.querySelectorAll('h1, h2, h3, h4, h5, h6, p, div, span, td, th, label, small');
        textElements.forEach(el => {
            if (!el.classList.contains('text-white') && !el.closest('.btn-primary, .btn-success, .btn-danger, .btn-warning, .btn-info, .badge, .alert')) {
                el.style.setProperty('color', 'var(--text-primary)', 'important');
            }
        });


        const formElements = document.querySelectorAll('.form-control, .form-select, input, textarea, select');
        formElements.forEach(el => {
            el.style.setProperty('background-color', 'var(--card-bg)', 'important');
            el.style.setProperty('color', 'var(--text-primary)', 'important');
            el.style.setProperty('border-color', 'var(--border-color)', 'important');
        });


        const cards = document.querySelectorAll('.card, .card-body, .card-header, .card-footer');
        cards.forEach(card => {
            card.style.setProperty('background-color', 'var(--card-bg)', 'important');
            card.style.setProperty('border-color', 'var(--card-border)', 'important');
            card.style.setProperty('color', 'var(--text-primary)', 'important');
        });


        const cardElements = document.querySelectorAll('.card *, .card-body *, .card-header *');
        cardElements.forEach(el => {
            if (!el.classList.contains('btn') && !el.classList.contains('badge') && !el.classList.contains('alert')) {
                el.style.setProperty('color', 'var(--text-primary)', 'important');
            }
        });


        const tables = document.querySelectorAll('.table');
        tables.forEach(table => {
            table.style.setProperty('color', 'var(--text-primary)', 'important');
        });


        const navbars = document.querySelectorAll('.navbar');
        navbars.forEach(navbar => {
            navbar.style.setProperty('background-color', 'var(--navbar-bg)', 'important');
            const navLinks = navbar.querySelectorAll('.nav-link, .navbar-brand');
            navLinks.forEach(link => {
                link.style.setProperty('color', 'var(--navbar-text)', 'important');
            });


            const togglerIcons = navbar.querySelectorAll('.navbar-toggler-icon');
            togglerIcons.forEach(icon => {

                icon.style.setProperty('background-image', 'none', 'important');


                const computedStyle = getComputedStyle(document.documentElement);
                const navbarTextColor = computedStyle.getPropertyValue('--navbar-text').trim();


                if (!document.getElementById('dynamic-toggler-style')) {
                    const style = document.createElement('style');
                    style.id = 'dynamic-toggler-style';
                    document.head.appendChild(style);
                }

                const styleSheet = document.getElementById('dynamic-toggler-style');
                styleSheet.textContent = `
                    .navbar-toggler-icon::before {
                        background: var(--navbar-text) !important;
                        box-shadow: 0 -6px 0 var(--navbar-text), 0 6px 0 var(--navbar-text) !important;
                    }
                `;
            });
        });
    }

    updateMetaThemeColor(theme) {
        let themeColor = '#0d6efd';

        if (this.themes[theme]) {
            themeColor = this.themes[theme].color;
        }


        let metaTheme = document.querySelector('meta[name="theme-color"]');
        if (!metaTheme) {
            metaTheme = document.createElement('meta');
            metaTheme.name = 'theme-color';
            document.head.appendChild(metaTheme);
        }
        metaTheme.content = themeColor;
    }

    dispatchThemeChangeEvent(theme) {
        const event = new CustomEvent('themeChange', {
            detail: { theme, themes: this.themes }
        });
        document.dispatchEvent(event);
    }

    createThemeSelector() {

        if (document.querySelector('.theme-selector')) {
            return;
        }

        const selector = document.createElement('div');
        selector.className = 'theme-selector';
        selector.innerHTML = `
            <button class="btn btn-outline-secondary btn-sm" id="themeToggle" title="切换主题">
                <i class="bi bi-palette"></i>
                <span class="theme-icon-fallback" style="display: none;">🎨</span>
            </button>
            <div class="theme-options" id="themeOptions">
                <div class="mb-2">
                    <small class="theme-options-title" style="font-weight: bold; color: inherit !important;">选择主题</small>
                </div>
                ${Object.entries(this.themes).map(([key, theme]) => `
                    <div class="theme-option ${this.currentTheme === key ? 'active' : ''}" data-theme="${key}">
                        <div class="theme-color" style="background-color: ${theme.color}"></div>
                        <div class="theme-option-content">
                            <div class="theme-option-name" style="font-weight: 500; color: inherit !important;">${theme.name}</div>
                            <small class="theme-option-desc" style="color: inherit !important; opacity: 0.7;">${theme.description}</small>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;


        const navbar = document.querySelector('.navbar .navbar-nav');
        if (navbar) {
            const li = document.createElement('li');
            li.className = 'nav-item ms-2';
            li.appendChild(selector);
            navbar.appendChild(li);


            setTimeout(() => {
                const themeOptions = selector.querySelectorAll('.theme-option');
                themeOptions.forEach(option => {
                    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
                    const textColor = currentTheme === 'dark' ? '#f8fafc' : '#1e293b';
                    const descColor = currentTheme === 'dark' ? '#cbd5e1' : '#64748b';

                    const nameEl = option.querySelector('.theme-option-name');
                    const descEl = option.querySelector('.theme-option-desc');

                    if (nameEl) nameEl.style.color = textColor;
                    if (descEl) descEl.style.color = descColor;
                });

            }, 100);


            setTimeout(() => {
                const icon = selector.querySelector('.bi-palette');
                const fallback = selector.querySelector('.theme-icon-fallback');

                if (icon && fallback) {

                    const computedStyle = window.getComputedStyle(icon, '::before');
                    const content = computedStyle.getPropertyValue('content');


                    if (!content || content === 'none' || content === '""') {
                        icon.style.display = 'none';
                        fallback.style.display = 'inline';
                    }
                }
            }, 100);
        }
    }

    bindEvents() {
        document.addEventListener('click', (e) => {
            const themeToggle = document.getElementById('themeToggle');
            const themeOptions = document.getElementById('themeOptions');

            if (e.target.closest('#themeToggle')) {
                e.preventDefault();
                themeOptions.classList.toggle('show');
            } else if (e.target.closest('.theme-option')) {
                const theme = e.target.closest('.theme-option').dataset.theme;
                this.changeTheme(theme);
                themeOptions.classList.remove('show');
            } else if (!e.target.closest('.theme-selector')) {
                themeOptions?.classList.remove('show');
            }
        });


        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.shiftKey && e.key === 'T') {
                e.preventDefault();
                this.toggleQuickTheme();
            }
        });
    }

    changeTheme(theme) {
        if (theme !== this.currentTheme) {
            this.saveTheme(theme);
            this.applyTheme(theme);
            this.updateActiveThemeOption(theme);


            this.updateThemeOptionsColors();


            setTimeout(() => {
                this.forceCompleteRefresh();
                this.updateThemeOptionsColors();
            }, 200);


            if (window.notify) {
                window.notify.success(`已切换到${this.themes[theme].name}`);
            }
        }
    }

    updateThemeOptionsColors() {
        setTimeout(() => {
            const themeOptions = document.querySelectorAll('.theme-option');
            themeOptions.forEach(option => {
                const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
                const textColor = currentTheme === 'dark' ? '#f8fafc' : '#1e293b';
                const descColor = currentTheme === 'dark' ? '#cbd5e1' : '#64748b';

                const nameEl = option.querySelector('.theme-option-name');
                const descEl = option.querySelector('.theme-option-desc');

                if (nameEl) {
                    nameEl.style.color = textColor + ' !important';
                    nameEl.style.setProperty('color', textColor, 'important');
                }
                if (descEl) {
                    descEl.style.color = descColor + ' !important';
                    descEl.style.setProperty('color', descColor, 'important');
                }
            });

        }, 50);
    }

    forceCompleteRefresh() {

        const html = document.documentElement;
        const currentTheme = html.getAttribute('data-theme');


        html.removeAttribute('data-theme');


        html.offsetHeight;


        html.setAttribute('data-theme', currentTheme);


        this.updateElementStyles();


        document.body.style.display = 'none';
        document.body.offsetHeight;
        document.body.style.display = '';
    }

    updateActiveThemeOption(theme) {
        document.querySelectorAll('.theme-option').forEach(option => {
            option.classList.remove('active');
        });

        const activeOption = document.querySelector(`[data-theme="${theme}"]`);
        if (activeOption) {
            activeOption.classList.add('active');
        }
    }

    toggleQuickTheme() {

        const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
        this.changeTheme(newTheme);
    }

    getCurrentTheme() {
        return this.currentTheme;
    }

    getThemeInfo(theme = this.currentTheme) {
        return this.themes[theme] || this.themes['light'];
    }


    getThemeVariable(varName) {
        return getComputedStyle(document.documentElement).getPropertyValue(varName).trim();
    }


    setThemeVariable(varName, value) {
        document.documentElement.style.setProperty(varName, value);
    }
}


class ResponsiveManager {
    constructor() {
        this.breakpoints = {
            xs: 0,
            sm: 576,
            md: 768,
            lg: 992,
            xl: 1200,
            xxl: 1400
        };

        this.init();
    }

    init() {
        this.handleResize();
        window.addEventListener('resize', this.debounce(() => {
            this.handleResize();
        }, 250));

        this.optimizeTable();
        this.optimizeNavigation();
        this.optimizeCards();
    }

    handleResize() {
        const width = window.innerWidth;
        const currentBreakpoint = this.getCurrentBreakpoint(width);

        document.body.setAttribute('data-breakpoint', currentBreakpoint);


        const event = new CustomEvent('breakpointChange', {
            detail: { width, breakpoint: currentBreakpoint }
        });
        document.dispatchEvent(event);
    }

    getCurrentBreakpoint(width = window.innerWidth) {
        for (const [name, size] of Object.entries(this.breakpoints).reverse()) {
            if (width >= size) {
                return name;
            }
        }
        return 'xs';
    }

    optimizeTable() {
        const tables = document.querySelectorAll('.table-responsive');
        tables.forEach(table => {

            if (!table.querySelector('.scroll-hint')) {
                const hint = document.createElement('div');
                hint.className = 'scroll-hint text-muted small mt-1 d-md-none';
                hint.innerHTML = '<i class="bi bi-arrow-left-right"></i> 左右滑动查看更多';
                table.parentNode.insertBefore(hint, table.nextSibling);
            }
        });
    }

    optimizeNavigation() {
        const navbar = document.querySelector('.navbar');
        if (!navbar) return;


        const navToggler = navbar.querySelector('.navbar-toggler');
        const navCollapse = navbar.querySelector('.navbar-collapse');

        if (navToggler && navCollapse) {
            navToggler.addEventListener('click', () => {
                navCollapse.classList.toggle('show');
            });


            navCollapse.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 992) {
                        navCollapse.classList.remove('show');
                    }
                });
            });
        }
    }

    optimizeCards() {
        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {

            card.addEventListener('touchstart', () => {
                card.style.transform = 'scale(0.98)';
            });

            card.addEventListener('touchend', () => {
                card.style.transform = '';
            });
        });
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}


class AnimationManager {
    constructor() {
        this.init();
    }

    init() {
        this.addPageLoadAnimation();
        this.addScrollAnimations();
        this.addHoverEffects();
    }

    addPageLoadAnimation() {

        const cards = document.querySelectorAll('.card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';

            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    addScrollAnimations() {

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.card, .table').forEach(el => {
            observer.observe(el);
        });
    }

    addHoverEffects() {

        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('mouseenter', () => {
                btn.style.transform = 'translateY(-2px)';
            });

            btn.addEventListener('mouseleave', () => {
                btn.style.transform = '';
            });
        });
    }
}


document.addEventListener('DOMContentLoaded', () => {

    window.themeManager = new ThemeManager();


    window.responsiveManager = new ResponsiveManager();


    window.animationManager = new AnimationManager();

    console.log('🎨 主题系统已初始化');
    console.log('📱 响应式管理器已启动');
    console.log('✨ 动画效果已加载');
});


window.ThemeManager = ThemeManager;
window.ResponsiveManager = ResponsiveManager;
window.AnimationManager = AnimationManager;