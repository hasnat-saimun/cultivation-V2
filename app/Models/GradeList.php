<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradeList extends Model
{
    use HasFactory;

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
        return null;
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
        return $candidate;
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
