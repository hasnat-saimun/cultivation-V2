<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\sectionManage;
use App\Models\classManage;
use App\Models\sessionManage;
use App\Models\tuitionFee;
use App\Models\newAdmission;
use App\Models\feesManager;
use Carbon\Carbon;



class tuitionController extends Controller
{
    // tuition form page
    public function tuitionFee(){
        $sectionDetails= sectionManage::all();
        $classDetails= classManage::all();
        $sessionDetails = sessionManage::all();
        return view('account.tuition.tuitionFeesFrom',['sectionData'=>$sectionDetails,'classData'=>$classDetails,'sessionData'=>$sessionDetails,]);
    }

    // get tution admission student on form page
    public function getStudentForTutionFee($stdId){
        $studentList = newAdmission::where(['stdId'=>$stdId])->first();
        $feesDetails = feesManager::all();
        // return count($studentList);
        return view('account.tuition.getTutionStudentList',['studentData'=>$studentList,'feesData'=>$feesDetails]);
    }

    public function getStudentsForTutionFeeFilter(Request $requ){
        $classId = $requ->input('classId');
        $sessionId = $requ->input('sessionId');
        $sectionId = $requ->input('sectionId');
        $search = trim((string)$requ->input('search', ''));

        $query = newAdmission::query();
        if($classId){ $query->where('className', (int)$classId); }
        if($sessionId){ $query->where('sessName', (int)$sessionId); }
        if($sectionId){ $query->where('sectionName', (int)$sectionId); }
        if($search !== ''){
            $query->where(function($q) use ($search){
                $q->where('stdId','like',"%{$search}%")
                    ->orWhere('fullName','like',"%{$search}%")
                    ->orWhere('sureName','like',"%{$search}%")
                    ->orWhere('rollNumber','like',"%{$search}%");
            });
        }

        $students = $query->orderBy('className','ASC')
            ->orderBy('rollNumber','ASC')
            ->limit(100)
            ->get();

        return view('account.tuition.getTutionStudentFilterList', [
            'students' => $students,
        ]);
    }

    public function saveTuitionfee(Request $requ){
        // Support multi-row submission
        $validated = $requ->validate([
            'stdId' => 'required|string',
            'feesType' => 'required|array|min:1',
            'feesType.*' => 'distinct|required|integer|exists:fees_managers,id',
            'amount' => 'required|array|min:1',
            'amount.*' => 'required|numeric|min:0',
        ]);

        $stdId = $validated['stdId'];
        $types = $validated['feesType'];
        $amts  = $validated['amount'];
        $inserted = 0; $failed = 0; $duplicates = [];
        foreach($types as $idx => $t){
            // Monthly duplicate check: same student, same fee type, same calendar month
            $alreadyExists = tuitionFee::where('stdId',$stdId)
                ->where('feesType',$t)
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->exists();
            if($alreadyExists){
                $feeName = feesManager::find($t)->feesName ?? ('ID '.$t);
                $duplicates[] = $feeName;
                continue; // skip insert
            }
            $fee = new tuitionFee();
            $fee->stdId = $stdId;
            $fee->feesType = $t;
            $fee->amount = $amts[$idx] ?? 0;
            if($fee->save()) $inserted++; else $failed++;
        }

        if($inserted>0 && $failed===0 && empty($duplicates)){
            return back()->with('success',"$inserted fee item(s) saved successfully");
        }

        $messages = [];
        if($inserted>0){ $messages[] = "$inserted saved"; }
        if($failed>0){ $messages[] = "$failed failed"; }
        if(!empty($duplicates)){
            $messages[] = "Skipped (duplicate this month): ".implode(', ', $duplicates);
        }
        $flash = implode(' | ', $messages);
        if($inserted>0){
            return back()->with('warning', $flash); // show as warning if partial success
        }
        return back()->with('error', $flash ?: 'Failed to save fee items');
    }
    
    // tution Fee List
    public function tuitionFeeList(){
        $tutionfeeData = tuitionFee::all();
        return view('account.tuition.tuitionFeesList',['tfd'=>$tutionfeeData]);
    }

    public function tuitionFeeView($id){
        $singleData = tuitionFee::find($id);
        return view('account.tuition.viewTutionFee',['singleView'=>$singleData]);
        // $stdData = newAdmission::where(['stdId'=>$singleData->stdId])->first();
        // return view('tuition.viewTutionFee',['singleView'=>$singleData,'stdData'=>$stdData]);
    }

    //edit tiutionfee
    public function editTuitionFee($id){
        $tuitionFeeData = tuitionFee::find($id);
        if(!$tuitionFeeData){
            return back()->with('error','Sorry! Tuition fee not found');
        }
        $feesDetails = feesManager::all();
        return view('account.tuition.editTuitionFee',[
            'editData'=>$tuitionFeeData,
            'feesData'=>$feesDetails,
        ]);
    }

    //update tiutionfee 
    public function updateTuitionFee(Request $requ){
        $id = $requ->input('tuitionFeeId');
        $updateData = tuitionFee::find($id);
        if(!$updateData){
            return back()->with('error','Tuition fee record not found');
        }

        // validate only fields that exist on tuitionFee
        $validated = $requ->validate([
            'stdId'    => 'required|string',
            'feesType' => 'required|integer|exists:fees_managers,id',
            'amount'   => 'required|numeric|min:0',
        ]);

        // Monthly duplicate prevention on update: if changing fee type to another that already exists same month
        $newType = $validated['feesType'];
        $sameMonthDup = tuitionFee::where('stdId', $validated['stdId'])
            ->where('feesType', $newType)
            ->whereMonth('created_at', $updateData->created_at->month)
            ->whereYear('created_at', $updateData->created_at->year)
            ->where('id','!=',$updateData->id)
            ->exists();
        if($sameMonthDup){
            $feeName = feesManager::find($newType)->feesName ?? 'selected fee type';
            return back()->with('warning',"Cannot change: a '$feeName' entry already exists for this student in that month.");
        }

        $updateData->stdId    = $validated['stdId'];
        $updateData->feesType = $newType;
        $updateData->amount   = $validated['amount'];

        if($updateData->save()){
            return redirect(route('tuitionFeeList'))->with('success','Update successfully');
        }
        return back()->with('error','Data update failed');
    }

    //delelte 
    public function dltTuitionFee($id){
        $dltData = tuitionFee::find($id);

        if($dltData->delete()):
            return back()->with('success','data entry successfully');
        else:
            return back()->with('error','data deletion failed');
        endif;
    
     }

     // bulk delete
     public function bulkDeleteTuitionFees(Request $requ){
        $ids = $requ->input('feeIds', []);
        if(empty($ids) || !is_array($ids)){
            return back()->with('error','No items selected');
        }
        $deleted = tuitionFee::whereIn('id', $ids)->delete();
        if($deleted>0){
            return back()->with('success', $deleted.' record(s) deleted');
        }
        return back()->with('error','Failed to delete selected records');
     }

     //report
     
    public function tuitionReport($id){
        $singleData = tuitionFee::find($id);
        return view('account.tuition.report',['singleView'=>$singleData]);
    }

    public function feesReport(){
        return view('account.tuition.feesReport');
    }

    public function getFeesReport(Request $requ){
        $reportType = $requ->input('reportType','range');

        // Basic validation for stdId
        $requ->validate([
            'stdId' => 'required'
        ]);

        $feesQuery = tuitionFee::where('stdId', $requ->stdId);

        if($reportType === 'single'){
            $validated = $requ->validate([
                'singleDate' => 'required|date'
            ]);
            $date = Carbon::parse($validated['singleDate'])->toDateString();
            $feesQuery = $feesQuery->whereDate('created_at', $date);
        } else { // range (default)
            $validated = $requ->validate([
                'fromDate' => 'required|date',
                'toDate' => 'required|date|after_or_equal:fromDate'
            ]);
            $from = Carbon::parse($validated['fromDate'])->toDateString();
            $to   = Carbon::parse($validated['toDate'])->toDateString();
            $feesQuery = $feesQuery->whereBetween('created_at', [$from." 00:00:00", $to." 23:59:59"]);
        }

        // Handle Multiple Dates separately
        if($reportType === 'multiple'){
            $validated = $requ->validate([
                'dates' => 'required|array|min:1',
                'dates.*' => 'required|date'
            ]);
            $dates = array_values(array_map(function($d){
                return Carbon::parse($d)->toDateString();
            }, $validated['dates']));

            $feesQuery = tuitionFee::where('stdId', $requ->stdId)
                ->where(function($q) use ($dates){
                    foreach($dates as $d){ $q->orWhereDate('created_at', $d); }
                });
            $feesList = $feesQuery->get();
            $student = newAdmission::where(['stdId'=>$requ->stdId])->first();
            if($feesList->isNotEmpty()){
                return view('account.tuition.generateStudentReport',[
                    'feesList'=>$feesList,
                    'student'=>$student,
                    'reportType' => 'multiple',
                    'selectedDates' => $dates,
                ]);
            }
            return back()->with('error','Sorry! No data found with your query');
        }

        $feesList = $feesQuery->get();
        $student = newAdmission::where(['stdId'=>$requ->stdId])->first();

        if($feesList->isNotEmpty()){
            $viewData = [
                'feesList'=>$feesList,
                'student'=>$student,
                'reportType'=>$reportType,
            ];
            if($reportType === 'single'){
                $viewData['singleDate'] = $date;
            } else {
                $viewData['fromDate'] = $from;
                $viewData['toDate'] = $to;
            }
            return view('account.tuition.generateStudentReport', $viewData);
        }
        return back()->with('error','Sorry! No data found with your query');
    }

}
