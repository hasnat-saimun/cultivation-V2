<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TransferCertificate as TC;
use App\Models\newAdmission;
use App\Models\ServerConfig;
use App\Models\classManage;

class TransferCertificateController extends Controller
{
    private function generateRefNo(TC $tc): string {
        return 'TC-' . date('Y') . '-' . str_pad((string)$tc->id, 5, '0', STR_PAD_LEFT);
    }

    public function create($admissionId){
        $admission = newAdmission::findOrFail($admissionId);
        return view('tc.create', compact('admission'));
    }

    public function store(Request $request){
        $data = $request->validate([
            'admission_id'   => 'required|exists:new_admissions,id',
            'issue_date'     => 'nullable|date',
            'ref_no'         => 'nullable|string',
            'leaving_class'  => 'nullable|string',
            'leaving_date'   => 'nullable|date',
            'reason'         => 'nullable|string',
            'conduct'        => 'nullable|string',
            'character'      => 'nullable|string',
            'remarks'        => 'nullable|string',
            'composed_by'    => 'nullable|string',
            'composed_date'  => 'nullable|date',
            'headmaster_name'=> 'nullable|string',
        ]);
        $admission = newAdmission::findOrFail($data['admission_id']);
        $personal = [
            'student_name' => $admission->fullName ?? $admission->student_name ?? $admission->studentName ?? null,
            'father_name'  => $admission->father ?? $admission->father_name ?? null,
            'mother_name'  => $admission->mother ?? $admission->mother_name ?? null,
            'address'      => $admission->address ?? null,
            'class_name'   => optional(classManage::find($admission->className ?? null))->className,
            'session'      => $admission->session ?? $admission->sessionId ?? null,
            'roll_no'      => $admission->rollNumber ?? $admission->roll ?? null,
            'reg_no'       => $admission->stdId ?? $admission->registration_no ?? null,
            'dob'          => $admission->dob ?? null,
        ];
        $tc = TC::create(array_merge($data, $personal));
        if(empty($tc->ref_no)){
            $tc->ref_no = $this->generateRefNo($tc);
            $tc->save();
        }
        return redirect()->route('tc.print', $tc->id)->with('success','Transfer Certificate created');
    }

    public function show($id){
        $tc = TC::findOrFail($id);
        $config = ServerConfig::first();
        $headmasterName = $config->headmasterName ?? $config->principalName ?? $config->principal ?? $tc->headmaster_name ?? '';
        return view('tc.certificate', [
            'tc' => $tc,
            'admission' => $tc->admission,
            'config' => $config,
            'principalSign' => $config->principalSign ?? null,
            'headmasterName' => $headmasterName,
        ]);
    }

    public function print($id){
        $tc = TC::findOrFail($id);
        $config = ServerConfig::first();
        $headmasterName = $config->headmasterName ?? $config->principalName ?? $config->principal ?? $tc->headmaster_name ?? '';
        return view('tc.certificate', [
            'tc' => $tc,
            'admission' => $tc->admission,
            'config' => $config,
            'headmasterName' => $headmasterName,
            'autoPrint' => true,
        ]);
    }

    public function edit($id){
        $tc = TC::findOrFail($id);
        $admission = $tc->admission;
        return view('tc.create', compact('admission','tc'));
    }

    public function update(Request $request){
        $data = $request->validate([
            'id'            => 'required|exists:transfer_certificates,id',
            'issue_date'    => 'nullable|date',
            'ref_no'        => 'nullable|string',
            'leaving_class' => 'nullable|string',
            'leaving_date'  => 'nullable|date',
            'reason'        => 'nullable|string',
            'conduct'       => 'nullable|string',
            'character'     => 'nullable|string',
            'remarks'       => 'nullable|string',
            'composed_by'   => 'nullable|string',
            'composed_date' => 'nullable|date',
        ]);
        $tc = TC::findOrFail($data['id']);
        $tc->update(collect($data)->except('id')->toArray());
        if(empty($tc->ref_no)){
            $tc->ref_no = $this->generateRefNo($tc);
            $tc->save();
        }
        return redirect()->route('tc.show', $tc->id)->with('success','Transfer Certificate updated');
    }
}
