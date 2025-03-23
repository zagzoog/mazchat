<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Load configuration
$config = require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - <?php echo htmlspecialchars($_SESSION['username']); ?></title>
    <link href="/chat/public/css/styles.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        /* Add dropdown styles */
        .dropdown {
            position: relative;
            display: inline-block;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            z-index: 1000;
            border-radius: 0.5rem;
            padding: 0.5rem 0;
        }
        .dropdown:hover .dropdown-content {
            display: block;
        }
        .dropdown-item {
            color: #374151;
            padding: 0.75rem 1rem;
            text-decoration: none;
            display: block;
            transition: background-color 0.2s;
        }
        .dropdown-item:hover {
            background-color: #f3f4f6;
        }
        .dropdown-item i {
            margin-left: 0.5rem;
            width: 1.25rem;
            text-align: center;
        }
        .stats-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .membership-badge {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-weight: 600;
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
        .chart-container {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 2rem;
            height: 300px; /* Fixed height for chart containers */
        }
        .chart-wrapper {
            position: relative;
            height: 250px; /* Fixed height for chart canvas */
            width: 100%;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div class="flex items-center">
                <div class="dropdown">
                    <button class="flex items-center text-gray-600 hover:text-gray-800">
                        <i class="fas fa-bars text-xl"></i>
                        <span class="mr-2">القائمة</span>
                    </button>
                    <div class="dropdown-content">
                        <a href="dashboard.php" class="dropdown-item">
                            <i class="fas fa-chart-line"></i>
                            لوحة التحكم
                        </a>
                        <a href="profile.php" class="dropdown-item">
                            <i class="fas fa-user"></i>
                            الملف الشخصي
                        </a>
                        <a href="logout.php" class="dropdown-item text-red-600 hover:text-red-700">
                            <i class="fas fa-sign-out-alt"></i>
                            تسجيل الخروج
                        </a>
                    </div>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mr-4">لوحة التحكم</h1>
            </div>
            <div class="flex items-center space-x-4 space-x-reverse">
                <a href="index.php" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-right"></i> العودة للدردشة
                </a>
            </div>
        </div>
        
        <!-- Membership Status -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">حالة العضوية</h2>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600">نوع العضوية: <span id="membership-type" class="font-semibold"></span></p>
                    <p class="text-gray-600">الحد الشهري للمحادثات: <span id="monthly-limit" class="font-semibold"></span></p>
                    <p class="text-gray-600">الحد الشهري للأسئلة: <span id="question-limit" class="font-semibold"></span></p>
                    <p class="text-gray-600">المحادثات المستخدمة: <span id="current-usage" class="font-semibold"></span></p>
                    <p class="text-gray-600">الأسئلة المستخدمة: <span id="current-questions" class="font-semibold"></span></p>
                </div>
                <div class="space-x-4 space-x-reverse">
                    <button onclick="showUpgradeModal()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        <i class="fas fa-crown"></i> ترقية العضوية
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-2">المحادثات</h3>
                <p class="text-3xl font-bold text-blue-600" id="total-conversations">0</p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-2">الأسئلة</h3>
                <p class="text-3xl font-bold text-green-600" id="total-questions">0</p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-2">الكلمات</h3>
                <p class="text-3xl font-bold text-purple-600" id="total-words">0</p>
            </div>
        </div>
        
        <!-- Usage Charts -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Conversations Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">المحادثات</h2>
                <div class="chart-wrapper">
                    <canvas id="conversations-chart"></canvas>
                </div>
            </div>
            
            <!-- Questions Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">الأسئلة</h2>
                <div class="chart-wrapper">
                    <canvas id="questions-chart"></canvas>
                </div>
            </div>
            
            <!-- Words Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">الكلمات</h2>
                <div class="chart-wrapper">
                    <canvas id="words-chart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Top Topics -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">المواضيع الأكثر استخداماً</h2>
            <div id="top-topics" class="space-y-2"></div>
        </div>
    </div>
    
    <!-- Upgrade Modal -->
    <div id="upgrade-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-lg p-6 max-w-md mx-auto mt-20">
            <h2 class="text-xl font-semibold mb-4">ترقية العضوية</h2>
            <div class="space-y-4">
                <div class="border rounded p-4">
                    <h3 class="font-semibold">العضوية الأساسية</h3>
                    <p class="text-gray-600">$9.99/شهرياً</p>
                    <ul class="mt-2 space-y-1">
                        <li><i class="fas fa-check text-green-500"></i> 100 محادثة شهرياً</li>
                        <li><i class="fas fa-check text-green-500"></i> دعم البريد الإلكتروني</li>
                    </ul>
                    <button onclick="initiatePayment('basic')" class="mt-4 w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        اختيار
                    </button>
                </div>
                <div class="border rounded p-4">
                    <h3 class="font-semibold">العضوية المميزة</h3>
                    <p class="text-gray-600">$19.99/شهرياً</p>
                    <ul class="mt-2 space-y-1">
                        <li><i class="fas fa-check text-green-500"></i> محادثات غير محدودة</li>
                        <li><i class="fas fa-check text-green-500"></i> دعم مباشر</li>
                        <li><i class="fas fa-check text-green-500"></i> ميزات متقدمة</li>
                    </ul>
                    <button onclick="initiatePayment('premium')" class="mt-4 w-full bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                        اختيار
                    </button>
                </div>
            </div>
            <button onclick="hideUpgradeModal()" class="mt-4 w-full bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300">
                إلغاء
            </button>
        </div>
    </div>
    
    <script>
        // Fetch dashboard data
        async function fetchDashboardData() {
            try {
                const response = await fetch('api/dashboard.php');
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
                
                // Update top topics
                const topTopicsHtml = data.top_topics.map(topic => `
                    <div class="flex justify-between items-center">
                        <span>${topic.topic}</span>
                        <span class="text-gray-600">${topic.count} محادثة</span>
                    </div>
                `).join('');
                document.getElementById('top-topics').innerHTML = topTopicsHtml;
                
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
                const response = await fetch('/chat/api/create_payment.php', {
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
    </script>
</body>
</html> 