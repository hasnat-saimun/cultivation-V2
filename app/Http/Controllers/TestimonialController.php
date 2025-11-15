<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Testimonial;
use App\Models\newAdmission;
use App\Models\ServerConfig;
use App\Models\classManage;

class TestimonialController extends Controller
{
    private function isEligibleForTestimonial($admission): bool {
        $classId = $admission->className ?? null;
        if (!$classId) return false;
        $class = classManage::find($classId);
        if (!$class) return false;
        $name = strtolower(trim((string)$class->className));
        return $name === 'ten' || $name === 'twelve' || $name === '10' || $name === '12'
            || strpos($name, 'ten') !== false || strpos($name, 'twelve') !== false;
    }
    private function generateRefNo(Testimonial $testimonial): string {
        return 'SL-' . date('Y') . '-' . str_pad((string)$testimonial->id, 5, '0', STR_PAD_LEFT);
    }
    public function create($admissionId) {
        $admission = newAdmission::findOrFail($admissionId);
        if (!$this->isEligibleForTestimonial($admission)) {
            return redirect()->route('studentList')->with('error', 'Testimonial is only allowed for Class Ten and Twelve students.');
        }
        return view('testimonials.create', compact('admission'));
    }
    public function store(Request $request) {
        $data = $request->validate([
            'admission_id' => 'required|exists:new_admissions,id',
            'ssc_year' => 'nullable',
            'roll_no' => 'nullable',
            'reg_no' => 'nullable',
            'gpa' => 'nullable',
            'grade' => 'nullable',
            'subject' => 'nullable',
            'education_board' => 'nullable|string',
            'exam_name' => 'nullable|string',
            'ref_no' => 'nullable',
            'issue_date' => 'nullable|date',
            'remarks' => 'nullable',
            'composed_by' => 'nullable',
            'composed_date' => 'nullable|date',
            'headmaster_name' => 'nullable',
        ]);
        $admission = newAdmission::findOrFail($data['admission_id']);
        if (!$this->isEligibleForTestimonial($admission)) {
            return redirect()->route('studentList')->with('error', 'Testimonial is only allowed for Class Ten and Twelve students.');
        }
        $personal = [
            'student_name' => $admission->fullName ?? $admission->sureName ?? $admission->student_name ?? $admission->studentName ?? null,
            'father_name' => $admission->fatherName ?? $admission->father_name ?? null,
            'mother_name' => $admission->motherName ?? $admission->mother_name ?? null,
            // admission has a single 'address' field; keep village/district blank or reuse later
            'village' => null,
            'district' => null,
            'dob' => $admission->dob ?? null,
        ];
        $testimonial = Testimonial::create(array_merge($data, $personal));
        if (empty($testimonial->ref_no)) {
            $testimonial->ref_no = $this->generateRefNo($testimonial);
            $testimonial->save();
        }
        return redirect()->route('testimonials.print', $testimonial->id)->with('success','Testimonial created');
    }
    public function show($id) {
        $testimonial = Testimonial::findOrFail($id);
        $config = ServerConfig::first();
        $headmasterName = $config->headmasterName ?? $config->principalName ?? $config->principal ?? $testimonial->headmaster_name ?? '';
        return view('testimonials.certificate', [
            'testimonial' => $testimonial,
            'admission' => $testimonial->admission,
            'logo' => $config->logo ?? null,
            'instituteName' => $config->instituteName ?? 'School Name',
            'address' => $config->address ?? '',
            'establishDate' => $config->establishDate ?? '',
            'headmasterName' => $headmasterName,
            'principalSign' => $config->principalSign ?? null,
            'email' => $config->email ?? $config->emailAddress ?? $config->instituteEmail ?? null,
            'mobile' => $config->phone ?? $config->mobile ?? $config->contact ?? $config->mobileNumber ?? $config->phoneNumber ?? null,
        ]);
    }
    public function print($id) {
        $testimonial = Testimonial::findOrFail($id);
        $config = ServerConfig::first();
        $headmasterName = $config->headmasterName ?? $config->principalName ?? $config->principal ?? $testimonial->headmaster_name ?? '';
        return view('testimonials.certificate', [
            'testimonial' => $testimonial,
            'admission' => $testimonial->admission,
            'logo' => $config->logo ?? null,
            'instituteName' => $config->instituteName ?? 'School Name',
            'address' => $config->address ?? '',
            'establishDate' => $config->establishDate ?? '',
            'headmasterName' => $headmasterName,
            'principalSign' => $config->principalSign ?? null,
            'email' => $config->email ?? $config->emailAddress ?? $config->instituteEmail ?? null,
            'mobile' => $config->phone ?? $config->mobile ?? $config->contact ?? $config->mobileNumber ?? $config->phoneNumber ?? null,
            'autoPrint' => true,
        ]);
    }

    public function edit($id) {
        $testimonial = Testimonial::findOrFail($id);
        $admission = $testimonial->admission; // Might be null if data missing
        return view('testimonials.create', compact('admission','testimonial'));
    }

    public function update(Request $request) {
        $data = $request->validate([
            'id' => 'required|exists:testimonials,id',
            'ssc_year' => 'nullable',
            'roll_no' => 'nullable',
            'reg_no' => 'nullable',
            'gpa' => 'nullable',
            'grade' => 'nullable',
            'subject' => 'nullable',
            'education_board' => 'nullable|string',
            'exam_name' => 'nullable|string',
            'ref_no' => 'nullable',
            'issue_date' => 'nullable|date',
            'remarks' => 'nullable',
            'composed_by' => 'nullable',
            'composed_date' => 'nullable|date',
            // headmaster/principal name comes from ServerConfig
        ]);
        $testimonial = Testimonial::findOrFail($data['id']);
        $testimonial->update(collect($data)->except('id')->toArray());
        if (empty($testimonial->ref_no)) {
            $testimonial->ref_no = $this->generateRefNo($testimonial);
            $testimonial->save();
        }
        return redirect()->route('testimonials.show', $testimonial->id)->with('success','Testimonial updated');
    }
}
