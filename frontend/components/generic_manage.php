<?php
require_once __DIR__ . '/../utils/api_client.php'; // your API helper


// === Detect Mode ===
$isEdit = isset($_GET['id']) &&  $_GET['id'] != 'null';
$pageTitle = $isEdit ? 'Edit ' . $config['title'] : 'Add New ' . $config['title'];

// === Load existing data (for edit) ===
$existing = [];
if ($isEdit) {
    $data = apiRequest($config['endpoint'] . '?id=' . $_GET['id'], 'GET');
    if ($data && !isset($data['error'])) {
        $existing = $data['item'] ?? $data;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="assets/css/manage_screen.css" />
</head>
<body>
  <div class="form-container">
    <div class="form-card">
      <h2><?= htmlspecialchars($pageTitle) ?></h2>
      <form id="<?= $config['formId'] ?>" enctype="multipart/form-data" validate>
        <?php if ($isEdit): ?>
          <input type="hidden" name="id" value="<?= htmlspecialchars($_GET['id']) ?>">
          <input type="hidden" name="image" value="<?= htmlspecialchars($existing['image_url'] ?? '') ?>">
        <?php endif; ?>

        <div class="form-grid">
          <?php foreach ($config['fields'] as $field): 
            $value = htmlspecialchars($existing[$field['name']] ?? '');
            $isFile = $field['type'] === 'file';
          ?>
          <div class="form-group">
            <label for="<?= $field['name'] ?>"><?= htmlspecialchars($field['label']) ?></label>

            <div class="input-wrapper">
              <?php if (!empty($field['prefix'])): ?>
                <span class="prefix"><?= $field['prefix'] ?></span>
              <?php endif; ?>

              <?php if ($field['type'] === 'select'): ?>
                <select 
                  id="<?= $field['name'] ?>" 
                  name="<?= $field['name'] ?>"
                  class="form-input"
                  <?= !empty($field['required']) ? 'required' : '' ?>
                >
                  <option value="">Select <?= $field['label'] ?></option>
                  <?php foreach ($field['options'] as $option): ?>
                    <option 
                      value="<?= htmlspecialchars($option['value']) ?>"
                      <?= $value === $option['value'] ? 'selected' : '' ?>
                    >
                      <?= htmlspecialchars($option['label']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              <?php else: ?>
                <input 
                  type="<?= $field['type'] ?>" 
                  id="<?= $field['name'] ?>" 
                  name="<?= $field['name'] ?>"
                  class="form-input"
                  value="<?= $isFile ? '' : $value ?>"
                  <?= !empty($field['accept']) ? 'accept="'.$field['accept'].'"' : '' ?>
                  <?= !empty($field['step']) ? 'step="'.$field['step'].'"' : '' ?>
                  <?= !empty($field['min']) ? 'min="'.$field['min'].'"' : '' ?>
                  <?= !empty($field['required']) ? 'required' : '' ?>
                />
              <?php endif; ?>

              <?php if (!empty($field['suffix'])): ?>
                <span class="suffix"><?= $field['suffix'] ?></span>
              <?php endif; ?>
            </div>

            <div class="invalid-feedback">Please enter <?= strtolower($field['label']) ?>.</div>

            <?php if ($isFile && !empty($value)): ?>
              <div class="preview-container">
                <img src="<?= htmlspecialchars($value) ?>" alt="Preview" class="preview-image">
              </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn primary">Save</button>
          <button onclick="loadPage('<?= $config['redirectPage'] ?>')" class="btn secondary">Cancel</button>
        </div>
      </form>

      <?php if (!$isEdit): ?>
        <hr class="divider">
        
        <h3>Bulk Upload (CSV / Excel)</h3>
        <form  id="<?= $config['bulkUploadFormId'] ?>" enctype="multipart/form-data">
          <input type="file" name="file" accept=".csv,.xlsx" required class="form-input">
          <button type="submit" class="btn upload">Upload</button>
        </form>

      <?php endif; ?>
        <div id="<?= $config['resultDivId'] ?>" class="form-result"></div>

    </div>
  </div>
</body>
</html>
