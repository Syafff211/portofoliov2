<?php
 $dir = __DIR__ . '/';
 $likes = file_exists($dir . 'likes.txt') ? (int)file_get_contents($dir . 'likes.txt') : 0;
 $files = glob($dir . '*.txt');
rsort($files); // Pesan terbaru di atas
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox - Muhammad Syafiq</title>
    
    <!-- Fonts (Sama dengan Portfolio) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;700&family=Manrope:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --color-primary: #dc2626;
            --color-on-primary: #ffffff;
            --color-background: #ffffff;
            --color-on-background: #111111;
            --color-surface-variant: #f3f4f6;
            --color-on-surface-variant: #6b7280;
            --color-surface-container-high: #e5e7eb;
            --color-surface-container-highest: #d1d5db;
            --font-headline: 'Space Grotesk', sans-serif;
            --font-body: 'Manrope', sans-serif;
            --font-label: 'Manrope', sans-serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: var(--font-body);
            background-color: var(--color-background);
            color: var(--color-on-background);
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        /* Container */
        .container { max-width: 1160px; margin: 0 auto; padding: 0 2rem; }

        /* Header */
        header {
            margin-bottom: 3rem;
            display: flex; justify-content: space-between; align-items: center;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--color-surface-container-highest);
        }
        h1 {
            font-family: var(--font-headline);
            font-size: clamp(1.5rem, 4vw, 2rem);
            font-weight: 700; letter-spacing: -0.05em;
        }
        .back-link {
            display: inline-flex; align-items: center; gap: 0.5rem;
            font-family: var(--font-headline); font-size: 0.875rem; font-weight: 700;
            color: var(--color-on-background); text-decoration: none;
            padding: 0.5rem 1.25rem; border: 1px solid var(--color-surface-container-highest);
            border-radius: 9999px; transition: all 0.2s ease; transform: scale(0.95);
        }
        .back-link:hover {
            background: var(--color-on-background); color: var(--color-background);
            transform: scale(1);
        }

        /* Stats Grid (Mirip Section Skills/Projects) */
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1px; background: var(--color-surface-container-highest);
            border: 1px solid var(--color-surface-container-highest);
            border-radius: 16px; overflow: hidden; margin-bottom: 3rem;
        }
        .stat-box {
            background: var(--color-background); padding: 1.5rem 2rem;
            transition: background 0.3s;
        }
        .stat-box:hover { background: var(--color-surface-variant); }
        .stat-number {
            font-family: var(--font-headline); font-size: 2.5rem;
            font-weight: 700; color: var(--color-primary); line-height: 1;
        }
        .stat-label {
            font-family: var(--font-label); font-size: 0.75rem;
            letter-spacing: 0.1em; text-transform: uppercase;
            color: var(--color-on-surface-variant); margin-top: 0.5rem;
        }

        /* Section Label (Sama kayak di Portfolio) */
        .section-label {
            display: inline-flex; align-items: center; gap: 0.625rem;
            font-size: 0.75rem; font-weight: 500; letter-spacing: 0.12em;
            text-transform: uppercase; color: var(--color-primary); margin-bottom: 1.25rem;
        }
        .section-label::before {
            content: ''; width: 20px; height: 1px;
            background: var(--color-primary); opacity: 0.6;
        }

        /* Messages Grid (Mirip Projects Grid) */
        .messages-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }

        /* Message Card (Mirip Project Card) */
        .message-card {
            border-radius: 14px; overflow: hidden;
            border: 1px solid var(--color-surface-container-highest);
            background: var(--color-background);
            transition: border-color 0.3s, transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .message-card:hover {
            border-color: var(--color-on-surface-variant);
            transform: translateY(-4px);
        }
        .card-header {
            padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--color-surface-container-highest);
            display: flex; justify-content: space-between; align-items: center;
        }
        .sender-name {
            font-family: var(--font-headline); font-weight: 700;
            font-size: 1rem; letter-spacing: -0.01em;
        }
        .sender-email {
            font-size: 0.6875rem; color: var(--color-on-surface-variant);
            letter-spacing: 0.05em; text-transform: lowercase;
        }
        .card-meta {
            display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem;
            color: var(--color-on-surface-variant);
            background: var(--color-surface-variant); padding: 0.25rem 0.75rem;
            border-radius: 9999px; font-weight: 600;
        }
        .card-body {
            padding: 1.5rem; font-size: 0.9375rem; line-height: 1.7;
            color: var(--color-on-surface-variant); white-space: pre-wrap;
        }

        /* Empty State */
        .empty-state {
            text-align: center; padding: 4rem 2rem; color: var(--color-on-surface-variant);
        }
        .empty-icon { font-size: 3rem; margin-bottom: 1rem; color: var(--color-surface-container-highest); }

        /* Responsive */
        @media (max-width: 768px) {
            body { padding: 1rem 0; }
            .container { padding: 0 1rem; }
            header { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .messages-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="container">
        <header>
            <h1>Inbox</h1>
            <a href="../" class="back-link"><i class="fas fa-arrow-left"></i> Portfolio</a>
        </header>

        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-number"><?= count(array_filter($files, fn($f) => basename($f) !== 'likes.txt')) ?></div>
                <div class="stat-label">Total Messages</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?= $likes ?></div>
                <div class="stat-label">Total Likes</div>
            </div>
        </div>

        <div class="section-label">Messages</div>

        <div class="messages-grid">
            <?php
            $msgCount = 0;
            foreach ($files as $file):
                if (basename($file) === 'likes.txt') continue;
                $msgCount++;
                
                // Parse isi file
                $content = file_get_contents($file);
                $lines = explode("\n", $content);
                $name = str_replace('Dari: ', '', $lines[0] ?? 'Anonim');
                $email = str_replace('Email: ', '', $lines[1] ?? '-');
                $date = str_replace('Tanggal: ', '', $lines[2] ?? '-');
                $message = implode("\n", array_slice($lines, 4)); // Ambil setelah baris kosong
            ?>
                <div class="message-card">
                    <div class="card-header">
                        <div>
                            <div class="sender-name"><?= htmlspecialchars($name) ?></div>
                            <div class="sender-email"><?= htmlspecialchars($email) ?></div>
                        </div>
                        <div class="card-meta">
                            <i class="far fa-clock"></i> <?= htmlspecialchars($date) ?>
                        </div>
                    </div>
                    <div class="card-body"><?= htmlspecialchars(trim($message)) ?></div>
                </div>
            <?php endforeach; ?>
            
            <?php if ($msgCount === 0): ?>
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <div class="empty-icon"><i class="far fa-envelope-open"></i></div>
                    <h3>Belum ada pesan</h3>
                    <p style="margin-top: 0.5rem; font-size: 0.875rem;">Pesan dari form kontak akan muncul di sini.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
