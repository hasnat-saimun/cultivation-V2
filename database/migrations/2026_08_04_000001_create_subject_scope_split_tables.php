<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('subject_class_scopes', function(Blueprint $t){$t->id();$t->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();$t->unsignedBigInteger('class_id')->nullable();$t->timestamps();$t->unique(['subject_id','class_id'],'subject_class_scope_unique');$t->index('class_id');});
  Schema::create('subject_scope_migration_audits', function(Blueprint $t){$t->id();$t->uuid('operation_uuid')->unique();$t->unsignedBigInteger('source_subject_id');$t->unsignedBigInteger('destination_subject_id');$t->json('remain_class_ids');$t->json('migrate_class_ids');$t->json('discovered_references');$t->json('affected_counts');$t->string('actor')->nullable();$t->timestamp('applied_at');$t->timestamps();});
 }
 public function down(): void {Schema::dropIfExists('subject_scope_migration_audits');Schema::dropIfExists('subject_class_scopes');}
};
