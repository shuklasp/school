<?php
namespace SPPMod\SPPAudit;

use SPPMod\SPPDB\SPPDB;
use SPP\SPPSession;

/**
 * Class SPPAudit
 * Handles auditing of entity changes across the SPP framework.
 */
class SPPAudit extends \SPP\SPPObject
{
    /**
     * Log an audit event.
     */
    public static function log(string $entityType, $entityId, string $action, ?array $oldValues = null, ?array $newValues = null)
    {
        try {
            $db = new SPPDB();
            $tableName = $db->sppTable('audit_logs');
            
            // Try to get user from session
            $userId = null;
            try {
                if (\SPP\SPPSession::sessionExists()) {
                    $userId = \SPP\SPPSession::getSessionVar('__user_id__');
                }
            } catch (\Exception $e) {}

            $data = [
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'action'      => $action,
                'old_values'  => $oldValues ? json_encode($oldValues) : null,
                'new_values'  => $newValues ? json_encode($newValues) : null,
                'user_id'     => $userId,
                'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'created_at'  => date('Y-m-d H:i:s')
            ];

            // In an enterprise environment, we might use a separate Audit connection
            // For now, we use the default connection
            $fields = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));
            
            $sql = "INSERT INTO {$tableName} ({$fields}) VALUES ({$placeholders})";
            $db->exec_squery($sql, $tableName, array_values($data));
            
        } catch (\Exception $e) {
            // Enterprise rule: Don't let an audit failure crash the main transaction
            // But link it to the system error log
            error_log("Audit Logging Failed: " . $e->getMessage());
        }
    }

    /**
     * Ensure the audit_logs table exists.
     */
    public static function install()
    {
        $db = new SPPDB();
        $tableName = $db->sppTable('audit_logs');
        
        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            entity_type VARCHAR(100) NOT NULL,
            entity_id VARCHAR(100) NOT NULL,
            action VARCHAR(20) NOT NULL,
            old_values TEXT,
            new_values TEXT,
            user_id INT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $db->exec_squery($sql, $tableName);
    }
}
