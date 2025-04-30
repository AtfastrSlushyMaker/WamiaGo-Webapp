<?php
// API status checker

header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>API Status Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; }
        .status { padding: 10px; border-radius: 5px; margin: 10px 0; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .warning { background: #fff3cd; color: #856404; }
        .info { background: #d1ecf1; color: #0c5460; }
        code { background: #f8f9fa; padding: 2px 5px; border-radius: 3px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>API Status Check</h1>
        
        <?php
        $apis = [
            '/debug-user-api.php' => 'Mock User API',
            '/admin/users/api' => 'Real User API'
        ];
        
        foreach ($apis as $endpoint => $name) {
            echo "<h2>{$name} ({$endpoint})</h2>";
            
            // Perform the check
            $url = 'http://' . $_SERVER['HTTP_HOST'] . $endpoint;
            echo "<p>Testing: <code>{$url}</code></p>";
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_HEADER, true);
            
            $start = microtime(true);
            $response = curl_exec($ch);
            $end = microtime(true);
            
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $body = substr($response, $header_size);
            
            $info = curl_getinfo($ch);
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            
            curl_close($ch);
            
            $time_ms = round(($end - $start) * 1000);
            
            // Show results
            if ($errno) {
                echo "<div class='status error'>";
                echo "<strong>Error:</strong> {$error} (Code: {$errno})";
                echo "</div>";
            } else {
                $statusClass = ($info['http_code'] >= 200 && $info['http_code'] < 300) ? 'success' : 'error';
                
                echo "<div class='status {$statusClass}'>";
                echo "<strong>Status:</strong> {$info['http_code']} ({$info['http_code_text'] ?? 'Unknown'})";
                echo "<br><strong>Response Time:</strong> {$time_ms}ms";
                echo "</div>";
                
                echo "<h3>Response Headers:</h3>";
                echo "<pre>" . htmlspecialchars($header) . "</pre>";
                
                echo "<h3>Response Body:</h3>";
                
                // Try to pretty print if JSON
                $json = json_decode($body);
                if (json_last_error() === JSON_ERROR_NONE) {
                    echo "<pre>" . htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT)) . "</pre>";
                } else {
                    echo "<pre>" . htmlspecialchars($body) . "</pre>";
                }
            }
            
            echo "<hr>";
        }
        ?>
        
        <h2>Environment Information</h2>
        <ul>
            <li><strong>Server:</strong> <?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') ?></li>
            <li><strong>PHP Version:</strong> <?= phpversion() ?></li>
            <li><strong>Request Time:</strong> <?= date('Y-m-d H:i:s') ?></li>
        </ul>
        
        <p><a href="/admin/debug/user-management">Back to Debug Dashboard</a></p>
    </div>
</body>
</html>
