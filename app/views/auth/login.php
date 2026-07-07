<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Admin Login' ?> - TemplateLink Builder</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --background: #09090b;
            --card-bg: rgba(24, 24, 27, 0.6);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #f4f4f5;
            --text-muted: #a1a1aa;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--background);
            color: var(--text-main);
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden;
            position: relative;
        }

        /* Ambient background glow */
        .glow {
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, rgba(0,0,0,0) 70%);
            filter: blur(50px);
            z-index: -1;
        }
        .glow-1 { top: -10%; left: -10%; }
        .glow-2 { bottom: -10%; right: -10%; }

        .login-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-radius: 24px;
            padding: 3rem 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            box-sizing: border-box;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-text {
            font-weight: 700;
            font-size: 1.8rem;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #818cf8 0%, #c084fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
        }

        .logo-sub {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            display: flex;
            flex-direction: column;
        }

        label {
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #e4e4e7;
        }

        input {
            background: rgba(9, 9, 11, 0.8);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 0.8rem 1rem;
            color: var(--text-main);
            font-family: inherit;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            outline: none;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary) 0%, #7c3aed 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-family: inherit;
            font-weight: 600;
            font-size: 1rem;
            padding: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
            margin-top: 0.5rem;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.5);
        }

        .alert {
            padding: 0.8rem 1rem;
            border-radius: 12px;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #a7f3d0;
        }
    </style>
</head>
<body>
    <div class="glow glow-1"></div>
    <div class="glow glow-2"></div>

    <div class="login-card">
        <div class="logo-container">
            <h1 class="logo-text">TemplateLink</h1>
            <div class="logo-sub">Portal Administrator Login</div>
        </div>

        <?php
        // Access static flash via helper on controller instance or direct session check since this is plain layout
        $error = $_SESSION['flash']['error'] ?? null;
        $success = $_SESSION['flash']['success'] ?? null;
        unset($_SESSION['flash']['error'], $_SESSION['flash']['success']);
        
        if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>admin/login" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" placeholder="admin" required autofocus autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn-submit">Sign In</button>
        </form>
    </div>
</body>
</html>
