<?php
// Overlap Calculator with Max Width

// Example inputs — you can replace these with form inputs or dynamic values
$frame_width = 176;    // Kaderbreedte (frame width) in cm
$no_of_panels = 2;     // Number of glass panels
$width_of_glass = 90;  // Width of one glass panel in cm

// Validate inputs
if ($no_of_panels < 1 || $width_of_glass <= 0 || $frame_width <= 0) {
    die("Invalid input values.");
}

// Calculate total overlap
$total_overlap = ($no_of_panels * $width_of_glass) - $frame_width;

// Calculate per overlap (only if more than 1 panel)
$no_of_overlaps = $no_of_panels - 1;

//calculate width
$overlap_width = ($no_of_panels - 1) * 2;

// Calculate Max Breedte (total possible width without overlap)
$panel_width = $no_of_panels * $width_of_glass;

$max_width = $panel_width - $overlap_width;
// Display the result
echo "<h2>Overlap Calculation Result</h2>";
echo "Kaderbreedte (Frame Width): {$frame_width} cm<br>";
echo "Aantal glazen (Panels): {$no_of_panels}<br>";
echo "Breedte glas (Width of Glass): {$width_of_glass} cm<br><br>";

echo "<strong>Total Overlap:</strong> {$total_overlap} cm<br>";
echo "<strong>Overlap:</strong> {$overlap_width} cm<br>";
echo "<strong>Panel Width:</strong> {$panel_width} cm<br>";
echo "<strong>Max Width:</strong> {$max_width} cm";
?>
