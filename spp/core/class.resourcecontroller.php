<?php
namespace SPP\Core;

use SPPMod\SPPEntity\SPPEntity;
use SPP\Exceptions\EntityNotFoundException;

/**
 * Class ResourceController
 * Provides a base for RESTful resource management in SPP.
 */
abstract class ResourceController
{
    protected string $entityClass;

    public function __construct()
    {
        if (empty($this->entityClass)) {
            // Try to guess from controller name (e.g. UserController -> User)
            $className = (new \ReflectionClass($this))->getShortName();
            $entityName = str_replace('Controller', '', $className);
            $this->entityClass = "\\App\\Default\\Entities\\$entityName";
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index($args)
    {
        $entities = ($this->entityClass)::find_all();
        return [
            'view' => 'index',
            'data' => [
                'items' => $entities,
                'entityName' => basename(str_replace('\\', '/', $this->entityClass))
            ]
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store($args)
    {
        $data = $_POST;
        $entity = new $this->entityClass();
        $entity->setValues($data);
        $id = $entity->save();
        
        return [
            'success' => true,
            'id' => $id,
            'message' => 'Resource created successfully.'
        ];
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $entity = new $this->entityClass($id);
        return [
            'view' => 'show',
            'data' => ['item' => $entity]
        ];
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($id, $args)
    {
        $data = $_POST;
        $entity = new $this->entityClass($id);
        $entity->setValues($data);
        $entity->save();

        return [
            'success' => true,
            'message' => 'Resource updated successfully.'
        ];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $db = new \SPPMod\SPPDB\SPPDB();
        $entity = new $this->entityClass($id);
        $table = $entity->getTable();
        $sql = "DELETE FROM %tab% WHERE " . $entity->getMetadata('id_field') . " = ?";
        $db->exec_squery($sql, $table, [$id]);

        return [
            'success' => true,
            'message' => 'Resource deleted successfully.'
        ];
    }
}
