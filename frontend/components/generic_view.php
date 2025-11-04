<?php
require_once __DIR__ . '/../utils/api_client.php';
// Fetch data
$data = apiRequest($config['endpoint'], 'GET');
if (!$data || isset($data['error'])) {
    die("<h2 style='text-align:center;color:red;'>Failed to load data</h2>");
}
$item = $data['item'] ?? $data;

// Helper to safely get nested value (like category.name)
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
</head>

<body>
    <div class="fullscreen-container">
        <div class="content-card">
            <?php if (!empty($config['image_field'])): ?>
                <div class="left-panel">
                    <?php if (!empty($item[$config['image_field']])): ?>
                        <img src="<?= htmlspecialchars($item[$config['image_field']]) ?>" alt="Image">
                    <?php else: ?>
                        <div class="placeholder">No Image</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="right-panel<?= empty($config['image_field']) ? ' full-width' : '' ?>">
                <h2 class="title"><?= htmlspecialchars($config['title']) ?></h2>
                <div class="info-list">
                    <?php foreach ($config['fields'] as $key => $field): ?>
                        <?php if ($key !== $config['image_field']): ?>
                            <div class="info-item">
                                <span class="label">
                                    <?= is_array($field) ? htmlspecialchars($field['label']) : htmlspecialchars($field) ?>:
                                </span>
                                <span class="value">
                                    <?php
                                    $value = $item[$key] ?? '—';
                                    if (is_array($field) && isset($field['format'])) {
                                        switch ($field['format']) {
                                            case 'currency':
                                                echo '$' . number_format($value, 2);
                                                break;
                                            case 'percentage':
                                                echo number_format($value, 1) . '%';
                                                break;
                                            case 'number':
                                                echo number_format($value);
                                                break;
                                            case 'date':
                                                echo date('F j, Y', strtotime($value));
                                                break;
                                            case 'object':
                                                echo getNestedValue($value, $field['path']);
                                                break;
                                            default:
                                                echo htmlspecialchars($value);
                                        }
                                    } else {
                                        echo htmlspecialchars($value);
                                    }
                                    ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <div class="button-group">
                    <?php if (!empty($config['actions'])): ?>
                        <?php foreach ($config['actions'] as $action): ?>
                            <?php
                            // Parse the URL to get page and params
                            $url = parse_url($action['link']);
                            parse_str($url['query'] ?? '', $params);
                            $page = ltrim($params['page'] ?? '', '/');
                            // Remove page from params since it's passed separately
                            unset($params['page']);
                            $paramsJson = htmlspecialchars(json_encode($params), ENT_QUOTES, 'UTF-8');
                            ?>
                            <button type="button" class="<?= htmlspecialchars($action['class']) ?>"
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
    </div>
</body>

</html>