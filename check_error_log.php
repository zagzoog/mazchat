<?php
echo "PHP Error Log Location: " . ini_get('error_log') . "\n";
echo "Display Errors: " . ini_get('display_errors') . "\n";
echo "Error Reporting: " . ini_get('error_reporting') . "\n";
echo "Log Errors: " . ini_get('log_errors') . "\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Current Session ID: " . session_id() . "\n";
echo "Session Data: " . print_r($_SESSION, true) . "\n"; 