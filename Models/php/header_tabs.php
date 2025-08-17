<!-- header-tabs.html - Reusable Header Component -->
<style>
    .header-tabs-container {
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border-bottom: 1px solid #e9ecef;
        position: sticky;
        top: 0;
        z-index: 1000;
    }
    
    .header-tabs {
        display: flex;
        align-items: center;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0;
        overflow-x: auto;
        scrollbar-width: thin;
    }
    
    .header-tabs::-webkit-scrollbar {
        height: 3px;
    }
    
    .header-tabs::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    .header-tabs::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 2px;
    }
    
    .tab-item {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 16px 24px;
        text-decoration: none;
        color: #6c757d;
        background: transparent;
        border: none;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 14px;
        font-weight: 500;
        white-space: nowrap;
        user-select: none;
        position: relative;
    }
    
    .tab-item:hover {
        color: #495057;
        background: rgba(102, 126, 234, 0.08);
        transform: translateY(-1px);
    }
    
    .tab-item.active {
        color: #667eea;
        border-bottom-color: #667eea;
        font-weight: 600;
        background: rgba(102, 126, 234, 0.05);
    }
    
    .tab-item.disabled {
        color: #adb5bd;
        cursor: not-allowed;
        opacity: 0.6;
    }
    
    .tab-icon {
        font-size: 16px;
        margin-right: 4px;
    }
    
    .logo-section {
        padding: 16px 24px;
        font-weight: bold;
        color: #667eea;
        font-size: 18px;
        border-right: 1px solid #e9ecef;
        margin-right: 20px;
    }
    
    .user-section {
        margin-left: auto;
        padding: 16px 24px;
        color: #6c757d;
        font-size: 14px;
        border-left: 1px solid #e9ecef;
    }
    
    .user-section .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #667eea;
        color: white;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-left: 10px;
        font-size: 12px;
        font-weight: bold;
        position: absolute;
        margin: 0 auto;
    }
    
    /* Mobile responsive */
    @media (max-width: 768px) {
        .logo-section {
            display: none;
        }
        
        .tab-item {
            padding: 12px 16px;
            font-size: 13px;
        }
        
        .user-section {
            padding: 12px 16px;
        }
    }
</style>

<div class="header-tabs-container">
    <nav class="header-tabs">
        <div class="logo-section">
            🚀 Cartocesna
        </div>
        
        <a href="../../index.html" class="tab-item" data-page="index">
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
        
        <a href="../../list.php" class="tab-item" data-page="article">
            <span class="tab-icon">📖</span>
            Articles
        </a>
        
        <a href="contact.html" class="tab-item" data-page="contact">
            <span class="tab-icon">📞</span>
            Contact
        </a>
        
        <div class="user-section">
            <a href="../../login.php" class="tab-item" data-page="login" id="login-link">
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