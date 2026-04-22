<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exception: <?php echo $title; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #0f172a; --surface: #1e293b; --primary: #f43f5e; --text: #f1f5f9; --muted: #94a3b8; }
        body { margin: 0; background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; line-height: 1.6; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .header { background: var(--surface); padding: 30px; border-radius: 12px; border-left: 6px solid var(--primary); margin-bottom: 30px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3); }
        .type { font-size: 14px; text-transform: uppercase; letter-spacing: 1px; color: var(--primary); font-weight: 700; margin-bottom: 10px; }
        .message { font-size: 24px; font-weight: 700; margin: 0; }
        .location { margin-top: 15px; color: var(--muted); font-family: 'Fira Code', monospace; font-size: 14px; }
        .trace-container { background: var(--surface); padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .trace-title { font-size: 18px; font-weight: 600; margin-bottom: 20px; border-bottom: 1px solid #334155; padding-bottom: 10px; }
        .trace-item { margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #334155; }
        .trace-item:last-child { border: none; }
        .trace-call { font-family: 'Fira Code', monospace; color: #38bdf8; font-weight: 500; }
        .trace-loc { font-size: 13px; color: var(--muted); margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="type"><?php echo $title; ?></div>
            <h1 class="message"><?php echo $message; ?></h1>
            <div class="location">in <?php echo $file; ?> on line <?php echo $line; ?></div>
        </div>

        <div class="trace-container">
            <div class="trace-title">Stack Trace</div>
            <?php foreach ($trace as $i => $t): ?>
                <div class="trace-item">
                    <div class="trace-call">
                        #<?php echo $i; ?> 
                        <?php echo isset($t['class']) ? $t['class'] . $t['type'] : ''; ?>
                        <?php echo $t['function']; ?>(...)
                    </div>
                    <?php if (isset($t['file'])): ?>
                        <div class="trace-loc"><?php echo $t['file']; ?>:<?php echo $t['line']; ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
