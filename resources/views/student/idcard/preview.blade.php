{{--
    Preview wrapper — renders front + back pairs for all students
    Used by: StudentIdCardController@preview  (returns res.html)
--}}
<style>
@import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap');
* { box-sizing: border-box; }

.preview-page {
    background: #f1f5f9;
    padding: 24px;
    min-height: 100%;
}

.preview-pair {
    display: flex;
    gap: 20px;
    justify-content: center;
    align-items: flex-start;
    margin-bottom: 32px;
    flex-wrap: wrap;
}

.preview-label {
    text-align: center;
    font-family: 'Nunito', sans-serif;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.preview-card-col {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.pair-divider {
    border: none;
    border-top: 1px dashed #cbd5e1;
    margin: 4px 0 28px;
    width: 100%;
    max-width: 700px;
}

.student-number-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #1e3a5f;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    margin-bottom: 10px;
}

/* Print / PDF overrides */
@media print {
    .preview-page { background: #fff; padding: 0; }
    .pair-divider { page-break-after: always; margin: 0; border: none; }
    .preview-pair { margin-bottom: 0; }
}
</style>

<div class="preview-page">
    @foreach($students as $index => $student)
    <div style="text-align:center;margin-bottom:6px;">
        <span class="student-number-badge">{{ $index + 1 }}</span>
    </div>

    <div class="preview-pair">
        {{-- FRONT --}}
        <div class="preview-card-col">
            <div class="preview-label">&#9654; Front</div>
            @include('student.idcard.card-front', [
                'student'    => $student,
                'schoolInfo' => $schoolInfo ?? null,
            ])
        </div>

        {{-- BACK --}}
        <div class="preview-card-col">
            <div class="preview-label">&#9664; Back</div>
            @include('student.idcard.card-back', [
                'student'    => $student,
                'schoolInfo' => $schoolInfo ?? null,
            ])
        </div>
    </div>

    @if(!$loop->last)
        <hr class="pair-divider">
    @endif
    @endforeach
</div>
