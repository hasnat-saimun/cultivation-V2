<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServerConfig;
use App\Models\CultivationAdmin;
use App\Models\Subject;
use App\Models\classManage as ClassModel;
use App\Models\Attendance;
use App\Models\newAdmission;
use App\Models\cashManage;
use App\Models\TeacherManagement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Hash;
use sessionData;
use File;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Schema;

class CultivationController extends Controller
{
    public function cultivationIndex(){
        $adminId = session('cultivationAdmin');
        $user = $adminId ? CultivationAdmin::find($adminId) : null;
        $isTeacher = $user && $user->isTeacher();
        $today = date('Y-m-d');
        // Earnings timeframe (today|month|all) via query param ?earningsScope=
        $scope = request()->query('earningsScope','all');
        // Handle missing table gracefully on fresh setups
        if(!Schema::hasTable('attendances')){
            $summary = [
                'total' => 0,
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'excused' => 0,
            ];
            $attendanceRate = 0;
            $metrics = [
                'students' => newAdmission::count(),
                'teachers' => TeacherManagement::count(),
                'parents'  => 0,
                'earnings' => 0,
                'earningsScope' => $scope,
            ];
            return view('cultivation.index', compact('summary','today','isTeacher','metrics','attendanceRate'))
                ->with('error','Attendance table not migrated yet.');
        }
        $q = Attendance::query()->where('attendance_date', $today);
        if($isTeacher){
            $classIds = $user->access_class_array ?? [];
            if(!empty($classIds)){
                $q->whereIn('class_id', $classIds);
            } else {
                // If teacher has no classes assigned, show zeroes
                $summary = [
                    'total' => 0,
                    'present' => 0,
                    'absent' => 0,
                    'late' => 0,
                    'excused' => 0,
                ];
                return view('cultivation.index', compact('summary','today','isTeacher'));
            }
        }
        $summary = [
            'total' => (clone $q)->count(),
            'present' => (clone $q)->where('status','Present')->count(),
            'absent' => (clone $q)->where('status','Absent')->count(),
            'late' => (clone $q)->where('status','Late')->count(),
            'excused' => (clone $q)->where('status','Excused')->count(),
        ];
        // Dashboard headline metrics
        // Incoming vs outgoing markers for cash ledger classification
        $incomingMarkers = ['credit','income','in','cr','receive','received','payment_in','deposit','Credit','Income','In','CR','Receive','Received','Payment_In','Deposit'];
        // Attempt profit/loss for current month using 'date' column first (fallback to created_at)
        $firstMonthDay = date('Y-m-01');
        $lastMonthDay  = date('Y-m-t');
        $incomeMonth = cashManage::query()
            ->whereBetween('date', [$firstMonthDay,$lastMonthDay])
            ->whereIn('transaction',$incomingMarkers)
            ->selectRaw('COALESCE(SUM(CAST(amount as DECIMAL(18,2))),0) as total')->value('total');
        $expenseMonth = cashManage::query()
            ->whereBetween('date', [$firstMonthDay,$lastMonthDay])
            ->whereNotIn('transaction',$incomingMarkers)
            ->selectRaw('COALESCE(SUM(CAST(amount as DECIMAL(18,2))),0) as total')->value('total');
        // Fallback using created_at if result zero and records exist with timestamps
        if((float)$incomeMonth === 0 && (float)$expenseMonth === 0){
            $incomeMonth = cashManage::query()
                ->whereMonth('created_at', date('m'))
                ->whereYear('created_at', date('Y'))
                ->whereIn('transaction',$incomingMarkers)
                ->selectRaw('COALESCE(SUM(CAST(amount as DECIMAL(18,2))),0) as total')->value('total');
            $expenseMonth = cashManage::query()
                ->whereMonth('created_at', date('m'))
                ->whereYear('created_at', date('Y'))
                ->whereNotIn('transaction',$incomingMarkers)
                ->selectRaw('COALESCE(SUM(CAST(amount as DECIMAL(18,2))),0) as total')->value('total');
        }
        $monthlyProfitLoss = (float)$incomeMonth - (float)$expenseMonth;
        // Earnings (legacy box) still available but now mapped to selected scope; keep for backward compatibility
        $cashQ = cashManage::query()->whereIn('transaction', $incomingMarkers);
        if($scope === 'today'){
            $cashQ->whereDate('created_at', $today);
        } elseif($scope === 'month'){
            $cashQ->whereMonth('created_at', date('m'))->whereYear('created_at', date('Y'));
        }
        $cashIncoming = $cashQ->selectRaw('COALESCE(SUM(CAST(amount as DECIMAL(18,2))),0) as total')->value('total');
        // Parents count: prefer guardian phone if column exists, else guardian name, else fallback to student count
        $parentsCount = 0;
        if (Schema::hasColumn('new_admissions', 'gurdianPhone')) {
            $parentsCount = newAdmission::whereNotNull('gurdianPhone')
                ->where('gurdianPhone','!=','')
                ->distinct('gurdianPhone')
                ->count('gurdianPhone');
        } elseif (Schema::hasColumn('new_admissions', 'gurdian')) {
            $parentsCount = newAdmission::whereNotNull('gurdian')
                ->where('gurdian','!=','')
                ->distinct('gurdian')
                ->count('gurdian');
        } else {
            $parentsCount = newAdmission::count();
        }
        // Teacher Panel: Count all teachers (userType=ROLE_TEACHER) since no is_active/is_deleted columns exist
        $metrics = [
            'students' => newAdmission::count(),
            'teachers' => TeacherManagement::count(),
            'parents'  => $parentsCount,
            'earnings' => (float)$cashIncoming,
            'monthlyProfitLoss' => $monthlyProfitLoss,
            'monthlyProfitIncome' => (float)$incomeMonth,
            'monthlyProfitExpense' => (float)$expenseMonth,
            'earningsScope' => $scope,
        ];
        // Build monthly cash chart (labels, income, expense) with fallback to created_at if date column empty
        $daysInMonth = (int)date('t');
        $labels = [];
        $incomeSeries = array_fill(0,$daysInMonth,0.0);
        $expenseSeries = array_fill(0,$daysInMonth,0.0);
        $ledgerRows = cashManage::query()
            ->where(function($qq) use($firstMonthDay,$lastMonthDay){
                $qq->whereBetween('date', [$firstMonthDay,$lastMonthDay])
                   ->orWhereBetween(DB::raw('DATE(created_at)'), [$firstMonthDay,$lastMonthDay]);
            })
            ->select(['id','transaction','amount','date','created_at'])
            ->get();
        foreach($ledgerRows as $row){
            // Determine effective date (prefer explicit date column if non-empty)
            $effectiveDate = $row->date && trim($row->date) !== '' ? $row->date : ($row->created_at ? $row->created_at->format('Y-m-d') : null);
            if(!$effectiveDate) continue;
            if(substr($effectiveDate,0,7) !== date('Y-m')) continue; // ensure current month
            $day = (int)substr($effectiveDate,8,2); // 1-31
            $index = $day - 1;
            if($index < 0 || $index >= $daysInMonth) continue;
            $amt = (float)$row->amount;
            $isIncome = in_array(strtolower($row->transaction), $incomingMarkers, true);
            if($isIncome){
                $incomeSeries[$index] += $amt;
            } else {
                $expenseSeries[$index] += $amt;
            }
        }
        for($d=1;$d<=$daysInMonth;$d++){ $labels[] = (string)$d; }
        $metrics['cashChart'] = [ 'labels'=>$labels, 'income'=>$incomeSeries, 'expense'=>$expenseSeries ];
        $attendanceRate = $summary['total'] > 0 ? round(($summary['present'] / $summary['total']) * 100) : 0;
        return view('cultivation.index', compact('summary','today','isTeacher','metrics','attendanceRate'));
    }

    public function serverConfig(){
        return view('cultivation.configuration');
    }

    public function adminProfile(){
        return view('cultivation.adminProfile');
    }
    
    public function saveAdminProfile(Request $requ){
        $cultivation = CultivationAdmin::find($requ->adminId);
        if(empty($cultivation)):
            return back()->with('error','Sorry! No data found');
        else:
            $cultivation->adminName     = $requ->adminName;
            $cultivation->adminMobile   = $requ->adminMobile;
            
            if($cultivation->save()):
                return back()->with('success','Success! Admin profile updated successfully');
            else:
                return back()->with('success','error! There was an error. Please try later');
            endif;
        endif;
    }
    
    public function changeAdminPassword(Request $requ){
        $cultivation = CultivationAdmin::find($requ->adminId);
        if(empty($cultivation)):
            return back()->with('error','Sorry! No data found');
        else:
            if(!Hash::check($requ->oldPassword,$cultivation->loginPassword)):
                return back()->with('error','Sorry! old password wrong provided');
            else:
                if($requ->newPassword !== $requ->confirmPassword):
                    return back()->with('error','Sorry! new password and confirm password does not match');
                else:
                    $authPass    = Hash::make($requ->newPassword);
                    $cultivation->loginPassword   = $authPass;
                    
                    if($cultivation->save()):
                        return back()->with('success','Success! Password change successfully');
                    else:
                        return back()->with('success','error! There was an error. Please try later');
                    endif;
                endif;
            endif;
        endif;
    }

    public function saveConfig(Request $requ){
        if(empty($requ->serverId)):
            $server = new ServerConfig();
        else:
            $server = ServerConfig::find($requ->serverId);
        endif;

        $server->instituteName      = $requ->insName;
        $server->address            = $requ->insAddress;
        $server->principalName      = $requ->principalName;
        $server->principalMobile    = $requ->principalMobile;
        $server->principalDesignation = $requ->principalDesignation;
        $server->principalMail      = $requ->principalMail;
        $server->officeMobile       = $requ->officeMobile;
        $server->officeEmail        = $requ->officeMail;
        $server->facebookPage       = $requ->facebookPage;
        $server->twitterLink        = $requ->twitterLink;
        $server->einNumber          = $requ->einNumber;
        $server->studentIdPrefix    = $requ->studentIdPrefix;
        $server->teacherIdPrefix    = $requ->teacherIdPrefix;
        $server->staffIdPrefix      = $requ->staffIdPrefix;
        $server->youtubeChanel      = $requ->youtubeChanel;
        $server->establishDate      = $requ->establishDate;
        $server->eduMinName         = $requ->eduMinName;
        $server->boardChairmanName  = $requ->boardChairmanName;
        $server->mapEmbed           = $requ->mapEmbed;

        if(!empty($requ->insLogo)):
            $insLogo        = $requ->insLogo;
            $validated = $requ->validate([
                    'logo' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'logo.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'logo.max'    => 'Each file must be less than 5MB.'
                ]);
            $newInsLogo     = rand().date('Ymd').'.'.$insLogo->getClientOriginalExtension();
            $insLogo->move(public_path('upload/image/cultivation'),$newInsLogo);
            $server->logo           = $newInsLogo;
        endif;
        if(!empty($requ->principalSign)):
            $principalSign          = $requ->principalSign;
            $validated = $requ->validate([
                    'principalSign' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'principalSign.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'principalSign.max'    => 'Each file must be less than 5MB.'
                ]);
            $newPrincipalSign       = rand().date('Ymd').'.'.$principalSign->getClientOriginalExtension();
            $principalSign->move(public_path('upload/image/cultivation'),$newPrincipalSign);
            $server->principalSign  = $newPrincipalSign;
        endif;
        if(!empty($requ->adminPhoto)):
            // $adminPhoto             = $requ->adminPhoto;
            $validated = $requ->validate([
                    'adminPhoto' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'adminPhoto.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'adminPhoto.max'    => 'Each file must be less than 5MB.'
                ]);

            $file = $requ->file('adminPhoto');

            // Use guessed extension from MIME (safer than client original)
            $ext  = strtolower($file->extension()); // e.g. jpg|jpeg|png|webp|avif
            $name = Str::uuid().'.'.$ext;

            // Ensure destination directory exists
            $dir = public_path('upload/image/cultivation');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $path = $dir.DIRECTORY_SEPARATOR.$name;

            // Read & resize (keeps aspect, prevents upscaling)
            $img = Image::read($file)->cover(200, 300);

            // Encode with quality for lossy formats (PNG will ignore "quality")
            $binary = $img->encodeByExtension($ext, quality: 80);

            // Write file to disk
            file_put_contents($path, (string) $binary);
            $server->avatar         = $name;
        endif;
        if(!empty($requ->favicon)):
            $favicon                = $requ->favicon;
            $validated = $requ->validate([
                    'favicon' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'favicon.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'favicon.max'    => 'Each file must be less than 5MB.'
                ]);
            $newFavicon             = rand().date('Ymd').'.'.$favicon->getClientOriginalExtension();
            $favicon->move(public_path('upload/image/cultivation'),$newFavicon);
            $server->favicon        = $newFavicon;
        endif;

        
        if(!empty($requ->eduMinImg)):
            $eduMinImg                = $requ->eduMinImg;
            $validated = $requ->validate([
                    'eduMinImg' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'eduMinImg.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'eduMinImg.max'    => 'Each file must be less than 5MB.'
                ]);
            $newEduMinImg             = rand().date('Ymd').'.'.$eduMinImg->getClientOriginalExtension();
            $eduMinImg->move(public_path('upload/image/cultivation'),$newEduMinImg);
            $server->eduMinImg        = $newEduMinImg;
        endif;

        if(!empty($requ->boardChairmanImg)):
            $boardChairmanImg                = $requ->boardChairmanImg;
            $validated = $requ->validate([
                    'boardChairmanImg' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'boardChairmanImg.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'boardChairmanImg.max'    => 'Each file must be less than 5MB.'
                ]);
            $newBoardChairmanImg             = rand().date('Ymd').'.'.$boardChairmanImg->getClientOriginalExtension();
            $boardChairmanImg->move(public_path('upload/image/cultivation'),$newBoardChairmanImg);
            $server->boardChairmanImg        = $newBoardChairmanImg;
        endif;

        if($server->save()):
            return back()->with('success','Data saved successfully');
        else:
            return back()->with('error','Data failed to save');
        endif;
    }

    public function delAvatar($id){
        $avatar = ServerConfig::find($id);
        if(!empty($avatar)):
            if(File::exists(public_path('upload/image/cultivation/').$avatar->avatar)):
                File::delete(public_path('upload/image/cultivation/').$avatar->avatar);
            endif;
            $avatar->avatar   = "";
            $avatar->save();
            return back()->with('success','Avatar delete successful');
        else:
            return back()->with('success','Avatar failed to delete');
        endif;
    }

    public function delSign($id){
        $sign = ServerConfig::find($id);
        if(!empty($sign)):
            if(File::exists(public_path('upload/image/cultivation/').$sign->principalSign)):
                File::delete(public_path('upload/image/cultivation/').$sign->principalSign);
            endif;
            $sign->principalSign   = "";
            $sign->save();
            return back()->with('success','Principal Sign delete successful');
        else:
            return back()->with('success','Principal Sign failed to delete');
        endif;
    }

    public function delLogo($id){
        $logo = ServerConfig::find($id);
        if(!empty($logo)):
            // return public_path('upload/image/cultivation/').$logo->logo;
            if(File::exists(public_path('upload/image/cultivation/').$logo->logo)):
                File::delete(public_path('upload/image/cultivation/').$logo->logo);
            endif;
            $logo->logo   = "";
            $logo->save();
            return back()->with('success','Logo delete successful');
        else:
            return back()->with('success','Logo failed to delete');
        endif;
    }

    public function delFavicon($id){
        $favicon = ServerConfig::find($id);
        if(!empty($favicon)):
            if(File::exists(public_path('upload/image/cultivation/').$favicon->favicon)):
                File::delete(public_path('upload/image/cultivation/').$favicon->favicon);
            endif;
            $favicon->favicon   = "";
            $favicon->save();
            return back()->with('success','Favicon delete successful');
        else:
            return back()->with('success','Favicon failed to delete');
        endif;
    }

    
    public function delEduMinImg($id){
        $eduMinImg = ServerConfig::find($id);
        if(!empty($eduMinImg)):
            if(File::exists(public_path('upload/image/cultivation/').$eduMinImg->eduMinImg)):
                File::delete(public_path('upload/image/cultivation/').$eduMinImg->eduMinImg);
            endif;
            $eduMinImg->eduMinImg   = "";
            $eduMinImg->save();
            return back()->with('success','eduMinImg delete successful');
        else:
            return back()->with('success','eduMinImg failed to delete');
        endif;
    }

     public function delBoardChairmanImg($id){
        $boardChairmanImg = ServerConfig::find($id);
        if(!empty($boardChairmanImg)):
            if(File::exists(public_path('upload/image/cultivation/').$boardChairmanImg->boardChairmanImg)):
                File::delete(public_path('upload/image/cultivation/').$boardChairmanImg->boardChairmanImg);
            endif;
            $boardChairmanImg->boardChairmanImg   = "";
            $boardChairmanImg->save();
            return back()->with('success','boardChairmanImg delete successful');
        else:
            return back()->with('success','boardChairmanImg failed to delete');
        endif;
    }

    public function saveAvatar(Request $requ){
        $avatar = ServerConfig::find($requ->serverId);
        if(!empty($avatar)):
            if(File::exists(public_path('upload/image/cultivation/').$avatar->avatar)):
                File::delete(public_path('upload/image/cultivation/').$avatar->avatar);
            endif;
            $adminPhoto             = $requ->adminPhoto;
            $validated = $requ->validate([
                    'adminPhoto' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'adminPhoto.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'adminPhoto.max'    => 'Each file must be less than 5MB.'
                ]);

            $file = $requ->file('adminPhoto');

            // Use guessed extension from MIME (safer than client original)
            $ext  = strtolower($file->extension()); // e.g. jpg|jpeg|png|webp|avif
            $fileName = Str::uuid().'.'.$ext;

            // Ensure destination directory exists
            $dir = public_path('upload/image/cultivation');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $path = $dir.DIRECTORY_SEPARATOR.$fileName;

            // Read & resize (keeps aspect, prevents upscaling)
            $img = Image::read($file)->cover(400, 450);

            // Encode with quality for lossy formats (PNG will ignore "quality")
            $binary = $img->encodeByExtension($ext, quality: 80);

            // Write file to disk
            file_put_contents($path, (string) $binary);
            $avatar->avatar         = $fileName;
            if($avatar->save()):
                return back()->with('success','Avatar saved successfully');
            else:
                return back()->with('error','Avatar failed to save');
            endif;
        else:
            return back()->with('success','Avatar not found');
        endif;
    }

    public function saveSign(Request $requ){
        $sign = ServerConfig::find($requ->serverId);
        if(!empty($sign)):
            if(File::exists(public_path('upload/image/cultivation/').$sign->principalSign)):
                File::delete(public_path('upload/image/cultivation/').$sign->principalSign);
            endif;
            $principalSign             = $requ->principalSign;
            $validated = $requ->validate([
                    'principalSign' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'principalSign.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'principalSign.max'    => 'Each file must be less than 5MB.'
                ]);
            $newSign          = rand().date('Ymd').'.'.$principalSign->getClientOriginalExtension();
            $principalSign->move(public_path('upload/image/cultivation'),$newSign);
            $sign->principalSign         = $newSign;
            if($sign->save()):
                return back()->with('success','Avatar saved successfully');
            else:
                return back()->with('error','Avatar failed to save');
            endif;
        else:
            return back()->with('success','Avatar not found');
        endif;
    }

    public function saveLogo(Request $requ){
        $logo = ServerConfig::find($requ->serverId);
        if(!empty($logo)):
            if(File::exists(public_path('upload/image/cultivation/').$logo->logo)):
                File::delete(public_path('upload/image/cultivation/').$logo->logo);
            endif;
            $insLogo             = $requ->insLogo;
            $validated = $requ->validate([
                    'insLogo' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'insLogo.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'insLogo.max'    => 'Each file must be less than 5MB.'
                ]);
            $newLogo          = rand().date('Ymd').'.'.$insLogo->getClientOriginalExtension();
            $insLogo->move(public_path('upload/image/cultivation'),$newLogo);
            $logo->logo         = $newLogo;
            if($logo->save()):
                return back()->with('success','Logo saved successfully');
            else:
                return back()->with('error','Logo failed to save');
            endif;
        else:
            return back()->with('success','Logo not found');
        endif;
    }

    public function saveFavicon(Request $requ){
        $data = ServerConfig::find($requ->serverId);
        if(!empty($data)):
            if(File::exists(public_path('upload/image/cultivation/').$data->favicon)):
                File::delete(public_path('upload/image/cultivation/').$data->favicon);
            endif;
            $favicon             = $requ->favicon;
            $validated = $requ->validate([
                    'favicon' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'favicon.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'favicon.max'    => 'Each file must be less than 5MB.'
                ]);
            $newFavicon          = rand().date('Ymd').'.'.$favicon->getClientOriginalExtension();
            $favicon->move(public_path('upload/image/cultivation'),$newFavicon);
            $data->favicon         = $newFavicon;
            if($data->save()):
                return back()->with('success','Favicon saved successfully');
            else:
                return back()->with('error','Favicon failed to save');
            endif;
        else:
            return back()->with('success','Favicon not found');
        endif;
    }

    
    public function saveEduMinImg(Request $requ){
        $data = ServerConfig::find($requ->serverId);
        if(!empty($data)):
            if(File::exists(public_path('upload/image/cultivation/').$data->eduMinImg)):
                File::delete(public_path('upload/image/cultivation/').$data->eduMinImg);
            endif;
            $eduMinImg             = $requ->eduMinImg;
            $validated = $requ->validate([
                    'eduMinImg' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'eduMinImg.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'eduMinImg.max'    => 'Each file must be less than 5MB.'
                ]);
            $newEduMinImg          = rand().date('Ymd').'.'.$eduMinImg->getClientOriginalExtension();
            $eduMinImg->move(public_path('upload/image/cultivation'),$newEduMinImg);
            $data->eduMinImg         = $newEduMinImg;
            if($data->save()):
                return back()->with('success','eduMinImg saved successfully');
            else:
                return back()->with('error','eduMinImg failed to save');
            endif;
        else:
            return back()->with('success','eduMinImg not found');
        endif;
    } 
    
    public function saveBoardChairmanImg(Request $requ){
        $data = ServerConfig::find($requ->serverId);
        if(!empty($data)):
            if(File::exists(public_path('upload/image/cultivation/').$data->boardChairmanImg)):
                File::delete(public_path('upload/image/cultivation/').$data->boardChairmanImg);
            endif;
            $boardChairmanImg             = $requ->boardChairmanImg;
            $validated = $requ->validate([
                    'boardChairmanImg' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'boardChairmanImg.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'boardChairmanImg.max'    => 'Each file must be less than 5MB.'
                ]);
            $newBoardChairmanImg          = rand().date('Ymd').'.'.$boardChairmanImg->getClientOriginalExtension();
            $boardChairmanImg->move(public_path('upload/image/cultivation'),$newBoardChairmanImg);
            $data->boardChairmanImg         = $newBoardChairmanImg;
            if($data->save()):
                return back()->with('success','boardChairmanImg saved successfully');
            else:
                return back()->with('error','boardChairmanImg failed to save');
            endif;
        else:
            return back()->with('success','boardChairmanImg not found');
        endif;
    }

     public function userType(){
        $subjectList = Subject::orderBy('id','ASC')->get();
        $classList   = ClassModel::orderBy('id','ASC')->get();
        return view('userPanal.userRegister',compact('subjectList','classList'));
    }

     public function editUser($id){
        $user = CultivationAdmin::find($id);
        if(empty($user)){
            return back()->with('error','Sorry! No data found');
        }
        $subjectList = Subject::orderBy('id','ASC')->get();
        $classList   = ClassModel::orderBy('id','ASC')->get();
        return view('userPanal.userRegister',compact('subjectList','classList','user'));
    }

     public function saveUser(Request $requ){
        if($requ->filled('userId')) {
            $cultivation = CultivationAdmin::find($requ->userId);
            if(!$cultivation) {
                return back()->with('error', 'User not found for update');
            }
            // Only update password if a new password is provided
            if($requ->filled('pass')) {
                $cultivation->loginPassword = Hash::make($requ->pass);
            }
        } else {
            if($requ->pass !== $requ->confirmPass) {
                return back()->with('error', 'Password and Confirm Password do not match');
            }
            
            $cultivation = CultivationAdmin::where('adminUser',$requ->userName)->orWhere('adminMail',$requ->userMail)->first();
            if($cultivation) {
                return back()->with('error', 'User already exists with this ID('.$requ->userName.') or Email('.$requ->userMail.')');
            }
            $cultivation = new CultivationAdmin;
            $cultivation->loginPassword = Hash::make($requ->pass);
            $cultivation->adminUser     = $requ->userName;
            $cultivation->adminMail     = $requ->userMail;
        }

        $cultivation->adminName     = $requ->adminName;
        $cultivation->adminMobile   = $requ->userMobile;
        $cultivation->userType      = $requ->userType;

        if($cultivation->save()):
            // Sync teacher assignments to pivot tables (pivot-only; no legacy columns)
            if ((int)$requ->userType === \App\Models\CultivationAdmin::ROLE_TEACHER) {
                $clsIds = ($requ->has('className') && is_array($requ->className)) ? array_map('intval', $requ->className) : [];
                $subIds = ($requ->has('subject') && is_array($requ->subject)) ? array_map('intval', $requ->subject) : [];
                // Sync to pivots
                $cultivation->classes()->sync($clsIds);
                $cultivation->subjects()->sync($subIds);
            } else {
                // Clear assignments for non-teacher
                $cultivation->classes()->sync([]);
                $cultivation->subjects()->sync([]);
            }
            $msg = $requ->filled('userId') ? 'Success! Admin profile updated successfully' : 'Success! Admin profile created successfully';
            return back()->with('success', $msg);
        else:
            return back()->with('success','error! There was an error. Please try later');
        endif;
    }

    public function userRegList(){
        $currentUserId = session('cultivationAdmin');
        $userList = CultivationAdmin::where('id', '!=', $currentUserId)
            ->orderBy('id','ASC')->get();
        return view('userPanal.userList',compact('userList'));
    }
    public function deleteUser($id)
    {
        $user = CultivationAdmin::find($id);
        if (!$user) {
            return back()->with('error', 'User not found');
        }
        if ($user->delete()) {
            return back()->with('success', 'User deleted successfully');
        } else {
            return back()->with('error', 'Failed to delete user');
        }
    }
}
