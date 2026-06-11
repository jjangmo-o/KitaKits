<?php
require_once(__DIR__ . '/../api/_auth.php');

if (!function_exists('kk_html')) {
    function kk_html($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('kk_route')) {
    function kk_route($section, $key)
    {
        $routes = [
            'root' => [
                'home' => 'index.php',
                'my_bookings' => 'pages/my_bookings.php',
                'portal' => 'pages/patient_portal.php',
                'guide' => 'pages/patient_guide.php',
                'faq' => 'pages/faq.php',
                'about' => 'pages/about_cataracts.php',
                'login' => 'pages/login.php',
                'register' => 'pages/register.php',
                'logout' => 'pages/logout.php',
                'admin' => 'admin/admin_dashboard.php',
                'admin_login' => 'admin/login.php',
                'admin_logout' => 'admin/logout.php',
                'asset' => 'assets/',
            ],
            'pages' => [
                'home' => '../index.php',
                'my_bookings' => 'my_bookings.php',
                'portal' => 'patient_portal.php',
                'guide' => 'patient_guide.php',
                'faq' => 'faq.php',
                'about' => 'about_cataracts.php',
                'login' => 'login.php',
                'register' => 'register.php',
                'logout' => 'logout.php',
                'admin' => '../admin/admin_dashboard.php',
                'admin_login' => '../admin/login.php',
                'admin_logout' => '../admin/logout.php',
                'asset' => '../assets/',
            ],
            'admin' => [
                'home' => '../index.php',
                'my_bookings' => '../pages/my_bookings.php',
                'portal' => '../pages/patient_portal.php',
                'guide' => '../pages/patient_guide.php',
                'faq' => '../pages/faq.php',
                'about' => '../pages/about_cataracts.php',
                'login' => '../pages/login.php',
                'register' => '../pages/register.php',
                'logout' => '../pages/logout.php',
                'admin' => 'admin_dashboard.php',
                'admin_login' => 'login.php',
                'admin_logout' => 'logout.php',
                'asset' => '../assets/',
            ],
        ];

        return $routes[$section][$key] ?? '#';
    }
}

if (!function_exists('kk_nav_link')) {
    function kk_nav_link($section, $active, $key, $label)
    {
        $is_active = $active === $key;
        $class = 'make-nav-link' . ($is_active ? ' is-active' : '');
        echo '<a class="' . $class . '" href="' . kk_html(kk_route($section, $key)) . '">' . kk_html($label) . '</a>';
    }
}

if (!function_exists('kk_render_header')) {
    function kk_render_header($options = [])
    {
        $section = $options['section'] ?? 'pages';
        $active = $options['active'] ?? '';
        $mode = $options['mode'] ?? 'patient';
        $no_print = !empty($options['no_print']);
        $header_class = 'make-header' . ($mode === 'admin' ? ' make-admin-header' : '') . ($no_print ? ' no-print' : '');
        $asset = kk_route($section, 'asset');
        $patient = function_exists('current_patient_user') ? current_patient_user() : null;
        $admin = function_exists('current_admin_user') ? current_admin_user() : null;

        if ($mode === 'admin') {
            $admin_links = [
                ['key' => 'admin', 'label' => 'Missions', 'href' => kk_route($section, 'admin') . '#admin-missions'],
                ['key' => 'add_mission', 'label' => 'Add Mission', 'href' => 'add_mission.php'],
                ['key' => 'bookings', 'label' => 'Bookings', 'href' => kk_route($section, 'admin') . '#admin-bookings'],
                ['key' => 'patients', 'label' => 'Patients', 'href' => kk_route($section, 'admin') . '#admin-patients'],
                ['key' => 'content', 'label' => 'Content', 'href' => kk_route($section, 'admin') . '#admin-content'],
            ];
            ?>
            <div class="<?php echo $no_print ? 'no-print ' : ''; ?>admin-shell">
                <aside class="admin-sidebar" aria-label="Admin navigation">
                    <a href="<?php echo kk_html(kk_route($section, 'admin')); ?>" class="admin-sidebar-brand">
                        <span class="admin-sidebar-logo"><img src="<?php echo kk_html($asset); ?>images/logo.png" alt="KitaKits Logo"></span>
                        <span>
                            <strong>KitaKits</strong>
                            <small>ADMIN</small>
                        </span>
                    </a>

                    <nav class="admin-sidebar-nav">
                        <?php foreach ($admin_links as $link): ?>
                            <a class="<?php echo $active === $link['key'] ? 'is-active' : ''; ?>" href="<?php echo kk_html($link['href']); ?>">
                                <?php echo kk_html($link['label']); ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>

                    <div class="admin-sidebar-actions">
                        <a href="<?php echo kk_html(kk_route($section, 'home')); ?>">Patient View</a>
                        <?php if ($admin): ?>
                            <a href="<?php echo kk_html(kk_route($section, 'admin_logout')); ?>">Log Out</a>
                        <?php else: ?>
                            <a href="<?php echo kk_html(kk_route($section, 'admin_login')); ?>">Admin Login</a>
                        <?php endif; ?>
                    </div>
                </aside>

                <div class="admin-content">
                    <header class="admin-topbar">
                        <div class="admin-topbar-left">
                            <span class="admin-mobile-brand">
                                <span class="admin-sidebar-logo"><img src="<?php echo kk_html($asset); ?>images/logo.png" alt="KitaKits Logo"></span>
                                <strong>KitaKits Admin</strong>
                            </span>
                            <span class="admin-panel-chip">Admin Panel</span>
                        </div>
                        <div class="admin-topbar-actions">
                            <a href="<?php echo kk_html(kk_route($section, 'home')); ?>">&larr; Patient View</a>
                            <?php if ($admin): ?>
                                <a href="<?php echo kk_html(kk_route($section, 'admin_logout')); ?>">Log out</a>
                            <?php endif; ?>
                        </div>
                    </header>

                    <nav class="admin-mobile-nav" aria-label="Admin mobile navigation">
                        <?php foreach ($admin_links as $link): ?>
                            <a class="<?php echo $active === $link['key'] ? 'is-active' : ''; ?>" href="<?php echo kk_html($link['href']); ?>">
                                <?php echo kk_html($link['label']); ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>
            <?php
            return;
        }
        ?>
        <header class="<?php echo kk_html($header_class); ?>">
            <div class="make-header-inner">
                <a href="<?php echo kk_html($mode === 'admin' ? kk_route($section, 'admin') : kk_route($section, 'home')); ?>" class="make-brand" aria-label="KitaKits home">
                    <span class="make-brand-logo"><img src="<?php echo kk_html($asset); ?>images/logo.png" alt="KitaKits Logo"></span>
                    <span class="make-brand-text">KitaKits</span>
                    <?php if ($mode === 'admin'): ?>
                        <span class="make-brand-kicker">Admin</span>
                    <?php endif; ?>
                </a>

                <?php if ($mode === 'admin'): ?>
                    <nav class="make-nav make-admin-nav" aria-label="Admin navigation">
                        <?php kk_nav_link($section, $active, 'admin', 'Dashboard'); ?>
                        <a class="make-nav-link" href="#admin-missions">Missions</a>
                        <a class="make-nav-link" href="#admin-bookings">Bookings</a>
                        <a class="make-nav-link" href="#admin-patients">Patients</a>
                        <a class="make-nav-link" href="#admin-content">Content</a>
                    </nav>
                    <div class="make-header-actions">
                        <a class="make-link-action" href="<?php echo kk_html(kk_route($section, 'home')); ?>">Patient View</a>
                        <?php if ($admin): ?>
                            <a class="make-primary-action" href="<?php echo kk_html(kk_route($section, 'admin_logout')); ?>">Log out</a>
                        <?php else: ?>
                            <a class="make-primary-action" href="<?php echo kk_html(kk_route($section, 'admin_login')); ?>">Admin Login</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <nav class="make-nav" aria-label="Primary navigation">
                        <?php kk_nav_link($section, $active, 'home', 'Home'); ?>
                        <?php kk_nav_link($section, $active, 'my_bookings', 'My Bookings'); ?>
                        <?php kk_nav_link($section, $active, 'guide', 'Patient Guide'); ?>
                        <?php kk_nav_link($section, $active, 'faq', 'FAQ'); ?>
                        <?php kk_nav_link($section, $active, 'about', 'About Cataracts'); ?>
                    </nav>
                    <div class="make-header-actions">
                        <?php if ($patient): ?>
                            <a class="make-link-action" href="<?php echo kk_html(kk_route($section, 'portal')); ?>">Dashboard</a>
                            <a class="make-primary-action" href="<?php echo kk_html(kk_route($section, 'logout')); ?>">Log out</a>
                        <?php else: ?>
                            <a class="make-link-action" href="<?php echo kk_html(kk_route($section, 'login')); ?>">Log In</a>
                            <a class="make-primary-action" href="<?php echo kk_html(kk_route($section, 'register')); ?>">Register</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </header>
        <?php
    }
}

if (!function_exists('kk_render_breadcrumbs')) {
    function kk_render_breadcrumbs($section, $items = [], $options = [])
    {
        if (empty($items)) {
            return;
        }

        $no_print = !empty($options['no_print']);
        ?>
        <nav class="make-breadcrumbs<?php echo $no_print ? ' no-print' : ''; ?>" aria-label="Breadcrumb">
            <div class="make-breadcrumbs-inner">
                <a href="<?php echo kk_html(kk_route($section, 'home')); ?>">Home</a>
                <?php foreach ($items as $item): ?>
                    <span class="make-breadcrumb-separator">&rsaquo;</span>
                    <?php if (!empty($item['href'])): ?>
                        <a href="<?php echo kk_html($item['href']); ?>"><?php echo kk_html($item['label']); ?></a>
                    <?php else: ?>
                        <span aria-current="page"><?php echo kk_html($item['label']); ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </nav>
        <?php
    }
}

if (!function_exists('kk_render_footer')) {
    function kk_render_footer($section = 'pages', $options = [])
    {
        $no_print = !empty($options['no_print']);
        $asset = kk_route($section, 'asset');
        if ($section === 'admin' && (($options['mode'] ?? 'admin') === 'admin')) {
            ?>
                </div>
            </div>
            <?php
            return;
        }
        ?>
        <footer class="make-footer<?php echo $no_print ? ' no-print' : ''; ?>">
            <div class="make-footer-inner">
                <div class="make-footer-brand">
                    <span class="make-footer-logo"><img src="<?php echo kk_html($asset); ?>images/logo.png" alt="KitaKits Logo"></span>
                    <strong>KitaKits</strong>
                    <p>Connecting patients with free cataract surgery missions across the Philippines.</p>
                </div>
                <div class="make-footer-links">
                    <div>
                        <span>Patient</span>
                        <a href="<?php echo kk_html(kk_route($section, 'home')); ?>">Home</a>
                        <a href="<?php echo kk_html(kk_route($section, 'my_bookings')); ?>">My Bookings</a>
                        <a href="<?php echo kk_html(kk_route($section, 'guide')); ?>">Patient Guide</a>
                    </div>
                    <div>
                        <span>Learn</span>
                        <a href="<?php echo kk_html(kk_route($section, 'faq')); ?>">FAQ</a>
                        <a href="<?php echo kk_html(kk_route($section, 'about')); ?>">About Cataracts</a>
                    </div>
                    <div>
                        <span>Account</span>
                        <a href="<?php echo kk_html(kk_route($section, 'login')); ?>">Log In</a>
                        <a href="<?php echo kk_html(kk_route($section, 'register')); ?>">Register</a>
                    </div>
                </div>
            </div>
            <div class="make-footer-bottom">
                &copy; 2025 KitaKits. All rights reserved. Information is for educational purposes only &mdash; always consult a licensed ophthalmologist.
            </div>
        </footer>
        <?php
    }
}
?>
