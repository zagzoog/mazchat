<?php
session_start();
require_once '../config/config.php';

// Check if already installed
if (file_exists('../config/installed.lock')) {
    die('Application is already installed. Please remove the installed.lock file to reinstall.');
}

// Installation steps
$steps = [
    1 => 'System Requirements',
    2 => 'Database Configuration',
    3 => 'Admin Account',
    4 => 'Final Setup'
];

$currentStep = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// Function to check system requirements
function checkRequirements() {
    $requirements = [
        'PHP Version (>= 7.4)' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'PDO Extension' => extension_loaded('pdo'),
        'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
        'JSON Extension' => extension_loaded('json'),
        'Config Directory Writable' => is_writable('../config'),
        'Logs Directory Writable' => is_writable('../logs')
    ];
    
    return $requirements;
}

// Function to test database connection
function testDatabaseConnection($host, $username, $password, $database) {
    try {
        $dsn = "mysql:host=$host;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Try to create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$database`");
        
        return true;
    } catch (PDOException $e) {
        return $e->getMessage();
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($currentStep) {
        case 2: // Database configuration
            $dbHost = $_POST['db_host'] ?? '';
            $dbName = $_POST['db_name'] ?? '';
            $dbUser = $_POST['db_user'] ?? '';
            $dbPass = $_POST['db_pass'] ?? '';
            
            $testResult = testDatabaseConnection($dbHost, $dbUser, $dbPass, $dbName);
            
            if ($testResult === true) {
                // Save database configuration
                $dbConfig = [
                    'host' => $dbHost,
                    'name' => $dbName,
                    'user' => $dbUser,
                    'pass' => $dbPass
                ];
                
                file_put_contents('../config/db_config.php', '<?php return ' . var_export($dbConfig, true) . ';');
                $_SESSION['install_step'] = 3;
                header('Location: index.php?step=3');
                exit;
            } else {
                $error = "Database connection failed: " . $testResult;
            }
            break;
            
        case 3: // Admin account
            $adminUsername = $_POST['admin_username'] ?? '';
            $adminEmail = $_POST['admin_email'] ?? '';
            $adminPassword = $_POST['admin_password'] ?? '';
            $adminPasswordConfirm = $_POST['admin_password_confirm'] ?? '';
            
            if ($adminPassword !== $adminPasswordConfirm) {
                $error = "Passwords do not match";
            } else {
                // Create admin user
                try {
                    require_once '../config/db_config.php';
                    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4";
                    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Run migrations
                    require_once '../migrations/run.php';
                    
                    // Create admin user
                    $userId = uuid_create();
                    $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("INSERT INTO users (id, username, email, password, role) VALUES (?, ?, ?, ?, 'admin')");
                    $stmt->execute([$userId, $adminUsername, $adminEmail, $hashedPassword]);
                    
                    $_SESSION['install_step'] = 4;
                    header('Location: index.php?step=4');
                    exit;
                } catch (PDOException $e) {
                    $error = "Error creating admin user: " . $e->getMessage();
                }
            }
            break;
            
        case 4: // Final setup
            // Create installed.lock file
            file_put_contents('../config/installed.lock', date('Y-m-d H:i:s'));
            
            // Redirect to login page
            header('Location: ../login.php');
            exit;
            break;
    }
}

// Get current step from session if not in URL
if (!isset($_GET['step']) && isset($_SESSION['install_step'])) {
    $currentStep = $_SESSION['install_step'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install AI Chat Application</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-2xl w-full bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-center mb-8">Install AI Chat Application</h1>
            
            <!-- Progress bar -->
            <div class="mb-8">
                <div class="flex justify-between mb-2">
                    <?php foreach ($steps as $step => $name): ?>
                        <div class="text-sm <?php echo $step <= $currentStep ? 'text-blue-600' : 'text-gray-400'; ?>">
                            <?php echo $name; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo ($currentStep / count($steps)) * 100; ?>%"></div>
                </div>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Step content -->
            <div class="mt-8">
                <?php if ($currentStep === 1): ?>
                    <h2 class="text-xl font-semibold mb-4">System Requirements</h2>
                    <?php $requirements = checkRequirements(); ?>
                    <ul class="space-y-2">
                        <?php foreach ($requirements as $requirement => $met): ?>
                            <li class="flex items-center">
                                <span class="<?php echo $met ? 'text-green-500' : 'text-red-500'; ?> mr-2">
                                    <?php echo $met ? '✓' : '✗'; ?>
                                </span>
                                <?php echo htmlspecialchars($requirement); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if (array_sum($requirements) === count($requirements)): ?>
                        <form method="post" class="mt-6">
                            <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">
                                Continue to Database Setup
                            </button>
                        </form>
                    <?php endif; ?>
                    
                <?php elseif ($currentStep === 2): ?>
                    <h2 class="text-xl font-semibold mb-4">Database Configuration</h2>
                    <form method="post" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Database Host</label>
                            <input type="text" name="db_host" value="localhost" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Database Name</label>
                            <input type="text" name="db_name" value="mychat" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Database Username</label>
                            <input type="text" name="db_user" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Database Password</label>
                            <input type="password" name="db_pass" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">
                            Test Connection & Continue
                        </button>
                    </form>
                    
                <?php elseif ($currentStep === 3): ?>
                    <h2 class="text-xl font-semibold mb-4">Create Admin Account</h2>
                    <form method="post" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Username</label>
                            <input type="text" name="admin_username" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="admin_email" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="password" name="admin_password" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                            <input type="password" name="admin_password_confirm" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">
                            Create Admin Account
                        </button>
                    </form>
                    
                <?php elseif ($currentStep === 4): ?>
                    <h2 class="text-xl font-semibold mb-4">Installation Complete</h2>
                    <p class="text-gray-600 mb-4">The application has been successfully installed. You can now log in with your admin account.</p>
                    <form method="post">
                        <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">
                            Go to Login Page
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 