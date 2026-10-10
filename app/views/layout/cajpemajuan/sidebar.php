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


    <nav class="sidebar-nav">

        <a href="<?= PUBLIC_ROOT ?>dashboard" class="sidebar-link active">
            <span class="sidebar-icon">⌂</span>

            <span>
                Dashboard
            </span>
        </a>


        <div class="sidebar-divider"></div>

        <div class="sidebar-section-title">
            TINDAKAN
        </div>

        <button type="button" class="sidebar-action-button" data-bs-toggle="modal" data-bs-target="#addChargeModal">
            <span class="sidebar-icon">
                +
            </span>

            <span>
                Tambah Caj
            </span>
        </button>

        <button type="button" class="sidebar-action-button" data-bs-toggle="modal" data-bs-target="#addProjectModal">
            <span class="sidebar-icon">
                +
            </span>

            <span>
                Tambah Projek
            </span>
        </button>

        <div class="sidebar-divider"></div>


        <?php if (
            isset($_SESSION['user_role']) &&
            $_SESSION['user_role'] === 'admin'
        ): ?>

            <div class="sidebar-section-title">
                PENTADBIRAN
            </div>

            <button type="button" class="sidebar-action-button" data-bs-toggle="modal" data-bs-target="#manageUserModal">
                <span class="sidebar-icon">
                    👥
                </span>

                <span>
                    Urus Pengguna
                </span>
            </button>

            <button type="button" class="sidebar-action-button" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <span class="sidebar-icon">
                    +
                </span>

                <span>
                    Daftar Pengguna
                </span>
            </button>

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