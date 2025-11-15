<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'admission_id','ref_no','issue_date','student_name','father_name','mother_name','address','class_name','session','roll_no','reg_no','dob','leaving_class','leaving_date','reason','conduct','character','remarks','composed_by','composed_date','headmaster_name'
    ];

    public function admission(){
        return $this->belongsTo(newAdmission::class, 'admission_id');
    }
}
