<?php
namespace App\Default\Tests\Auto;

use App\Default\Entities\TestEntity;

/**
 * Auto-generated Test for TestEntity (Parikshak)
 * Generation Date: 2026-04-23 15:42:46
 */
class TestEntityAutoTest
{
    public static function run()
    {
        echo "Running evaluator for TestEntity... ";
        try {
            $entity = new TestEntity();
            $data = array (
  'id' => 702826,
  'username' => 'PARIKSHAK_FUZZ_363d2',
  'email' => 'PARIKSHAK_FUZZ_dd04d',
  'password_hash' => 'PARIKSHAK_FUZZ_b5c22',
  'password' => 'PARIKSHAK_FUZZ_63602',
  'role_id' => 219792,
  'status' => 'PARIKSHAK_FUZZ_2d52d',
  'created_at' => '2026-04-23 15:42:46',
  'updated_at' => '2026-04-23 15:42:46',
  'name' => 'PARIKSHAK_FUZZ_10ec9',
  'test1' => 684436,
  'dob' => '2026-04-23 15:42:46',
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
