<?php
function planner_monthly_planner_template(TCPDF $pdf, string $note_style, float $margin): void
{
    [$start_x, $start_y, $width, $height] = planner_size_dimensions($margin);
    $size = $height / 33;

}

Templates::register('planner-monthly-planner', 'planner_monthly_planner_template');

function planner_monthly_planner(TCPDF $pdf, Month $month, string $note_style): void
{
    [$tabs, $tab_targets] = planner_make_monthly_tabs($pdf, $month);

    $pdf->AddPage();
    $pdf->setLink(Links::monthly($pdf, $month, 'planner'));

    planner_monthly_header($pdf, $month, 1, $tabs);
    link_tabs($pdf, $tabs, $tab_targets);

    $margin = 2;
    $line_height = 2;

    Templates::draw('planner-monthly-planner', $note_style, $margin);

    [$start_x, $start_y, $width, $height] = planner_size_dimensions($margin);
    $size = $height / 33;
    [$offset_x, $offset_y] = planner_calculate_marking_offset($width, $height, $note_style, $size);

    $pdf->setFont(Loc::_('fonts.font2'));
    $pdf->setFontSize(Size::fontSize($size, $line_height));
    $pdf->setTextColor(...Colors::g(6));

    $x = $start_x + $offset_x;
    $y = $start_y + $offset_y + $size;

    $background_color = Colors::g(13); // Start with the first color


    foreach ($month->days as $day) {
        // Change the background color only on Mondays
        if ($day->dow === 1) { // Monday is dow = 1
            $background_color = ($background_color === Colors::g(13)) ? Colors::g(14) : Colors::g(13); // Alternate colors
            $pdf->setFillColor(...$background_color);
        }

    // Draw the background for the current day
    $pdf->Rect($start_x, $y, $width, $size, 'F'); // One row per day

    // Set border style just for this cell
    $pdf->SetDrawColor(200, 200, 200); // Light gray border
    $pdf->SetLineWidth(0.1); // Thin border

    // Render the day name with borders
    $pdf->setAbsXY($x, $y);
    $pdf->Cell($size * 2, $size, Loc::_(sprintf('weekday.m%d', $day->dow)), 'LTRB', 0, 'L', false); // Day name with borders
    $pdf->Cell($size, $size, $day->day, 'LTRB', 0, 'R', false); // Date with borders
    $pdf->Link($x, $y, $size * 3, $size, Links::daily($pdf, $day));
        $y += $size;
    }
    planner_draw_note_area($pdf, $start_x, $start_y, $width, $height, 'hekll', $size);


    planner_nav_sub($pdf, $month);
    planner_nav_main($pdf, 0);
}