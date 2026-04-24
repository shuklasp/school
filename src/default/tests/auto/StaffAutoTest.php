<?php
namespace App\Default\Tests\Auto;

use App\Default\Entities\Staff;

/**
 * Auto-generated Test for Staff (Parikshak)
 * Generation Date: 2026-04-23 15:42:45
 */
class StaffAutoTest
{
    public static function run()
    {
        echo "Running evaluator for Staff... ";
        try {
            $entity = new Staff();
            $data = array (
  'id' => 133835,
  'name' => 'PARIKSHAK_FUZZ_cbb66',
  'department' => 'PARIKSHAK_FUZZ_881f7',
  'created_at' => '2026-04-23 15:42:45',
  'parent_id' => 'PARIKSHAK_FUZZ_5da4c',
);
            foreach ($data as $k => $v) $entity->set($k, $v);
            $id = $entity->save();
            if (!$id) throw new \Exception('Failed to save entity');
            echo "OK (ID: $id)\n";
        } catch (\Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }
}
