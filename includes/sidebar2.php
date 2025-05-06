<!-- [ Sidebar Menu ] start -->
<nav class="pc-sidebar">
    <div class="navbar-wrapper">
        <div class="m-header d-flex justify-content-center text-cente mb-2r">
            <a href="../../dashboard/index.php" class="b-brand text-primary text-decoration-none d-flex flex-column align-items-center">
                <img src="/narasa-cake/assets/images/Logo.png" alt="Logo Narasa" class=" mt-5" style="width: 100px; object-fit: cover;">
            </a>

        </div>
        <br>
        <hr>
        <div class="navbar-content mt-3">
            <ul class="pc-navbar">
                <li class="pc-item <?= $active_page == 'dashboard' ? 'active' : '' ?>">
                    <a href="../../dashboard/index.php" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-dashboard"></i></span>
                        <span class="pc-mtext">Dashboard</span>
                    </a>
                </li>

                <li class="pc-item pc-caption">
                    <label>Manajemen</label>
                    <i class="ti ti-settings"></i>
                </li>
                <li class="pc-item <?= $active_page == 'bahan' ? 'active' : '' ?>">
                    <a href="../../bahan/index.php" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-package"></i></span>
                        <span class="pc-mtext">Bahan Baku</span>
                    </a>
                </li>
                <li class="pc-item <?= $active_page == 'kue' ? 'active' : '' ?>">
                    <a href="../../kue/index.php" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-layout-grid"></i></span>
                        <span class="pc-mtext">Jenis Kue</span>
                    </a>
                </li>
                <li class="pc-item <?= $active_page == 'resep' ? 'active' : '' ?>">
                    <a href="../../kue/resep/index.php" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-notebook"></i></span>
                        <span class="pc-mtext">Resep</span>
                    </a>
                </li>
                <li class="pc-item <?= $active_page == 'produksi' ? 'active' : '' ?>">
                    <a href="../../produksi/index.php" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-chart-pie"></i></span>
                        <span class="pc-mtext">Produksi</span>
                    </a>
                </li>
                <li class="pc-item pc-caption">
                    <label>Transaksi</label>
                    <i class="ti ti-settings"></i>
                </li>
                <li class="pc-item <?= $active_page == 'penjualan' ? 'active' : '' ?>">
                    <a href="../../penjualan/index.php" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-shopping-cart"></i></span>
                        <span class="pc-mtext">Penjualan</span>
                    </a>
                </li>
                <!-- <li class="pc-item <?= $active_page == 'pembelian' ? 'active' : '' ?>">
                    <a href="../../pembelian/index.php" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-shopping-cart-plus"></i></span>
                        <span class="pc-mtext">Pembelian</span>
                    </a>
                </li> -->
                <li class="pc-item <?= $active_page == 'pelanggan' ? 'active' : '' ?>">
                    <a href="../../pelanggan/index.php" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-users"></i></span>
                        <span class="pc-mtext">Pelanggan</span>
                    </a>
                </li>

                <li class="pc-item pc-caption">
                    <label>Laporan</label>
                    <i class="ti ti-report-analytics"></i>
                </li>
                <li class="pc-item <?= $active_page == 'laporan' ? 'active' : '' ?>">
                    <a href="../../laporan/index.php" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-chart-bar"></i></span>
                        <span class="pc-mtext">Laporan</span>
                    </a>
                </li>

                <!-- <li class="pc-item pc-caption">
                    <label>Pengaturan</label>
                    <i class="ti ti-tool"></i>
                </li>
                <li class="pc-item <?= $active_page == 'admin' ? 'active' : '' ?>">
                    <a href="../../admin/index.php" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-user"></i></span>
                        <span class="pc-mtext">Manajemen Admin</span>
                    </a>
                </li> -->
            </ul>
        </div>
    </div>
</nav>
<!-- [ Sidebar Menu ] end -->