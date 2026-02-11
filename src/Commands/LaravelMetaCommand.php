<?php

namespace Zynfly\LaravelMeta\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class LaravelMetaCommand extends Command
{
    public $signature = 'make:meta-migration {table : The parent table name (e.g. posts)} {--foreign-key= : Custom foreign key column name}';

    public $description = 'Generate a migration file that creates a meta table for the given table.';

    public function handle(): int
    {
        $table = $this->argument('table');
        $foreignKey = $this->option('foreign-key') ?? Str::singular($table) . '_id';

        $migrationName = 'create_' . $table . '_meta_table';
        $fileName = date('Y_m_d_His') . '_' . $migrationName . '.php';

        $stub = $this->buildStub($table, $foreignKey);

        $path = database_path('migrations/' . $fileName);
        file_put_contents($path, $stub);

        $this->info("Migration [{$path}] created successfully.");
        $this->info("Run `php artisan migrate` to create the {$table}_meta table.");

        return self::SUCCESS;
    }

    protected function buildStub(string $table, string $foreignKey): string
    {
        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$table}_meta', function (Blueprint \$table) {
            \$table->id();
            \$table->unsignedBigInteger('{$foreignKey}')->index();
            \$table->string('key');
            \$table->text('value')->nullable();
            \$table->timestamps();

            \$table->index(['{$foreignKey}', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$table}_meta');
    }
};

PHP;
    }
}
