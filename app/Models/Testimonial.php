<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use HasFactory;
    protected $fillable = [
        'admission_id','ref_no','issue_date','student_name','father_name','mother_name','village','district','ssc_year','roll_no','reg_no','gpa','grade','subject','education_board','exam_name','dob','remarks','composed_by','composed_date','headmaster_name'
    ];
    public function admission() {
        return $this->belongsTo(newAdmission::class, 'admission_id');
    }
}
