<?php
require_once __DIR__ . '/api_client.php';
// require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class GenericProcessor
{
    private $entityConfig;

    public function __construct($entityConfig)
    {
        $this->entityConfig = $entityConfig;
    }

    /**
     * Process manual form submission
     * @param array $formData
     * @return array
     */
    public function processManualSubmission($formData)
    {
        try {
            // Validate form data
            $this->validateFormData($formData);

            // Prepare data using field mappings
            $preparedData = $this->prepareData($formData);

            // Send to API
            $response = apiRequest($this->entityConfig['apiEndpoint'], 'POST', $preparedData);
            return [
                'success' => true,
                'message' => $this->entityConfig['successMessage'],
                'data' => $response
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Process Excel/CSV file upload
     * @param array $file $_FILES array element
     * @return array
     */
   

    /**
     * Prepare data using field mappings
     * @param array $data
     * @return array
     */
    private function prepareData($data)
    {
        $prepared = [];
        foreach ($this->entityConfig['fields'] as $field => $config) {
            $value = isset($data[$config['fileField']]) ? $data[$config['fileField']] :
                (isset($data[$field]) ? $data[$field] : null);

            if (isset($config['transform'])) {
                switch ($config['transform']) {
                    case 'int':
                        $value = intval($value);
                        break;
                    case 'float':
                        $value = floatval($value);
                        break;
                    case 'string':
                        $value = (string) $value;
                        break;
                }
            }

            $prepared[$field] = $value;
        }
        return $prepared;
    }

    /**
     * Validate data
     * @param array $data
     * @throws Exception
     */
    private function validateFormData($data)
    {
        foreach ($this->entityConfig['fields'] as $field => $config) {
            // Check required fields
            if (
                !empty($config['required']) &&
                (!isset($data[$field]) || (empty($data[$field]) && $data[$field] !== '0'))
            ) {
                throw new Exception("Missing required field: {$field}");
            }

            if (isset($data[$field])) {
                // Validate by type
                if (isset($config['validate'])) {
                    foreach ($config['validate'] as $rule => $param) {
                        switch ($rule) {
                            case 'numeric':
                                if (!is_numeric($data[$field])) {
                                    throw new Exception("Field {$field} must be numeric");
                                }
                                break;
                            case 'min':
                                if ($data[$field] < $param) {
                                    throw new Exception("Field {$field} must be greater than {$param}");
                                }
                                break;
                            case 'enum':
                                if (!in_array($data[$field], $param)) {
                                    throw new Exception("Field {$field} must be one of: " . implode(', ', $param));
                                }
                                break;
                            case 'date':
                                if (!date_create($data[$field])) {
                                    throw new Exception("Field {$field} must be a valid date");
                                }
                                break;
                            case 'email':
                                if (!filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                                    throw new Exception("Field {$field} must be a valid email");
                                }
                                break;
                        }
                    }
                }
            }
        }
    }
}
?>