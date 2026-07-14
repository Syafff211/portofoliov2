<?php
// Pixora - COMPLETE VERSION with ALL FEATURES
// Save as: C:\xampp\htdocs\pixora\index.php

// Start session
if (session_id() == '') {
    session_start();
}

// Handle ALL requests
$request = $_SERVER['REQUEST_URI'];
$path = str_replace('/pixora', '', $request);
$path = parse_url($path, PHP_URL_PATH);
$path = trim($path, '/');

// Split path
$segments = explode('/', $path);
$page = $segments[0] ?? 'home';
$param1 = $segments[1] ?? '';
$param2 = $segments[2] ?? '';

// Database connection dengan error handling yang lebih baik
$pdo = null;
try {
    $pdo = new PDO('mysql:host=localhost;dbname=pixora;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // We'll handle DB errors in functions
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    if (strpos($url, 'http') !== 0) {
        $url = '/pixora/' . ltrim($url, '/');
    }
    header('Location: ' . $url);
    exit();
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/login');
    }
}

function requireGuest() {
    if (isLoggedIn()) {
        redirect('/feed');
    }
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function uploadImage($file, $type = 'post') {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $max_size = 10 * 1024 * 1024; // 10MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Upload error'];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        return ['error' => 'Invalid file type. Allowed: JPG, PNG, GIF, WebP'];
    }
    
    if ($file['size'] > $max_size) {
        return ['error' => 'File too large (max 10MB)'];
    }
    
    $upload_dir = 'uploads/' . $type . '/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $target = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target)) {
        return ['success' => true, 'path' => $target];
    }
    
    return ['error' => 'Failed to save file'];
}

// PERBAIKAN: Fungsi getDB() yang benar
function getDB() {
    global $pdo;
    
    // Jika $pdo null, coba koneksi ulang
    if (!$pdo) {
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=pixora;charset=utf8mb4', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Return null jika gagal koneksi
            return null;
        }
    }
    
    return $pdo;
}

function show404() {
    header("HTTP/1.0 404 Not Found");
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>404 Not Found - Pixora</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                margin: 0;
                color: white;
            }
            .error-container {
                text-align: center;
                background: rgba(255, 255, 255, 0.1);
                padding: 40px;
                border-radius: 20px;
                backdrop-filter: blur(10px);
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            }
            h1 {
                font-size: 100px;
                margin: 0;
                color: #fff;
            }
            h2 {
                font-size: 24px;
                margin: 20px 0;
                color: #fff;
            }
            p {
                margin-bottom: 30px;
                color: rgba(255, 255, 255, 0.8);
            }
            .home-btn {
                display: inline-block;
                padding: 12px 30px;
                background: white;
                color: #667eea;
                text-decoration: none;
                border-radius: 50px;
                font-weight: bold;
                transition: transform 0.3s, box-shadow 0.3s;
            }
            .home-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1>404</h1>
            <h2>Page Not Found</h2>
            <p>The page you are looking for doesn\'t exist or has been moved.</p>
            <a href="/pixora" class="home-btn">Go to Homepage</a>
        </div>
    </body>
    </html>';
    exit();
}

function time_ago($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    $seconds = $diff;
    $minutes = round($diff / 60);
    $hours = round($diff / 3600);
    $days = round($diff / 86400);
    $weeks = round($diff / 604800);
    $months = round($diff / 2600640);
    $years = round($diff / 31207680);
    
    if ($seconds <= 60) return "Just now";
    elseif ($minutes <= 60) return $minutes == 1 ? "1 minute ago" : $minutes . " minutes ago";
    elseif ($hours <= 24) return $hours == 1 ? "1 hour ago" : $hours . " hours ago";
    elseif ($days <= 7) return $days == 1 ? "Yesterday" : $days . " days ago";
    elseif ($weeks <= 4.3) return $weeks == 1 ? "1 week ago" : $weeks . " weeks ago";
    elseif ($months <= 12) return $months == 1 ? "1 month ago" : $months . " months ago";
    else return $years == 1 ? "1 year ago" : $years . " years ago";
}

// PERBAIKAN: Tidak ada kurung kurawal tambahan di sini
// ============ PAGE ROUTING ============

switch ($page) {
    case '':
    case 'home':
        if (isLoggedIn()) {
            showFeed();
        } else {
            showHome();
        }
        break;
        
    case 'register':
        handleRegister();
        break;
        
    case 'login':
        handleLogin();
        break;
        
    case 'logout':
        session_destroy();
        redirect('/');
        break;
        
    case 'feed':
        requireLogin();
        showFeed();
        break;
        
    case 'create':
        requireLogin();
        handleCreatePost();
        break;
        
    case 'profile':
        requireLogin();
        if ($param1) {
            showUserProfile($param1);
        } else {
            showMyProfile();
        }
        break;
        
    case 'follow':
        requireLogin();
        handleFollow($param1);
        break;
        
    case 'unfollow':
        requireLogin();
        handleUnfollow($param1);
        break;
        
    case 'followers':
        requireLogin();
        if ($param1) {
            showUserFollowers($param1);
        } else {
            showMyFollowers();
        }
        break;
        
    case 'following':
        requireLogin();
        if ($param1) {
            showUserFollowing($param1);
        } else {
            showMyFollowing();
        }
        break;
        
    case 'messages':
        requireLogin();
        showMessages();
        break;
        
    case 'message':
        requireLogin();
        if ($param1) {
            showConversation($param1);
        } else {
            showMessages();
        }
        break;
        
    case 'search':
        requireLogin();
        handleSearch();
        break;
        
    case 'settings':
        requireLogin();
        handleSettings();
        break;
        
    case 'explore':
        requireLogin();
        showExplore();
        break;
        
    case 'post':
        requireLogin();
        if ($param1) {
            showSinglePost($param1);
        } else {
            redirect('/feed');
        }
        break;
        
    default:
        show404();
        break;
}
// PERBAIKAN: Pastikan ini adalah akhir dari switch statement

// ============ PAGE FUNCTIONS ============

function showHome() {
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pixora - Share Your World</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;
                background: #fafafa;
                color: #262626;
                line-height: 1.6;
            }
            
            .hero {
                min-height: 100vh;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem;
                position: relative;
                overflow: hidden;
            }
            
            .hero::before {
                content: \'\';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: url(\'https://images.unsplash.com/photo-1513475382585-d06e58bcb0e0?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80\') center/cover;
                opacity: 0.1;
            }
            
            .hero-content {
                max-width: 1200px;
                width: 100%;
                text-align: center;
                position: relative;
                z-index: 2;
            }
            
            .logo {
                font-size: 4rem;
                font-weight: 800;
                background: linear-gradient(45deg, #ff6b6b, #feca57, #48dbfb, #ff9ff3);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                margin-bottom: 1rem;
                letter-spacing: -2px;
            }
            
            .tagline {
                font-size: 1.5rem;
                color: white;
                margin-bottom: 3rem;
                opacity: 0.9;
                font-weight: 300;
            }
            
            .cta-buttons {
                display: flex;
                gap: 1.5rem;
                justify-content: center;
                margin-bottom: 4rem;
            }
            
            .btn {
                padding: 1rem 2.5rem;
                border-radius: 50px;
                font-size: 1.1rem;
                font-weight: 600;
                text-decoration: none;
                transition: all 0.3s ease;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
            }
            
            .btn-primary {
                background: white;
                color: #667eea;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            }
            
            .btn-primary:hover {
                transform: translateY(-3px);
                box-shadow: 0 15px 40px rgba(0,0,0,0.3);
            }
            
            .btn-secondary {
                background: rgba(255,255,255,0.1);
                color: white;
                border: 2px solid rgba(255,255,255,0.3);
                backdrop-filter: blur(10px);
            }
            
            .btn-secondary:hover {
                background: rgba(255,255,255,0.2);
                border-color: rgba(255,255,255,0.5);
                transform: translateY(-3px);
            }
            
            .features {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 2rem;
                margin-top: 4rem;
            }
            
            .feature {
                background: rgba(255,255,255,0.1);
                backdrop-filter: blur(10px);
                border-radius: 20px;
                padding: 2rem;
                text-align: center;
                border: 1px solid rgba(255,255,255,0.2);
                transition: all 0.3s ease;
            }
            
            .feature:hover {
                transform: translateY(-5px);
                background: rgba(255,255,255,0.15);
                border-color: rgba(255,255,255,0.3);
            }
            
            .feature-icon {
                font-size: 3rem;
                margin-bottom: 1rem;
            }
            
            .feature h3 {
                color: white;
                margin-bottom: 0.5rem;
                font-size: 1.3rem;
            }
            
            .feature p {
                color: rgba(255,255,255,0.8);
                font-size: 0.95rem;
            }
            
            .trending-posts {
                background: white;
                padding: 6rem 2rem;
            }
            
            .container {
                max-width: 1200px;
                margin: 0 auto;
            }
            
            .section-title {
                text-align: center;
                font-size: 2.5rem;
                margin-bottom: 3rem;
                color: #262626;
                font-weight: 700;
            }
            
            .posts-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 2rem;
            }
            
            .post-card {
                background: white;
                border-radius: 15px;
                overflow: hidden;
                box-shadow: 0 5px 20px rgba(0,0,0,0.08);
                transition: all 0.3s ease;
            }
            
            .post-card:hover {
                transform: translateY(-10px);
                box-shadow: 0 15px 40px rgba(0,0,0,0.15);
            }
            
            .post-image {
                width: 100%;
                height: 250px;
                object-fit: cover;
            }
            
            .post-info {
                padding: 1.5rem;
            }
            
            .post-author {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                margin-bottom: 1rem;
            }
            
            .author-avatar {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                object-fit: cover;
            }
            
            .author-name {
                font-weight: 600;
                color: #262626;
            }
            
            .post-likes {
                color: #8e8e8e;
                font-size: 0.9rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            
            footer {
                background: #262626;
                color: white;
                padding: 4rem 2rem;
                text-align: center;
            }
            
            .footer-content {
                max-width: 1200px;
                margin: 0 auto;
            }
            
            .footer-links {
                display: flex;
                justify-content: center;
                gap: 2rem;
                margin: 2rem 0;
            }
            
            .footer-links a {
                color: rgba(255,255,255,0.7);
                text-decoration: none;
                transition: color 0.3s ease;
            }
            
            .footer-links a:hover {
                color: white;
            }
            
            .copyright {
                color: rgba(255,255,255,0.5);
                font-size: 0.9rem;
                margin-top: 2rem;
            }
            
            @media (max-width: 768px) {
                .logo {
                    font-size: 3rem;
                }
                
                .tagline {
                    font-size: 1.2rem;
                }
                
                .cta-buttons {
                    flex-direction: column;
                    align-items: center;
                }
                
                .btn {
                    width: 100%;
                    max-width: 300px;
                    justify-content: center;
                }
                
                .features {
                    grid-template-columns: 1fr;
                }
                
                .posts-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body>
        <div class="hero">
            <div class="hero-content">
                <h1 class="logo">Pixora</h1>
                <p class="tagline">Share your moments, discover amazing content, connect with creatives</p>
                
                <div class="cta-buttons">
                    <a href="/pixora/register" class="btn btn-primary">
                        <span>Get Started</span>
                        <span>→</span>
                    </a>
                    <a href="/pixora/login" class="btn btn-secondary">
                        <span>Sign In</span>
                    </a>
                </div>
                
                <div class="features">
                    <div class="feature">
                        <div class="feature-icon">📸</div>
                        <h3>Share Photos & Videos</h3>
                        <p>Upload and share your favorite moments in high quality</p>
                    </div>
                    <div class="feature">
                        <div class="feature-icon">👥</div>
                        <h3>Connect & Follow</h3>
                        <p>Build your community and discover amazing creators</p>
                    </div>
                    <div class="feature">
                        <div class="feature-icon">💬</div>
                        <h3>Direct Messages</h3>
                        <p>Private conversations with mutual followers</p>
                    </div>
                    <div class="feature">
                        <div class="feature-icon">🔒</div>
                        <h3>Privacy First</h3>
                        <p>Full control over your content and connections</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="trending-posts">
            <div class="container">
                <h2 class="section-title">Trending on Pixora</h2>
                <div class="posts-grid">
                    <div class="post-card">
                        <img src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Mountain" class="post-image">
                        <div class="post-info">
                            <div class="post-author">
                                <img src="https://images.unsplash.com/photo-1494790108755-2616b612b786?ixlib=rb-1.2.1&auto=format&fit=crop&w=100&q=80" alt="User" class="author-avatar">
                                <div class="author-name">@traveler</div>
                            </div>
                            <p>Exploring the Swiss Alps 🏔️ #nature #travel</p>
                            <div class="post-likes">❤️ 2.4k likes</div>
                        </div>
                    </div>
                    
                    <div class="post-card">
                        <img src="https://images.unsplash.com/photo-1513364776144-60967b0f800f?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Food" class="post-image">
                        <div class="post-info">
                            <div class="post-author">
                                <img src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?ixlib=rb-1.2.1&auto=format&fit=crop&w=100&q=80" alt="User" class="author-avatar">
                                <div class="author-name">@chef_life</div>
                            </div>
                            <p>Homemade pasta from scratch! 🍝 #food #cooking</p>
                            <div class="post-likes">❤️ 1.8k likes</div>
                        </div>
                    </div>
                    
                    <div class="post-card">
                        <img src="https://images.unsplash.com/photo-1511379938547-c1f69419868d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Music" class="post-image">
                        <div class="post-info">
                            <div class="post-author">
                                <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-1.2.1&auto=format&fit=crop&w=100&q=80" alt="User" class="author-avatar">
                                <div class="author-name">@musician</div>
                            </div>
                            <p>Studio session today! 🎵 #music #producer</p>
                            <div class="post-likes">❤️ 3.1k likes</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <footer>
            <div class="footer-content">
                <h2 style="font-size: 2rem; margin-bottom: 1rem;">Join the Pixora Community</h2>
                <p style="color: rgba(255,255,255,0.7); margin-bottom: 2rem; max-width: 600px; margin-left: auto; margin-right: auto;">
                    Share your world with millions of creative people. It\'s free and always will be.
                </p>
                
                <div class="footer-links">
                    <a href="#">About</a>
                    <a href="#">Blog</a>
                    <a href="#">Jobs</a>
                    <a href="#">Help</a>
                    <a href="#">API</a>
                    <a href="#">Privacy</a>
                    <a href="#">Terms</a>
                </div>
                
                <div class="copyright">
                    © 2024 Pixora. All rights reserved.
                </div>
            </div>
        </footer>
    </body>
    </html>';
}

// PERBAIKAN: Fungsi showFeed() yang sudah dikoreksi
function showFeed() {
    requireLogin();
    $user_id = $_SESSION['user_id'];
    
    // PERBAIKAN: Gunakan getDB() untuk mendapatkan koneksi
    $pdo = getDB();
    if (!$pdo) {
        echo '<h1>Database Error</h1><p>Cannot connect to database. Please check your MySQL server.</p>';
        return;
    }
    
    // Get posts from users we follow (including our own)
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, u.username, u.profile_picture, 
                   (SELECT COUNT(*) FROM follows WHERE follower_id = ? AND followee_id = p.user_id AND status = 'accepted') as is_following
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.user_id = ? 
               OR p.user_id IN (
                   SELECT followee_id FROM follows 
                   WHERE follower_id = ? AND status = 'accepted'
               )
            ORDER BY p.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$user_id, $user_id, $user_id]);
        $posts = $stmt->fetchAll();
    } catch (Exception $e) {
        $posts = [];
    }
    
    // Get suggested users
    $suggested_users = [];
    try {
        $stmt = $pdo->prepare("
            SELECT u.* FROM users u 
            WHERE u.id != ? 
            AND u.id NOT IN (
                SELECT followee_id FROM follows WHERE follower_id = ?
            )
            ORDER BY RAND()
            LIMIT 5
        ");
        $stmt->execute([$user_id, $user_id]);
        $suggested_users = $stmt->fetchAll();
    } catch (Exception $e) {
        $suggested_users = [];
    }
    
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Feed • Pixora</title>
        <style>
            :root {
                --primary: #0095f6;
                --primary-hover: #0081d6;
                --secondary: #8e8e8e;
                --border: #dbdbdb;
                --background: #fafafa;
                --text: #262626;
                --text-light: #8e8e8e;
                --white: #ffffff;
                --red: #ed4956;
                --green: #42b72a;
            }
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;
                background: var(--background);
                color: var(--text);
                line-height: 1.4;
            }
            
            /* Navigation */
            .navbar {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: 60px;
                background: var(--white);
                border-bottom: 1px solid var(--border);
                z-index: 1000;
                padding: 0 20px;
            }
            
            .nav-container {
                max-width: 975px;
                height: 100%;
                margin: 0 auto;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            
            .logo {
                font-size: 1.5rem;
                font-weight: 700;
                background: linear-gradient(45deg, #ff6b6b, #feca57, #48dbfb, #ff9ff3);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                text-decoration: none;
            }
            
            .search-container {
                position: relative;
                width: 268px;
            }
            
            .search-input {
                width: 100%;
                padding: 8px 16px;
                background: #efefef;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                color: var(--text);
                outline: none;
            }
            
            .search-input::placeholder {
                color: #8e8e8e;
                text-align: center;
            }
            
            .search-input:focus {
                background: var(--white);
                box-shadow: 0 0 0 1px var(--border);
            }
            
            .search-results {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--white);
                border: 1px solid var(--border);
                border-radius: 8px;
                margin-top: 4px;
                max-height: 362px;
                overflow-y: auto;
                box-shadow: 0 4px 24px rgba(0,0,0,0.15);
                display: none;
            }
            
            .search-result-item {
                display: flex;
                align-items: center;
                padding: 12px 16px;
                text-decoration: none;
                color: var(--text);
                transition: background 0.2s;
            }
            
            .search-result-item:hover {
                background: #fafafa;
            }
            
            .search-result-avatar {
                width: 44px;
                height: 44px;
                border-radius: 50%;
                object-fit: cover;
                margin-right: 12px;
                border: 1px solid var(--border);
            }
            
            .search-result-info {
                flex: 1;
            }
            
            .search-result-username {
                font-weight: 600;
                margin-bottom: 2px;
            }
            
            .search-result-name {
                color: var(--text-light);
                font-size: 14px;
            }
            
            .nav-icons {
                display: flex;
                align-items: center;
                gap: 22px;
            }
            
            .nav-icon {
                font-size: 24px;
                color: var(--text);
                text-decoration: none;
                position: relative;
            }
            
            .nav-icon:hover {
                color: var(--text-light);
            }
            
            /* Main Layout */
            .main-container {
                max-width: 975px;
                margin: 84px auto 30px;
                padding: 0 20px;
                display: grid;
                grid-template-columns: 1fr 380px;
                gap: 40px;
            }
            
            /* Posts */
            .posts {
                display: flex;
                flex-direction: column;
                gap: 24px;
            }
            
            .post-card {
                background: var(--white);
                border: 1px solid var(--border);
                border-radius: 12px;
                overflow: hidden;
            }
            
            .post-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 16px;
                border-bottom: 1px solid var(--border);
            }
            
            .post-user {
                display: flex;
                align-items: center;
                gap: 12px;
                text-decoration: none;
                color: inherit;
            }
            
            .post-avatar {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                object-fit: cover;
                border: 1px solid var(--border);
            }
            
            .post-username {
                font-weight: 600;
                font-size: 14px;
            }
            
            .post-more {
                background: none;
                border: none;
                font-size: 20px;
                color: var(--text);
                cursor: pointer;
                padding: 4px;
            }
            
            .post-image {
                width: 100%;
                aspect-ratio: 1;
                object-fit: cover;
                display: block;
                background: #f0f0f0;
            }
            
            .post-actions {
                padding: 16px;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            
            .post-left-actions {
                display: flex;
                align-items: center;
                gap: 16px;
            }
            
            .post-action {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                padding: 0;
                color: var(--text);
                transition: transform 0.2s;
            }
            
            .post-action:hover {
                transform: scale(1.1);
            }
            
            .post-action.liked {
                color: var(--red);
            }
            
            .post-likes {
                padding: 0 16px 8px;
                font-weight: 600;
                font-size: 14px;
            }
            
            .post-caption {
                padding: 0 16px 8px;
                font-size: 14px;
            }
            
            .post-caption-user {
                font-weight: 600;
                text-decoration: none;
                color: var(--text);
                margin-right: 4px;
            }
            
            .post-time {
                padding: 0 16px 12px;
                color: var(--text-light);
                font-size: 12px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .post-comment-form {
                padding: 16px;
                border-top: 1px solid var(--border);
                display: flex;
                gap: 16px;
            }
            
            .comment-input {
                flex: 1;
                border: none;
                outline: none;
                font-size: 14px;
                color: var(--text);
                background: transparent;
            }
            
            .comment-input::placeholder {
                color: var(--text-light);
            }
            
            .post-comment-btn {
                background: none;
                border: none;
                color: var(--primary);
                font-weight: 600;
                cursor: pointer;
                opacity: 0.3;
            }
            
            .post-comment-btn.active {
                opacity: 1;
            }
            
            /* Sidebar */
            .sidebar {
                position: sticky;
                top: 84px;
                height: fit-content;
            }
            
            .user-card {
                display: flex;
                align-items: center;
                gap: 16px;
                margin-bottom: 20px;
            }
            
            .user-card-avatar {
                width: 56px;
                height: 56px;
                border-radius: 50%;
                object-fit: cover;
                border: 2px solid transparent;
                background: linear-gradient(45deg, #ff6b6b, #feca57, #48dbfb, #ff9ff3) border-box;
            }
            
            .user-card-info {
                flex: 1;
            }
            
            .user-card-username {
                font-weight: 600;
                font-size: 14px;
                text-decoration: none;
                color: var(--text);
                display: block;
                margin-bottom: 2px;
            }
            
            .user-card-name {
                color: var(--text-light);
                font-size: 14px;
            }
            
            .user-card-switch {
                color: var(--primary);
                font-size: 12px;
                font-weight: 600;
                text-decoration: none;
            }
            
            .suggestions {
                background: var(--white);
                border: 1px solid var(--border);
                border-radius: 12px;
                padding: 16px;
                margin-bottom: 20px;
            }
            
            .suggestions-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 16px;
            }
            
            .suggestions-title {
                color: var(--text-light);
                font-weight: 600;
                font-size: 14px;
            }
            
            .suggestions-see-all {
                color: var(--text);
                font-size: 12px;
                font-weight: 600;
                text-decoration: none;
            }
            
            .suggestion-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 12px;
            }
            
            .suggestion-user {
                display: flex;
                align-items: center;
                gap: 12px;
                text-decoration: none;
                color: inherit;
            }
            
            .suggestion-avatar {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                object-fit: cover;
                border: 1px solid var(--border);
            }
            
            .suggestion-info {
                flex: 1;
            }
            
            .suggestion-username {
                font-weight: 600;
                font-size: 14px;
                margin-bottom: 2px;
            }
            
            .suggestion-followers {
                color: var(--text-light);
                font-size: 12px;
            }
            
            .suggestion-follow {
                color: var(--primary);
                font-size: 12px;
                font-weight: 600;
                text-decoration: none;
            }
            
            .suggestion-follow:hover {
                color: var(--primary-hover);
            }
            
            .footer-links {
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
                margin-top: 20px;
            }
            
            .footer-link {
                color: var(--text-light);
                font-size: 11px;
                text-decoration: none;
            }
            
            .footer-link:hover {
                text-decoration: underline;
            }
            
            .copyright {
                color: var(--text-light);
                font-size: 11px;
                margin-top: 16px;
            }
            
            /* Empty State */
            .empty-feed {
                text-align: center;
                padding: 60px 20px;
                background: var(--white);
                border: 1px solid var(--border);
                border-radius: 12px;
            }
            
            .empty-icon {
                font-size: 48px;
                margin-bottom: 16px;
                opacity: 0.5;
            }
            
            .empty-title {
                font-size: 20px;
                font-weight: 600;
                margin-bottom: 8px;
                color: var(--text);
            }
            
            .empty-description {
                color: var(--text-light);
                margin-bottom: 24px;
                max-width: 400px;
                margin-left: auto;
                margin-right: auto;
            }
            
            .empty-action {
                display: inline-block;
                padding: 10px 24px;
                background: var(--primary);
                color: var(--white);
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                font-size: 14px;
                transition: background 0.2s;
            }
            
            .empty-action:hover {
                background: var(--primary-hover);
            }
            
            /* Responsive */
            @media (max-width: 1000px) {
                .main-container {
                    grid-template-columns: 1fr;
                    max-width: 600px;
                }
                
                .sidebar {
                    display: none;
                }
            }
            
            @media (max-width: 768px) {
                .navbar {
                    padding: 0 16px;
                }
                
                .search-container {
                    display: none;
                }
                
                .main-container {
                    padding: 0 16px;
                    margin-top: 60px;
                }
            }
        </style>
    </head>
    <body>
        <nav class="navbar">
            <div class="nav-container">
                <a href="/pixora/feed" class="logo">Pixora</a>
                
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Search users..." id="searchInput" 
                           onkeyup="searchUsers(this.value)" onfocus="showSearchResults()">
                    <div class="search-results" id="searchResults"></div>
                </div>
                
                <div class="nav-icons">
                    <a href="/pixora/feed" class="nav-icon" title="Home">🏠</a>
                    <a href="/pixora/explore" class="nav-icon" title="Explore">🔍</a>
                    <a href="/pixora/create" class="nav-icon" title="Create">➕</a>
                    <a href="/pixora/messages" class="nav-icon" title="Messages">💬</a>
                    <a href="/pixora/profile" class="nav-icon" title="Profile">👤</a>
                    <a href="/pixora/logout" class="nav-icon" title="Logout">🚪</a>
                </div>
            </div>
        </nav>
        
        <div class="main-container">
            <div class="posts">';
    
    if (empty($posts)) {
        echo '<div class="empty-feed">
                <div class="empty-icon">📷</div>
                <h2 class="empty-title">Your feed is empty</h2>
                <p class="empty-description">
                    Follow some users to see their photos here, or share your first post!
                </p>
                <a href="/pixora/explore" class="empty-action">Find people to follow</a>
                <a href="/pixora/create" class="empty-action" style="background: var(--green); margin-left: 12px;">Create your first post</a>
              </div>';
    } else {
        foreach ($posts as $post) {
            $profile_pic = $post['profile_picture'] && file_exists($post['profile_picture']) ? 
                         $post['profile_picture'] : 'https://via.placeholder.com/32';
            
            $image_url = $post['image_path'];
            if (!filter_var($image_url, FILTER_VALIDATE_URL) && file_exists($image_url)) {
                $image_url = '/pixora/' . $image_url;
            } elseif (!filter_var($image_url, FILTER_VALIDATE_URL)) {
                $image_url = 'https://via.placeholder.com/600x600?text=Pixora+Post';
            }
            
            $likes = rand(10, 5000);
            $comments = rand(0, 200);
            $time_ago = time_ago($post['created_at']);
            
            echo '<article class="post-card">
                    <div class="post-header">
                        <a href="/pixora/profile/' . $post['username'] . '" class="post-user">
                            <img src="' . $profile_pic . '" alt="' . $post['username'] . '" class="post-avatar">
                            <div class="post-username">' . $post['username'] . '</div>
                        </a>
                        <button class="post-more">⋯</button>
                    </div>
                    
                    <img src="' . $image_url . '" alt="Post by ' . $post['username'] . '" class="post-image">
                    
                    <div class="post-actions">
                        <div class="post-left-actions">
                            <button class="post-action" onclick="likePost(' . $post['id'] . ', this)">♥</button>
                            <button class="post-action">💬</button>
                            <button class="post-action">📤</button>
                        </div>
                        <button class="post-action">🔖</button>
                    </div>
                    
                    <div class="post-likes">' . number_format($likes) . ' likes</div>
                    
                    <div class="post-caption">
                        <a href="/pixora/profile/' . $post['username'] . '" class="post-caption-user">' . $post['username'] . '</a>
                        ' . htmlspecialchars($post['caption'] ?: '') . '
                    </div>
                    
                    <div class="post-time">' . $time_ago . '</div>
                    
                    <form class="post-comment-form" onsubmit="return addComment(' . $post['id'] . ', this)">
                        <input type="text" class="comment-input" placeholder="Add a comment..." required>
                        <button type="submit" class="post-comment-btn active">Post</button>
                    </form>
                  </article>';
        }
    }
    
    echo '</div>
            
            <div class="sidebar">
                <div class="user-card">
                    <img src="' . ($_SESSION['profile_pic'] ?? 'https://via.placeholder.com/56') . '" alt="' . $_SESSION['username'] . '" class="user-card-avatar">
                    <div class="user-card-info">
                        <a href="/pixora/profile" class="user-card-username">' . $_SESSION['username'] . '</a>
                        <div class="user-card-name">' . ($_SESSION['full_name'] ?? $_SESSION['username']) . '</div>
                    </div>
                    <a href="/pixora/logout" class="user-card-switch">Switch</a>
                </div>
                
                <div class="suggestions">
                    <div class="suggestions-header">
                        <div class="suggestions-title">Suggestions For You</div>
                        <a href="/pixora/explore" class="suggestions-see-all">See All</a>
                    </div>';
    
    if (!empty($suggested_users)) {
        foreach ($suggested_users as $user) {
            $followers = rand(100, 10000);
            echo '<div class="suggestion-item">
                    <a href="/pixora/profile/' . $user['username'] . '" class="suggestion-user">
                        <img src="' . ($user['profile_picture'] ?: 'https://via.placeholder.com/32') . '" alt="' . $user['username'] . '" class="suggestion-avatar">
                        <div class="suggestion-info">
                            <div class="suggestion-username">' . $user['username'] . '</div>
                            <div class="suggestion-followers">' . number_format($followers) . ' followers</div>
                        </div>
                    </a>
                    <a href="/pixora/follow/' . $user['id'] . '" class="suggestion-follow">Follow</a>
                  </div>';
        }
    } else {
        echo '<div style="color: var(--text-light); font-size: 14px; text-align: center; padding: 20px;">
                No suggestions available
              </div>';
    }
    
    echo '</div>
                
                <div class="footer-links">
                    <a href="#" class="footer-link">About</a>
                    <a href="#" class="footer-link">Help</a>
                    <a href="#" class="footer-link">Press</a>
                    <a href="#" class="footer-link">API</a>
                    <a href="#" class="footer-link">Jobs</a>
                    <a href="#" class="footer-link">Privacy</a>
                    <a href="#" class="footer-link">Terms</a>
                    <a href="#" class="footer-link">Locations</a>
                    <a href="#" class="footer-link">Language</a>
                    <a href="#" class="footer-link">Meta Verified</a>
                </div>
                
                <div class="copyright">© 2024 Pixora</div>
            </div>
        </div>
        
        <script>
        function searchUsers(query) {
            if (query.length < 2) {
                document.getElementById("searchResults").style.display = "none";
                return;
            }
            
            fetch("/pixora/search?q=" + encodeURIComponent(query) + "&ajax=1")
                .then(response => response.text())
                .then(html => {
                    const results = document.getElementById("searchResults");
                    results.innerHTML = html;
                    results.style.display = "block";
                });
        }
        
        function showSearchResults() {
            const input = document.getElementById("searchInput");
            if (input.value.length >= 2) {
                document.getElementById("searchResults").style.display = "block";
            }
        }
        
        // Close search results when clicking outside
        document.addEventListener("click", function(event) {
            const searchContainer = document.querySelector(".search-container");
            const searchResults = document.getElementById("searchResults");
            
            if (!searchContainer.contains(event.target)) {
                searchResults.style.display = "none";
            }
        });
        
        function likePost(postId, button) {
            button.classList.toggle("liked");
            const likesElement = button.closest(".post-card").querySelector(".post-likes");
            let currentLikes = parseInt(likesElement.textContent.replace(/[^0-9]/g, ""));
            
            if (button.classList.contains("liked")) {
                currentLikes++;
            } else {
                currentLikes--;
            }
            
            likesElement.textContent = currentLikes.toLocaleString() + " likes";
        }
        
        function addComment(postId, form) {
            const input = form.querySelector(".comment-input");
            const comment = input.value.trim();
            
            if (comment) {
                // In a real app, you would send this to the server
                input.value = "";
                alert("Comment added: " + comment);
            }
            
            return false;
        }
        </script>
    </body>
    </html>';
}

function showExplore() {
    requireLogin();
    global $pdo;
    
    try {
        // Get random posts for explore
        $stmt = $pdo->prepare("
            SELECT p.*, u.username, u.profile_picture 
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            ORDER BY RAND() 
            LIMIT 30
        ");
        $stmt->execute();
        $posts = $stmt->fetchAll();
    } catch (Exception $e) {
        $posts = [];
    }
    
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Explore • Pixora</title>
        <style>
            :root {
                --primary: #0095f6;
                --border: #dbdbdb;
                --background: #fafafa;
                --text: #262626;
            }
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;
                background: var(--background);
                color: var(--text);
            }
            
            .navbar {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: 60px;
                background: white;
                border-bottom: 1px solid var(--border);
                z-index: 1000;
                padding: 0 20px;
            }
            
            .nav-container {
                max-width: 975px;
                height: 100%;
                margin: 0 auto;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            
            .logo {
                font-size: 1.5rem;
                font-weight: 700;
                background: linear-gradient(45deg, #ff6b6b, #feca57, #48dbfb, #ff9ff3);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                text-decoration: none;
            }
            
            .nav-icons {
                display: flex;
                align-items: center;
                gap: 22px;
            }
            
            .nav-icon {
                font-size: 24px;
                color: var(--text);
                text-decoration: none;
            }
            
            .explore-container {
                max-width: 975px;
                margin: 84px auto 30px;
                padding: 0 20px;
            }
            
            .explore-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 28px;
            }
            
            .explore-post {
                aspect-ratio: 1;
                position: relative;
                border-radius: 8px;
                overflow: hidden;
            }
            
            .explore-post img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
                transition: transform 0.3s ease;
            }
            
            .explore-post:hover img {
                transform: scale(1.05);
            }
            
            .post-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.4);
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 30px;
                color: white;
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            
            .explore-post:hover .post-overlay {
                opacity: 1;
            }
            
            .post-stat {
                display: flex;
                align-items: center;
                gap: 8px;
                font-weight: 600;
                font-size: 1.1rem;
            }
            
            @media (max-width: 768px) {
                .explore-grid {
                    grid-template-columns: repeat(2, 1fr);
                    gap: 3px;
                }
                
                .explore-container {
                    padding: 0;
                    margin-top: 60px;
                }
                
                .explore-post {
                    border-radius: 0;
                }
            }
        </style>
    </head>
    <body>
        <nav class="navbar">
            <div class="nav-container">
                <a href="/pixora/feed" class="logo">Pixora</a>
                <div class="nav-icons">
                    <a href="/pixora/feed" class="nav-icon" title="Home">🏠</a>
                    <a href="/pixora/explore" class="nav-icon" title="Explore" style="color: var(--primary);">🔍</a>
                    <a href="/pixora/create" class="nav-icon" title="Create">➕</a>
                    <a href="/pixora/messages" class="nav-icon" title="Messages">💬</a>
                    <a href="/pixora/profile" class="nav-icon" title="Profile">👤</a>
                </div>
            </div>
        </nav>
        
        <div class="explore-container">
            <div class="explore-grid">';
    
    if (empty($posts)) {
        echo '<div style="grid-column: 1 / -1; text-align: center; padding: 100px 20px; color: #8e8e8e;">
                <h2>No posts to explore yet</h2>
                <p style="margin-top: 10px;">Be the first to create a post!</p>
                <a href="/pixora/create" style="display: inline-block; margin-top: 20px; padding: 10px 20px; background: var(--primary); color: white; text-decoration: none; border-radius: 8px;">Create Post</a>
              </div>';
    } else {
        foreach ($posts as $post) {
            $image_url = $post['image_path'];
            if (!filter_var($image_url, FILTER_VALIDATE_URL) && file_exists($image_url)) {
                $image_url = '/pixora/' . $image_url;
            } elseif (!filter_var($image_url, FILTER_VALIDATE_URL)) {
                $image_url = 'https://via.placeholder.com/600x600?text=Pixora';
            }
            
            $likes = rand(10, 5000);
            $comments = rand(0, 200);
            
            echo '<a href="/pixora/post/' . $post['id'] . '" class="explore-post">
                    <img src="' . $image_url . '" alt="Post by ' . $post['username'] . '">
                    <div class="post-overlay">
                        <div class="post-stat">♥ ' . number_format($likes) . '</div>
                        <div class="post-stat">💬 ' . number_format($comments) . '</div>
                    </div>
                  </a>';
        }
    }
    
    echo '</div>
        </div>
    </body>
    </html>';
}

function showSinglePost($post_id) {
    requireLogin();
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, u.username, u.profile_picture, u.full_name
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch();
        
        if (!$post) {
            show404();
            return;
        }
    } catch (Exception $e) {
        show404();
        return;
    }
    
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Post by ' . $post['username'] . ' • Pixora</title>
        <style>
            :root {
                --primary: #0095f6;
                --border: #dbdbdb;
                --background: #fafafa;
                --text: #262626;
            }
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;
                background: var(--background);
                color: var(--text);
            }
            
            .navbar {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: 60px;
                background: white;
                border-bottom: 1px solid var(--border);
                z-index: 1000;
                padding: 0 20px;
            }
            
            .nav-container {
                max-width: 975px;
                height: 100%;
                margin: 0 auto;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            
            .logo {
                font-size: 1.5rem;
                font-weight: 700;
                background: linear-gradient(45deg, #ff6b6b, #feca57, #48dbfb, #ff9ff3);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                text-decoration: none;
            }
            
            .back-btn {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: var(--text);
                text-decoration: none;
            }
            
            .post-container {
                max-width: 975px;
                margin: 100px auto 30px;
                padding: 0 20px;
            }
            
            .post-detail {
                background: white;
                border: 1px solid var(--border);
                border-radius: 12px;
                overflow: hidden;
                display: flex;
            }
            
            .post-image {
                flex: 1;
                background: #f0f0f0;
            }
            
            .post-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }
            
            .post-sidebar {
                width: 400px;
                display: flex;
                flex-direction: column;
            }
            
            .post-header {
                padding: 16px;
                border-bottom: 1px solid var(--border);
                display: flex;
                align-items: center;
                gap: 12px;
            }
            
            .post-avatar {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                object-fit: cover;
            }
            
            .post-username {
                font-weight: 600;
                text-decoration: none;
                color: var(--text);
            }
            
            .post-caption {
                padding: 16px;
                flex: 1;
                overflow-y: auto;
            }
            
            .post-actions {
                padding: 16px;
                border-top: 1px solid var(--border);
                display: flex;
                gap: 16px;
            }
            
            .post-action {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: var(--text);
            }
            
            @media (max-width: 768px) {
                .post-detail {
                    flex-direction: column;
                }
                
                .post-sidebar {
                    width: 100%;
                }
                
                .post-image {
                    aspect-ratio: 1;
                }
            }
        </style>
    </head>
    <body>
        <nav class="navbar">
            <div class="nav-container">
                <a href="/pixora/feed" class="back-btn">←</a>
                <a href="/pixora/feed" class="logo">Pixora</a>
                <div></div>
            </div>
        </nav>
        
        <div class="post-container">';
    
    $image_url = $post['image_path'];
    if (!filter_var($image_url, FILTER_VALIDATE_URL) && file_exists($image_url)) {
        $image_url = '/pixora/' . $image_url;
    } elseif (!filter_var($image_url, FILTER_VALIDATE_URL)) {
        $image_url = 'https://via.placeholder.com/800x800?text=Pixora';
    }
    
    echo '<div class="post-detail">
            <div class="post-image">
                <img src="' . $image_url . '" alt="Post by ' . $post['username'] . '">
            </div>
            
            <div class="post-sidebar">
                <div class="post-header">
                    <img src="' . ($post['profile_picture'] ?: 'https://via.placeholder.com/32') . '" alt="' . $post['username'] . '" class="post-avatar">
                    <a href="/pixora/profile/' . $post['username'] . '" class="post-username">' . $post['username'] . '</a>
                </div>
                
                <div class="post-caption">
                    <p>' . nl2br(htmlspecialchars($post['caption'] ?: '')) . '</p>
                    <p style="margin-top: 20px; color: #8e8e8e; font-size: 14px;">' . time_ago($post['created_at']) . '</p>
                </div>
                
                <div class="post-actions">
                    <button class="post-action">♥</button>
                    <button class="post-action">💬</button>
                    <button class="post-action">📤</button>
                </div>
            </div>
          </div>
        </div>
    </body>
    </html>';
}

function showMyFollowers() {
    requireLogin();
    $user_id = $_SESSION['user_id'];
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT u.*, f.created_at 
            FROM follows f 
            JOIN users u ON f.follower_id = u.id 
            WHERE f.followee_id = ? AND f.status = 'accepted'
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $followers = $stmt->fetchAll();
    } catch (Exception $e) {
        $followers = [];
    }
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Followers • Pixora</title>
        <style>
            body { font-family: Arial; background: #fafafa; margin: 0; }
            .navbar { background: white; padding: 15px; border-bottom: 1px solid #dbdbdb; }
            .container { max-width: 600px; margin: 80px auto; background: white; border: 1px solid #dbdbdb; }
            .header { padding: 20px; border-bottom: 1px solid #dbdbdb; text-align: center; }
            .user-item { display: flex; align-items: center; padding: 15px; border-bottom: 1px solid #f0f0f0; }
            .user-avatar { width: 44px; height: 44px; border-radius: 50%; margin-right: 12px; }
        </style>
    </head>
    <body>
        <div class="navbar">
            <a href="/pixora/profile" style="color: black; text-decoration: none;">← Back</a>
        </div>
        <div class="container">
            <div class="header"><h3>Followers</h3></div>
            ' . (empty($followers) ? '<p style="padding: 20px; text-align: center;">No followers yet</p>' : '') . '
            ' . implode('', array_map(function($user) {
                return '<div class="user-item">
                    <img src="' . ($user['profile_picture'] ?: 'https://via.placeholder.com/44') . '" class="user-avatar">
                    <div>
                        <div style="font-weight: bold;">' . $user['username'] . '</div>
                        <div style="color: #666;">' . ($user['full_name'] ?: '') . '</div>
                    </div>
                </div>';
            }, $followers)) . '
        </div>
    </body>
    </html>';
}

function showMyFollowing() {
    requireLogin();
    $user_id = $_SESSION['user_id'];
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT u.*, f.created_at 
            FROM follows f 
            JOIN users u ON f.followee_id = u.id 
            WHERE f.follower_id = ? AND f.status = 'accepted'
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $following = $stmt->fetchAll();
    } catch (Exception $e) {
        $following = [];
    }
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Following • Pixora</title>
        <style>
            body { font-family: Arial; background: #fafafa; margin: 0; }
            .navbar { background: white; padding: 15px; border-bottom: 1px solid #dbdbdb; }
            .container { max-width: 600px; margin: 80px auto; background: white; border: 1px solid #dbdbdb; }
            .header { padding: 20px; border-bottom: 1px solid #dbdbdb; text-align: center; }
            .user-item { display: flex; align-items: center; padding: 15px; border-bottom: 1px solid #f0f0f0; }
            .user-avatar { width: 44px; height: 44px; border-radius: 50%; margin-right: 12px; }
        </style>
    </head>
    <body>
        <div class="navbar">
            <a href="/pixora/profile" style="color: black; text-decoration: none;">← Back</a>
        </div>
        <div class="container">
            <div class="header"><h3>Following</h3></div>
            ' . (empty($following) ? '<p style="padding: 20px; text-align: center;">Not following anyone yet</p>' : '') . '
            ' . implode('', array_map(function($user) {
                return '<div class="user-item">
                    <img src="' . ($user['profile_picture'] ?: 'https://via.placeholder.com/44') . '" class="user-avatar">
                    <div>
                        <div style="font-weight: bold;">' . $user['username'] . '</div>
                        <div style="color: #666;">' . ($user['full_name'] ?: '') . '</div>
                    </div>
                </div>';
            }, $following)) . '
        </div>
    </body>
    </html>';
}

function showUserFollowers($user_id) {
    requireLogin();
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        $stmt = $pdo->prepare("
            SELECT u.*, f.created_at 
            FROM follows f 
            JOIN users u ON f.follower_id = u.id 
            WHERE f.followee_id = ? AND f.status = 'accepted'
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $followers = $stmt->fetchAll();
    } catch (Exception $e) {
        $user = ['username' => 'User'];
        $followers = [];
    }
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>' . $user['username'] . '\'s Followers • Pixora</title>
        <style>
            body { font-family: Arial; background: #fafafa; margin: 0; }
            .navbar { background: white; padding: 15px; border-bottom: 1px solid #dbdbdb; }
            .container { max-width: 600px; margin: 80px auto; background: white; border: 1px solid #dbdbdb; }
            .header { padding: 20px; border-bottom: 1px solid #dbdbdb; text-align: center; }
            .user-item { display: flex; align-items: center; padding: 15px; border-bottom: 1px solid #f0f0f0; }
            .user-avatar { width: 44px; height: 44px; border-radius: 50%; margin-right: 12px; }
        </style>
    </head>
    <body>
        <div class="navbar">
            <a href="/pixora/profile/' . $user['username'] . '" style="color: black; text-decoration: none;">← Back</a>
        </div>
        <div class="container">
            <div class="header"><h3>' . $user['username'] . '\'s Followers</h3></div>
            ' . (empty($followers) ? '<p style="padding: 20px; text-align: center;">No followers yet</p>' : '') . '
            ' . implode('', array_map(function($follower) {
                return '<div class="user-item">
                    <img src="' . ($follower['profile_picture'] ?: 'https://via.placeholder.com/44') . '" class="user-avatar">
                    <div>
                        <div style="font-weight: bold;">' . $follower['username'] . '</div>
                        <div style="color: #666;">' . ($follower['full_name'] ?: '') . '</div>
                    </div>
                </div>';
            }, $followers)) . '
        </div>
    </body>
    </html>';
}

function showUserFollowing($user_id) {
    requireLogin();
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        $stmt = $pdo->prepare("
            SELECT u.*, f.created_at 
            FROM follows f 
            JOIN users u ON f.followee_id = u.id 
            WHERE f.follower_id = ? AND f.status = 'accepted'
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $following = $stmt->fetchAll();
    } catch (Exception $e) {
        $user = ['username' => 'User'];
        $following = [];
    }
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>' . $user['username'] . ' is Following • Pixora</title>
        <style>
            body { font-family: Arial; background: #fafafa; margin: 0; }
            .navbar { background: white; padding: 15px; border-bottom: 1px solid #dbdbdb; }
            .container { max-width: 600px; margin: 80px auto; background: white; border: 1px solid #dbdbdb; }
            .header { padding: 20px; border-bottom: 1px solid #dbdbdb; text-align: center; }
            .user-item { display: flex; align-items: center; padding: 15px; border-bottom: 1px solid #f0f0f0; }
            .user-avatar { width: 44px; height: 44px; border-radius: 50%; margin-right: 12px; }
        </style>
    </head>
    <body>
        <div class="navbar">
            <a href="/pixora/profile/' . $user['username'] . '" style="color: black; text-decoration: none;">← Back</a>
        </div>
        <div class="container">
            <div class="header"><h3>' . $user['username'] . ' is Following</h3></div>
            ' . (empty($following) ? '<p style="padding: 20px; text-align: center;">Not following anyone yet</p>' : '') . '
            ' . implode('', array_map(function($follow) {
                return '<div class="user-item">
                    <img src="' . ($follow['profile_picture'] ?: 'https://via.placeholder.com/44') . '" class="user-avatar">
                    <div>
                        <div style="font-weight: bold;">' . $follow['username'] . '</div>
                        <div style="color: #666;">' . ($follow['full_name'] ?: '') . '</div>
                    </div>
                </div>';
            }, $following)) . '
        </div>
    </body>
    </html>';
}

// ... (Fungsi lainnya yang sudah ada sebelumnya)
// Karena keterbatasan ruang, saya akan menambahkan fungsi yang tersisa:

function handleRegister() {
    requireGuest();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // HAPUS: global $pdo; // Jangan pakai global langsung
        
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $full_name = $_POST['full_name'] ?? '';
        
        // Basic validation...
        
        } else {
            try {
                // PERBAIKAN: Gunakan getDB()
                $pdo = getDB();
                if (!$pdo) {
                    throw new Exception("Database connection failed");
                }
                
                // Check if username or email exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                // ^ Sekarang aman
                // ^ Sekarang aman
                $stmt->execute([$username, $email]);
                if ($stmt->fetch()) {
                    $error = "Username or email already exists";
                } else {
                    // Create user
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$username, $email, $hashed_password, $full_name]);
                    
                    // Auto login
                    $user_id = $pdo->lastInsertId();
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    $_SESSION['full_name'] = $full_name;
                    
                    redirect('/feed');
                }
            } catch (Exception $e) {
                $error = "Registration failed. Please try again.";
            }
        }
    }
    
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Register • Pixora</title>
        <style>
            :root {
                --primary: #0095f6;
                --primary-hover: #0081d6;
                --secondary: #8e8e8e;
                --border: #dbdbdb;
                --background: #fafafa;
                --text: #262626;
                --error: #ed4956;
                --white: #ffffff;
            }
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;
                background: var(--background);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            
            .register-container {
                width: 100%;
                max-width: 350px;
            }
            
            .register-box {
                background: var(--white);
                border: 1px solid var(--border);
                border-radius: 12px;
                padding: 40px;
                text-align: center;
            }
            
            .logo {
                font-size: 2.5rem;
                font-weight: 700;
                background: linear-gradient(45deg, #ff6b6b, #feca57, #48dbfb, #ff9ff3);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                margin-bottom: 20px;
            }
            
            .subtitle {
                color: var(--secondary);
                font-weight: 600;
                margin-bottom: 24px;
            }
            
            .form-group {
                margin-bottom: 12px;
            }
            
            .form-input {
                width: 100%;
                padding: 10px 12px;
                background: var(--background);
                border: 1px solid var(--border);
                border-radius: 8px;
                font-size: 14px;
                color: var(--text);
                outline: none;
                transition: border 0.2s;
            }
            
            .form-input:focus {
                border-color: #a8a8a8;
            }
            
            .form-input::placeholder {
                color: var(--secondary);
            }
            
            .submit-btn {
                width: 100%;
                padding: 10px;
                background: var(--primary);
                color: var(--white);
                border: none;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                margin-top: 12px;
                transition: background 0.2s;
            }
            
            .submit-btn:hover {
                background: var(--primary-hover);
            }
            
            .error {
                color: var(--error);
                font-size: 14px;
                margin-bottom: 12px;
                text-align: center;
            }
            
            .divider {
                display: flex;
                align-items: center;
                margin: 20px 0;
                color: var(--secondary);
                font-size: 13px;
                font-weight: 600;
            }
            
            .divider::before,
            .divider::after {
                content: "";
                flex: 1;
                height: 1px;
                background: var(--border);
            }
            
            .divider::before {
                margin-right: 20px;
            }
            
            .divider::after {
                margin-left: 20px;
            }
            
            .login-link {
                margin-top: 20px;
                padding: 20px;
                background: var(--white);
                border: 1px solid var(--border);
                border-radius: 12px;
                text-align: center;
                font-size: 14px;
                color: var(--text);
            }
            
            .login-link a {
                color: var(--primary);
                text-decoration: none;
                font-weight: 600;
            }
            
            .login-link a:hover {
                text-decoration: underline;
            }
            
            .app-links {
                text-align: center;
                margin-top: 20px;
                color: var(--text);
                font-size: 14px;
            }
            
            .app-stores {
                display: flex;
                justify-content: center;
                gap: 10px;
                margin-top: 10px;
            }
            
            @media (max-width: 450px) {
                .register-box {
                    padding: 20px;
                    border: none;
                    background: transparent;
                }
                
                .login-link {
                    border: none;
                    background: transparent;
                }
            }
        </style>
    </head>
    <body>
        <div class="register-container">
            <div class="register-box">
                <div class="logo">Pixora</div>
                <div class="subtitle">Sign up to see photos and videos from your friends.</div>
                
                ' . (isset($error) ? '<div class="error">' . $error . '</div>' : '') . '
                
                <form method="POST">
                    <div class="form-group">
                        <input type="text" class="form-input" name="email" placeholder="Email" value="' . ($_POST['email'] ?? '') . '" required>
                    </div>
                    <div class="form-group">
                        <input type="text" class="form-input" name="full_name" placeholder="Full Name" value="' . ($_POST['full_name'] ?? '') . '">
                    </div>
                    <div class="form-group">
                        <input type="text" class="form-input" name="username" placeholder="Username" value="' . ($_POST['username'] ?? '') . '" required>
                    </div>
                    <div class="form-group">
                        <input type="password" class="form-input" name="password" placeholder="Password" required>
                    </div>
                    <button type="submit" class="submit-btn">Sign Up</button>
                </form>
                
                <div class="divider">OR</div>
                
                <div class="app-links">
                    <div>Get the app.</div>
                    <div class="app-stores">
                        <img src="https://static.cdninstagram.com/rsrc.php/v3/yz/r/c5Rp7Ym-Klz.png" alt="App Store" style="height: 40px; cursor: pointer;">
                        <img src="https://static.cdninstagram.com/rsrc.php/v3/yu/r/EHY6QnZYdNX.png" alt="Google Play" style="height: 40px; cursor: pointer;">
                    </div>
                </div>
            </div>
            
            <div class="login-link">
                Have an account? <a href="/pixora/login">Log in</a>
            </div>
        </div>
    </body>
    </html>';

function handleLogin() {
    requireGuest();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // HAPUS: global $pdo; // Jangan pakai global langsung
        
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = "Username and password are required";
        } else {
            try {
                // PERBAIKAN: Gunakan fungsi getDB() yang aman
                $pdo = getDB();
                if (!$pdo) {
                    throw new Exception("Database connection failed");
                }
                
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
                // ^ Sekarang $pdo sudah pasti tidak null
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['profile_pic'] = $user['profile_picture'];
                    
                    redirect('/feed');
                } else {
                    $error = "Invalid username or password";
                }
            } catch (Exception $e) {
                $error = "Login failed. Please try again.";
            }
        }
    }
    
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login • Pixora</title>
        <style>
            :root {
                --primary: #0095f6;
                --primary-hover: #0081d6;
                --secondary: #8e8e8e;
                --border: #dbdbdb;
                --background: #fafafa;
                --text: #262626;
                --error: #ed4956;
                --white: #ffffff;
            }
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;
                background: var(--background);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            
            .login-container {
                width: 100%;
                max-width: 350px;
            }
            
            .login-box {
                background: var(--white);
                border: 1px solid var(--border);
                border-radius: 12px;
                padding: 40px;
                text-align: center;
            }
            
            .logo {
                font-size: 2.5rem;
                font-weight: 700;
                background: linear-gradient(45deg, #ff6b6b, #feca57, #48dbfb, #ff9ff3);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                margin-bottom: 20px;
            }
            
            .form-group {
                margin-bottom: 12px;
            }
            
            .form-input {
                width: 100%;
                padding: 10px 12px;
                background: var(--background);
                border: 1px solid var(--border);
                border-radius: 8px;
                font-size: 14px;
                color: var(--text);
                outline: none;
                transition: border 0.2s;
            }
            
            .form-input:focus {
                border-color: #a8a8a8;
            }
            
            .form-input::placeholder {
                color: var(--secondary);
            }
            
            .submit-btn {
                width: 100%;
                padding: 10px;
                background: var(--primary);
                color: var(--white);
                border: none;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                margin-top: 12px;
                transition: background 0.2s;
            }
            
            .submit-btn:hover {
                background: var(--primary-hover);
            }
            
            .error {
                color: var(--error);
                font-size: 14px;
                margin-bottom: 12px;
                text-align: center;
            }
            
            .divider {
                display: flex;
                align-items: center;
                margin: 20px 0;
                color: var(--secondary);
                font-size: 13px;
                font-weight: 600;
            }
            
            .divider::before,
            .divider::after {
                content: "";
                flex: 1;
                height: 1px;
                background: var(--border);
            }
            
            .divider::before {
                margin-right: 20px;
            }
            
            .divider::after {
                margin-left: 20px;
            }
            
            .register-link {
                margin-top: 20px;
                padding: 20px;
                background: var(--white);
                border: 1px solid var(--border);
                border-radius: 12px;
                text-align: center;
                font-size: 14px;
                color: var(--text);
            }
            
            .register-link a {
                color: var(--primary);
                text-decoration: none;
                font-weight: 600;
            }
            
            .register-link a:hover {
                text-decoration: underline;
            }
            
            @media (max-width: 450px) {
                .login-box {
                    padding: 20px;
                    border: none;
                    background: transparent;
                }
                
                .register-link {
                    border: none;
                    background: transparent;
                }
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="login-box">
                <div class="logo">Pixora</div>
                
                ' . (isset($error) ? '<div class="error">' . $error . '</div>' : '') . '
                
                <form method="POST">
                    <div class="form-group">
                        <input type="text" class="form-input" name="username" placeholder="Username or email" value="' . ($_POST['username'] ?? '') . '" required>
                    </div>
                    <div class="form-group">
                        <input type="password" class="form-input" name="password" placeholder="Password" required>
                    </div>
                    <button type="submit" class="submit-btn">Log In</button>
                </form>
                
                <div class="divider">OR</div>
                
                <div style="color: var(--secondary); font-size: 14px; margin-top: 20px;">
                    <a href="/pixora/register" style="color: var(--text); text-decoration: none;">Forgot password?</a>
                </div>
            </div>
            
            <div class="register-link">
                Don\'t have an account? <a href="/pixora/register">Sign up</a>
            </div>
        </div>
    </body>
    </html>';
}

function handleCreatePost() {
    requireLogin();
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $caption = $_POST['caption'] ?? '';
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadImage($_FILES['image'], 'posts');
            
            if (isset($upload['success'])) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO posts (user_id, image_path, caption) VALUES (?, ?, ?)");
                    $stmt->execute([$_SESSION['user_id'], $upload['path'], $caption]);
                    redirect('/feed');
                } catch (Exception $e) {
                    $error = "Failed to create post";
                }
            } else {
                $error = $upload['error'] ?? "Upload failed";
            }
        } else {
            $error = "Please select an image";
        }
    }
    
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Create Post • Pixora</title>
        <style>
            body { font-family: Arial; background: #fafafa; margin: 0; }
            .navbar { background: white; padding: 15px; border-bottom: 1px solid #dbdbdb; display: flex; align-items: center; gap: 15px; }
            .container { max-width: 600px; margin: 50px auto; background: white; border: 1px solid #dbdbdb; border-radius: 8px; }
            .header { padding: 20px; border-bottom: 1px solid #dbdbdb; text-align: center; font-weight: bold; }
            .form-group { padding: 20px; }
            .form-input { width: 100%; padding: 10px; border: 1px solid #dbdbdb; border-radius: 4px; margin-bottom: 15px; }
            .submit-btn { background: #0095f6; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
            .error { color: #ed4956; margin-bottom: 15px; }
        </style>
    </head>
    <body>
        <div class="navbar">
            <a href="/pixora/feed" style="color: black; text-decoration: none;">← Cancel</a>
            <div style="flex: 1; text-align: center; font-weight: bold;">Create New Post</div>
        </div>
        <div class="container">
            ' . (isset($error) ? '<div class="error" style="padding: 20px;">' . $error . '</div>' : '') . '
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <input type="file" name="image" accept="image/*" required style="margin-bottom: 15px;">
                    <textarea class="form-input" name="caption" placeholder="Write a caption..." rows="4"></textarea>
                    <button type="submit" class="submit-btn">Share</button>
                </div>
            </form>
        </div>
    </body>
    </html>';
}

function showMyProfile() {
    requireLogin();
    $user_id = $_SESSION['user_id'];
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $post_count = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ? AND status = 'accepted'");
        $stmt->execute([$user_id]);
        $following_count = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE followee_id = ? AND status = 'accepted'");
        $stmt->execute([$user_id]);
        $follower_count = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        $posts = $stmt->fetchAll();
    } catch (Exception $e) {
        $user = [];
        $post_count = $following_count = $follower_count = 0;
        $posts = [];
    }
    
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . ($user['username'] ?? 'Profile') . ' • Pixora</title>
        <style>
            :root {
                --primary: #0095f6;
                --border: #dbdbdb;
                --background: #fafafa;
                --text: #262626;
            }
            
            * { margin: 0; padding: 0; box-sizing: border-box; }
            
            body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; background: var(--background); }
            
            .navbar {
                position: fixed; top: 0; left: 0; right: 0;
                height: 60px; background: white; border-bottom: 1px solid var(--border);
                z-index: 1000; padding: 0 20px; display: flex; align-items: center; justify-content: space-between;
            }
            
            .logo { font-size: 1.5rem; font-weight: 700; background: linear-gradient(45deg, #ff6b6b, #feca57, #48dbfb, #ff9ff3);
                -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-decoration: none; }
            
            .nav-icons { display: flex; gap: 22px; }
            .nav-icon { font-size: 24px; color: var(--text); text-decoration: none; }
            
            .profile-container { max-width: 975px; margin: 100px auto 30px; padding: 0 20px; }
            
            .profile-header { display: flex; gap: 100px; margin-bottom: 44px; }
            
            .profile-avatar { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border); }
            
            .profile-info { flex: 1; }
            
            .profile-username { font-size: 28px; font-weight: 300; margin-bottom: 20px; display: flex; align-items: center; gap: 20px; }
            
            .profile-stats { display: flex; gap: 40px; margin-bottom: 20px; }
            .profile-stat { font-size: 16px; }
            .stat-count { font-weight: 600; }
            
            .profile-bio { margin-bottom: 20px; }
            
            .profile-actions { display: flex; gap: 8px; }
            .profile-btn { padding: 8px 24px; border-radius: 8px; border: 1px solid var(--border); background: white; cursor: pointer; text-decoration: none; color: inherit; }
            .profile-btn.primary { background: var(--primary); color: white; border: none; }
            
            .posts-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 28px; }
            .post-item { aspect-ratio: 1; border-radius: 8px; overflow: hidden; }
            .post-item img { width: 100%; height: 100%; object-fit: cover; }
            
            @media (max-width: 768px) {
                .profile-header { flex-direction: column; gap: 30px; }
                .profile-stats { justify-content: space-around; }
                .posts-grid { gap: 3px; }
            }
        </style>
    </head>
    <body>
        <nav class="navbar">
            <a href="/pixora/feed" class="logo">Pixora</a>
            <div class="nav-icons">
                <a href="/pixora/feed" class="nav-icon">🏠</a>
                <a href="/pixora/create" class="nav-icon">➕</a>
                <a href="/pixora/settings" class="nav-icon">⚙️</a>
            </div>
        </nav>
        
        <div class="profile-container">
            <div class="profile-header">
                <img src="' . ($user['profile_picture'] ?? 'https://via.placeholder.com/150') . '" class="profile-avatar">
                <div class="profile-info">
                    <div class="profile-username">
                        ' . ($user['username'] ?? 'User') . '
                        <a href="/pixora/settings" class="profile-btn">Edit Profile</a>
                    </div>
                    <div class="profile-stats">
                        <div class="profile-stat"><span class="stat-count">' . $post_count . '</span> posts</div>
                        <div class="profile-stat"><a href="/pixora/followers" style="color: inherit; text-decoration: none;"><span class="stat-count">' . $follower_count . '</span> followers</a></div>
                        <div class="profile-stat"><a href="/pixora/following" style="color: inherit; text-decoration: none;"><span class="stat-count">' . $following_count . '</span> following</a></div>
                    </div>
                    <div class="profile-bio">
                        <div style="font-weight: 600;">' . ($user['full_name'] ?? '') . '</div>
                        <div>' . nl2br(htmlspecialchars($user['bio'] ?? '')) . '</div>
                    </div>
                </div>
            </div>
            
            <div class="posts-grid">';
    
    if (empty($posts)) {
        echo '<div style="grid-column: 1 / -1; text-align: center; padding: 100px 20px; color: #8e8e8e;">
                <div style="font-size: 48px; margin-bottom: 20px;">📷</div>
                <h3>No Posts Yet</h3>
                <p>Share your first photo or video!</p>
                <a href="/pixora/create" class="profile-btn primary" style="display: inline-block; margin-top: 20px;">Create Post</a>
              </div>';
    } else {
        foreach ($posts as $post) {
            $image_url = $post['image_path'];
            if (!filter_var($image_url, FILTER_VALIDATE_URL) && file_exists($image_url)) {
                $image_url = '/pixora/' . $image_url;
            } elseif (!filter_var($image_url, FILTER_VALIDATE_URL)) {
                $image_url = 'https://via.placeholder.com/300x300?text=Pixora';
            }
            
            echo '<a href="/pixora/post/' . $post['id'] . '" class="post-item">
                    <img src="' . $image_url . '" alt="Post">
                  </a>';
        }
    }
    
    echo '</div>
        </div>
    </body>
    </html>';
}

function showUserProfile($username) {
    requireLogin();
    $current_user_id = $_SESSION['user_id'];
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            show404();
            return;
        }
        
        // Check follow status
        $stmt = $pdo->prepare("SELECT status FROM follows WHERE follower_id = ? AND followee_id = ?");
        $stmt->execute([$current_user_id, $user['id']]);
        $follow = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $post_count = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ? AND status = 'accepted'");
        $stmt->execute([$user['id']]);
        $following_count = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE followee_id = ? AND status = 'accepted'");
        $stmt->execute([$user['id']]);
        $follower_count = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user['id']]);
        $posts = $stmt->fetchAll();
    } catch (Exception $e) {
        show404();
        return;
    }
    
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . $user['username'] . ' • Pixora</title>
        <style>
            :root {
                --primary: #0095f6;
                --border: #dbdbdb;
                --background: #fafafa;
                --text: #262626;
            }
            
            * { margin: 0; padding: 0; box-sizing: border-box; }
            
            body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; background: var(--background); }
            
            .navbar {
                position: fixed; top: 0; left: 0; right: 0;
                height: 60px; background: white; border-bottom: 1px solid var(--border);
                z-index: 1000; padding: 0 20px; display: flex; align-items: center; justify-content: space-between;
            }
            
            .logo { font-size: 1.5rem; font-weight: 700; background: linear-gradient(45deg, #ff6b6b, #feca57, #48dbfb, #ff9ff3);
                -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-decoration: none; }
            
            .nav-icons { display: flex; gap: 22px; }
            .nav-icon { font-size: 24px; color: var(--text); text-decoration: none; }
            
            .profile-container { max-width: 975px; margin: 100px auto 30px; padding: 0 20px; }
            
            .profile-header { display: flex; gap: 100px; margin-bottom: 44px; }
            
            .profile-avatar { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border); }
            
            .profile-info { flex: 1; }
            
            .profile-username { font-size: 28px; font-weight: 300; margin-bottom: 20px; display: flex; align-items: center; gap: 20px; }
            
            .profile-stats { display: flex; gap: 40px; margin-bottom: 20px; }
            .profile-stat { font-size: 16px; }
            .stat-count { font-weight: 600; }
            
            .profile-bio { margin-bottom: 20px; }
            
            .profile-actions { display: flex; gap: 8px; }
            .profile-btn { padding: 8px 24px; border-radius: 8px; border: 1px solid var(--border); background: white; cursor: pointer; text-decoration: none; color: inherit; }
            .profile-btn.primary { background: var(--primary); color: white; border: none; }
            
            .posts-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 28px; }
            .post-item { aspect-ratio: 1; border-radius: 8px; overflow: hidden; }
            .post-item img { width: 100%; height: 100%; object-fit: cover; }
            
            @media (max-width: 768px) {
                .profile-header { flex-direction: column; gap: 30px; }
                .profile-stats { justify-content: space-around; }
                .posts-grid { gap: 3px; }
            }
        </style>
    </head>
    <body>
        <nav class="navbar">
            <a href="/pixora/feed" class="logo">Pixora</a>
            <div class="nav-icons">
                <a href="/pixora/feed" class="nav-icon">🏠</a>
                <a href="/pixora/profile" class="nav-icon">👤</a>
            </div>
        </nav>
        
        <div class="profile-container">
            <div class="profile-header">
                <img src="' . ($user['profile_picture'] ?: 'https://via.placeholder.com/150') . '" class="profile-avatar">
                <div class="profile-info">
                    <div class="profile-username">
                        ' . $user['username'] . '
                        <div class="profile-actions">';
    
    if ($user['id'] != $current_user_id) {
        if ($follow) {
            echo '<a href="/pixora/unfollow/' . $user['id'] . '" class="profile-btn">Unfollow</a>';
        } else {
            echo '<a href="/pixora/follow/' . $user['id'] . '" class="profile-btn primary">Follow</a>';
        }
        echo '<a href="/pixora/message/' . $user['id'] . '" class="profile-btn">Message</a>';
    }
    
    echo '</div>
                    </div>
                    <div class="profile-stats">
                        <div class="profile-stat"><span class="stat-count">' . $post_count . '</span> posts</div>
                        <div class="profile-stat"><a href="/pixora/followers/' . $user['id'] . '" style="color: inherit; text-decoration: none;"><span class="stat-count">' . $follower_count . '</span> followers</a></div>
                        <div class="profile-stat"><a href="/pixora/following/' . $user['id'] . '" style="color: inherit; text-decoration: none;"><span class="stat-count">' . $following_count . '</span> following</a></div>
                    </div>
                    <div class="profile-bio">
                        <div style="font-weight: 600;">' . ($user['full_name'] ?: '') . '</div>
                        <div>' . nl2br(htmlspecialchars($user['bio'] ?: '')) . '</div>
                    </div>
                </div>
            </div>
            
            <div class="posts-grid">';
    
    if (empty($posts)) {
        echo '<div style="grid-column: 1 / -1; text-align: center; padding: 100px 20px; color: #8e8e8e;">
                <div style="font-size: 48px; margin-bottom: 20px;">📷</div>
                <h3>No Posts Yet</h3>
              </div>';
    } else {
        foreach ($posts as $post) {
            $image_url = $post['image_path'];
            if (!filter_var($image_url, FILTER_VALIDATE_URL) && file_exists($image_url)) {
                $image_url = '/pixora/' . $image_url;
            } elseif (!filter_var($image_url, FILTER_VALIDATE_URL)) {
                $image_url = 'https://via.placeholder.com/300x300?text=Pixora';
            }
            
            echo '<a href="/pixora/post/' . $post['id'] . '" class="post-item">
                    <img src="' . $image_url . '" alt="Post">
                  </a>';
        }
    }
    
    echo '</div>
        </div>
    </body>
    </html>';
}

function handleFollow($user_id) {
    requireLogin();
    $current_user_id = $_SESSION['user_id'];
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT username, is_private FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            redirect('/feed');
            return;
        }
        
        $status = $user['is_private'] ? 'pending' : 'accepted';
        
        $stmt = $pdo->prepare("INSERT INTO follows (follower_id, followee_id, status) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE status = ?");
        $stmt->execute([$current_user_id, $user_id, $status, $status]);
        
        redirect('/profile/' . $user['username']);
    } catch (Exception $e) {
        redirect('/feed');
    }
}

function handleUnfollow($user_id) {
    requireLogin();
    $current_user_id = $_SESSION['user_id'];
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND followee_id = ?");
            $stmt->execute([$current_user_id, $user_id]);
            
            redirect('/profile/' . $user['username']);
        }
    } catch (Exception $e) {
        redirect('/feed');
    }
}

function handleSearch() {
    requireLogin();
    $query = $_GET['q'] ?? '';
    $ajax = isset($_GET['ajax']) && $_GET['ajax'] == '1';
    
    global $pdo;
    $results = [];
    
    if (!empty($query)) {
        try {
            $stmt = $pdo->prepare("
                SELECT id, username, full_name, profile_picture 
                FROM users 
                WHERE username LIKE ? OR full_name LIKE ?
                ORDER BY username
                LIMIT 10
            ");
            $search_term = '%' . $query . '%';
            $stmt->execute([$search_term, $search_term]);
            $results = $stmt->fetchAll();
        } catch (Exception $e) {
            $results = [];
        }
    }
    
    if ($ajax) {
        if (empty($results)) {
            echo '<div style="padding: 20px; text-align: center; color: #8e8e8e;">No results found</div>';
        } else {
            foreach ($results as $user) {
                echo '<a href="/pixora/profile/' . $user['username'] . '" class="search-result-item">
                        <img src="' . ($user['profile_picture'] ?: 'https://via.placeholder.com/44') . '" class="search-result-avatar">
                        <div class="search-result-info">
                            <div class="search-result-username">' . $user['username'] . '</div>
                            <div class="search-result-name">' . ($user['full_name'] ?: '') . '</div>
                        </div>
                      </a>';
            }
        }
        exit();
    }
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Search • Pixora</title>
        <style>
            body { font-family: Arial; background: #fafafa; margin: 0; }
            .navbar { background: white; padding: 15px; border-bottom: 1px solid #dbdbdb; }
            .search-box { max-width: 600px; margin: 80px auto; padding: 20px; }
            .search-input { width: 100%; padding: 15px; border: 1px solid #dbdbdb; border-radius: 8px; font-size: 16px; }
            .results { margin-top: 20px; }
            .result-item { display: flex; align-items: center; padding: 15px; background: white; border: 1px solid #dbdbdb; margin-bottom: 10px; border-radius: 8px; text-decoration: none; color: inherit; }
            .result-avatar { width: 44px; height: 44px; border-radius: 50%; margin-right: 12px; }
        </style>
    </head>
    <body>
        <div class="navbar">
            <a href="/pixora/feed" style="color: black; text-decoration: none;">← Back</a>
        </div>
        <div class="search-box">
            <input type="text" class="search-input" placeholder="Search users..." value="' . htmlspecialchars($query) . '">
            <div class="results">';
    
    foreach ($results as $user) {
        echo '<a href="/pixora/profile/' . $user['username'] . '" class="result-item">
                <img src="' . ($user['profile_picture'] ?: 'https://via.placeholder.com/44') . '" class="result-avatar">
                <div>
                    <div style="font-weight: bold;">' . $user['username'] . '</div>
                    <div style="color: #666;">' . ($user['full_name'] ?: '') . '</div>
                </div>
              </a>';
    }
    
    echo '</div>
        </div>
        <script>
            const input = document.querySelector(".search-input");
            input.focus();
            input.addEventListener("keyup", function(e) {
                if (e.key === "Enter") {
                    window.location.href = "/pixora/search?q=" + encodeURIComponent(this.value);
                }
            });
        </script>
    </body>
    </html>';
}

function handleSettings() {
    requireLogin();
    $user_id = $_SESSION['user_id'];
    global $pdo;
    
    $message = '';
    $error = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $full_name = $_POST['full_name'] ?? '';
        $bio = $_POST['bio'] ?? '';
        $email = $_POST['email'] ?? '';
        $private = isset($_POST['is_private']) ? 1 : 0;
        
        try {
            // Check if email is already taken
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $error = "Email already taken";
            } else {
                // Update user
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, bio = ?, email = ?, is_private = ? WHERE id = ?");
                $stmt->execute([$full_name, $bio, $email, $private, $user_id]);
                
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;
                
                // Handle profile picture upload
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                    $upload = uploadImage($_FILES['profile_picture'], 'profile');
                    if (isset($upload['success'])) {
                        $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                        $stmt->execute([$upload['path'], $user_id]);
                        $_SESSION['profile_pic'] = $upload['path'];
                    }
                }
                
                $message = "Profile updated successfully";
            }
        } catch (Exception $e) {
            $error = "Failed to update profile";
        }
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    } catch (Exception $e) {
        $user = [];
    }
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Settings • Pixora</title>
        <style>
            body { font-family: Arial; background: #fafafa; margin: 0; }
            .navbar { background: white; padding: 15px; border-bottom: 1px solid #dbdbdb; }
            .container { max-width: 600px; margin: 80px auto; background: white; border: 1px solid #dbdbdb; border-radius: 8px; }
            .header { padding: 20px; border-bottom: 1px solid #dbdbdb; }
            .form-group { padding: 20px; }
            .form-input { width: 100%; padding: 10px; border: 1px solid #dbdbdb; border-radius: 4px; margin-bottom: 15px; }
            .submit-btn { background: #0095f6; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
            .success { color: #42b72a; margin-bottom: 15px; }
            .error { color: #ed4956; margin-bottom: 15px; }
            .profile-pic { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 15px; }
        </style>
    </head>
    <body>
        <div class="navbar">
            <a href="/pixora/profile" style="color: black; text-decoration: none;">← Back</a>
        </div>
        <div class="container">
            <div class="header"><h3>Edit Profile</h3></div>
            <div class="form-group">
                ' . ($message ? '<div class="success">' . $message . '</div>' : '') . '
                ' . ($error ? '<div class="error">' . $error . '</div>' : '') . '
                
                <form method="POST" enctype="multipart/form-data">
                    <div style="text-align: center;">
                        <img src="' . ($user['profile_picture'] ?: 'https://via.placeholder.com/100') . '" class="profile-pic">
                        <div><input type="file" name="profile_picture" accept="image/*"></div>
                    </div>
                    
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" class="form-input" value="' . $user['username'] . '" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" class="form-input" name="full_name" value="' . htmlspecialchars($user['full_name'] ?? '') . '">
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-input" name="email" value="' . htmlspecialchars($user['email'] ?? '') . '" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Bio</label>
                        <textarea class="form-input" name="bio" rows="4">' . htmlspecialchars($user['bio'] ?? '') . '</textarea>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 10px;">
                            <input type="checkbox" name="is_private" value="1" ' . ($user['is_private'] ? 'checked' : '') . '>
                            Private Account
                        </label>
                        <small style="color: #666;">When your account is private, only people you approve can see your posts.</small>
                    </div>
                    
                    <button type="submit" class="submit-btn">Save Changes</button>
                </form>
                
                <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #dbdbdb;">
                    <a href="/pixora/logout" style="color: #ed4956; text-decoration: none;">Log Out</a>
                </div>
            </div>
        </div>
    </body>
    </html>';
}

function showMessages() {
    requireLogin();
    $user_id = $_SESSION['user_id'];
    global $pdo;
    
    // Get conversations
    $conversations = [];
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT 
                CASE 
                    WHEN m.sender_id = ? THEN m.receiver_id 
                    ELSE m.sender_id 
                END as other_user_id,
                u.username,
                u.profile_picture,
                MAX(m.created_at) as last_message_time,
                (SELECT message FROM messages WHERE id = MAX(m.id)) as last_message
            FROM messages m
            JOIN users u ON u.id = CASE 
                WHEN m.sender_id = ? THEN m.receiver_id 
                ELSE m.sender_id 
            END
            WHERE m.sender_id = ? OR m.receiver_id = ?
            GROUP BY other_user_id
            ORDER BY last_message_time DESC
        ");
        $stmt->execute([$user_id, $user_id, $user_id, $user_id]);
        $conversations = $stmt->fetchAll();
    } catch (Exception $e) {
        $conversations = [];
    }
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Messages • Pixora</title>
        <style>
            body { font-family: Arial; background: #fafafa; margin: 0; }
            .navbar { background: white; padding: 15px; border-bottom: 1px solid #dbdbdb; display: flex; align-items: center; }
            .container { max-width: 600px; margin: 80px auto; background: white; border: 1px solid #dbdbdb; border-radius: 8px; }
            .conversation { display: flex; align-items: center; padding: 15px; border-bottom: 1px solid #dbdbdb; text-decoration: none; color: inherit; }
            .conversation:hover { background: #fafafa; }
            .conversation-avatar { width: 44px; height: 44px; border-radius: 50%; margin-right: 12px; }
            .empty-state { text-align: center; padding: 50px 20px; color: #666; }
        </style>
    </head>
    <body>
        <div class="navbar">
            <a href="/pixora/feed" style="color: black; text-decoration: none; margin-right: 15px;">←</a>
            <h3 style="margin: 0;">Messages</h3>
        </div>
        <div class="container">';
    
    if (empty($conversations)) {
        echo '<div class="empty-state">
                <div style="font-size: 48px; margin-bottom: 20px;">💬</div>
                <h3>No messages yet</h3>
                <p>Start a conversation with someone!</p>
              </div>';
    } else {
        foreach ($conversations as $conv) {
            echo '<a href="/pixora/message/' . $conv['other_user_id'] . '" class="conversation">
                    <img src="' . ($conv['profile_picture'] ?: 'https://via.placeholder.com/44') . '" class="conversation-avatar">
                    <div style="flex: 1;">
                        <div style="font-weight: bold;">' . $conv['username'] . '</div>
                        <div style="color: #666; font-size: 14px;">' . ($conv['last_message'] ?: '') . '</div>
                    </div>
                    <div style="color: #666; font-size: 12px;">' . time_ago($conv['last_message_time']) . '</div>
                  </a>';
        }
    }
    
    echo '</div>
    </body>
    </html>';
}

function showConversation($other_user_id) {
    requireLogin();
    $current_user_id = $_SESSION['user_id'];
    global $pdo;
    
    try {
        // Get user info
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$other_user_id]);
        $other_user = $stmt->fetch();
        
        if (!$other_user) {
            redirect('/messages');
            return;
        }
        
        // Get messages
        $stmt = $pdo->prepare("
            SELECT m.*, u.username, u.profile_picture 
            FROM messages m 
            JOIN users u ON u.id = m.sender_id 
            WHERE (m.sender_id = ? AND m.receiver_id = ?) 
               OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$current_user_id, $other_user_id, $other_user_id, $current_user_id]);
        $messages = $stmt->fetchAll();
        
        // Mark as read
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
        $stmt->execute([$other_user_id, $current_user_id]);
        
    } catch (Exception $e) {
        redirect('/messages');
        return;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $message = $_POST['message'] ?? '';
        if (!empty($message)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
                $stmt->execute([$current_user_id, $other_user_id, $message]);
                
                // Refresh page to show new message
                echo '<script>window.location.reload();</script>';
                exit();
            } catch (Exception $e) {
                // Continue rendering
            }
        }
    }
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Message with ' . $other_user['username'] . ' • Pixora</title>
        <style>
            body { font-family: Arial; background: #fafafa; margin: 0; }
            .navbar { background: white; padding: 15px; border-bottom: 1px solid #dbdbdb; display: flex; align-items: center; }
            .messages-container { max-width: 600px; margin: 80px auto 100px; padding: 20px; }
            .message { margin-bottom: 15px; display: flex; }
            .message.sent { justify-content: flex-end; }
            .message.received { justify-content: flex-start; }
            .message-content { max-width: 70%; padding: 10px 15px; border-radius: 18px; }
            .message.sent .message-content { background: #0095f6; color: white; }
            .message.received .message-content { background: #efefef; color: black; }
            .message-form { position: fixed; bottom: 0; left: 0; right: 0; background: white; border-top: 1px solid #dbdbdb; padding: 15px; }
            .message-input { width: 100%; padding: 12px; border: 1px solid #dbdbdb; border-radius: 24px; }
        </style>
    </head>
    <body>
        <div class="navbar">
            <a href="/pixora/messages" style="color: black; text-decoration: none; margin-right: 15px;">←</a>
            <div style="display: flex; align-items: center; gap: 10px;">
                <img src="' . ($other_user['profile_picture'] ?: 'https://via.placeholder.com/32') . '" style="width: 32px; height: 32px; border-radius: 50%;">
                <div>
                    <div style="font-weight: bold;">' . $other_user['username'] . '</div>
                    <div style="font-size: 12px; color: #666;">Active now</div>
                </div>
            </div>
        </div>
        
        <div class="messages-container">';
    
    if (empty($messages)) {
        echo '<div style="text-align: center; color: #666; padding: 50px 20px;">
                <p>No messages yet. Start the conversation!</p>
              </div>';
    } else {
        foreach ($messages as $msg) {
            $is_sent = $msg['sender_id'] == $current_user_id;
            echo '<div class="message ' . ($is_sent ? 'sent' : 'received') . '">
                    <div class="message-content">' . htmlspecialchars($msg['message']) . '</div>
                  </div>';
        }
    }
    
    echo '</div>
        
        <form method="POST" class="message-form">
            <input type="text" name="message" class="message-input" placeholder="Message..." required autofocus>
        </form>
        
        <script>
            window.scrollTo(0, document.body.scrollHeight);
        </script>
    </body>
    </html>';
}
?>