<aside class="app-sidebar">
    <div class="sidebar-brand">

        <div class="brand-left">

            <div class="brand-icon">
                $
            </div>

            <div class="brand-text">

                <div class="brand-title">
                    Akaun Caj
                </div>

                <div class="brand-subtitle">
                    Pemajuan
                </div>

            </div>

        </div>


        <button type="button" id="sidebarToggle" class="sidebar-toggle" title="Buka / Tutup Sidebar">
            ☰
        </button>

    </div>


    <?php $activeModule = strtolower($this->controller->request->param('controller')); ?>
    <nav class="sidebar-nav">
<a href="<?= PUBLIC_ROOT ?>dashboard" class="sidebar-link <?= in_array($activeModule, ['dashboardcontroller'], true) ? 'active' : '' ?>"><span class="sidebar-icon">⌂</span><span>Dashboard</span></a>
<div class="sidebar-divider"></div><div class="sidebar-section-title">MODUL</div>
<a href="<?= PUBLIC_ROOT ?>charge" class="sidebar-link <?= in_array($activeModule, ['chargecontroller'], true) ? 'active' : '' ?>"><span class="sidebar-icon">RM</span><span>Caj Pemajuan</span></a>
<a href="<?= PUBLIC_ROOT ?>project" class="sidebar-link <?= in_array($activeModule, ['projectcontroller'], true) ? 'active' : '' ?>"><span class="sidebar-icon">P</span><span>Projek Pembangunan</span></a>
<?php if ($_SESSION['user_role'] === 'admin'): ?><div class="sidebar-divider"></div><div class="sidebar-section-title">PENTADBIRAN</div>
<a href="<?= PUBLIC_ROOT ?>user" class="sidebar-link <?= in_array($activeModule, ['usercontroller'], true) ? 'active' : '' ?>"><span class="sidebar-icon">👥</span><span>Pengurusan Pengguna</span></a>
<?php endif; ?>
</nav>

    <div class="sidebar-footer">

        <form method="POST" action="<?= PUBLIC_ROOT ?>auth/logout">

            <button type="submit" class="sidebar-logout">
                <span class="sidebar-icon">
                    ↪
                </span>

                <span>
                    Log Keluar
                </span>
            </button>

        </form>

    </div>
</aside>
