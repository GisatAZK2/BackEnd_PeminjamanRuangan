<body class="bg-gray-100 font-sans antialiased text-gray-900">

    <?php include 'views/layout/header.php'; ?>
    <?php include 'views/layout/sidebar.php'; ?>

     <main id="main-content" class="pt-16 lg:pl-64 transition-all duration-300 min-h-screen">
        <div id="spa-content" class="p-6">
            <div class="flex justify-center py-20">
                <i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i>
            </div>
        </div>
    </main>

    <script src="/public/js/api.js"></script>
    <script src="/public/js/notify.js"></script>
    <script src="/public/js/loadPage.js"></script>

    <script src="/public/js/sidebarShowHide.js"></script>
</body>
