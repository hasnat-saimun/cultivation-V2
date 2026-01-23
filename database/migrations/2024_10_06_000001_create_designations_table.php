<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->index();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->nullable();
            $table->timestamps();
            $table->unique(['name', 'type']);
        });

        Schema::table('teacher_management', function (Blueprint $table) {
            $table->unsignedBigInteger('designation_id')->nullable()->after('designation');
            $table->foreign('designation_id')->references('id')->on('designations')->nullOnDelete();
        });

        Schema::table('staff_management', function (Blueprint $table) {
            $table->unsignedBigInteger('designation_id')->nullable()->after('designation');
            $table->foreign('designation_id')->references('id')->on('designations')->nullOnDelete();
        });

        Schema::table('managing_comittees', function (Blueprint $table) {
            $table->unsignedBigInteger('designation_id')->nullable()->after('designation');
            $table->foreign('designation_id')->references('id')->on('designations')->nullOnDelete();
        });

        $now = now();

        $teacherDesignations = [
            'Principal',
            'Principal(Incharge)',
            'Vice Principal',
            'Head Master',
            'Head Master(Incharge)',
            'Assistant Head Master',
            'Senior Teacher',
            'Assistant Teacher',
            'Muallim',
            'Assistant Muallim',
            'Lecturer (Fazil/Kamil)',
            'Hafiz & Hafezia Instructor',
            'Arabic Teacher',
            'Quran Teacher',
            'Hadith Teacher',
        ];

        $staffDesignations = [
            'Administrative Officer',
            'Office Assistant-cum-Computer Operator',
            'Accounts Assistant',
            'Office Assistant',
            'Registrar',
            'Librarian',
            'Assistant Librarian',
            'IT Officer / System Admin / ICT Technician',
            'Data Entry Operator',
            'Lab Assistant / Lab Attendant',
            'Sports Instructor / Coach',
            'Music Teacher / Art Teacher',
            'Hostel Superintendent / Hostel Warden',
            'Office Peon / Office Assistant',
            'MLSS',
            'Security Guard',
            'Gatekeeper',
            'Gardener',
            'Cleaner / Ayah',
            'Driver',
        ];

        $committeeDesignations = [
            'President',
            'Chairman',
            'Vice President',
            'Vice Chairman',
            'President Trust',
            'Acting Principal',
            'General Secretary',
            'Member Secretary',
            'Treasurer',
            'Parent Member',
            'Teacher Member',
            'General Member',
            'Member Educationiost',
            'Legal Advisor',
            'Trustee',
        ];

        $rows = [];
        foreach ($teacherDesignations as $i => $name) {
            $rows[] = [
                'name' => $name,
                'type' => 'teacher',
                'sort_order' => $i + 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach ($staffDesignations as $i => $name) {
            $rows[] = [
                'name' => $name,
                'type' => 'staff',
                'sort_order' => $i + 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach ($committeeDesignations as $i => $name) {
            $rows[] = [
                'name' => $name,
                'type' => 'committee',
                'sort_order' => $i + 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('designations')->insert($rows);
    }

    public function down(): void
    {
        Schema::table('teacher_management', function (Blueprint $table) {
            $table->dropForeign(['designation_id']);
            $table->dropColumn('designation_id');
        });

        Schema::table('staff_management', function (Blueprint $table) {
            $table->dropForeign(['designation_id']);
            $table->dropColumn('designation_id');
        });

        Schema::table('managing_comittees', function (Blueprint $table) {
            $table->dropForeign(['designation_id']);
            $table->dropColumn('designation_id');
        });

        Schema::dropIfExists('designations');
    }
};
