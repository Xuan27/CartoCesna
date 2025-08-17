<!-- header-tabs.html - Reusable Header Component -->
<link rel="stylesheet" href="Models/css/header_tabs.css">

<div class="header-tabs-container">
    <nav class="header-tabs">
        <div class="logo-section">
            🚀 Cartocesna
        </div>
        
        <a href="./index.html" class="tab-item" data-page="index">
            <span class="tab-icon">🏠</span>
            Home
        </a>
        
        <a href="about.html" class="tab-item" data-page="about">
            <span class="tab-icon">👋</span>
            About
        </a>
        
        <a href="services.html" class="tab-item" data-page="services">
            <span class="tab-icon">🛠️</span>
            Services
        </a>
        
        <a href="portfolio.html" class="tab-item" data-page="portfolio">
            <span class="tab-icon">💼</span>
            Portfolio
        </a>
        
        <a href="./list.php" class="tab-item" data-page="article">
            <span class="tab-icon">📖</span>
            Articles
        </a>
        
        <a href="contact.html" class="tab-item" data-page="contact">
            <span class="tab-icon">📞</span>
            Contact
        </a>
        
        <div class="user-section">
            <a href="./login.php" class="tab-item" data-page="login" id="login-link">
                <span class="tab-icon">🔐</span>
                <?php echo htmlspecialchars($_SESSION['username']);?>
                Login
            </a>
            <div class="user-avatar" id="user-avatar" style="display: none;">
                JD
            </div>
        </div>
    </nav>
</div>