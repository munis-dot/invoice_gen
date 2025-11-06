<?php
require_once __DIR__ . '/../utils/api_client.php';

// Fetch data
$data = apiRequest($config['endpoint'], 'GET');
if (!$data || isset($data['error'])) {
    die("<div class='error-container'><h2>Failed to load data</h2></div>");
}
$item = $data['item'] ?? $data;

// Helper to safely get nested value
function getNestedValue($data, $path)
{
    $keys = explode('.', $path);
    foreach ($keys as $k) {
        if (is_array($data) && isset($data[$k])) {
            $data = $data[$k];
        } else {
            return '—';
        }
    }
    return $data;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($config['title']) ?></title>
    <link rel="stylesheet" href="assets/css/view_screen.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/js/app.js"></script>
</head>

<body>
    <div class="view-container">
        <!-- Header Section -->
        <div class="view-header">
            <div class="header-content">
                <h1 class="view-title"><?= htmlspecialchars($config['title']) ?></h1>
                <div class="header-actions">
                    <?php if (!empty($config['actions'])): ?>
                        <?php foreach ($config['actions'] as $action): ?>
                            <?php
                            $url = parse_url($action['link']);
                            parse_str($url['query'] ?? '', $params);
                            $page = ltrim($params['page'] ?? '', '/');
                            unset($params['page']);
                            $paramsJson = htmlspecialchars(json_encode($params), ENT_QUOTES, 'UTF-8');
                            ?>
                            <button class="action-btn <?= htmlspecialchars($action['class']) ?>"
                                onclick="loadPage('<?= $page ?>', <?= $paramsJson ?>)">
                                <?php if (!empty($action['icon'])): ?>
                                    <i class="fas fa-<?= htmlspecialchars($action['icon']) ?>"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($action['label']) ?>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="view-content">
            <?php if (!empty($config['image_field']) && !empty($item[$config['image_field']])): ?>
                <div class="media-section">
                    <div class="media-card">
                        <div class="media-header">
                            <h3>Media</h3>
                            <span class="media-badge">Preview</span>
                        </div>
                        <div class="media-preview">
                            <img src="<?= htmlspecialchars($item[$config['image_field']]) ?>" alt="Preview"
                                class="preview-image">
                            <div class="image-overlay">
                                <button class="zoom-btn">
                                    <i class="fas fa-expand"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="details-section">
                <div class="details-card">
                    <div class="card-header">
                        <h3>Details</h3>
                        <div class="status-indicator">
                            <span class="status-dot active"></span>
                            <span>Active</span>
                        </div>
                    </div>

                    <div class="details-grid">
                        <?php foreach ($config['fields'] as $key => $field): ?>
                            <?php if ($key !== $config['image_field']): ?>
                                <?php
                                $value = $item[$key] ?? '—';
                                $label = is_array($field) ? $field['label'] : $field;

                                // Format value
                                if (is_array($field) && isset($field['format'])) {
                                    switch ($field['format']) {
                                        case 'currency':
                                            $value = '$' . number_format($value, 2);
                                            break;
                                        case 'percentage':
                                            $value = number_format($value, 1) . '%';
                                            break;
                                        case 'number':
                                            $value = number_format($value);
                                            break;
                                        case 'date':
                                            $value = date('F j, Y', strtotime($value));
                                            break;
                                        case 'object':
                                            $value = getNestedValue($value, $field['path']);
                                            break;
                                        default:
                                            $value = htmlspecialchars($value);
                                    }
                                } else {
                                    $value = htmlspecialchars($value);
                                }
                                ?>

                                <div class="detail-item">
                                    <div class="detail-label">
                                        <i class="fas fa-tag detail-icon"></i>
                                        <?= htmlspecialchars($label) ?>
                                    </div>
                                    <div class="detail-value"><?= $value ?></div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Additional Info Card -->
                <div class="info-card">
                    <div class="card-header">
                        <h3>Additional Information</h3>
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-item">
                            <div class="info-label">Created</div>
                            <div class="info-value"><?= date('M j, Y', strtotime($item['created_at'] ?? 'now')) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Last Updated</div>
                            <div class="info-value"><?= date('M j, Y', strtotime($item['updated_at'] ?? 'now')) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">ID</div>
                            <div class="info-value">#<?= htmlspecialchars($item['id'] ?? 'N/A') ?></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>


</body>

</html>