/**
 * Mobile Sidebar Toggle
 * Управление выезжающим сайдбаром на мобильных устройствах
 */
document.addEventListener('DOMContentLoaded', function() {
    const menuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (!menuBtn || !sidebar || !overlay) return;

    // Открыть/закрыть сайдбар
    function toggleSidebar() {
        menuBtn.classList.toggle('active');
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
        
        // Блокируем скролл body когда сайдбар открыт
        document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
    }

    // Закрыть сайдбар
    function closeSidebar() {
        menuBtn.classList.remove('active');
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Клик по кнопке меню
    menuBtn.addEventListener('click', toggleSidebar);

    // Клик по overlay закрывает сайдбар
    overlay.addEventListener('click', closeSidebar);

    // Закрытие по Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });

    // Закрываем сайдбар при клике на ссылку навигации (для SPA-like поведения)
    sidebar.querySelectorAll('.sidebar-nav__link').forEach(function(link) {
        link.addEventListener('click', function() {
            // Небольшая задержка чтобы пользователь видел что нажал
            setTimeout(closeSidebar, 150);
        });
    });

    // При ресайзе окна закрываем сайдбар если перешли на десктоп
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });
});
