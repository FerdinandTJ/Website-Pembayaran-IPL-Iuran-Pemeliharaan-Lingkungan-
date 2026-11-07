<?php

// CLI-only schema inspection. Never use the application's configured database.
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (PHP_SAPI !== 'cli' || empty($argv[1])) {
    fwrite(STDERR, "Usage: php docs/tools/export_schema.php /tmp/enviropay-schema.json\n");
    exit(1);
}

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config([
    'database.default' => 'documentation_audit',
    'database.connections.documentation_audit' => [
        'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
    ],
    'cache.default' => 'array', 'session.driver' => 'array', 'services.fonnte.enabled' => false,
]);
DB::purge('documentation_audit');
if (DB::connection()->getName() !== 'documentation_audit' || DB::connection()->getDatabaseName() !== ':memory:') {
    throw new RuntimeException('Refusing to inspect a non-isolated database.');
}
if (Artisan::call('migrate', ['--database' => 'documentation_audit', '--force' => true]) !== 0) {
    throw new RuntimeException('Isolated migration failed.');
}

$schema = [];
foreach (Schema::getTableListing() as $table) {
    $name = str_starts_with($table, 'main.') ? substr($table, 5) : $table;
    $schema[$name] = [
        'columns' => array_values(Schema::getColumns($table)),
        'indexes' => array_values(Schema::getIndexes($table)),
        'foreign_keys' => array_values(Schema::getForeignKeys($table)),
    ];
}
if (file_put_contents($argv[1], json_encode($schema, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n") === false) {
    throw new RuntimeException('Could not write schema output.');
}
echo count($schema)." tables exported from isolated SQLite memory; no application data exported.\n";
