<?php
include 'dbconnect.php';
/** @var PDO $pdo */

header('Content-Type: application/json');

$gender      = $_POST['gender'] ?? 'women';
$userPalette = json_decode($_POST['colors'] ?? '[]', true);
$mode        = intval($_POST['mode'] ?? 0);
$excludeMap  = json_decode($_POST['excludeMap'] ?? '{}', true);

if (empty($userPalette)) {
    echo json_encode(['type' => 'none', 'products' => [], 'totalSlots' => 0]);
    exit;
}

// ─── Color Helpers ────────────────────────────────────────────────────────────

function hexToHsl($hex) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) return null;

    $r = hexdec(substr($hex, 0, 2)) / 255;
    $g = hexdec(substr($hex, 2, 2)) / 255;
    $b = hexdec(substr($hex, 4, 2)) / 255;

    $max   = max($r, $g, $b);
    $min   = min($r, $g, $b);
    $delta = $max - $min;
    $l     = ($max + $min) / 2;

    if ($delta == 0) {
        return ['h' => 0, 's' => 0, 'l' => $l * 100];
    }

    $s = $delta / (1 - abs(2 * $l - 1));

    if ($max == $r)     $h = 60 * fmod((($g - $b) / $delta), 6);
    elseif ($max == $g) $h = 60 * ((($b - $r) / $delta) + 2);
    else                $h = 60 * ((($r - $g) / $delta) + 4);

    if ($h < 0) $h += 360;

    return ['h' => $h, 's' => $s * 100, 'l' => $l * 100];
}

function hueDistance($h1, $h2) {
    $diff = abs($h1 - $h2);
    return min($diff, 360 - $diff);
}

function isNeutral($hex) {
    $hsl = hexToHsl($hex);
    if (!$hsl) return false;
    return $hsl['s'] < 15;
}

// Match a product color to a user palette color
// Only checks if product color is in the same color family as the user color
// Does NOT check if user colors match each other
function isColorMatch($userHex, $productHex) {
    $u = hexToHsl($userHex);
    $p = hexToHsl($productHex);
    if (!$u || !$p) return false;

    $uIsNeutral = $u['s'] < 15;
    $pIsNeutral = $p['s'] < 15;

    // Both neutral: match by lightness proximity
    if ($uIsNeutral && $pIsNeutral) {
        return abs($u['l'] - $p['l']) < 40;
    }

    // One neutral one chromatic: no match
    // (neutrals only come from the system in single-color mode)
    if ($uIsNeutral || $pIsNeutral) {
        return false;
    }

    // Both chromatic: must be same hue family
    if (hueDistance($u['h'], $p['h']) > 35) return false;

    // Both very dark (e.g. brown, dark green, maroon): allow
    $uDark = $u['l'] < 25;
    $pDark = $p['l'] < 25;
    if ($uDark && $pDark) return true;

    // One dark one light: reject (yellow ≠ brown even if same hue family)
    if ($uDark !== $pDark) return false;

    // Both mid/light: lightness within 30, saturation within 40
    if (abs($u['l'] - $p['l']) > 30) return false;
    if (abs($u['s'] - $p['s']) > 40) return false;

    return true;
}

// Match a product to a neutral color bucket
function isNeutralMatch($neutralHex, $productHex) {
    $n = hexToHsl($neutralHex);
    $p = hexToHsl($productHex);
    if (!$n || !$p) return false;
    // Product must also be neutral and close in lightness
    return $p['s'] < 15 && abs($n['l'] - $p['l']) < 40;
}

// ─── Setup ────────────────────────────────────────────────────────────────────

// Neutrals used ONLY in single-color mode for two-piece pairing
$neutralColors   = ['#1a1a1a', '#FFFFFF', '#808080', '#dbae8a', '#ebd9c7'];
$singleColorMode = count($userPalette) === 1;

// In single-color mode: match products against user color + neutrals
// In multi-color mode: match products against user colors ONLY
if ($singleColorMode) {
    $effectivePalette = array_merge($userPalette, $neutralColors);
} else {
    $effectivePalette = $userPalette;
}

// ─── Fetch ALL variants with valid color_hex ──────────────────────────────────
$sql = "
    SELECT
        p.id,
        p.name,
        p.slug,
        p.base_price,
        c.slug AS category_slug,
        v.color_hex,
        v.color,
        COALESCE(v.price, p.base_price) AS price,
        (
            SELECT i.image_url
            FROM product_images i
            WHERE i.product_id = p.id
              AND i.is_primary = 1
            LIMIT 1
        ) AS image_url
    FROM products p
    JOIN categories c      ON p.category_id = c.id
    JOIN categories parent ON c.parent_id   = parent.id
    JOIN product_variants v ON v.product_id = p.id
        AND v.color_hex IS NOT NULL
        AND v.color_hex != ''
        AND v.color_hex != 'NULL'
    WHERE parent.slug = ?
      AND p.is_active = 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$gender]);
$allVariants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Deduplicate: one row per product+color_hex combo
$allProducts     = [];
$seenVariantKeys = [];
foreach ($allVariants as $variant) {
    $key = $variant['id'] . '_' . strtolower($variant['color_hex']);
    if (isset($seenVariantKeys[$key])) continue;
    $seenVariantKeys[$key] = true;
    $allProducts[] = $variant;
}

// ─── Match each product to a palette color bucket ─────────────────────────────
$matched = [];

foreach ($allProducts as $product) {
    if (empty($product['color_hex'])) continue;

    $productIsNeutral = isNeutral($product['color_hex']);
    $matchedColor     = null;

    if ($singleColorMode) {
        // Try user's single color first (chromatic match)
        if (!$productIsNeutral) {
            if (isColorMatch($userPalette[0], $product['color_hex'])) {
                $matchedColor = $userPalette[0];
            }
        } else {
            // Product is neutral: find which neutral bucket it fits
            foreach ($neutralColors as $nColor) {
                if (isNeutralMatch($nColor, $product['color_hex'])) {
                    $matchedColor = $nColor;
                    break;
                }
            }
        }
    } else {
        // Multi-color mode: try each user color
        foreach ($userPalette as $uColor) {
            $uIsNeutral = isNeutral($uColor);

            if ($uIsNeutral && $productIsNeutral) {
                // User explicitly chose a neutral, product is neutral
                if (isNeutralMatch($uColor, $product['color_hex'])) {
                    $matchedColor = $uColor;
                    break;
                }
            } elseif (!$uIsNeutral && !$productIsNeutral) {
                // Both chromatic: strict family match
                if (isColorMatch($uColor, $product['color_hex'])) {
                    $matchedColor = $uColor;
                    break;
                }
            }
            // Neutral user color vs chromatic product (or vice versa): skip
        }
    }

    if ($matchedColor !== null) {
        $product['palette_color'] = $matchedColor;
        $matched[] = $product;
    }
}

// ─── Bucket by category AND palette color ─────────────────────────────────────

$tops      = [];
$bottoms   = [];
$onePieces = [];

foreach ($matched as $m) {
    $slug = $m['category_slug'];
    $pCol = $m['palette_color'];

    if (in_array($slug, ['men-top', 'women-top'])) {
        $tops[$pCol][] = $m;
    } elseif (in_array($slug, ['men-bottom', 'women-bottom'])) {
        $bottoms[$pCol][] = $m;
    } elseif ($slug === 'women-one-piece') {
        $onePieces[$pCol][] = $m;
    }
}

// ─── Build slot plan ──────────────────────────────────────────────────────────
// Slots are built from ALL palette colors (including neutrals in single-color mode)
// BUT one-pieces and same-color two-pieces only use USER's chosen colors

$allPaletteColors = array_values(array_unique(array_column($matched, 'palette_color')));
$userChosenColors = $userPalette;

$slots = [];

// 1. Contrasting two-piece: all combinations across palette colors
// In single-color mode this gives: userColor+neutral, neutral+userColor
// In multi-color mode this gives: color1+color2, color2+color1, etc.
foreach ($allPaletteColors as $topColor) {
    foreach ($allPaletteColors as $bottomColor) {
        if ($topColor === $bottomColor) continue;
        if (!empty($tops[$topColor]) && !empty($bottoms[$bottomColor])) {
            $slots[] = [
                'type'        => 'two-piece',
                'topColor'    => $topColor,
                'bottomColor' => $bottomColor,
                'key'         => "two_{$topColor}_{$bottomColor}",
            ];
        }
    }
}

// 2. One-pieces: ONLY from user's chosen colors — never a neutral one-piece
foreach ($userChosenColors as $color) {
    if (!empty($onePieces[$color])) {
        $slots[] = [
            'type'  => 'one-piece',
            'color' => $color,
            'key'   => "one_{$color}",
        ];
    }
}

// 3. Same-color two-pieces: ONLY from user's chosen colors
foreach ($userChosenColors as $color) {
    if (!empty($tops[$color]) && !empty($bottoms[$color])) {
        $slots[] = [
            'type'        => 'two-piece',
            'topColor'    => $color,
            'bottomColor' => $color,
            'key'         => "two_{$color}_{$color}",
        ];
    }
}

// ─── Pick from slot ───────────────────────────────────────────────────────────

function pickFromPool($pool, $exclude) {
    $available = array_values(array_filter($pool, function($p) use ($exclude) {
        return !in_array($p['id'], $exclude);
    }));
    if (empty($available)) $available = $pool;
    shuffle($available);
    return $available[0] ?? null;
}

$response = ['type' => 'none', 'products' => [], 'totalSlots' => count($slots)];

if (empty($slots)) {
    echo json_encode($response);
    exit;
}

$slotIndex   = $mode % count($slots);
$slot        = $slots[$slotIndex];
$slotKey     = $slot['key'];
$slotExclude = $excludeMap[$slotKey] ?? [];

if ($slot['type'] === 'two-piece') {
    $topPool    = $tops[$slot['topColor']]       ?? [];
    $bottomPool = $bottoms[$slot['bottomColor']] ?? [];

    $top    = pickFromPool($topPool,    $slotExclude);
    $bottom = pickFromPool($bottomPool, $slotExclude);

    if ($top && $bottom) {
        $response = [
            'type'       => 'two-piece',
            'products'   => [$top, $bottom],
            'totalSlots' => count($slots),
            'slotKey'    => $slotKey,
        ];
    }

} elseif ($slot['type'] === 'one-piece') {
    $pool  = $onePieces[$slot['color']] ?? [];
    $piece = pickFromPool($pool, $slotExclude);

    if ($piece) {
        $response = [
            'type'       => 'one-piece',
            'products'   => [$piece],
            'totalSlots' => count($slots),
            'slotKey'    => $slotKey,
        ];
    }
}
// TEMPORARY DEBUG - remove after fixing
error_log(print_r($response, true));

echo json_encode($response);