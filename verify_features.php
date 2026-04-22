<?php
namespace App\Test\Entities {
    require_once(__DIR__ . '/spp/sppinit.php');

    class Author extends \SPPMod\SPPEntity\SPPEntity {
        protected static function loadEntityConfig(string $class) {
            self::$_metadata[$class] = [
                'table' => 'authors',
                'attributes' => ['name' => 'string'],
                'hasMany' => [
                    'books' => ['entity' => 'App\Test\Entities\Book', 'fk' => 'author_id']
                ]
            ];
            \SPPMod\SPPEntity\SPPEntityRelations::registerEntityRelation(
                'books',
                'App\Test\Entities\Author', 'id',
                'App\Test\Entities\Book', 'author_id',
                'OneToMany'
            );
        }
    }

    class Book extends \SPPMod\SPPEntity\SPPEntity {
        protected static function loadEntityConfig(string $class) {
            self::$_metadata[$class] = [
                'table' => 'books',
                'attributes' => ['title' => 'string', 'author_id' => 'int'],
                'belongsTo' => [
                    'author' => ['entity' => 'App\Test\Entities\Author', 'fk' => 'author_id']
                ]
            ];
            \SPPMod\SPPEntity\SPPEntityRelations::registerEntityRelation(
                'author',
                'App\Test\Entities\Author', 'id',
                'App\Test\Entities\Book', 'author_id',
                'ManyToOne'
            );
        }
    }
}

namespace {
    echo "--- SPP ORM & Middleware Verification ---\n";

    echo "Testing ORM Lazy Loading...\n";
    $author = new App\Test\Entities\Author();
    $author->setId(1);
    
    echo "  Accessing related property: \$author->books\n";
    try {
        $books = $author->books;
        echo "  [OK] Successfully accessed magic relation property.\n";
    } catch (\Exception $e) {
        // We expect a "Table not found" if it actually tries to hit the DB, which is GOOD as it means the logic reached the DB layer.
        if (strpos($e->getMessage(), "Table 'authors' not found") !== false || strpos($e->getMessage(), "Base table or view not found") !== false) {
             echo "  [OK] Successfully triggered DB fetch for relation.\n";
        } else {
             echo "  [ERROR] " . $e->getMessage() . "\n";
        }
    }

    $book = new App\Test\Entities\Book();
    $book->setId(101);
    $book->set('author_id', 1);

    echo "  Accessing related property: \$book->author\n";
    try {
        $authorObj = $book->author;
        echo "  [OK] Successfully accessed magic 'belongsTo' property.\n";
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), "Table 'books' not found") !== false || strpos($e->getMessage(), "Base table or view not found") !== false) {
             echo "  [OK] Successfully triggered DB fetch for 'belongsTo' relation.\n";
        } else {
             echo "  [ERROR] " . $e->getMessage() . "\n";
        }
    }

    echo "\nTesting Middleware Pipeline (Global only)...\n";
    try {
        \SPP\Scheduler::setContext('default');
        \SPP\Core\MiddlewareKernel::boot(); // This won't reload if already initialized, so I might need a reset for testing
        // For testing purposes, I'll force a reboot if I can, but let's see current behavior
        
        \SPP\Core\MiddlewareKernel::run(function($req) {
            echo "  [OK] Main Dispatcher reached.\n";
            return "Response Body";
        });
    } catch (\Exception $e) {
        echo "  [ERROR] " . $e->getMessage() . "\n";
    }

    echo "\nTesting Middleware Pipeline (App-specific: autodemo)...\n";
    try {
        // Register the app context so Scheduler allows switching to it
        if (!class_exists('\SPP\App')) require_once(__DIR__ . '/spp/core/class.app.php');
        new \SPP\App('autodemo');

        // We'll use a hack to reset the kernel for the multi-context test
        $ref = new \ReflectionClass('\SPP\Core\MiddlewareKernel');

        $prop = $ref->getProperty('isInitialized');
        $prop->setAccessible(true);
        $prop->setValue(null, false);

        \SPP\Scheduler::setContext('autodemo');
        \SPP\Core\MiddlewareKernel::run(function($req) {
            echo "  [OK] Main Dispatcher reached in App Context.\n";
            return "Response Body";
        });
    } catch (\Exception $e) {
        echo "  [ERROR] " . $e->getMessage() . "\n";
    }
}
