<header class="pc-header">
    <div class="header-wrapper"> <!-- [Mobile Media Block] start -->
        <div class="me-auto pc-mob-drp">
            <ul class="list-unstyled">
                <!-- ======= Menu collapse Icon ===== -->
                <li class="pc-h-item pc-sidebar-collapse">
                    <a href="#" class="pc-head-link ms-0" id="sidebar-hide">
                        <i class="ti ti-menu-2"></i>
                    </a>
                </li>
                <li class="pc-h-item pc-sidebar-popup">
                    <a href="#" class="pc-head-link ms-0" id="mobile-collapse">
                        <i class="ti ti-menu-2"></i>
                    </a>
                </li>
            </ul>
        </div>
        <!-- [Mobile Media Block end] -->
        <div class="ms-auto">
            <ul class="list-unstyled">
                <li class="dropdown pc-h-item header-user-profile">
                    <a
                        class="pc-head-link dropdown-toggle arrow-none me-0"
                        data-bs-toggle="dropdown"
                        href="#"
                        role="button"
                        aria-haspopup="false"
                        data-bs-auto-close="outside"
                        aria-expanded="false">
                        <img src="/narasa-cake/assets/images/Logo.png" alt="user-image" width="25" class="me-2">
                        <span><?= htmlspecialchars($_SESSION['nama'] ?? $_SESSION['username'] ?? 'Pengguna') ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown">
                        <div class="dropdown-header">
                            <div class="d-flex mb-1">
                                <div class="flex-shrink-0">
                                    <img src="/narasa-cake/assets/images/Logo.png" alt="user-image" width="25">
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1"><?= htmlspecialchars($_SESSION['nama'] ?? $_SESSION['username'] ?? 'Pengguna') ?></h6>
                                </div>
                                <!-- <a href="#!" class="pc-head-link bg-transparent"><i class="ti ti-power text-danger"></i></a> -->
                            </div>
                        </div>
                        <div class="tab-content" id="mysrpTabContent">
                            <div class="tab-pane fade show active" id="drp-tab-1" role="tabpanel" aria-labelledby="drp-t1" tabindex="0">

                                <a href="/narasa-cake/modules/profile/index.php" class="dropdown-item">
                                    <i class="ti ti-settings"></i>
                                    <span>Profil Saya</span>
                                </a>
                                <?php if (isset($_SESSION['level']) && $_SESSION['level'] === 'superadmin'): ?>
                                    <a href="../admin/index.php" class="dropdown-item">
                                        <i class="ti ti-user"></i>
                                        <span>Pengguna</span>
                                    </a>
                                <?php endif; ?>
                                <a href="/narasa-cake/logout.php" class="dropdown-item text-danger">
                                    <i class="ti ti-power"></i>
                                    <span>Logout</span>
                                </a>
                            </div>

                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</header>
<!-- [ Header ] end -->