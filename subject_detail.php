<?php
/* ================================================================
    subject_detail.php - Trang chi tiết môn học / quản lý câu hỏi
    ================================================================ */
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);

$subjectsFile = $dataDir . '/subjects.json';
if (!file_exists($subjectsFile)) { header('Location: dashboard.php'); exit; }

function loadJSON($file) {
    return json_decode(file_get_contents($file), true) ?: [];
}
function saveJSON($file, $data) {
    file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

$subjectId = $_GET['id'] ?? '';
if (!$subjectId) { header('Location: dashboard.php'); exit; }

$subjects = loadJSON($subjectsFile);
$subject = null;
foreach ($subjects as $s) {
    if ($s['id'] === $subjectId) { $subject = $s; break; }
}
if (!$subject) { header('Location: dashboard.php'); exit; }

$qFile = $dataDir . '/questions_' . $subjectId . '.json';
if (!file_exists($qFile)) saveJSON($qFile, []);

$success = '';

/* ---- Xử lý POST ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_questions') {
        $questionsRaw = $_POST['questions'] ?? '[]';
        $questions = json_decode($questionsRaw, true);
        if (is_array($questions)) {
            saveJSON($qFile, $questions);
            $success = 'Lưu câu hỏi thành công.';
        }
    }
}

$questions = loadJSON($qFile);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($subject['name']) ?> - Chi tiết</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { min-height:100vh; background:var(--body-bg); padding:40px 20px; }
        .container { max-width:1280px; margin:0 auto; }
        .content-grid { display:grid; grid-template-columns: minmax(360px, 460px) minmax(0, 1fr); gap:24px; align-items:start; }
        .left-panel { position:sticky; top:24px; }
        .right-panel { min-width:0; }

        /* Header */
        .page-header { display:flex; align-items:center; gap:16px; margin-bottom:32px; }
        .back-btn { display:flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:var(--radius-sm); background:var(--card-bg); box-shadow:var(--shadow-sm); color:var(--text-secondary); text-decoration:none; font-size:18px; transition:var(--transition); flex-shrink:0; }
        .back-btn:hover { background:var(--primary); color:#fff; }
        .page-title { flex:1; }
        .page-title h1 { font-size:22px; font-weight:800; color:var(--text-primary); margin:0; }
        .page-title small { font-size:13px; color:var(--text-muted); }

        /* Card */
        .card { background:var(--card-bg); border-radius:var(--radius-lg); box-shadow:var(--shadow); padding:28px; margin-bottom:24px; }
        .card-title { font-size:16px; font-weight:700; color:var(--text-primary); margin:0 0 6px; }
        .card-desc { font-size:13px; color:var(--text-muted); margin:0 0 20px; line-height:1.5; }

        /* Alerts */
        .alert { padding:12px 16px; border-radius:var(--radius-sm); font-size:14px; font-weight:500; margin-bottom:20px; }
        .alert-success { background:rgba(45,206,137,0.12); color:#1aad6e; }

        /* Form */
        label { font-size:13px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px; }
        textarea, input[type="text"], select { width:100%; padding:12px 14px; border:1.5px solid var(--border-color); border-radius:var(--radius-sm); font-size:14px; color:var(--text-primary); outline:none; transition:var(--transition); background:#fff; font-family:inherit; box-sizing:border-box; }
        textarea { resize:vertical; min-height:160px; line-height:1.7; }
        textarea:focus, input[type="text"]:focus, select:focus { border-color:var(--primary); box-shadow:0 0 0 3px var(--primary-light); }
        .field-stack { display:flex; flex-direction:column; gap:14px; }
        .mode-switch { display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-bottom:18px; }
        .mode-btn { border:none; border-radius:var(--radius-sm); padding:12px 14px; font-size:14px; font-weight:700; background:var(--body-bg); color:var(--text-secondary); cursor:pointer; transition:var(--transition); }
        .mode-btn.active { background:var(--primary); color:#fff; box-shadow:var(--shadow-sm); }
        .mode-panel { display:none; }
        .mode-panel.active { display:block; }
        .btn-row { display:flex; gap:10px; margin-top:14px; }
        .btn { padding:10px 22px; border:none; border-radius:var(--radius-sm); font-size:14px; font-weight:600; cursor:pointer; transition:var(--transition); font-family:inherit; }
        .btn-parse { background:var(--primary); color:#fff; }
        .btn-parse:hover { background:var(--primary-hover); }
        .btn-clear { background:var(--border-color); color:var(--text-secondary); }
        .btn-clear:hover { background:#ddd; }
        .btn-add-tf { background:var(--success,#2DCE89); color:#fff; }
        .btn-add-tf:hover { opacity:0.85; }
        .btn-add-dd { background:var(--info,#11CDEF); color:#fff; }
        .btn-add-dd:hover { opacity:0.85; }
        .dd-stmt-row { display:flex; gap:10px; align-items:start; margin-bottom:10px; background:var(--body-bg); padding:10px 12px; border-radius:var(--radius-sm); }
        .dd-stmt-row textarea { min-height:60px; flex:1; }
        .dd-stmt-row select { width:180px; flex-shrink:0; }
        .dd-stmt-row .btn-remove-stmt { background:var(--danger,#F5365C); color:#fff; border:none; border-radius:var(--radius-xs); padding:6px 10px; cursor:pointer; font-size:12px; flex-shrink:0; align-self:center; }
        .dd-match-display { display:flex; flex-direction:column; gap:6px; }
        .dd-match-row { display:flex; gap:8px; align-items:start; padding:8px 12px; border-radius:var(--radius-xs); background:rgba(0,0,0,0.02); font-size:13px; }
        .dd-match-stmt { flex:1; color:var(--text-secondary); white-space:pre-wrap; }
        .dd-match-arrow { color:var(--primary); font-weight:700; flex-shrink:0; }
        .dd-match-opt { flex:1; color:var(--primary); font-weight:600; white-space:pre-wrap; }

        /* Questions list */
        .q-list-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
        .q-count { font-size:13px; color:var(--text-muted); }
        .q-card { background:var(--body-bg); border-radius:var(--radius); padding:18px 20px; margin-bottom:12px; position:relative; }
        .q-head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin:0 0 6px; padding-right:56px; }
        .q-num { font-size:11px; font-weight:700; color:var(--primary); text-transform:uppercase; letter-spacing:0.5px; margin:0; }
        .q-status { font-size:11px; font-weight:700; padding:4px 8px; border-radius:999px; text-transform:uppercase; letter-spacing:0.4px; }
        .q-status-unsaved { background:rgba(251,99,64,0.14); color:var(--warning,#FB6340); }
        .q-status-saved { background:rgba(45,206,137,0.12); color:var(--success,#2DCE89); }
        .q-status-type { background:rgba(108,99,255,0.12); color:var(--primary); }
        .q-text { font-size:14px; font-weight:600; color:var(--text-primary); margin:0 0 12px; line-height:1.5; white-space:pre-wrap; }
        .q-options { display:flex; flex-direction:column; gap:6px; }
        .q-option { display:flex; align-items:center; gap:10px; padding:8px 12px; border-radius:var(--radius-xs); font-size:14px; color:var(--text-secondary); transition:var(--transition); }
        .q-option:hover { background:rgba(0,0,0,0.03); }
        .q-option input[type="checkbox"] { width:17px; height:17px; accent-color:var(--primary); cursor:pointer; flex-shrink:0; }
        .q-option.correct { background:rgba(45,206,137,0.08); color:var(--success,#2DCE89); font-weight:600; }
        .q-option label { cursor:pointer; flex:1; white-space:pre-wrap; }
        .q-opt-letter { width:24px; height:24px; border-radius:50%; background:var(--card-bg); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px; color:var(--text-muted); flex-shrink:0; }
        .q-option.correct .q-opt-letter { background:var(--success,#2DCE89); color:#fff; }
        .q-actions { position:absolute; top:14px; right:14px; }
        .btn-del { padding:4px 10px; background:var(--danger,#F5365C); color:#fff; border:none; border-radius:var(--radius-xs); font-size:12px; cursor:pointer; font-family:inherit; opacity:0; transition:var(--transition); }
        .q-card:hover .btn-del { opacity:1; }
        .btn-del:hover { opacity:0.8 !important; }

        /* Save bar */
        .save-bar-wrap { position:sticky; top:20px; z-index:20; margin-bottom:20px; }
        .save-bar { background:var(--card-bg); padding:16px 28px; border-radius:var(--radius-lg); box-shadow:var(--shadow); display:none; }
        .save-bar.visible { display:flex; align-items:center; justify-content:space-between; }
        .save-info { font-size:13px; color:var(--text-muted); }
        .btn-save { padding:12px 32px; background:var(--success,#2DCE89); color:#fff; border:none; border-radius:var(--radius); font-size:15px; font-weight:700; cursor:pointer; font-family:inherit; transition:var(--transition); }
        .btn-save:hover { opacity:0.85; box-shadow:var(--shadow); }

        .empty-state { text-align:center; color:var(--text-muted); font-size:14px; padding:24px 0; }
        .contact-link { position:fixed; right:18px; bottom:18px; display:inline-flex; align-items:center; min-height:42px; padding:0 14px; border-radius:999px; background:#fff; border:1px solid var(--border-color); box-shadow:var(--shadow); color:var(--text-primary); font-size:13px; font-weight:700; z-index:50; }
        .contact-link:hover { color:var(--primary); border-color:rgba(37,99,235,0.22); }

        @media (max-width: 980px) {
            .content-grid { grid-template-columns: 1fr; }
            .left-panel { position:static; }
            .contact-link { right:12px; bottom:12px; }
        }
    </style>
</head>
<body>
<div class="container">

    <div class="page-header">
        <a href="dashboard.php" class="back-btn">&larr;</a>
        <div class="page-title">
            <h1><?= htmlspecialchars($subject['name']) ?></h1>
            <small>Quản lý câu hỏi trắc nghiệm</small>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" id="saveForm" class="save-bar-wrap">
        <input type="hidden" name="action" value="save_questions">
        <input type="hidden" name="questions" id="saveQuestionsData">
        <div class="save-bar" id="saveBar">
            <div class="save-info" id="saveInfo"></div>
            <button type="submit" class="btn-save">Lưu tất cả</button>
        </div>
    </form>

    <div class="content-grid">
        <div class="left-panel">
            <div class="card">
                <div class="card-title">Thêm câu hỏi</div>
                <div class="card-desc">Chọn loại câu hỏi bên dưới. Bạn có thể tiếp tục dán nhanh cho trắc nghiệm, hoặc thêm riêng từng câu đúng/sai.</div>

                <div class="mode-switch">
                    <button type="button" class="mode-btn active" id="modeMcqBtn" onclick="switchQuestionMode('multiple_choice')">Trắc nghiệm</button>
                    <button type="button" class="mode-btn" id="modeTfBtn" onclick="switchQuestionMode('true_false')">Đúng / Sai</button>
                    <button type="button" class="mode-btn" id="modeDdBtn" onclick="switchQuestionMode('drag_drop')">Kéo thả</button>
                </div>

                <div class="mode-panel active" id="multipleChoicePanel">
                    <label for="pasteArea">Nội dung câu hỏi:</label>
                    <textarea id="pasteArea" placeholder="VD:&#10;Chức năng của những giá trị được chấp nhận trong doanh nghiệp là gì?&#10;Định hướng cách ứng xử cho nhân viên mới&#10;Hướng dẫn nhân viên cách đối phó với các tình huống cơ bản&#10;Cả A và B đều đúng (Đáp án chọn)&#10;Không có đáp án đúng&#10;&#10;Câu hỏi thứ 2?&#10;Đáp án 1&#10;Đáp án 2 (Đáp án chọn)&#10;Đáp án 3&#10;Đáp án 4"></textarea>

                    <div class="btn-row">
                        <button type="button" class="btn btn-parse" onclick="parseQuestions()">Thêm vào danh sách</button>
                        <button type="button" class="btn btn-clear" onclick="document.getElementById('pasteArea').value=''">Xóa nội dung</button>
                    </div>
                </div>

                <div class="mode-panel" id="trueFalsePanel">
                    <div class="field-stack">
                        <div>
                            <label for="trueFalseText">Nội dung câu đúng / sai:</label>
                            <textarea id="trueFalseText" placeholder="VD: Biểu trưng trực quan có thể thay đổi theo đặc điểm ngành nghề kinh doanh.&#10;&#10;Thêm dòng mới để tránh bị viết liền."></textarea>
                        </div>
                        <div>
                            <label for="trueFalseAnswer">Đáp án đúng:</label>
                            <select id="trueFalseAnswer">
                                <option value="0">Đúng</option>
                                <option value="1">Sai</option>
                            </select>
                        </div>
                    </div>

                    <div class="btn-row">
                        <button type="button" class="btn btn-add-tf" onclick="addTrueFalseQuestion()">Thêm đúng / sai</button>
                        <button type="button" class="btn btn-clear" onclick="clearTrueFalseForm()">Xóa nội dung</button>
                    </div>
                </div>

                <div class="mode-panel" id="dragDropPanel">
                    <div class="field-stack">
                        <div>
                            <label for="ddQuestionText">Nội dung câu hỏi:</label>
                            <textarea id="ddQuestionText" style="min-height:80px;" placeholder="VD: Kéo thả đáp án phù hợp vào các mô tả sau:"></textarea>
                        </div>
                        <div>
                            <label for="ddOptions">Các đáp án để kéo (phân cách bằng |):</label>
                            <input type="text" id="ddOptions" placeholder="VD: Văn hóa nguyên tắc|Văn hóa đồng đội|Văn hóa quyền hạn|Văn hóa sáng tạo">
                        </div>
                        <div>
                            <label>Các mệnh đề và đáp án đúng:</label>
                            <div id="ddStatements"></div>
                            <button type="button" class="btn btn-clear" onclick="addStatementRow()" style="margin-top:8px;">Thêm mệnh đề</button>
                        </div>
                    </div>
                    <div class="btn-row">
                        <button type="button" class="btn btn-add-dd" onclick="addDragDropQuestion()">Thêm kéo thả</button>
                        <button type="button" class="btn btn-clear" onclick="clearDragDropForm()">Xóa nội dung</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="right-panel">
            <div class="card" id="questionsCard">
                <div class="q-list-header">
                    <div class="card-title" style="margin:0;">Danh sách câu hỏi</div>
                    <div class="q-count" id="qCount"></div>
                </div>
                <div id="questionsContainer"></div>
            </div>
        </div>
    </div>

</div>
<a class="contact-link" href="https://www.facebook.com/userqhun" target="_blank" rel="noopener noreferrer">Liên hệ: qhun22</a>

<script>
let questions = <?= json_encode($questions, JSON_UNESCAPED_UNICODE) ?>.map(question => ({
    ...question,
    type: question.type || 'multiple_choice',
    _saved: true,
}));
let hasChanges = false;
let currentQuestionMode = 'multiple_choice';

renderAll();

function switchQuestionMode(mode) {
    currentQuestionMode = mode;
    document.getElementById('modeMcqBtn').classList.toggle('active', mode === 'multiple_choice');
    document.getElementById('modeTfBtn').classList.toggle('active', mode === 'true_false');
    document.getElementById('modeDdBtn').classList.toggle('active', mode === 'drag_drop');
    document.getElementById('multipleChoicePanel').classList.toggle('active', mode === 'multiple_choice');
    document.getElementById('trueFalsePanel').classList.toggle('active', mode === 'true_false');
    document.getElementById('dragDropPanel').classList.toggle('active', mode === 'drag_drop');
}

function parseQuestions() {
    const raw = document.getElementById('pasteArea').value.trim();
    if (!raw) return;

    const blocks = raw.split(/\n\s*\n/);
    let added = 0;

    blocks.forEach(block => {
        const lines = block.split('\n').map(l => l.trim()).filter(l => l);
        if (lines.length < 2) return;

        const text = lines[0];
        const answers = [];
        const correct = [];

        for (let i = 1; i < lines.length; i++) {
            let line = lines[i];
            let isCorrect = false;

            const markerRegex = /\s*\((?:[Dd]\u00e1p\s*[Aa\u00e1]n\s*[Cc]h\u1ecdn|[Dd]ap\s*[Aa]n\s*[Cc]hon|correct|dap\s*an)\)\s*/i;
            if (markerRegex.test(line)) {
                isCorrect = true;
                line = line.replace(markerRegex, '').trim();
            }
            line = line.replace(/^[A-Ha-h][.)]\s*/, '');

            answers.push(line);
            if (isCorrect) correct.push(i - 1);
        }

        questions.unshift({
            id: Date.now() + '_' + Math.random().toString(36).substr(2,5),
            text: text,
            answers: answers,
            correct: correct,
            type: 'multiple_choice',
            _saved: false
        });
        added++;
    });

    if (added > 0) {
        document.getElementById('pasteArea').value = '';
        hasChanges = true;
        renderAll();
    }
}

function addTrueFalseQuestion() {
    const textInput = document.getElementById('trueFalseText');
    const answerSelect = document.getElementById('trueFalseAnswer');
    const text = textInput.value.trim();
    if (!text) return;

    questions.unshift({
        id: Date.now() + '_' + Math.random().toString(36).substr(2,5),
        text: text,
        answers: ['Đúng', 'Sai'],
        correct: [parseInt(answerSelect.value, 10)],
        type: 'true_false',
        _saved: false
    });

    clearTrueFalseForm();
    hasChanges = true;
    renderAll();
}

function clearTrueFalseForm() {
    document.getElementById('trueFalseText').value = '';
    document.getElementById('trueFalseAnswer').value = '0';
}

/* -- Drag Drop admin -- */
function getOptionsFromInput() {
    return document.getElementById('ddOptions').value.split('|').map(s => s.trim()).filter(s => s);
}

function addStatementRow() {
    const container = document.getElementById('ddStatements');
    const row = document.createElement('div');
    row.className = 'dd-stmt-row';
    row.innerHTML = '<textarea placeholder="Nội dung mệnh đề..." rows="2"></textarea>'
        + '<select class="dd-match-select"><option value="">-- Chọn đáp án --</option></select>'
        + '<button type="button" class="btn-remove-stmt" onclick="this.parentElement.remove()">X</button>';
    container.appendChild(row);
    refreshDdSelects();
}

function refreshDdSelects() {
    const options = getOptionsFromInput();
    document.querySelectorAll('.dd-match-select').forEach(sel => {
        const cur = sel.value;
        sel.innerHTML = '<option value="">-- Chọn đáp án --</option>';
        options.forEach((opt, i) => {
            const o = document.createElement('option');
            o.value = i;
            o.textContent = opt;
            sel.appendChild(o);
        });
        if (cur !== '' && parseInt(cur) < options.length) sel.value = cur;
    });
}

document.getElementById('ddOptions').addEventListener('input', refreshDdSelects);

function addDragDropQuestion() {
    const text = document.getElementById('ddQuestionText').value.trim();
    const options = getOptionsFromInput();
    if (!text) { alert('Vui lòng nhập nội dung câu hỏi'); return; }
    if (options.length < 2) { alert('Cần ít nhất 2 đáp án để kéo'); return; }
    const rows = document.querySelectorAll('#ddStatements .dd-stmt-row');
    if (rows.length === 0) { alert('Vui lòng thêm ít nhất 1 mệnh đề'); return; }

    const statements = [];
    const correctMatches = [];
    let valid = true;

    rows.forEach(row => {
        const stmtText = row.querySelector('textarea').value.trim();
        const matchVal = row.querySelector('select').value;
        if (!stmtText || matchVal === '') valid = false;
        statements.push(stmtText);
        correctMatches.push(parseInt(matchVal, 10));
    });

    if (!valid) { alert('Vui lòng nhập đầy đủ mệnh đề và chọn đáp án đúng'); return; }

    questions.unshift({
        id: Date.now() + '_' + Math.random().toString(36).substr(2,5),
        type: 'drag_drop',
        text: text,
        options: options,
        statements: statements,
        correct_matches: correctMatches,
        _saved: false
    });

    clearDragDropForm();
    hasChanges = true;
    renderAll();
}

function clearDragDropForm() {
    document.getElementById('ddQuestionText').value = '';
    document.getElementById('ddOptions').value = '';
    document.getElementById('ddStatements').innerHTML = '';
}

function toggleCorrect(qi, ai) {
    const idx = questions[qi].correct.indexOf(ai);
    if (idx > -1) {
        questions[qi].correct.splice(idx, 1);
    } else {
        questions[qi].correct.push(ai);
    }
    hasChanges = true;
    renderAll();
}

function deleteQuestion(qi) {
    questions.splice(qi, 1);
    hasChanges = true;
    renderAll();
}

function renderAll() {
    const container = document.getElementById('questionsContainer');
    const labels = ['A','B','C','D','E','F','G','H'];
    const visibleQuestions = questions
        .map((question, index) => ({ question, index }))
        .sort((left, right) => Number(left.question._saved) - Number(right.question._saved));

    document.getElementById('qCount').textContent = questions.length + ' câu hỏi';

    if (questions.length === 0) {
        container.innerHTML = '<div class="empty-state">Chưa có câu hỏi nào. Dán câu hỏi vào ô phía trên để bắt đầu.</div>';
    } else {
        container.innerHTML = visibleQuestions.map(({ question: q, index: qi }, displayIndex) => {
            const statusClass = q._saved ? 'q-status q-status-saved' : 'q-status q-status-unsaved';
            const statusText = q._saved ? 'Đã lưu' : 'Chưa lưu';
            const typeText = q.type === 'drag_drop' ? 'Kéo thả' : q.type === 'true_false' ? 'Đúng / Sai' : 'Trắc nghiệm';
            let html = '<div class="q-card">';
            html += '<div class="q-actions"><button class="btn-del" onclick="deleteQuestion('+qi+')">Xóa</button></div>';
            html += '<div class="q-head">';
            html += '<div class="q-num">Câu ' + (displayIndex + 1) + '</div>';
            html += '<div style="display:flex; gap:8px; align-items:center;">';
            html += '<div class="q-status q-status-type">' + typeText + '</div>';
            html += '<div class="' + statusClass + '">' + statusText + '</div>';
            html += '</div>';
            html += '</div>';
            html += '<div class="q-text">' + escapeHtml(q.text) + '</div>';
            if (q.type === 'drag_drop') {
                html += '<div class="dd-match-display">';
                (q.statements || []).forEach((stmt, si) => {
                    const matchOpt = (q.options || [])[q.correct_matches[si]] || '---';
                    html += '<div class="dd-match-row">';
                    html += '<div class="dd-match-stmt">' + escapeHtml(stmt) + '</div>';
                    html += '<div class="dd-match-arrow">\u2192</div>';
                    html += '<div class="dd-match-opt">' + escapeHtml(matchOpt) + '</div>';
                    html += '</div>';
                });
                html += '</div></div>';
            } else {
                html += '<div class="q-options">';
                q.answers.forEach((a, ai) => {
                    const isCorrect = q.correct.includes(ai);
                    const cls = isCorrect ? 'q-option correct' : 'q-option';
                    const checked = isCorrect ? 'checked' : '';
                    html += '<div class="' + cls + '">';
                    html += '<input type="checkbox" ' + checked + ' onchange="toggleCorrect('+qi+','+ai+')" id="q'+qi+'a'+ai+'">';
                    html += '<div class="q-opt-letter">' + (labels[ai] || (ai+1)) + '</div>';
                    html += '<label for="q'+qi+'a'+ai+'">' + escapeHtml(a) + '</label>';
                    html += '</div>';
                });
                html += '</div></div>';
            }
            return html;
        }).join('');
    }

    // Save bar
    const saveBar = document.getElementById('saveBar');
    const payloadQuestions = questions.map(({ _saved, ...question }) => question);
    document.getElementById('saveQuestionsData').value = JSON.stringify(payloadQuestions);
    document.getElementById('saveInfo').textContent = questions.length + ' câu hỏi' + (hasChanges ? ' \u2022 Chưa lưu' : ' \u2022 Đã lưu');
    saveBar.classList.toggle('visible', questions.length > 0 || hasChanges);
}

function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}
</script>
</body>
</html>