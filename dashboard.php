<?php
/* ================================================================
    dashboard.php - Trang quản trị
    ================================================================ */
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);

$subjectsFile = $dataDir . '/subjects.json';
if (!file_exists($subjectsFile)) file_put_contents($subjectsFile, '[]');

function loadJSON($file) {
    return json_decode(file_get_contents($file), true) ?: [];
}
function saveJSON($file, $data) {
    file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

$subjects = loadJSON($subjectsFile);
$error = '';
$success = '';

/* ---- Xử lý POST ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_subject') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $error = 'Vui lòng nhập tên môn học.';
        } else {
            $id = 'subj_' . time() . '_' . mt_rand(100,999);
            $subjects[] = ['id' => $id, 'name' => $name];
            saveJSON($subjectsFile, $subjects);
            $qFile = $dataDir . '/questions_' . $id . '.json';
            if (!file_exists($qFile)) saveJSON($qFile, []);
            $success = 'Thêm môn học thành công.';
        }
    }
}

$subjects = loadJSON($subjectsFile);

/* Đếm câu hỏi từng môn */
$questionCounts = [];
foreach ($subjects as $s) {
    $qFile = $dataDir . '/questions_' . $s['id'] . '.json';
    $questionCounts[$s['id']] = 0;
    if (file_exists($qFile)) {
        $q = json_decode(file_get_contents($qFile), true);
        $questionCounts[$s['id']] = is_array($q) ? count($q) : 0;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản trị - Trắc Nghiệm Online</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { min-height:100vh; background:var(--body-bg); padding:40px 20px; }
        .container { max-width:760px; margin:0 auto; }
        .page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:32px; }
        .page-header h1 { font-size:24px; font-weight:800; color:var(--text-primary); margin:0; }
        .back-link { display:inline-flex; align-items:center; min-height:42px; padding:0 14px; background:#fff; border:1px solid var(--border-color); border-radius:12px; color:var(--text-primary); text-decoration:none; font-size:14px; font-weight:700; box-shadow:var(--shadow-sm); }
        .back-link:hover { color:var(--primary); border-color:rgba(37,99,235,0.22); }
        .card { background:var(--card-bg); border-radius:var(--radius-lg); box-shadow:var(--shadow); padding:28px; margin-bottom:24px; }
        .card-title { font-size:16px; font-weight:700; color:var(--text-primary); margin:0 0 20px; }
        .form-row { display:flex; gap:12px; }
        .form-group { display:flex; flex-direction:column; gap:6px; flex:1; }
        label { font-size:13px; font-weight:600; color:var(--text-secondary); }
        input[type="text"] { padding:10px 14px; border:1.5px solid var(--border-color); border-radius:var(--radius-sm); font-size:14px; color:var(--text-primary); outline:none; transition:var(--transition); background:#fff; font-family:inherit; }
        input[type="text"]:focus { border-color:var(--primary); box-shadow:0 0 0 3px var(--primary-light); }
        .btn-add { margin-top:20px; width:100%; padding:12px; background:var(--primary); color:#fff; border:none; border-radius:var(--radius); font-size:15px; font-weight:600; cursor:pointer; transition:var(--transition); font-family:inherit; }
        .btn-add:hover { background:var(--primary-hover); box-shadow:var(--shadow); }
        .alert { padding:12px 16px; border-radius:var(--radius-sm); font-size:14px; font-weight:500; margin-bottom:20px; }
        .alert-success { background:rgba(45,206,137,0.12); color:#1aad6e; }
        .alert-error { background:rgba(245,54,92,0.12); color:#c41e3a; }
        .subject-list { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:10px; }
        .subject-item { display:flex; align-items:center; gap:14px; padding:14px 16px; background:var(--body-bg); border-radius:var(--radius); font-size:15px; font-weight:500; color:var(--text-primary); text-decoration:none; transition:var(--transition); }
        .subject-item:hover { background:var(--primary-light); transform:translateY(-1px); }
        .subject-name { flex:1; }
        .subject-count { font-size:12px; color:var(--text-muted); }
        .subject-arrow { color:var(--text-muted); font-size:18px; }
        .empty-state { text-align:center; color:var(--text-muted); font-size:14px; padding:16px 0; }
        .contact-link { position:fixed; right:18px; bottom:18px; display:inline-flex; align-items:center; min-height:42px; padding:0 14px; border-radius:999px; background:#fff; border:1px solid var(--border-color); box-shadow:var(--shadow); color:var(--text-primary); font-size:13px; font-weight:700; z-index:50; }
        .contact-link:hover { color:var(--primary); border-color:rgba(37,99,235,0.22); }
    </style>
</head>
<body>
<div class="container">

    <div class="page-header">
        <h1>Quản trị</h1>
        <a href="index.html" class="back-link">Trang chủ</a>
    </div>

    <!-- Form thêm môn học -->
    <div class="card">
        <div class="card-title">Thêm môn học</div>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="add_subject">
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Tên môn học</label>
                    <input type="text" id="name" name="name" placeholder="VD: Toán học" required>
                </div>
            </div>
            <button type="submit" class="btn-add">Thêm môn học</button>
        </form>
    </div>

    <!-- Danh sách môn học -->
    <div class="card">
        <div class="card-title">Danh sách môn học (<?= count($subjects) ?>)</div>
        <?php if (empty($subjects)): ?>
            <div class="empty-state">Chưa có môn học nào.</div>
        <?php else: ?>
            <ul class="subject-list">
                <?php foreach ($subjects as $s):
                    $qCount = $questionCounts[$s['id']] ?? 0;
                ?>
                    <a href="subject_detail.php?id=<?= urlencode($s['id']) ?>" class="subject-item">
                        <span class="subject-name"><?= htmlspecialchars($s['name']) ?></span>
                        <span class="subject-count"><?= $qCount ?> câu</span>
                        <span class="subject-arrow">&rsaquo;</span>
                    </a>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

</div>
<a class="contact-link" href="https://www.facebook.com/userqhun" target="_blank" rel="noopener noreferrer">Liên hệ: qhun22</a>
</body>
</html>