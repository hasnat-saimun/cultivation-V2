<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('curriculum_subject_mappings')) {
            Schema::create('curriculum_subject_mappings', function (Blueprint $table): void {
                $table->id();
                $table->string('session_id', 64);
                $table->string('class_id', 64);
                $table->string('section_id', 64)->nullable();
                $table->string('department_id', 64)->nullable();
                $table->unsignedBigInteger('subject_id');
                $table->string('mapping_type', 24)->default('main');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->string('source', 24)->default('manual');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->string('normalized_section_scope', 72)->nullable();
                $table->string('normalized_department_scope', 72)->nullable();
                $table->timestamps();

                $table->unique(
                    ['session_id', 'class_id', 'normalized_section_scope', 'normalized_department_scope', 'subject_id'],
                    'curriculum_scope_subject_uq'
                );
                $table->index(['session_id', 'class_id'], 'curriculum_scope_session_class_idx');
                $table->index('subject_id', 'curriculum_subject_idx');
                $table->index('source', 'curriculum_source_idx');
            });
            return;
        }

        Schema::table('curriculum_subject_mappings', function (Blueprint $table): void {
            if (!Schema::hasColumn('curriculum_subject_mappings', 'mapping_type')) {
                $table->string('mapping_type', 24)->default('main')->after('subject_id');
            }
            if (!Schema::hasColumn('curriculum_subject_mappings', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('sort_order');
            }
            if (!Schema::hasColumn('curriculum_subject_mappings', 'normalized_section_scope')) {
                $table->string('normalized_section_scope', 72)->nullable()->after('updated_by');
            }
            if (!Schema::hasColumn('curriculum_subject_mappings', 'normalized_department_scope')) {
                $table->string('normalized_department_scope', 72)->nullable()->after('normalized_section_scope');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('curriculum_subject_mappings')) {
            return;
        }

        Schema::table('curriculum_subject_mappings', function (Blueprint $table): void {
            if (Schema::hasColumn('curriculum_subject_mappings', 'mapping_type')) {
                $table->dropColumn('mapping_type');
            }
            if (Schema::hasColumn('curriculum_subject_mappings', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
