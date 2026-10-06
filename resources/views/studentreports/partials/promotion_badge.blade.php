{{--
    resources/views/studentreports/partials/promotion_badge.blade.php

    Usage in studentresult.blade.php:
        @include('studentreports.partials.promotion_badge', [
            'promotionResult' => $promotion_result ?? []
        ])

    Usage in class_results_pdf.blade.php (inside per-student loop):
        @include('studentreports.partials.promotion_badge', [
            'promotionResult' => $studentData['promotion_result'] ?? [],
            'isPdf'           => true
        ])
--}}

@php
    $pr          = $promotionResult ?? [];
    $status      = $pr['status']              ?? 'awaiting';
    $isPromoTerm = $pr['is_promotional_term'] ?? false;
    $failed      = $pr['failed_compulsory']   ?? [];
    $avgFailed   = $pr['average_failed']      ?? false;
    $reqAvg      = $pr['required_average']    ?? null;
    $actAvg      = $pr['actual_average']      ?? null;
    $total       = $pr['compulsory_count']    ?? 0;
    $passed      = $pr['passed_compulsory']   ?? 0;
    $isPdf       = $isPdf ?? false;

    // Build failure detail strings
    $failedSubjectNames = collect($failed)
        ->filter(fn($f) => !empty($f['subject']))
        ->map(fn($f) =>
            $f['subject']
            . ' (got ' . ($f['grade'] ?? '-') . (!empty($f['min_grade']) ? ', need ' . $f['min_grade'] : '') . ')'
        )->implode(', ');

    if (!$failedSubjectNames && count($failed) > 0) {
        $failedSubjectNames = count($failed) . ' compulsory subject(s)';
    }
@endphp

@if (!$isPdf)
{{-- ═══════════════ WEB (Blade) VERSION ═══════════════ --}}

@if (!$isPromoTerm)
    <div class="promo-badge promo-awaiting">
        <div class="promo-icon-wrap promo-icon-awaiting">
            <i class="ri-time-line"></i>
        </div>
        <div class="promo-content">
            <div class="promo-label">Awaiting Final Term</div>
            <div class="promo-sub">Promotion will be assessed at the end of the academic year.</div>
        </div>
    </div>
@elseif ($status === 'promoted')
    <div class="promo-badge promo-promoted">
        <div class="promo-icon-wrap promo-icon-promoted">
            <i class="ri-award-line"></i>
        </div>
        <div class="promo-content">
            <div class="promo-label">PROMOTED</div>
            @if ($total > 0)
                <div class="promo-sub">
                    <i class="ri-checkbox-circle-line text-success me-1"></i>
                    Passed {{ $passed }}/{{ $total }} compulsory subject(s).
                </div>
            @endif
            @if ($reqAvg !== null && $actAvg !== null)
                <div class="promo-sub">
                    <i class="ri-bar-chart-line text-success me-1"></i>
                    Overall average: <strong>{{ number_format($actAvg, 1) }}%</strong>
                    (required {{ number_format($reqAvg, 1) }}%).
                </div>
            @endif
        </div>
    </div>
@else
    <div class="promo-badge promo-repeated">
        <div class="promo-icon-wrap promo-icon-repeated">
            <i class="ri-close-circle-line"></i>
        </div>
        <div class="promo-content">
            <div class="promo-label">NOT PROMOTED</div>
            @if ($failedSubjectNames)
                <div class="promo-sub">
                    <i class="ri-error-warning-line me-1"></i>
                    Failed compulsory: <strong>{{ $failedSubjectNames }}</strong>
                </div>
            @endif
            @if ($avgFailed && $reqAvg !== null && $actAvg !== null)
                <div class="promo-sub">
                    <i class="ri-bar-chart-line me-1"></i>
                    Average <strong>{{ number_format($actAvg, 1) }}%</strong>
                    is below the required <strong>{{ number_format($reqAvg, 1) }}%</strong>.
                </div>
            @endif
        </div>
    </div>
@endif

<style>
.promo-badge {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    border-radius: 14px;
    padding: 14px 18px;
    margin: 16px 0;
    max-width: 560px;
}
.promo-icon-wrap {
    width: 44px; height: 44px; flex-shrink: 0;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
}
.promo-content { flex: 1; }
.promo-label   { font-size: 15px; font-weight: 800; letter-spacing: .4px; margin-bottom: 4px; }
.promo-sub     { font-size: 12.5px; margin-bottom: 3px; }
.promo-sub:last-child { margin-bottom: 0; }

.promo-promoted {
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border: 2px solid #16a34a;
    color: #14532d;
    box-shadow: 0 4px 14px rgba(22,163,74,.12);
}
.promo-icon-promoted { background: #16a34a; color: #fff; }

.promo-repeated {
    background: linear-gradient(135deg, #fef2f2, #fee2e2);
    border: 2px solid #dc2626;
    color: #7f1d1d;
    box-shadow: 0 4px 14px rgba(220,38,38,.12);
}
.promo-icon-repeated { background: #dc2626; color: #fff; }

.promo-awaiting {
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border: 2px solid #94a3b8;
    color: #475569;
}
.promo-icon-awaiting { background: #94a3b8; color: #fff; }
</style>

@else
{{-- ═══════════════ PDF VERSION ═══════════════ --}}
{{-- CSS for PDF should be added to class_results_pdf.blade.php <style> block --}}

@if (!$isPromoTerm)
    <div class="promo-pdf-badge promo-pdf-awaiting">
        <span class="promo-pdf-icon">&#9201;</span>
        <div>
            <span class="promo-pdf-title">Awaiting Final Term</span>
            <span class="promo-pdf-sub">Promotion assessed at end of academic year.</span>
        </div>
    </div>
@elseif ($status === 'promoted')
    <div class="promo-pdf-badge promo-pdf-promoted">
        <span class="promo-pdf-icon">&#127891;</span>
        <div>
            <span class="promo-pdf-title">PROMOTED</span>
            @if ($total > 0)
                <span class="promo-pdf-sub">Passed {{ $passed }}/{{ $total }} compulsory subject(s).</span>
            @endif
            @if ($reqAvg !== null && $actAvg !== null)
                <span class="promo-pdf-sub">Average: {{ number_format($actAvg, 1) }}% (min. {{ number_format($reqAvg, 1) }}%).</span>
            @endif
        </div>
    </div>
@else
    <div class="promo-pdf-badge promo-pdf-repeated">
        <span class="promo-pdf-icon">&#9888;</span>
        <div>
            <span class="promo-pdf-title">NOT PROMOTED</span>
            @if ($failedSubjectNames)
                <span class="promo-pdf-sub">Failed: {{ $failedSubjectNames }}</span>
            @endif
            @if ($avgFailed && $reqAvg !== null && $actAvg !== null)
                <span class="promo-pdf-sub">Average {{ number_format($actAvg, 1) }}% below required {{ number_format($reqAvg, 1) }}%.</span>
            @endif
        </div>
    </div>
@endif

@endif
