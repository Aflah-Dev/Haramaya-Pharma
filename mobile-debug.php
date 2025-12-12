<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Mobile Debug - Haramaya Pharma</title>
    <link rel="stylesheet" href="assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .debug-info {
            position: fixed;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 9999;
        }
        
        .test-buttons {
            position: fixed;
            bottom: 10px;
            left: 10px;
            z-index: 9999;
        }
        
        .test-buttons button {
            display: block;
            margin: 5px 0;
            padding: 10px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="debug-info">
        Screen: <span id="screen-size"></span><br>
        Viewport: <span id="viewport-size"></span><br>
        Device: <span id="device-type"></span>
    </div>
    
    <div class="test-buttons">
        <button onclick="testSidebar()">Test Sidebar</button>
        <button onclick="testOverlay()">Test Overlay</button>
        <button onclick="testResponsive()">Test Responsive</button>
    </div>

    <div class="dashboard-container">
        <!-- Mobile sidebar overlay -->
        <div class="sidebar-overlay"></div>

        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-pills" style="font-size: 3rem; margin-bottom: 0.5rem; color: #28a745;"></i>
                <h2>Haramaya Pharma</h2>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Test Menu</div>
                    <a href="#" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="#" class="nav-link">
                        <i class="fas fa-pills"></i>
                        <span>Products</span>
                    </a>
                    <a href="#" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        <span>Reports</span>
                    </a>
                </div>
            </nav>
        </aside>
        
        <div class="main-content">
            <header class="top-header">
                <div class="header-title">
                    <button class="mobile-menu-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1>Mobile Debug Test</h1>
                </div>
                <div class="header-actions">
                    <button class="btn btn-secondary">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </button>
                </div>
            </header>
            
            <div class="content-area">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">📱 Mobile Responsiveness Test</h3>
                    </div>
                    <div class="card-body">
                        <h4>Test Instructions:</h4>
                        <ol>
                            <li><strong>Tap the hamburger menu (☰)</strong> - Should open sidebar</li>
                            <li><strong>Tap outside sidebar</strong> - Should close sidebar</li>
                            <li><strong>Check header layout</strong> - No overlapping elements</li>
                            <li><strong>Test buttons</strong> - All should be touch-friendly</li>
                            <li><strong>Scroll content</strong> - Should work smoothly</li>
                        </ol>
                        
                        <h4>Expected Behavior:</h4>
                        <ul>
                            <li>✅ Hamburger menu visible and functional</li>
                            <li>✅ Sidebar slides in from left</li>
                            <li>✅ Overlay prevents background interaction</li>
                            <li>✅ Header elements don't overlap</li>
                            <li>✅ All text is readable without zooming</li>
                            <li>✅ Buttons are at least 44px for easy tapping</li>
                        </ul>
                        
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-label">
                                    <i class="fas fa-mobile-alt"></i>
                                    Mobile Test
                                </div>
                                <div class="stat-value">OK</div>
                            </div>
                            <div class="stat-card success">
                                <div class="stat-label">
                                    <i class="fas fa-check"></i>
                                    Responsive
                                </div>
                                <div class="stat-value">✓</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Test Input</label>
                            <input type="text" class="form-control" placeholder="Test mobile keyboard">
                        </div>
                        
                        <button class="btn btn-primary">
                            <i class="fas fa-check"></i> Test Button
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js?v=<?php echo time(); ?>"></script>
    <script>
        function updateDebugInfo() {
            document.getElementById('screen-size').textContent = screen.width + 'x' + screen.height;
            document.getElementById('viewport-size').textContent = window.innerWidth + 'x' + window.innerHeight;
            
            let deviceType = 'Desktop';
            if (window.innerWidth <= 480) deviceType = 'Small Mobile';
            else if (window.innerWidth <= 768) deviceType = 'Mobile';
            else if (window.innerWidth <= 1024) deviceType = 'Tablet';
            
            document.getElementById('device-type').textContent = deviceType;
        }
        
        function testSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const isOpen = sidebar.classList.contains('mobile-open');
            alert('Sidebar is currently: ' + (isOpen ? 'OPEN' : 'CLOSED'));
        }
        
        function testOverlay() {
            const overlay = document.querySelector('.sidebar-overlay');
            const isActive = overlay.classList.contains('active');
            alert('Overlay is currently: ' + (isActive ? 'ACTIVE' : 'INACTIVE'));
        }
        
        function testResponsive() {
            const width = window.innerWidth;
            let message = `Screen width: ${width}px\n`;
            
            if (width <= 480) message += 'Device: Small Mobile\n';
            else if (width <= 768) message += 'Device: Mobile\n';
            else if (width <= 1024) message += 'Device: Tablet\n';
            else message += 'Device: Desktop\n';
            
            const toggle = document.querySelector('.mobile-menu-toggle');
            const isVisible = window.getComputedStyle(toggle).display !== 'none';
            message += `Hamburger menu visible: ${isVisible ? 'YES' : 'NO'}`;
            
            alert(message);
        }
        
        // Update debug info on load and resize
        updateDebugInfo();
        window.addEventListener('resize', updateDebugInfo);
        
        // Test touch events
        document.addEventListener('touchstart', function() {
            console.log('Touch detected - mobile device confirmed');
        });
    </script>
</body>
</html>