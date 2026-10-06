<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BroadsheetRankingSetting extends Model
{
    protected $table = 'broadsheet_ranking_settings';

    /** Available ranking measures (key => label). */
    public const MEASURES = [
        'cum_ave'       => 'Cumulative average',
        'term_ave'      => 'Term average',
        'term_total'    => 'Term total',
        'cum_total'     => 'Cumulative total',
        'gpa'           => 'GPA / CGPA',
        'distinctions'  => 'Distinctions count',
        'core_avg'      => 'Core-subjects average',
    ];

    public const SCOPES = [
        'overall'     => 'Overall best',
        'per_arm'     => 'Best per arm',
        'per_subject' => 'Best per subject',
    ];

    protected $fillable = [
        'section', 'primary_measure', 'tiebreakers', 'min_subjects',
        'require_all_compulsory', 'exclude_failed', 'min_average',
        'scope', 'top_n', 'core_subject_ids', 'show_rank_column', 'is_active',
    ];

    protected $casts = [
        'tiebreakers'            => 'array',
        'core_subject_ids'       => 'array',
        'require_all_compulsory' => 'boolean',
        'exclude_failed'         => 'boolean',
        'show_rank_column'       => 'boolean',
        'is_active'              => 'boolean',
        'min_average'            => 'decimal:2',
    ];

    /** The default settings used when a section has no saved row. */
    public static function defaultFor(string $section): self
    {
        return new self([
            'section'                => $section,
            'primary_measure'        => 'cum_ave',
            'tiebreakers'            => ['cum_total', 'gpa'],
            'min_subjects'           => null,
            'require_all_compulsory' => false,
            'exclude_failed'         => false,
            'min_average'            => null,
            'scope'                  => 'overall',
            'top_n'                  => 3,
            'core_subject_ids'       => [],
            'show_rank_column'       => false,
            'is_active'              => true,
        ]);
    }
}
