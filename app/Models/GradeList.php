<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradeList extends Model
{
    use HasFactory;

    private static function virtualGrade(string $gradeName, float $gradePoint): self
    {
        $row = new self();
        $row->gradeName = $gradeName;
        $row->gradePoint = $gradePoint;
        return $row;
    }

    private static function fallbackForScore(float $score): self
    {
        if ($score >= 80) return self::virtualGrade('A+', 5.00);
        if ($score >= 70) return self::virtualGrade('A', 4.00);
        if ($score >= 60) return self::virtualGrade('A-', 3.50);
        if ($score >= 50) return self::virtualGrade('B', 3.00);
        if ($score >= 40) return self::virtualGrade('C', 2.00);
        if ($score >= 33) return self::virtualGrade('D', 1.00);
        return self::virtualGrade('F', 0.00);
    }

    private static function fallbackForGpa(float $gpa): self
    {
        if ($gpa >= 5.00) return self::virtualGrade('A+', 5.00);
        if ($gpa >= 4.00) return self::virtualGrade('A', 4.00);
        if ($gpa >= 3.50) return self::virtualGrade('A-', 3.50);
        if ($gpa >= 3.00) return self::virtualGrade('B', 3.00);
        if ($gpa >= 2.00) return self::virtualGrade('C', 2.00);
        if ($gpa >= 1.00) return self::virtualGrade('D', 1.00);
        return self::virtualGrade('F', 0.00);
    }

    /**
     * Find grade row for a given score (0-100) using minMark/maxMark.
     */
    public static function forScore(float $score): ?self
    {
        // Use in-memory matching to avoid varchar comparison issues
        $rows = self::all();
        $scoreF = (float) $score;
        foreach ($rows as $row) {
            $min = is_numeric($row->minMark) ? (float) $row->minMark : null;
            $max = is_numeric($row->maxMark) ? (float) $row->maxMark : null;
            if ($min !== null && $max !== null && $scoreF >= $min && $scoreF <= $max) {
                return $row;
            }
        }
        return self::fallbackForScore($scoreF);
    }

    /**
     * Find grade row for a given GPA using minGp/maxGp if present,
     * and fall back to discrete gradePoint mapping.
     */
    public static function forGpa(float $gpa): ?self
    {
        $rows = self::all();
        $gpaF = (float) $gpa;
        $candidate = null;
        foreach ($rows as $row) {
            $min = is_numeric($row->minGp) ? (float) $row->minGp : null;
            $max = is_numeric($row->maxGp) ? (float) $row->maxGp : null;
            if ($min !== null && $max !== null && $gpaF >= $min && $gpaF <= $max) {
                return $row;
            }
            // fallback candidate: closest gradePoint <= gpa
            if (is_numeric($row->gradePoint)) {
                $gp = (float) $row->gradePoint;
                if ($gp <= $gpaF) {
                    if ($candidate === null || (float)$candidate->gradePoint < $gp) {
                        $candidate = $row;
                    }
                }
            }
        }
        return $candidate ?: self::fallbackForGpa($gpaF);
    }

    public static function letterForScore(float $score): ?string
    {
        $row = self::forScore($score);
        return $row ? $row->gradeName : null;
    }

    public static function pointForScore(float $score): ?float
    {
        $row = self::forScore($score);
        return $row ? (float) $row->gradePoint : null;
    }

    public static function letterForGpa(float $gpa): ?string
    {
        $row = self::forGpa($gpa);
        return $row ? $row->gradeName : null;
    }
}
