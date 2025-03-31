<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Load configuration
require_once __DIR__ . '/path_config.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - <?php echo htmlspecialchars($_SESSION['username']); ?></title>
    <link href="<?php echo getFullUrlPath('public/css/styles.css'); ?>" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script>
        window.baseUrlPath = <?php echo json_encode($base_url_path); ?>;
        window.baseUrl = <?php echo json_encode(rtrim($current_config['domain_name'], '/')); ?>;
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body {
            font-family: 'Cairo', sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        .container {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }
        .sidebar {
            width: 300px;
            background: white;
            border-left: 1px solid rgba(0,0,0,0.05);
            padding: 1.5rem;
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            overflow-y: auto;
        }
        .main-content {
            flex: 1;
            margin-right: 300px;
            padding: 2rem;
            min-height: 100vh;
            background: #f0f2f5;
        }
        .content {
            max-width: 1200px;
            margin: 0 auto;
        }
        .chart-container {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-top: 2rem;
            height: 300px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .chart-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0,0,0,0.1);
        }
        .chart-wrapper {
            position: relative;
            height: 250px;
            width: 100%;
        }
        .stats-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0,0,0,0.1);
        }
        .membership-badge {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .membership-badge.free {
            background-color: #e5e7eb;
            color: #374151;
        }
        .membership-badge.silver {
            background-color: #c0c0c0;
            color: white;
        }
        .membership-badge.gold {
            background-color: #ffd700;
            color: #000;
        }
        .card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0,0,0,0.1);
        }
        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .card-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }
        .card-body {
            padding: 1.5rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .btn-primary {
            background-color: #3b82f6;
            color: white;
        }
        .btn-primary:hover {
            background-color: #2563eb;
        }
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        .modal.show {
            opacity: 1;
            visibility: visible;
        }
        .modal-content {
            background: white;
            border-radius: 1rem;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }
        .modal.show .modal-content {
            transform: translateY(0);
        }
        .plan-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            border: 1px solid rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0,0,0,0.1);
        }
        .close-button {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6b7280;
            transition: color 0.3s ease;
        }
        .close-button:hover {
            color: #1f2937;
        }
        .header {
            background: white;
            padding: 1rem 2rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            border-radius: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .username {
            font-weight: 500;
            color: #4b5563;
        }
        .sidebar-header {
            margin-bottom: 2rem;
        }
        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }
        .sidebar-content {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            color: #4b5563;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .sidebar-menu a:hover {
            background-color: #f3f4f6;
            color: #1f2937;
        }
        .sidebar-menu a.active {
            background-color: #eef2ff;
            color: #4f46e5;
        }
        .sidebar-menu a i {
            margin-left: 0.75rem;
            font-size: 1.25rem;
        }
        .sidebar-footer {
            margin-top: 2rem;
            text-align: center;
        }
        .toggle-sidebar {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #4b5563;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }
        .toggle-sidebar:hover {
            background-color: #f3f4f6;
            color: #1f2937;
        }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .profile-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            color: #4b5563;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .profile-link:hover {
            background-color: #f3f4f6;
            color: #1f2937;
        }
        .profile-link i {
            font-size: 1.25rem;
        }
        @media (max-width: 768px) {
            .toggle-sidebar {
                display: block;
            }
            .sidebar {
                transform: translateX(100%);
                transition: transform 0.3s ease;
                z-index: 1000;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-right: 0;
                padding: 1rem;
            }
            .modal {
                z-index: 1001;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>لوحة التحكم</h2>
            </div>
            <div class="sidebar-content">
                <div class="sidebar-menu">
                    <a href="dashboard.php" class="active">
                        <i class="fas fa-chart-line"></i>
                        <span>لوحة التحكم</span>
                    </a>
                    <a href="profile.php">
                        <i class="fas fa-user"></i>
                        <span>الملف الشخصي</span>
                    </a>
                    <a href="index.php">
                        <i class="fas fa-comments"></i>
                        <span>الدردشة</span>
                    </a>
                </div>
            </div>
            <div class="sidebar-footer">
                <a href="logout.php" class="profile-link text-red-600">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>تسجيل الخروج</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-content">
                    <button class="toggle-sidebar" aria-label="Toggle Sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1>لوحة التحكم</h1>
                    <div class="header-actions">
                        <a href="profile.php" class="profile-link" aria-label="الملف الشخصي">
                            <i class="fas fa-user-circle"></i>
                            <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content">
                <!-- Membership Status -->
                <div class="card mb-6">
                    <div class="card-header">
                        <h2>حالة العضوية</h2>
                    </div>
                    <div class="card-body">
                        <div class="flex justify-between items-center">
                            <div class="space-y-2">
                                <p>نوع العضوية: <span id="membership-type" class="font-semibold"></span></p>
                                <p>الحد الشهري للمحادثات: <span id="monthly-limit" class="font-semibold"></span></p>
                                <p>الحد الشهري للأسئلة: <span id="question-limit" class="font-semibold"></span></p>
                                <p>المحادثات المستخدمة: <span id="current-usage" class="font-semibold"></span></p>
                                <p>الأسئلة المستخدمة: <span id="current-questions" class="font-semibold"></span></p>
                            </div>
                            <button onclick="showUpgradeModal()" class="btn btn-primary">
                                <i class="fas fa-crown ml-2"></i>
                                ترقية العضوية
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="stats-card">
                        <h3 class="text-lg font-semibold mb-2">المحادثات</h3>
                        <p class="text-3xl font-bold text-blue-600" id="total-conversations">0</p>
                    </div>
                    <div class="stats-card">
                        <h3 class="text-lg font-semibold mb-2">الأسئلة</h3>
                        <p class="text-3xl font-bold text-green-600" id="total-questions">0</p>
                    </div>
                    <div class="stats-card">
                        <h3 class="text-lg font-semibold mb-2">الكلمات</h3>
                        <p class="text-3xl font-bold text-purple-600" id="total-words">0</p>
                    </div>
                </div>

                <!-- Usage Charts -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="card">
                        <div class="card-header">
                            <h2>المحادثات</h2>
                        </div>
                        <div class="card-body">
                            <div class="chart-wrapper">
                                <canvas id="conversations-chart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2>الأسئلة</h2>
                        </div>
                        <div class="card-body">
                            <div class="chart-wrapper">
                                <canvas id="questions-chart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2>الكلمات</h2>
                        </div>
                        <div class="card-body">
                            <div class="chart-wrapper">
                                <canvas id="words-chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upgrade Modal -->
    <div id="upgrade-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>ترقية العضوية</h2>
                <button onclick="hideUpgradeModal()" class="close-button">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="space-y-4">
                    <div class="plan-card">
                        <h3 class="text-xl font-semibold">العضوية الأساسية</h3>
                        <p class="text-gray-600">$<?php echo number_format($current_config['silver_price'], 2); ?>/شهرياً</p>
                        <ul class="mt-4 space-y-2">
                            <li><i class="fas fa-check text-green-500 ml-2"></i> 100 محادثة شهرياً</li>
                            <li><i class="fas fa-check text-green-500 ml-2"></i> دعم البريد الإلكتروني</li>
                        </ul>
                        <button onclick="initiatePayment('basic')" class="btn btn-primary w-full mt-4">
                            اختيار
                        </button>
                    </div>
                    <div class="plan-card">
                        <h3 class="text-xl font-semibold">العضوية المميزة</h3>
                        <p class="text-gray-600">$<?php echo number_format($current_config['gold_price'], 2); ?>/شهرياً</p>
                        <ul class="mt-4 space-y-2">
                            <li><i class="fas fa-check text-green-500 ml-2"></i> محادثات غير محدودة</li>
                            <li><i class="fas fa-check text-green-500 ml-2"></i> دعم مباشر</li>
                            <li><i class="fas fa-check text-green-500 ml-2"></i> ميزات متقدمة</li>
                        </ul>
                        <button onclick="initiatePayment('premium')" class="btn btn-primary w-full mt-4">
                            اختيار
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fetch dashboard data
        async function fetchDashboardData() {
            try {
                const response = await fetch(`/${baseUrl}/api/dashboard.php`);
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                // Update membership info
                document.getElementById('membership-type').textContent = data.membership.type;
                document.getElementById('monthly-limit').textContent = data.membership.monthly_limit;
                document.getElementById('question-limit').textContent = data.membership.question_limit;
                document.getElementById('current-usage').textContent = data.membership.current_usage;
                document.getElementById('current-questions').textContent = data.membership.current_questions;
                
                // Update statistics
                document.getElementById('total-conversations').textContent = data.stats.total_conversations;
                document.getElementById('total-questions').textContent = data.stats.total_questions;
                document.getElementById('total-words').textContent = data.stats.total_words;
                
                // Update charts
                updateCharts(data.daily_stats);
            } catch (error) {
                console.error('Error fetching dashboard data:', error);
                showError('حدث خطأ أثناء تحميل البيانات');
            }
        }
        
        // Initialize charts
        let conversationsChart, questionsChart, wordsChart;
        
        function updateCharts(dailyStats) {
            const dates = dailyStats.map(stat => stat.date);
            const conversationsData = dailyStats.map(stat => stat.conversations);
            const questionsData = dailyStats.map(stat => stat.questions);
            const wordsData = dailyStats.map(stat => stat.words);
            
            // Common chart options
            const commonOptions = {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 750,
                    easing: 'easeInOutQuart'
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                family: 'Cairo'
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: {
                                family: 'Cairo'
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: 'Cairo'
                            }
                        }
                    }
                }
            };
            
            // Conversations Chart
            const conversationsCtx = document.getElementById('conversations-chart').getContext('2d');
            if (conversationsChart) {
                conversationsChart.destroy();
            }
            conversationsChart = new Chart(conversationsCtx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'المحادثات',
                        data: conversationsData,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: commonOptions
            });
            
            // Questions Chart
            const questionsCtx = document.getElementById('questions-chart').getContext('2d');
            if (questionsChart) {
                questionsChart.destroy();
            }
            questionsChart = new Chart(questionsCtx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'الأسئلة',
                        data: questionsData,
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: commonOptions
            });
            
            // Words Chart
            const wordsCtx = document.getElementById('words-chart').getContext('2d');
            if (wordsChart) {
                wordsChart.destroy();
            }
            wordsChart = new Chart(wordsCtx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'الكلمات',
                        data: wordsData,
                        borderColor: 'rgb(139, 92, 246)',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: commonOptions
            });
        }
        
        // Modal functions
        function showUpgradeModal() {
            document.getElementById('upgrade-modal').classList.remove('hidden');
        }
        
        function hideUpgradeModal() {
            document.getElementById('upgrade-modal').classList.add('hidden');
        }
        
        // Payment functions
        async function initiatePayment(membershipType) {
            try {
                const response = await fetch(`/${baseUrl}/api/create_payment.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ membership_type: membershipType })
                });
                
                const data = await response.json();
                if (data.success) {
                    window.location.href = data.paypal_url;
                } else {
                    alert('حدث خطأ أثناء إنشاء الدفع');
                }
            } catch (error) {
                console.error('Error initiating payment:', error);
                alert('حدث خطأ أثناء إنشاء الدفع');
            }
        }
        
        // Show error message
        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'fixed top-4 right-4 bg-red-500 text-white px-4 py-2 rounded shadow-lg';
            errorDiv.textContent = message;
            document.body.appendChild(errorDiv);
            
            setTimeout(() => {
                errorDiv.remove();
            }, 3000);
        }
        
        // Initialize dashboard
        fetchDashboardData();

        // Toggle sidebar on mobile
        document.querySelector('.toggle-sidebar').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const toggleButton = document.querySelector('.toggle-sidebar');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !toggleButton.contains(event.target) && 
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });

        // Close sidebar when window is resized above mobile breakpoint
        window.addEventListener('resize', function() {
            const sidebar = document.querySelector('.sidebar');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('show');
            }
        });
    </script>
</body>
</html> 