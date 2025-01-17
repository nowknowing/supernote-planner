<?php
function planner_daily_header_template(TCPDF $pdf, float $y, float $h, int $active, float $year_margin, float $week_margin, array $tabs): void
{
    $pdf->setLineStyle([
        'width' => 0.2,
        'cap' => 'butt',
        'color' => Colors::g(15)
    ]);
    $pdf->setFont(Loc::_('fonts.font2'));
    $pdf->setFontSize(Size::fontSize($h, 1.5));

    $pdf->setFillColor(...Colors::g(0));
    $pdf->Rect(0, $y, W, $h, 'F');

    $pdf->setFillColor(...Colors::g(15));
    $pdf->Rect($year_margin - 0.1, $y, 0.2, $h, 'F');

    $pdf->setFillColor(...Colors::g(15));
    $pdf->Rect($year_margin + $week_margin - 0.1, $y, 0.2, $h, 'F');

    draw_tabs($pdf, $active, $tabs);
}

Templates::register('planner-daily-header', 'planner_daily_header_template');

function planner_daily_make_day_str(Day $day): string
{
    $weekday_short = Loc::_(sprintf('weekday.m%d', $day->dow));
    $month_short = Loc::_(sprintf('month.s%02d', $day->month));
    $date = $day->day;
    return Loc::_('full-date', weekday: $weekday_short, month: $month_short, date: $date);
}

function planner_daily_header(TCPDF $pdf, Day $day, int $active, array $tabs): void
{
    $year_margin = 15;
    $week_margin = 12;
    $margin = planner_header_margin();
    $height = planner_header_height();

    Templates::draw('planner-daily-header', PX100, $height, $active, $year_margin, $week_margin, $tabs);

    $pdf->setFont(Loc::_('fonts.font2'));
    $pdf->setFontSize(Size::fontSize($height, 1.5));
    $pdf->setTextColor(...Colors::g(15));

    $pdf->setAbsXY($x = 0, PX100);
    $pdf->Cell($year_margin - $margin, $height, strval($day->year), align: 'R');
    $pdf->Link(0, PX100, $year_margin, $height, Links::yearly($pdf, $day->year()));

    $pdf->setAbsXY($x = $year_margin, PX100);
    $pdf->Cell($week_margin, $height, Loc::_('week.number_s', week: $day->week()->week), align: 'C');
    $pdf->Link($x, PX100, $week_margin, $height, Links::weekly($pdf, $day->week()));

    $day_str = planner_daily_make_day_str($day);
    $pdf->setAbsXY($margin + ($x += $week_margin), PX100);
    $pdf->Cell(W, $height, $day_str, align: 'L');

}

function planner_agenda_format_time_h(int $time, bool $hr12): string
{
    while ($time >= 24) {
        $time -= 24;
    }
    if ($hr12) {
        if ($time == 0) {
            return '12a';
        } else if ($time < 12) {
            return $time;
        } else if ($time == 12) {
            return '12p';
        } else {
            return ($time - 12) . 'p';
        }
    } else {
        return sprintf('%02d', $time);
    }
}

// function planner_daily_template(TCPDF $pdf, float $margin, bool $hr12, bool $night_shift, float $agenda_size, int $line_per_hour, float $agenda_line_height, float $task_line_size): void
// {
//     [$start_x, $start_y, $width, $height] = planner_size_dimensions($margin);
//     $time_start = 0;
//     $time_end = 23;
//
//     if ($night_shift) {
//         $time_start = 19;
//         $time_end = 24 + 8;
//     }
//
//     $pdf->setLineStyle([
//         'width' => 0.1,
//         'cap' => 'butt',
//         'color' => Colors::g(0)
//     ]);
//     $pdf->setFillColor(...Colors::g(0));
//     $pdf->setTextColor(...Colors::g(0));
//     $pdf->setFont(Loc::_('fonts.font2'));
//
//     // Agenda
//     $hours = $time_end - $time_start + 1;
//     $per_hour = ($height - 2 * $margin) / $hours;
//     $per_line = $per_hour / $line_per_hour;
//
//     $pdf->setFontSize(Size::fontSize($per_line, $agenda_line_height));
//     $pdf->setTextColor(...Colors::g(6));
//
//     $y = $start_y + $margin;
//     for ($h = $time_start; $h <= $time_end; $h++) {
//         $pdf->setAbsXY($start_x, $y);
//         $pdf->Cell($agenda_size, $per_line, planner_agenda_format_time_h($h, $hr12), align: 'L');
//         for ($i = 1; $i <= $line_per_hour; $i++)
//             $pdf->Line($start_x, $y + $i * $per_line, $start_x + $agenda_size, $y + $i * $per_line);
//         $y += $per_hour;
//     }
//
//     $start_x += $margin + $agenda_size;
//     $width -= $margin + $agenda_size;
//
//     // Task list
//     planner_draw_note_area($pdf, $start_x, $start_y, $width, $height, 'checkbox', $task_line_size);
// }
function planner_daily_template(TCPDF $pdf, float $margin, bool $hr12, bool $night_shift, float $agenda_size, int $line_per_hour, float $agenda_line_height, float $task_line_size): void
{
    [$start_x, $start_y, $width, $height] = planner_size_dimensions($margin);
    $time_start = 0;
    $time_end = 23;

    if ($night_shift) {
        $time_start = 19;
        $time_end = 24 + 8;
    }

    // Set line style once for consistent thickness
    $pdf->setLineStyle([
        'width' => 0.07,  // Consistent thickness
        'cap' => 'butt',
        'color' => Colors::g(10)
    ]);
    $pdf->setFillColor(...Colors::g(0));
    $pdf->setTextColor(...Colors::g(0));
    $pdf->setFont(Loc::_('fonts.font2'));

    // Calculate per-hour height to fully utilize the column space
    $total_hours = 21;  // 13.5 full hours for 6-23 and 5 half hours for 0-5
    $per_hour = ($height - 2 * $margin) / $total_hours;
    $per_line = $per_hour / $line_per_hour;

    $pdf->setFontSize(Size::fontSize($per_line, $agenda_line_height));
    $pdf->setTextColor(...Colors::g(6));

    $y = $start_y + $margin;
    for ($h = $time_start; $h <= $time_end; $h++) {
        // Set the label position for each hour
        $pdf->setAbsXY($start_x, $y);
        $pdf->Cell($agenda_size, $per_line, planner_agenda_format_time_h($h, $hr12), align: 'L');

        // Draw the top line for the hour slot
        $pdf->Line($start_x, $y, $start_x + $agenda_size, $y);

        if ($h >= 0 && $h <= 5) {
            // For hours 0 to 5, use half the height and only one line at the top
            $y += $per_hour / 2;  // Move down by half of per_hour height
        } else {
            // For other hours, move down by the full hour height
            $y += $per_hour;
        }

        // Draw the bottom line for the hour slot
        $pdf->Line($start_x, $y, $start_x + $agenda_size, $y);
    }


    $start_x += $margin + $agenda_size;
    $width -= $margin + $agenda_size;

    // Task list
    planner_draw_note_area($pdf, $start_x, $start_y, $width, $height, 'checkbox', $task_line_size);
}




Templates::register('planner-daily', 'planner_daily_template');

function planner_make_daily_tabs(TCPDF $pdf, Day $day): array
{
    $tabs = [
        ['name' => Loc::_('w-task'), 'type' => 'button'],
        ['name' => Loc::_('planner')],
        ['name' => Loc::_('diary')],
        ['name' => Loc::_('note')],
    ];
    $tab_targets = [
        Links::weekly($pdf, $day->week(), 'task'),
        Links::daily($pdf, $day),
        Links::daily($pdf, $day, 'diary'),
        Links::daily($pdf, $day, 'note'),
    ];

    planner_tabs_calculate_size($pdf, $tabs);
    return [$tabs, $tab_targets];
}

function planner_daily(TCPDF $pdf, Day $day, bool $hr12, bool $night_shift): void
{
    [$tabs, $tab_targets] = planner_make_daily_tabs($pdf, $day);

    $pdf->AddPage();
    $pdf->setLink(Links::daily($pdf, $day));

    planner_daily_header($pdf, $day, 1, $tabs);
    link_tabs($pdf, $tabs, $tab_targets);

    $margin = 2;
    $agenda_size = (W - 3 * $margin) * 0.4;
    $line_per_hour = 2;
    $agenda_line_height = 2.2;
    $task_line_size = 6;

    Templates::draw('planner-daily', $margin, $hr12, $night_shift, $agenda_size, $line_per_hour, $agenda_line_height, $task_line_size);

    planner_nav_sub($pdf, $day->month());
    planner_nav_main($pdf, 0);
}
