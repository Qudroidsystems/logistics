<?php

namespace App\Http\Controllers\Broadsheet;

use App\Http\Controllers\Controller;
use App\Models\BroadsheetRankingSetting;
use App\Models\Subject;
use Illuminate\Http\Request;

/**
 * Admin settings for the unofficial best-student ranking, one form per section.
 */
class BroadsheetRankingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Manage broadsheet ranking');
    }

    public function index()
    {
        $sections = [];
        foreach (['junior', 'senior'] as $section) {
            $sections[$section] = BroadsheetRankingSetting::where('section', $section)->first()
                ?? BroadsheetRankingSetting::defaultFor($section);
        }

        return view('broadsheet.ranking.index', [
            'pagetitle' => 'Broadsheet Ranking',
            'sections'  => $sections,
            'measures'  => BroadsheetRankingSetting::MEASURES,
            'scopes'    => BroadsheetRankingSetting::SCOPES,
            'subjects'  => Subject::orderBy('subject')->get(['id', 'subject']),
        ]);
    }

    public function save(Request $request, string $section)
    {
        abort_unless(in_array($section, ['junior', 'senior'], true), 404);

        $data = $request->validate([
            'primary_measure'        => 'required|string',
            'tiebreakers'            => 'nullable|array|max:3',
            'tiebreakers.*'          => 'string',
            'min_subjects'           => 'nullable|integer|min:1|max:40',
            'require_all_compulsory' => 'nullable|boolean',
            'exclude_failed'         => 'nullable|boolean',
            'min_average'            => 'nullable|numeric|min:0|max:100',
            'scope'                  => 'required|in:overall,per_arm,per_subject',
            'top_n'                  => 'required|integer|in:1,3,5',
            'core_subject_ids'       => 'nullable|array',
            'core_subject_ids.*'     => 'integer',
            'show_rank_column'       => 'nullable|boolean',
        ]);

        $valid = array_keys(BroadsheetRankingSetting::MEASURES);
        $primary = in_array($data['primary_measure'], $valid, true) ? $data['primary_measure'] : 'cum_ave';
        $tiebreakers = array_values(array_filter(
            (array) ($data['tiebreakers'] ?? []),
            fn ($k) => in_array($k, $valid, true) && $k !== $primary
        ));
        $tiebreakers = array_slice(array_unique($tiebreakers), 0, 3);

        BroadsheetRankingSetting::updateOrCreate(
            ['section' => $section],
            [
                'primary_measure'        => $primary,
                'tiebreakers'            => $tiebreakers,
                'min_subjects'           => $data['min_subjects'] ?? null,
                'require_all_compulsory' => $request->boolean('require_all_compulsory'),
                'exclude_failed'         => $request->boolean('exclude_failed'),
                'min_average'            => $data['min_average'] ?? null,
                'scope'                  => $data['scope'],
                'top_n'                  => (int) $data['top_n'],
                'core_subject_ids'       => array_values(array_map('intval', (array) ($data['core_subject_ids'] ?? []))),
                'show_rank_column'       => $request->boolean('show_rank_column'),
                'is_active'              => true,
            ]
        );

        return back()->with('success', ucfirst($section).' ranking settings saved.');
    }
}
