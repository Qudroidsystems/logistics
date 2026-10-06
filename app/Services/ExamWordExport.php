<?php

namespace App\Services;

use App\Models\ExamPaper;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;

/**
 * Renders a vetted exam paper as a professionally formatted Word (.docx) file,
 * as though it had been typed up. Returns the path to the saved temp file.
 */
class ExamWordExport
{
    public function build(ExamPaper $paper, array $meta = []): string
    {
        $paper->loadMissing('questions');

        $word = new PhpWord();
        $word->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language(\PhpOffice\PhpWord\Style\Language::EN_GB));
        $word->setDefaultFontName('Times New Roman');
        $word->setDefaultFontSize(12);

        // Named styles
        $word->addTitleStyle(1, ['bold' => true, 'size' => 14, 'name' => 'Times New Roman'], ['alignment' => Jc::CENTER, 'spaceAfter' => 60]);
        $word->addParagraphStyle('center', ['alignment' => Jc::CENTER]);
        $word->addParagraphStyle('qpara', ['spaceAfter' => 120, 'spaceBefore' => 60]);
        $word->addFontStyle('schoolName', ['bold' => true, 'size' => 15, 'allCaps' => true]);
        $word->addFontStyle('small', ['size' => 10, 'color' => '555555']);
        $word->addFontStyle('sec', ['bold' => true, 'size' => 12, 'underline' => 'single']);
        $word->addFontStyle('qnum', ['bold' => true]);
        $word->addFontStyle('marks', ['italic' => true, 'size' => 10, 'color' => '555555']);

        $section = $word->addSection([
            'marginTop'    => Converter::cmToTwip(1.8),
            'marginBottom' => Converter::cmToTwip(1.8),
            'marginLeft'   => Converter::cmToTwip(2),
            'marginRight'  => Converter::cmToTwip(2),
        ]);

        // ── School header ──
        $school = $meta['school'] ?? null;
        if ($school) {
            $section->addText($school->school_name ?? 'SCHOOL', 'schoolName', 'center');
            if (!empty($school->school_address)) $section->addText($school->school_address, 'small', 'center');
            if (!empty($school->school_motto))  $section->addText('"'.$school->school_motto.'"', ['italic' => true, 'size' => 10, 'color' => '555555'], 'center');
        }
        $section->addTextBreak(1);

        // ── Title + meta ──
        $section->addTitle($paper->title, 1);
        $label = $meta['label'] ?? '';
        $section->addText(
            trim($label.($paper->typeLabel() ? '  ·  '.$paper->typeLabel() : '')),
            ['bold' => true, 'size' => 11], 'center'
        );

        // meta table (class / time / marks)
        $tableStyle = ['borderSize' => 0, 'cellMargin' => 40, 'width' => 100 * 50, 'unit' => 'pct'];
        $word->addTableStyle('metaTbl', $tableStyle);
        $t = $section->addTable('metaTbl');
        $t->addRow();
        $dur = $paper->duration_minutes ? $paper->duration_minutes.' minutes' : '____________';
        $total = rtrim(rtrim(number_format((float) $paper->total_marks, 2), '0'), '.');
        $t->addCell(4500)->addText('Time allowed: '.$dur, 'small');
        $t->addCell(4500)->addText('Total marks: '.$total, 'small');
        $t->addRow();
        $t->addCell(4500)->addText('Name: ______________________________', 'small');
        $t->addCell(4500)->addText('Date: ____________', 'small');

        $section->addTextBreak(1);

        if ($paper->instructions) {
            $section->addText('Instructions: '.$paper->instructions, ['italic' => true, 'size' => 11]);
            $section->addTextBreak(1);
        }

        // ── Questions ──
        $lastSection = null;
        $n = 0;
        foreach ($paper->questions as $q) {
            $n++;
            if ($q->section && $q->section !== $lastSection) {
                $section->addText('Section '.$q->section, 'sec', ['spaceBefore' => 180, 'spaceAfter' => 60]);
                $lastSection = $q->section;
            }
            $num = $q->number ?: (string) $n;
            $qMarks = rtrim(rtrim(number_format((float) $q->marks, 2), '0'), '.');

            $para = $section->addTextRun('qpara');
            $para->addText($num.'. ', 'qnum');
            $para->addText((string) $q->question);
            $para->addText('   ['.$qMarks.']', 'marks');

            if ($q->type === 'objective' && is_array($q->options) && $q->options) {
                foreach ($q->options as $letter => $opt) {
                    if (trim((string) $opt) === '') continue;
                    $section->addText('     ('.$letter.')  '.$opt, ['size' => 11], ['spaceAfter' => 20]);
                }
            }
        }

        // ── Footer line ──
        $section->addTextBreak(1);
        $foot = $section->addTextRun('center');
        $vetter = $meta['vetter'] ?? null;
        $foot->addText($vetter ? 'Vetted & approved by: '.$vetter : 'Vetted & approved', 'small');
        if ($paper->vetted_at) $foot->addText('   ·   '.$paper->vetted_at->format('d M Y'), 'small');

        $tmp = tempnam(sys_get_temp_dir(), 'exam_').'.docx';
        IOFactory::createWriter($word, 'Word2007')->save($tmp);
        return $tmp;
    }
}
