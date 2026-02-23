<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_mappings', function (Blueprint $table) {
            if (!Schema::hasColumn('category_mappings', 'nivel1')) {
                $table->string('nivel1', 50)->nullable()->after('id');
            }
            if (!Schema::hasColumn('category_mappings', 'nivel2')) {
                $table->string('nivel2', 50)->nullable()->after('nivel1');
            }
            if (!Schema::hasColumn('category_mappings', 'nivel3')) {
                $table->string('nivel3', 50)->nullable()->after('nivel2');
            }
            if (!Schema::hasColumn('category_mappings', 'nivel4')) {
                $table->string('nivel4', 50)->nullable()->after('nivel3');
            }
            if (!Schema::hasColumn('category_mappings', 'nivel5')) {
                $table->string('nivel5', 50)->nullable()->after('nivel4');
            }
            if (!Schema::hasColumn('category_mappings', 'icg_key')) {
                $table->string('icg_key', 191)->nullable()->after('nivel5');
            }
            if (!Schema::hasColumn('category_mappings', 'magento_category_path')) {
                $table->string('magento_category_path')->nullable()->after('magento_category_name');
            }
            if (!Schema::hasColumn('category_mappings', 'category_level')) {
                $table->integer('category_level')->default(1)->after('magento_category_path');
            }
        });
        
        if (!$this->indexExists('category_mappings', 'category_mappings_icg_key_index')) {
            Schema::table('category_mappings', function (Blueprint $table) {
                $table->index('icg_key');
            });
        }
        
        if (!$this->indexExists('category_mappings', 'category_mappings_category_level_index')) {
            Schema::table('category_mappings', function (Blueprint $table) {
                $table->index('category_level');
            });
        }
        
        if (!$this->indexExists('category_mappings', 'category_mappings_nivel1_index')) {
            Schema::table('category_mappings', function (Blueprint $table) {
                $table->index('nivel1');
            });
        }
        
        Schema::table('category_mappings', function (Blueprint $table) {
            if (Schema::hasColumn('category_mappings', 'icg_category_key')) {
                $table->dropColumn('icg_category_key');
            }
            if (Schema::hasColumn('category_mappings', 'icg_category_type')) {
                $table->dropColumn('icg_category_type');
            }
        });
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS category_mappings_icg_key_index ON category_mappings');
        DB::statement('DROP INDEX IF EXISTS category_mappings_category_level_index ON category_mappings');
        DB::statement('DROP INDEX IF EXISTS category_mappings_nivel1_index ON category_mappings');
        
        Schema::table('category_mappings', function (Blueprint $table) {
            $columns = ['nivel1', 'nivel2', 'nivel3', 'nivel4', 'nivel5', 'icg_key', 'magento_category_path', 'category_level'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('category_mappings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function indexExists($table, $indexName)
    {
        $indexes = DB::select("SELECT indexname FROM pg_indexes WHERE tablename = '{$table}' AND indexname = ?", [$indexName]);
        return !empty($indexes);
    }
};
