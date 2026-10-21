<?php
// Create .htaccess to increase file upload limits if not exists
$htaccess = __DIR__ . '/.htaccess';
if (!file_exists($htaccess)) {
    file_put_contents($htaccess, "php_value max_file_uploads 200
php_value upload_max_filesize 50M
php_value post_max_size 500M
php_value memory_limit 256M
");
} else {
    $htContent = file_get_contents($htaccess);
    if (strpos($htContent, 'max_file_uploads') === false) {
        file_put_contents($htaccess, $htContent . "\nphp_value max_file_uploads 200\nphp_value upload_max_filesize 50M\nphp_value post_max_size 500M\nphp_value memory_limit 256M\n");
    }
}
// Helper: corrigeer EXIF-oriëntatie van smartphone-foto's
function correctOrientation($img, $tmpFile) {
    if (!function_exists('exif_read_data')) return $img;
    $exif = @exif_read_data($tmpFile);
    if (!$exif || !isset($exif['Orientation'])) return $img;
    switch ($exif['Orientation']) {
        case 3: return imagerotate($img, 180, 0);
        case 6: return imagerotate($img, -90, 0);
        case 8: return imagerotate($img,  90, 0);
    }
    return $img;
}

// Security: simple password protection
$password = "linderakkers2024";
$authenticated = false;
session_start();
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}
if (isset($_POST['password']) && $_POST['password'] === $password) {
    $_SESSION['authenticated'] = true;
}
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    $authenticated = true;
}
// Handle upload action
$message = '';
$messageType = '';
if ($authenticated && isset($_POST['action'])) {

    $picturesDir = __DIR__ . '/Pictures/';

    if ($_POST['action'] === 'create_gallery') {
        $galleryName = trim($_POST['gallery_name']);
        $galleryName = preg_replace('/[^a-zA-Z0-9 \-_]/', '', $galleryName);

        if (empty($galleryName)) {
            $message = "Geef een geldige naam op voor de galerij.";
            $messageType = "error";
        } else {
            $galleryDir = $picturesDir . $galleryName . '/';
            $imagesDir = $galleryDir . 'images/';
            $thumbsDir = $galleryDir . 'thumbs/';
            $orgDir = $galleryDir . 'org_images/';

            // Check for duplicate gallery name
            if (is_dir($galleryDir)) {
                $message = "Er bestaat al een galerij met de naam '$galleryName'. Kies een andere naam of gebruik 'Toevoegen aan bestaande galerij'.";
                $messageType = "error";
            } else {
                mkdir($galleryDir, 0755, true);
                mkdir($imagesDir, 0755, true);
                mkdir($thumbsDir, 0755, true);
                mkdir($orgDir, 0755, true);
            }

            if (empty($message)) {

            $uploadedFiles = [];
            $errors = [];

            if (!empty($_FILES['photos']['name'][0])) {
                $fileCount = count($_FILES['photos']['name']);
                $firstFile = true;

                for ($i = 0; $i < $fileCount; $i++) {
                    if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) continue;

                    $tmpFile = $_FILES['photos']['tmp_name'][$i];
                    $origName = $_FILES['photos']['name'][$i];
                    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                        $errors[] = "$origName: ongeldig bestandstype";
                        continue;
                    }

                    $safeName = strtolower(preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName));
                    // ensure lowercase extension
                    $safeName = preg_replace('/\.(jpeg)$/i', '.jpg', $safeName);
                    // Avoid conflict with reserved name cover.jpg
                    if ($safeName === 'cover.jpg') {
                        $safeName = 'foto_cover.jpg';
                    }

                    // Check for duplicate filename
                    if (file_exists($imagesDir . $safeName)) {
                        $errors[] = $safeName;
                        continue;
                    }

                    // Save original
                    copy($tmpFile, $orgDir . $safeName);

                    // Create resized version for images/ (max 1200px wide)
                    $img = null;
                    if (in_array($ext, ['jpg', 'jpeg'])) {
                        $img = imagecreatefromjpeg($tmpFile);
                    } elseif ($ext === 'png') {
                        $img = imagecreatefrompng($tmpFile);
                    }

                    if ($img) {
                        $img = correctOrientation($img, $tmpFile);
                        $origW = imagesx($img);
                        $origH = imagesy($img);

                        // Resize for images/ (max 1200px)
                        $maxW = 1200;
                        if ($origW > $maxW) {
                            $newW = $maxW;
                            $newH = intval($origH * $maxW / $origW);
                        } else {
                            $newW = $origW;
                            $newH = $origH;
                        }
                        $resized = imagecreatetruecolor($newW, $newH);
                        imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
                        imagejpeg($resized, $imagesDir . $safeName, 85);
                        imagedestroy($resized);

                        // Create square thumbnail for thumbs/ (200x200)
                        $thumbSize = 200;
                        $thumb = imagecreatetruecolor($thumbSize, $thumbSize);
                        $cropSize = min($origW, $origH);
                        $cropX = intval(($origW - $cropSize) / 2);
                        $cropY = intval(($origH - $cropSize) / 2);
                        imagecopyresampled($thumb, $img, 0, 0, $cropX, $cropY, $thumbSize, $thumbSize, $cropSize, $cropSize);
                        imagejpeg($thumb, $thumbsDir . $safeName, 80);
                        imagedestroy($thumb);

                        // First image becomes cover (square crop for both images/ and thumbs/)
                        if ($firstFile) {
                            // Square cover for images/ (600x600)
                            $coverSize = 600;
                            $cover = imagecreatetruecolor($coverSize, $coverSize);
                            $cropSize = min($origW, $origH);
                            $cropX = intval(($origW - $cropSize) / 2);
                            $cropY = intval(($origH - $cropSize) / 2);
                            imagecopyresampled($cover, $img, 0, 0, $cropX, $cropY, $coverSize, $coverSize, $cropSize, $cropSize);
                            imagejpeg($cover, $imagesDir . 'cover.jpg', 85);
                            imagedestroy($cover);
                            // Square cover for thumbs/
                            copy($thumbsDir . $safeName, $thumbsDir . 'cover.jpg');
                            $firstFile = false;
                        }

                        imagedestroy($img);
                        $uploadedFiles[] = $safeName;
                    }
                }

                // Write config.json - first photo is cover, don't list it twice
                if (!empty($uploadedFiles)) {
                    $allFiles = array_merge(['cover.jpg'], array_slice($uploadedFiles, 1));
                    $config = [
                        'imgFilesArray' => $allFiles,
                        'galleryTitle' => $galleryName
                    ];
                    file_put_contents($galleryDir . 'config.json', json_encode($config));

                    // Update galleries_config.json
                    $galleriesConfigFile = $picturesDir . 'galleries_config.json';
                    $galleriesConfig = [];
                    if (file_exists($galleriesConfigFile)) {
                        $galleriesConfig = json_decode(file_get_contents($galleriesConfigFile), true) ?? [];
                    }

                    // Remove if already exists
                    $galleriesConfig = array_filter($galleriesConfig, fn($g) => $g['dir'] !== $galleryName);
                    $galleriesConfig[] = ['dir' => $galleryName, 'name' => $galleryName];
                    file_put_contents($galleriesConfigFile, json_encode(array_values($galleriesConfig)));

                    $count = count($uploadedFiles) - 1; // subtract cover
                    $skipped = count($errors);
                    $message = "Galerij '$galleryName' aangemaakt met $count fotos!";
                    $messageType = "success";

                    if ($skipped > 0) {
                        $message .= " $skipped foto" . ($skipped === 1 ? " werd" : "s werden") . " overgeslagen omdat de bestandsnaam al bestaat.";
                    }
                } else {
                    $message = "Geen fotos geupload. " . implode(', ', $errors);
                    $messageType = "error";
                }
            } else {
                $message = "Geen fotos geselecteerd.";
                $messageType = "error";
            }
            } // end if empty($message)
        }
    }

    if ($_POST['action'] === 'add_to_gallery') {
        $galleryName = $_POST['existing_gallery'];
        $galleryDir = $picturesDir . $galleryName . '/';
        $imagesDir = $galleryDir . 'images/';
        $thumbsDir = $galleryDir . 'thumbs/';
        $orgDir = $galleryDir . 'org_images/';

        if (!is_dir($galleryDir)) {
            $message = "Galerij niet gevonden.";
            $messageType = "error";
        } else {
            // Load existing config
            $configFile = $galleryDir . 'config.json';
            $config = json_decode(file_get_contents($configFile), true) ?? ['imgFilesArray' => [], 'galleryTitle' => $galleryName];

            $uploadedFiles = [];
            $errors = [];
            if (!empty($_FILES['photos']['name'][0])) {
                $fileCount = count($_FILES['photos']['name']);
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $tmpFile = $_FILES['photos']['tmp_name'][$i];
                    $origName = $_FILES['photos']['name'][$i];
                    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) continue;
                    $safeName = strtolower(preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName));
                    $safeName = preg_replace('/\.(jpeg)$/i', '.jpg', $safeName);
                    if ($safeName === 'cover.jpg') { $safeName = 'foto_cover.jpg'; }
                    // Check for duplicate filename
                    if (file_exists($imagesDir . $safeName)) {
                        $errors[] = $safeName;
                        continue;
                    }
                    copy($tmpFile, $orgDir . $safeName);
                    $img = imagecreatefromjpeg($tmpFile);
                    if ($img) {
                        $img = correctOrientation($img, $tmpFile);
                        $origW = imagesx($img); $origH = imagesy($img);
                        $maxW = 1200;
                        $newW = $origW > $maxW ? $maxW : $origW;
                        $newH = intval($origH * $newW / $origW);
                        $resized = imagecreatetruecolor($newW, $newH);
                        imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
                        imagejpeg($resized, $imagesDir . $safeName, 85);
                        imagedestroy($resized);
                        $thumbSize = 200;
                        $thumb = imagecreatetruecolor($thumbSize, $thumbSize);
                        $cropSize = min($origW, $origH);
                        $cropX = intval(($origW - $cropSize) / 2);
                        $cropY = intval(($origH - $cropSize) / 2);
                        imagecopyresampled($thumb, $img, 0, 0, $cropX, $cropY, $thumbSize, $thumbSize, $cropSize, $cropSize);
                        imagejpeg($thumb, $thumbsDir . $safeName, 80);
                        imagedestroy($thumb);
                        imagedestroy($img);
                        $uploadedFiles[] = $safeName;
                    }
                }
                $config['imgFilesArray'] = array_merge($config['imgFilesArray'], $uploadedFiles);
                file_put_contents($configFile, json_encode($config));
                $count = count($uploadedFiles);
                $skipped = count($errors);
                $message = "$count foto" . ($count === 1 ? "" : "s") . " toegevoegd aan '$galleryName'!";
                $messageType = "success";
                if ($skipped > 0) {
                    $message .= " $skipped foto" . ($skipped === 1 ? " werd" : "s werden") . " overgeslagen omdat de bestandsnaam al bestaat in deze galerij.";
                }
            }
        }
    }
}
// Get existing galleries for dropdown
$existingGalleries = [];
if ($authenticated) {
    $galleriesConfigFile = __DIR__ . '/Pictures/galleries_config.json';
    if (file_exists($galleriesConfigFile)) {
        $existingGalleries = json_decode(file_get_contents($galleriesConfigFile), true) ?? [];
        usort($existingGalleries, fn($a, $b) => strcmp($b['name'], $a['name']));
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Foto Upload - Linderakkers</title>
    <link href="/vendor/bootstrap-4.1.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .header { background: #5a8a3c; color: white; padding: 20px; margin-bottom: 30px; }
        .header h1 { font-size: 1.8rem; margin: 0; }
        .drop-zone {
            border: 3px dashed #5a8a3c;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: background 0.2s;
            background: white;
        }
        .drop-zone:hover, .drop-zone.dragover { background: #e8f5e9; }
        .drop-zone i { font-size: 3rem; color: #5a8a3c; }
        .btn-green { background: #5a8a3c; color: white; border: none; }
        .btn-green:hover { background: #4a7a2c; color: white; }
        .preview-grid { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 15px; }
        .preview-item { width: 80px; height: 80px; object-fit: cover; border-radius: 4px; }
        .progress { display: none; }
        #fileCount { font-weight: bold; color: #5a8a3c; }
    </style>
</head>
<body>
<div class="header">
    <div class="container">
        <h1>📸 Foto Upload - Linderakkers</h1>
    </div>
</div>
<div class="container">
<?php if (!$authenticated): ?>
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h4>Inloggen</h4>
                    <form method="POST">
                        <div class="form-group">
                            <input type="password" name="password" class="form-control" placeholder="Wachtwoord" autofocus>
                        </div>
                        <button type="submit" class="btn btn-green btn-block">Inloggen</button>
                    </form>
                    <?php if (isset($_POST['password'])): ?>
                        <div class="alert alert-danger mt-2">Verkeerd wachtwoord</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?> alert-dismissible">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    <div class="row">
        <!-- Nieuwe galerij -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">➕ Nieuwe galerij aanmaken</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="newGalleryForm">
                        <input type="hidden" name="action" value="create_gallery">
                        <div class="form-group">
                            <label>Naam van de galerij</label>
                            <input type="text" name="gallery_name" class="form-control"
                                   placeholder="bijv. 2024 Vakantiekermis" required>
                            <small class="text-muted">Gebruik: jaar + naam, bijv. "2024 Vakantiekermis"</small>
                        </div>
                        <div class="form-group">
                            <label>Foto's selecteren</label>
                            <div class="drop-zone" id="dropZone1" onclick="document.getElementById('photos1').click()">
                                <div>📁 Klik of sleep foto's hier</div>
                                <div id="fileCount1" class="mt-2"></div>
                            </div>
                            <input type="file" id="photos1" name="photos[]" multiple accept="image/*" style="display:none">
                            <div class="preview-grid" id="preview1"></div>
                        </div>
                        <div class="progress mb-2" id="progress1">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width:100%">
                                Bezig met uploaden...
                            </div>
                        </div>
                        <button type="button" class="btn btn-green btn-block" onclick="handleBatchUpload(document.getElementById('newGalleryForm'), 'progress1', 18)">
                            🚀 Galerij aanmaken
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <!-- Toevoegen aan bestaande galerij -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header" style="background:#5a8a3c; color:white">
                    <h5 class="mb-0">📂 Foto's toevoegen aan bestaande galerij</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="addForm">
                        <input type="hidden" name="action" value="add_to_gallery">
                        <div class="form-group">
                            <label>Kies galerij</label>
                            <select name="existing_gallery" class="form-control" required>
                                <option value="">-- Selecteer galerij --</option>
                                <?php foreach ($existingGalleries as $g): ?>
                                    <option value="<?= htmlspecialchars($g['dir']) ?>">
                                        <?= htmlspecialchars($g['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Foto's selecteren</label>
                            <div class="drop-zone" id="dropZone2" onclick="document.getElementById('photos2').click()">
                                <div>📁 Klik of sleep foto's hier</div>
                                <div id="fileCount2" class="mt-2"></div>
                            </div>
                            <input type="file" id="photos2" name="photos[]" multiple accept="image/*" style="display:none">
                            <div class="preview-grid" id="preview2"></div>
                        </div>
                        <div class="progress mb-2" id="progress2">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width:100%">
                                Bezig met uploaden...
                            </div>
                        </div>
                        <button type="button" class="btn btn-green btn-block" onclick="handleBatchUpload(document.getElementById('addForm'), 'progress2', 18)">
                            ➕ Foto's toevoegen
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="text-center mb-4">
        <a href="/" class="btn btn-outline-secondary mr-2">← Terug naar de site</a>
        <a href="?logout=1" class="btn btn-outline-danger">Afmelden</a>
    </div>
<?php endif; ?>
</div>
<script src="/vendor/jquery/jquery.min.js"></script>
<script src="/vendor/bootstrap-4.1.3/js/bootstrap.bundle.min.js"></script>
<script>
async function uploadBatch(formData) {
    const response = await fetch(window.location.href, {
        method: 'POST',
        body: formData
    });
    return response.text();
}
async function handleBatchUpload(form, progressId, batchSize) {
    const files = form.querySelector('input[type="file"]').files;
    const totalFiles = files.length;

    if (totalFiles === 0) {
        alert("Geen fotos geselecteerd!");
        return;
    }
    const progressBar = document.getElementById(progressId);
    progressBar.style.display = 'block';
    const bar = progressBar.querySelector('.progress-bar');

    const galleryName = form.querySelector('[name="gallery_name"]') ?
        form.querySelector('[name="gallery_name"]').value : null;
    const existingGallery = form.querySelector('[name="existing_gallery"]') ?
        form.querySelector('[name="existing_gallery"]').value : null;
    const action = form.querySelector('[name="action"]').value;

    let uploaded = 0;
    let isFirst = true;

    while (uploaded < totalFiles) {
        const batch = Array.from(files).slice(uploaded, uploaded + batchSize);
        const fd = new FormData();
        fd.append('action', isFirst ? action : 'add_to_gallery');

        if (isFirst && galleryName) {
            fd.append('gallery_name', galleryName);
        } else if (!isFirst && galleryName) {
            fd.append('existing_gallery', galleryName);
        } else if (existingGallery) {
            fd.append('existing_gallery', existingGallery);
        }

        batch.forEach(file => fd.append('photos[]', file));

        await uploadBatch(fd);
        uploaded += batch.length;
        isFirst = false;

        const pct = Math.round(uploaded / totalFiles * 100);
        bar.style.width = pct + '%';
        bar.textContent = uploaded + ' / ' + totalFiles + " fotos verwerkt...";
    }

    bar.textContent = "Klaar! " + totalFiles + " fotos geupload.";
    bar.classList.remove('progress-bar-animated');
    setTimeout(() => { window.location.reload(); }, 1500);
}
function setupDropZone(dropZoneId, inputId, previewId, countId) {
    var dropZone = document.getElementById(dropZoneId);
    var input = document.getElementById(inputId);
    var preview = document.getElementById(previewId);
    var countEl = document.getElementById(countId);
    input.addEventListener('change', function() {
        showPreviews(this.files, preview, countEl);
    });
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });
    dropZone.addEventListener('dragleave', function() {
        this.classList.remove('dragover');
    });
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        input.files = e.dataTransfer.files;
        showPreviews(e.dataTransfer.files, preview, countEl);
    });
}
function showPreviews(files, previewEl, countEl) {
    previewEl.innerHTML = '';
    countEl.innerHTML = files.length + " foto" + (files.length !== 1 ? "s" : '') + " geselecteerd";
    Array.from(files).slice(0, 20).forEach(function(file) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'preview-item';
            previewEl.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
    if (files.length > 20) {
        var more = document.createElement('div');
        more.className = 'preview-item d-flex align-items-center justify-content-center bg-light';
        more.innerHTML = '+' + (files.length - 20) + '<br>meer';
        more.style.fontSize = '12px';
        previewEl.appendChild(more);
    }
}
setupDropZone('dropZone1', 'photos1', 'preview1', 'fileCount1');
setupDropZone('dropZone2', 'photos2', 'preview2', 'fileCount2');
</script>
</body>
</html>
