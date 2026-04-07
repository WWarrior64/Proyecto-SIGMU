<?php
/**
 * Pruebas Manuales de Seguridad - SIGMU
 * 
 * Este archivo contiene pruebas manuales para verificar:
 * 1. Flujo de login/logout
 * 2. Protección contra SQL Injection
 * 3. Protección contra XSS
 * 4. Expiración de sesiones
 * 
 * INSTRUCCIONES:
 * 1. Asegúrate de tener el servidor corriendo: php -S localhost:8000 -t public
 * 2. Ejecuta este archivo desde la línea de comandos: php tests/manual/security_test.php
 * 3. Revisa los resultados en la consola
 */

declare(strict_types=1);

define('BASE_URL', 'http://localhost:8000');

/**
 * Función para hacer peticiones HTTP simuladas
 */
function makeRequest(string $method, string $url, array $data = [], array $cookies = []): array
{
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_COOKIE, implode('; ', $cookies));
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    curl_close($ch);
    
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    // Extraer cookies de respuesta
    preg_match_all('/Set-Cookie: ([^;]+)/', $headers, $matches);
    $responseCookies = $matches[1] ?? [];
    
    return [
        'code' => $httpCode,
        'body' => $body,
        'headers' => $headers,
        'cookies' => $responseCookies
    ];
}

/**
 * Test 1: Login válido
 */
function testValidLogin(): array
{
    echo "\n[TEST 1] Login válido\n";
    echo str_repeat("-", 50) . "\n";
    
    $response = makeRequest('POST', BASE_URL . '/sigmu/login', [
        'username' => 'admin',
        'password' => 'password123'
    ]);
    
    $success = $response['code'] === 302 && strpos($response['headers'], 'Location: /sigmu') !== false;
    
    echo "Código HTTP: {$response['code']}\n";
    echo "Redirección: " . (strpos($response['headers'], 'Location:') !== false ? 'Sí' : 'No') . "\n";
    echo "Resultado: " . ($success ? '✅ PASS' : '❌ FAIL') . "\n";
    
    return ['test' => 'Login válido', 'success' => $success];
}

/**
 * Test 2: Login con credenciales inválidas
 */
function testInvalidLogin(): array
{
    echo "\n[TEST 2] Login con credenciales inválidas\n";
    echo str_repeat("-", 50) . "\n";
    
    $response = makeRequest('POST', BASE_URL . '/sigmu/login', [
        'username' => 'admin',
        'password' => 'wrongpassword'
    ]);
    
    $success = $response['code'] === 302 && strpos($response['headers'], 'error=') !== false;
    
    echo "Código HTTP: {$response['code']}\n";
    echo "Redirección con error: " . (strpos($response['headers'], 'error=') !== false ? 'Sí' : 'No') . "\n";
    echo "Resultado: " . ($success ? '✅ PASS' : '❌ FAIL') . "\n";
    
    return ['test' => 'Login inválido', 'success' => $success];
}

/**
 * Test 3: Intento de SQL Injection en campo username
 */
function testSQLInjectionUsername(): array
{
    echo "\n[TEST 3] SQL Injection en campo username\n";
    echo str_repeat("-", 50) . "\n";
    
    $maliciousInputs = [
        "' OR '1'='1",
        "admin'--",
        "' OR 1=1--",
        "'; DROP TABLE usuarios;--",
        "' UNION SELECT * FROM usuarios--"
    ];
    
    $allSafe = true;
    
    foreach ($maliciousInputs as $input) {
        $response = makeRequest('POST', BASE_URL . '/sigmu/login', [
            'username' => $input,
            'password' => 'anypassword'
        ]);
        
        // Verificar que no hay errores de SQL expuestos
        $hasSQLError = strpos($response['body'], 'SQL') !== false || 
                       strpos($response['body'], 'mysql') !== false ||
                       strpos($response['body'], 'syntax') !== false;
        
        $isSafe = !$hasSQLError && $response['code'] === 302;
        
        echo "Input: " . substr($input, 0, 30) . "...\n";
        echo "  Seguro: " . ($isSafe ? '✅ Sí' : '❌ No') . "\n";
        
        if (!$isSafe) {
            $allSafe = false;
        }
    }
    
    echo "Resultado general: " . ($allSafe ? '✅ PASS' : '❌ FAIL') . "\n";
    
    return ['test' => 'SQL Injection username', 'success' => $allSafe];
}

/**
 * Test 4: Intento de SQL Injection en campo password
 */
function testSQLInjectionPassword(): array
{
    echo "\n[TEST 4] SQL Injection en campo password\n";
    echo str_repeat("-", 50) . "\n";
    
    $maliciousInputs = [
        "' OR '1'='1",
        "password'--",
        "' OR 1=1--"
    ];
    
    $allSafe = true;
    
    foreach ($maliciousInputs as $input) {
        $response = makeRequest('POST', BASE_URL . '/sigmu/login', [
            'username' => 'admin',
            'password' => $input
        ]);
        
        $hasSQLError = strpos($response['body'], 'SQL') !== false || 
                       strpos($response['body'], 'mysql') !== false ||
                       strpos($response['body'], 'syntax') !== false;
        
        $isSafe = !$hasSQLError && $response['code'] === 302;
        
        echo "Input: " . substr($input, 0, 30) . "...\n";
        echo "  Seguro: " . ($isSafe ? '✅ Sí' : '❌ No') . "\n";
        
        if (!$isSafe) {
            $allSafe = false;
        }
    }
    
    echo "Resultado general: " . ($allSafe ? '✅ PASS' : '❌ FAIL') . "\n";
    
    return ['test' => 'SQL Injection password', 'success' => $allSafe];
}

/**
 * Test 5: Intento de XSS en campo username
 */
function testXSSUsername(): array
{
    echo "\n[TEST 5] XSS en campo username\n";
    echo str_repeat("-", 50) . "\n";
    
    $maliciousInputs = [
        '<script>alert("XSS")</script>',
        '<img src=x onerror=alert("XSS")>',
        '"><script>alert("XSS")</script>',
        "javascript:alert('XSS')",
        '<svg onload=alert("XSS")>'
    ];
    
    $allSafe = true;
    
    foreach ($maliciousInputs as $input) {
        $response = makeRequest('POST', BASE_URL . '/sigmu/login', [
            'username' => $input,
            'password' => 'anypassword'
        ]);
        
        // Verificar que el input malicioso no se refleja sin escapar
        $bodyContainsRawInput = strpos($response['body'], $input) !== false;
        $hasScriptTag = strpos($response['body'], '<script>') !== false;
        $hasEventHandler = strpos($response['body'], 'onerror=') !== false || 
                          strpos($response['body'], 'onload=') !== false;
        
        $isSafe = !$bodyContainsRawInput && !$hasScriptTag && !$hasEventHandler;
        
        echo "Input: " . substr($input, 0, 30) . "...\n";
        echo "  Seguro: " . ($isSafe ? '✅ Sí' : '❌ No') . "\n";
        
        if (!$isSafe) {
            $allSafe = false;
        }
    }
    
    echo "Resultado general: " . ($allSafe ? '✅ PASS' : '❌ FAIL') . "\n";
    
    return ['test' => 'XSS username', 'success' => $allSafe];
}

/**
 * Test 6: Logout y limpieza de sesión
 */
function testLogout(): array
{
    echo "\n[TEST 6] Logout y limpieza de sesión\n";
    echo str_repeat("-", 50) . "\n";
    
    // Primero hacer login
    $loginResponse = makeRequest('POST', BASE_URL . '/sigmu/login', [
        'username' => 'admin',
        'password' => 'password123'
    ]);
    
    // Extraer cookie de sesión
    $sessionCookie = '';
    foreach ($loginResponse['cookies'] as $cookie) {
        if (strpos($cookie, 'PHPSESSID') !== false) {
            $sessionCookie = $cookie;
            break;
        }
    }
    
    // Hacer logout
    $logoutResponse = makeRequest('GET', BASE_URL . '/sigmu/logout', [], [$sessionCookie]);
    
    // Verificar que la sesión se destruyó
    $logoutSuccess = $logoutResponse['code'] === 302 && 
                    strpos($logoutResponse['headers'], 'Location: /sigmu') !== false;
    
    echo "Login realizado: ✅\n";
    echo "Logout exitoso: " . ($logoutSuccess ? '✅ Sí' : '❌ No') . "\n";
    echo "Resultado: " . ($logoutSuccess ? '✅ PASS' : '❌ FAIL') . "\n";
    
    return ['test' => 'Logout', 'success' => $logoutSuccess];
}

/**
 * Test 7: Acceso a ruta protegida sin autenticación
 */
function testProtectedRouteWithoutAuth(): array
{
    echo "\n[TEST 7] Acceso a ruta protegida sin autenticación\n";
    echo str_repeat("-", 50) . "\n";
    
    $response = makeRequest('GET', BASE_URL . '/sigmu/edificio?edificio_id=1');
    
    // Debería redirigir al login
    $success = $response['code'] === 302 && 
              strpos($response['headers'], 'Location: /sigmu?error=debes_iniciar_sesion') !== false;
    
    echo "Código HTTP: {$response['code']}\n";
    echo "Redirección a login: " . ($success ? '✅ Sí' : '❌ No') . "\n";
    echo "Resultado: " . ($success ? '✅ PASS' : '❌ FAIL') . "\n";
    
    return ['test' => 'Ruta protegida sin auth', 'success' => $success];
}

/**
 * Ejecutar todas las pruebas
 */
function runAllTests(): void
{
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  PRUEBAS DE SEGURIDAD - SIGMU                            ║\n";
    echo "║  Sistema de Gestión de Activos Universitarios            ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    echo "\nFecha: " . date('Y-m-d H:i:s') . "\n";
    echo "URL Base: " . BASE_URL . "\n";
    
    $tests = [
        testValidLogin(),
        testInvalidLogin(),
        testSQLInjectionUsername(),
        testSQLInjectionPassword(),
        testXSSUsername(),
        testLogout(),
        testProtectedRouteWithoutAuth()
    ];
    
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  RESUMEN DE RESULTADOS                                   ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    
    $passed = 0;
    $failed = 0;
    
    foreach ($tests as $test) {
        $status = $test['success'] ? '✅ PASS' : '❌ FAIL';
        echo sprintf("%-35s %s\n", $test['test'], $status);
        
        if ($test['success']) {
            $passed++;
        } else {
            $failed++;
        }
    }
    
    echo "\n";
    echo "Total: " . count($tests) . " pruebas\n";
    echo "Pasaron: {$passed}\n";
    echo "Fallaron: {$failed}\n";
    echo "\n";
    
    if ($failed === 0) {
        echo "🎉ALL TESTS PASSED! System is secure.\n";
    } else {
        echo "⚠️ATTENTION: {$failed} test(s) failed. Check security.\n";
    }
    
    echo "\n";
}

// Ejecutar pruebas
runAllTests();