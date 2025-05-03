<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\cashManage;

class cashCalculasController extends Controller
{
    public function cashCalculasView(){
        return view('account.cashCalculas.cashCalculasPage');
    }
//cash data send to database
    public function saveCashCalculas(Request $requ){ 
        $cash = new cashManage();

        $cash->source       = $requ->source;
        $cash->amount       = $requ->amount;
        $cash->transaction  = $requ->transaction;
        $cash->date         = $requ->date;

        if($cash->save()):
            return back()->with('success','Data entry successfully');
        else:
            return back()->with('error','Data entry failed');
        endif;

    }
 //report list
    public function reportListView(){ 
        $calculasList = cashManage::all();
        return view('account.cashCalculas.reportList',['cashManageData'=> $calculasList]);
    }

    // report single page
    public function singleView($id){   
        $calculasData = cashManage::find($id);
        return view('account.cashCalculas.viewCashcalculas',['singleData' => $calculasData]);
    }

    //edit cash calculas
    public function editCashCalculas($id){
        $calculasData = cashManage::find($id);
        return view('account.cashCalculas.editCashcalculas',['editData' => $calculasData]);
    }

    //update cash caqlculas
     public function updateCashCalculas(Request $requ){
        $upData = cashManage::find($requ->calculasId);

        $upData->source  = $requ->source;
        $upData->amount  = $requ->amount;
        $upData->transaction  = $requ->transaction;
        
        if($upData->save()):
            return redirect(route('reportListView'))->with("success");
        else:
            return back()->with("error");
        endif;
     }
    //delelte calculasdata
    public function dltCalculasData($id){
        $dltData = cashManage::find($id);

        if($dltData->delete()):
            return back()->with('success','data entry successfully');
        else:
            return back()->with('error','data deletion failed');
        endif;
    
     }

     //report recipit
     public function cashReport($id){
        $singleData = cashManage::find($id);
        return view('account.cashCalculas.report',['singleView'=>$singleData]);
    }

    public function cashDateReport(){
        return view('account.cashCalculas.cashReport');
    }

    public function getCashReport(Request $requ){
        $from   = date('Y-m-d', strtotime($requ->fromDate));
        $to     = date('Y-m-d', strtotime($requ->toDate));

        $query = cashManage::whereBetween('created_at', [$from." 00:00:00",$to." 23:59:59"])->get();
        if(!$query->isEmpty()):
            return view('account.cashCalculas.generateCashReport',['feesList'=>$query]);
        else:
            return back()->with('error','Sorry! No data found with your query');
        endif;
    }

}
