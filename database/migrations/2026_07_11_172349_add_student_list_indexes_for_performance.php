<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('new_admissions')) {
            if ($this->columnsExist('new_admissions', ['stdId']) && !$this->indexExists('new_admissions', 'na_stdid_idx')) {
                Schema::table('new_admissions', function (Blueprint $table) {
                    $table->index(['stdId'], 'na_stdid_idx');
                });
            }

            if ($this->columnsExist('new_admissions', ['sessName', 'className', 'sectionName', 'departmentName']) && !$this->indexExists('new_admissions', 'na_scsd_idx')) {
                DB::statement('CREATE INDEX `na_scsd_idx` ON `new_admissions` (`sessName`(128), `className`(128), `sectionName`(128), `departmentName`(128))');
            }

            if ($this->columnsExist('new_admissions', ['sessName', 'className', 'sectionName', 'rollNumber']) && !$this->indexExists('new_admissions', 'na_scsr_idx')) {
                DB::statement('CREATE INDEX `na_scsr_idx` ON `new_admissions` (`sessName`(128), `className`(128), `sectionName`(128), `rollNumber`(128))');
            }
        }

        if (Schema::hasTable('testimonials')) {
            if ($this->columnsExist('testimonials', ['admission_id', 'id']) && !$this->indexExists('testimonials', 't_admid_id_idx')) {
                Schema::table('testimonials', function (Blueprint $table) {
                    $table->index(['admission_id', 'id'], 't_admid_id_idx');
                });
            }
        }

        if (Schema::hasTable('transfer_certificates')) {
            if ($this->columnsExist('transfer_certificates', ['admission_id', 'id']) && !$this->indexExists('transfer_certificates', 'tc_admid_id_idx')) {
                Schema::table('transfer_certificates', function (Blueprint $table) {
                    $table->index(['admission_id', 'id'], 'tc_admid_id_idx');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('new_admissions')) {
            if ($this->indexExists('new_admissions', 'na_stdid_idx')) {
                Schema::table('new_admissions', function (Blueprint $table) {
                    $table->dropIndex('na_stdid_idx');
                });
            }

            if ($this->indexExists('new_admissions', 'na_scsd_idx')) {
                Schema::table('new_admissions', function (Blueprint $table) {
                    $table->dropIndex('na_scsd_idx');
                });
            }

            if ($this->indexExists('new_admissions', 'na_scsr_idx')) {
                Schema::table('new_admissions', function (Blueprint $table) {
                    $table->dropIndex('na_scsr_idx');
                });
            }
        }

        if (Schema::hasTable('testimonials') && $this->indexExists('testimonials', 't_admid_id_idx')) {
            Schema::table('testimonials', function (Blueprint $table) {
                $table->dropIndex('t_admid_id_idx');
            });
        }

        if (Schema::hasTable('transfer_certificates') && $this->indexExists('transfer_certificates', 'tc_admid_id_idx')) {
            Schema::table('transfer_certificates', function (Blueprint $table) {
                $table->dropIndex('tc_admid_id_idx');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $rows = DB::select('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$indexName]);
        return !empty($rows);
    }

    private function columnsExist(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return false;
            }
        }
        return true;
    }
};
