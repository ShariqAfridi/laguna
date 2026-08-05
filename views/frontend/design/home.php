<?php
if (!isset($base)) {
  $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
  if (substr($scriptDir, -6) === '/logic') {
    $scriptDir = substr($scriptDir, 0, -6);
  }
  $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
}

require_once __DIR__ . '/../../../db.php';
$dbConn = get_db_connection();

$categoriesResult = $dbConn->query('SELECT * FROM categories WHERE status = 1 ORDER BY id ASC');
$categoriesList = [];
if ($categoriesResult && $categoriesResult->num_rows > 0) {
  while ($row = $categoriesResult->fetch_assoc()) {
    $categoriesList[] = $row;
  }
}

$colorsResult = $dbConn->query('SELECT * FROM colors WHERE status = 1 ORDER BY sort_order ASC, color_id ASC');
$dbColors = [];
if ($colorsResult && $colorsResult->num_rows > 0) {
  while ($row = $colorsResult->fetch_assoc()) {
    $rawHex = trim($row['color_hex'] ?? '#000000');
    $cleanHex = preg_replace('/[^0-9A-Fa-f]/', '', $rawHex);
    if (strlen($cleanHex) === 3) {
      $fmtHex = '#' . $cleanHex[0] . $cleanHex[0] . $cleanHex[1] . $cleanHex[1] . $cleanHex[2] . $cleanHex[2];
    } elseif (strlen($cleanHex) >= 6) {
      $fmtHex = '#' . substr($cleanHex, 0, 6);
    } else {
      $fmtHex = '#' . str_pad($cleanHex, 6, '0');
    }
    $dbColors[] = [
      'id' => $row['color_id'],
      'name' => $row['color_name'],
      'hex' => strtoupper($fmtHex),
      'image' => !empty($row['color_image']) ? base_url('/' . ltrim($row['color_image'], '/')) : '',
      'code' => !empty($row['sku']) ? $row['sku'] : sprintf('%02d', $row['color_id'])
    ];
  }
}

$boxesResult = $dbConn->query('SELECT * FROM boxes WHERE status = 1 ORDER BY sort_order ASC, box_id ASC');
$dbBoxes = [];
if ($boxesResult && $boxesResult->num_rows > 0) {
  while ($row = $boxesResult->fetch_assoc()) {
    $dbBoxes[] = [
      'id' => $row['box_id'],
      'name' => $row['box_name'],
      'price' => (float) $row['box_price'],
      'image' => !empty($row['box_image']) ? base_url('/' . ltrim($row['box_image'], '/')) : '',
      'description' => $row['box_description'] ?? '',
      'code' => 'B' . sprintf('%02d', $row['box_id'])
    ];
  }
}

$fragrancesResult = $dbConn->query('SELECT * FROM fragrances WHERE status = 1 ORDER BY sort_order ASC, fragrance_id ASC');
$dbFragrances = [];
if ($fragrancesResult && $fragrancesResult->num_rows > 0) {
  while ($row = $fragrancesResult->fetch_assoc()) {
    $dbFragrances[] = [
      'id' => $row['fragrance_id'],
      'name' => $row['fragrance_name'],
      'image' => !empty($row['fragrance_image']) ? base_url('/' . ltrim($row['fragrance_image'], '/')) : '',
      'scent_note_image' => !empty($row['scent_note_image']) ? base_url('/' . ltrim($row['scent_note_image'], '/')) : '',
      'description' => $row['fragrance_description'] ?? '',
      'code' => !empty($row['sku']) ? $row['sku'] : sprintf('%02d', $row['fragrance_id'])
    ];
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Design Yours — Laguna Vibe Beach</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300;1,400&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --cream: #f5f0e8;
      --teal: #2d5a5c;
      --teal-light: #3a7a7c;
      --text: #1a1a1a;
      --text-muted: #888;
      --border: #ddd;
      --bg-preview: #e8eef2;
      --serif: 'Cormorant Garamond', Georgia, serif;
      --sans: 'Jost', 'Inter', sans-serif;
    }

    .step-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 18px 10%;
      border-bottom: 1px solid var(--border);
      position: sticky;
      top: 0;
      background: #fff;
      z-index: 100;
    }
    .step-info {
      font-family: var(--sans);
      font-size: 11px;
      font-weight: 400;
      letter-spacing: 0.1em;
      color: var(--text-muted);
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .step-info .step-name {
      color: var(--text);
      font-weight: 500;
      letter-spacing: 0.05em;
    }
    .step-dots {
      display: flex;
      gap: 8px;
      align-items: center;
    }
    .step-dot {
      height: 2px;
      width: 56px;
      background: #ddd;
      border-radius: 2px;
      transition: background 0.3s;
    }
    .step-dot.active { background: var(--teal); }
    .step-dot.done { background: var(--teal); }

    .builder-layout {
      display: grid;
      grid-template-columns: 1fr 340px;
      min-height: calc(100vh - 60px);
      background: linear-gradient(to bottom, #F6FBFD, #DEEFF5);
      padding: 10px 50px;
    }

    .builder-main {
      padding: 60px 40px 80px 0;
      width: 100%;
      max-width: 100%;
    }

    .step-label {
      font-size: 11px;
      letter-spacing: 0.18em;
      color: var(--teal);
      font-weight: 400;
      margin-bottom: 18px;
    }

    .step-title {
      font-family: var(--serif);
      font-size: clamp(44px, 5vw, 58px);
      font-weight: 400;
      line-height: 1.05;
      margin-bottom: 22px;
      color: var(--text);
    }

    .step-desc {
      font-size: 14px;
      line-height: 1.7;
      color: #555;
      max-width: 100%;
      margin-bottom: 24px;
    }

    .preview-panel {
      background: #E2F1F7;
      position: sticky;
      top: 85px;
      align-self: start;
      height: fit-content;
      max-height: calc(100vh - 105px);
      display: flex;
      flex-direction: column;
      padding: 20px 18px;
      border-radius: 16px;
      box-shadow: 0 20px 50px rgba(0,0,0,0.08);
      overflow-y: auto;
      margin-top: 20px;
      z-index: 10;
      transition: background-color 0.4s ease, color 0.4s ease;
    }

    .preview-label {
      font-size: 10px;
      letter-spacing: 0.2em;
      color: var(--text-muted);
      font-weight: 500;
      margin-bottom: 40px;
    }

    .preview-candle {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      margin-bottom: 14px;
      padding-top: 10px;
      position: relative;
      width: 100%;
    }

    .candle-visual {
      width: 270px;
      height: 300px;
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .candle-card {
      width: 250px;
      height: 280px;
      background: var(--cream);
      border-radius: 6px;
      position: relative;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-end;
      padding-bottom: 28px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.1);
      transition: background 0.4s, box-shadow 0.4s;
    }

    .candle-card-text {
      text-align: center;
      font-family: var(--serif);
      line-height: 1.4;
    }
    .candle-brand { font-size: 9px; letter-spacing: 0.15em; color: #999; }
    .candle-name { font-size: 16px; color: #555; font-style: italic; }
    .candle-loc { font-size: 9px; letter-spacing: 0.15em; color: #aaa; }

    .candle-flame {
      position: absolute;
      top: -32px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 10;
    }

    .flame-wrapper {
      position: relative;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .flame-glow {
      position: absolute;
      top: 50%;
      left: 50%;
      width: 54px;
      height: 54px;
      background: radial-gradient(circle, rgba(255, 190, 60, 0.65) 0%, rgba(255, 130, 0, 0.25) 45%, rgba(255, 100, 0, 0) 80%);
      border-radius: 50%;
      transform: translate(-50%, -50%);
      animation: candleGlow 3s ease-in-out infinite alternate;
      pointer-events: none;
      z-index: 1;
    }

    .flame-svg {
      position: relative;
      z-index: 2;
      transform-origin: 50% 95%;
      animation: gentleSway 2.6s ease-in-out infinite alternate;
      filter: drop-shadow(0 0 9px rgba(255, 170, 30, 0.85));
    }

    .flame-wrapper:nth-child(2) .flame-svg {
      animation-delay: -0.9s;
      animation-duration: 2.9s;
    }

    .flame-wrapper:nth-child(3) .flame-svg {
      animation-delay: -1.7s;
      animation-duration: 2.3s;
    }

    @keyframes candleGlow {
      0% { transform: translate(-50%, -50%) scale(0.88); opacity: 0.55; }
      50% { transform: translate(-50%, -50%) scale(1.18); opacity: 0.85; }
      100% { transform: translate(-50%, -50%) scale(0.95); opacity: 0.6; }
    }

    @keyframes gentleSway {
      0% {
        transform: rotate(-6.5deg) translateX(-1.8px);
        filter: drop-shadow(-3px 0 8px rgba(255, 150, 20, 0.8));
      }
      25% {
        transform: rotate(-1.5deg) translateX(-0.4px);
        filter: drop-shadow(0 0 9px rgba(255, 175, 30, 0.85));
      }
      50% {
        transform: rotate(6.0deg) translateX(1.8px);
        filter: drop-shadow(3px 0 11px rgba(255, 185, 35, 0.95));
      }
      75% {
        transform: rotate(2.0deg) translateX(0.6px);
        filter: drop-shadow(1px 0 9px rgba(255, 170, 25, 0.85));
      }
      100% {
        transform: rotate(-5.8deg) translateX(-1.5px);
        filter: drop-shadow(-3px 0 8px rgba(255, 150, 20, 0.8));
      }
    }

    .candle-img-wrap {
      width: 250px;
      height: 280px;
      border-radius: 6px;
      overflow: hidden;
      display: none;
      box-shadow: 0 8px 25px rgba(0,0,0,0.14);
      background: transparent;
      align-items: center;
      justify-content: center;
    }
    .candle-img-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 6px;
    }

    .preview-frag-badge {
      position: relative;
      width: 100%;
      max-width: 250px;
      margin: 12px auto 0 auto;
      background: rgba(255, 255, 255, 0.96);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border: 1.5px solid var(--teal, #1b4d4f);
      border-radius: 12px;
      padding: 10px 14px;
      display: flex;
      align-items: flex-start;
      gap: 12px;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
      z-index: 5;
      transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
      animation: fragBadgePop 0.3s ease-out;
    }

    @keyframes fragBadgePop {
      0% { opacity: 0; transform: translateY(6px) scale(0.97); }
      100% { opacity: 1; transform: translateY(0) scale(1); }
    }

    .preview-frag-img {
      width: 42px;
      height: 42px;
      border-radius: 8px;
      overflow: hidden;
      flex-shrink: 0;
      background: #f3f4f6;
      border: 1px solid #e5e7eb;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-top: 2px;
    }

    .preview-frag-img img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .preview-frag-info {
      display: flex;
      flex-direction: column;
      overflow: hidden;
      text-align: left;
      flex: 1;
    }

    .preview-frag-label {
      font-family: var(--sans);
      font-size: 8px;
      font-weight: 700;
      letter-spacing: 0.12em;
      color: var(--teal, #1b4d4f);
      text-transform: uppercase;
    }

    .preview-frag-name {
      font-family: var(--sans);
      font-size: 13px;
      font-weight: 700;
      color: #111827;
      margin-top: 1px;
    }

    .preview-frag-desc {
      font-family: var(--sans);
      font-size: 10.5px;
      font-weight: 500;
      color: #334155;
      margin-top: 4px;
      line-height: 1.45;
      white-space: pre-line;
      word-break: break-word;
    }

    .preview-specs {
      border-top: 1px solid rgba(0,0,0,0.1);
      padding-top: 20px;
    }

    .spec-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 0;
      border-bottom: 1px solid rgba(0,0,0,0.06);
    }
    .spec-key {
      font-size: 10px;
      letter-spacing: 0.15em;
      color: var(--text-muted);
      font-weight: 500;
    }
    .spec-val {
      font-size: 12px;
      color: var(--text);
      text-align: right;
      max-width: 180px;
    }
    .spec-val.empty { color: #ccc; }

    .vessel-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      gap: 20px;
      margin-bottom: 48px;
      width: 100%;
    }

    .vessel-card {
      background: #FFFFFF;
      border: 1px solid #D1E5ED;
      border-radius: 12px;
      transition: all 0.2s ease-in-out;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      position: relative;
      height: 100%;
      width: 100%;
    }

    .vessel-img {
      width: 100%;
      height: 280px;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f9fafb;
      box-sizing: border-box;
      flex-shrink: 0;
    }

    .vessel-img img {
      display: block;
      width: 100%;
      height: 100%;
      object-fit: contain;
      object-position: center;
      padding: 12px;
    }

    .vessel-info {
      padding: 20px 24px;
      background: #FFFFFF;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
    }

    .vessel-card:hover { border-color: #aaa; }
    .vessel-card.selected {
      border-color: var(--teal);
      box-shadow: 0 0 0 1px var(--teal);
    }

    .vessel-check {
      position: absolute;
      top: 12px;
      right: 12px;
      width: 28px;
      height: 28px;
      background: var(--teal);
      border-radius: 50%;
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 2;
    }
    .vessel-card.selected .vessel-check { display: flex; }
    .vessel-check svg { width: 14px; height: 14px; }

    .vessel-name {
      font-family: var(--sans);
      font-size: 18px;
      color: var(--text);
      margin-bottom: 4px;
    }

    .vessel-hours {
      font-family: var(--sans);
      font-size: 11px;
      color: var(--text-muted);
      float: right;
    }

    .vessel-dims {
      font-family: var(--sans);
      font-size: 10px;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      color: var(--text-muted);
      margin-bottom: 12px;
    }

    .vessel-desc {
      font-family: var(--sans);
      font-size: 13px;
      color: #666;
      line-height: 1.5;
    }

    .vessel-title-row {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      margin-bottom: 6px;
    }

    .color-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 16px;
      margin-bottom: 48px;
      width: 100%;
    }

    .color-card {
      background: #FFFFFF;
      border: 1.5px solid #E5E7EB;
      border-radius: 12px;
      padding: 14px 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      transition: all 0.25s ease;
      cursor: pointer;
      position: relative;
      user-select: none;
      box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }

    .color-card.hidden {
      display: none;
    }

    .color-card:hover {
      border-color: #CBD5E1;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    }

    .color-card.selected {
      border: 2px solid var(--teal, #1b4d4f);
      background-color: #F8FAFC;
      box-shadow: 0 4px 14px rgba(27,77,79,0.12);
    }

    .color-card input[type="radio"] {
      display: none;
    }

    .radio-indicator {
      width: 20px;
      height: 20px;
      border-radius: 50%;
      border: 2px solid #CBD5E1;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
      flex-shrink: 0;
      background: #FFFFFF;
    }

    .color-card.selected .radio-indicator {
      border-color: var(--teal, #1b4d4f);
    }

    .radio-indicator::after {
      content: '';
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: var(--teal, #1b4d4f);
      transform: scale(0);
      transition: transform 0.2s ease;
    }

    .color-card.selected .radio-indicator::after {
      transform: scale(1);
    }

    .color-swatch-dot {
      width: 22px;
      height: 22px;
      border-radius: 50%;
      border: 1px solid rgba(0,0,0,0.15);
      flex-shrink: 0;
      box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
    }

    .color-name-label {
      font-family: var(--sans);
      font-size: 14px;
      font-weight: 500;
      color: #1E293B;
      letter-spacing: 0.01em;
      white-space: nowrap;
    }

    .fragrance-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 20px;
      margin-bottom: 48px;
      width: 100%;
    }

    .fragrance-card {
      background: #ffffff;
      border: 1px solid #D1E5ED;
      border-radius: 12px;
      cursor: pointer;
      position: relative;
      transition: border-color 0.2s, box-shadow 0.2s;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      width: 100%;
    }

    .frag-img {
      width: 100%;
      height: 220px;
      overflow: hidden;
      background: #F9FAFB;
      flex-shrink: 0;
      padding: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-sizing: border-box;
    }

    .frag-img img {
      display: block;
      max-width: 100%;
      max-height: 100%;
      width: auto;
      height: auto;
      object-fit: contain;
      object-position: center;
      transition: transform 0.3s ease;
    }

    .frag-info-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 16px;
      background: #fff;
      border-top: 1px solid #D1E5ED;
    }

    .frag-label {
      font-family: var(--sans);
      font-size: 10px;
      font-weight: 500;
      letter-spacing: 0.18em;
      color: var(--text);
      text-transform: uppercase;
    }

    .frag-view-btn {
      font-family: var(--sans);
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.06em;
      color: var(--teal, #2d5a5c);
      border: 1px solid var(--teal, #2d5a5c);
      border-radius: 40px;
      padding: 6px 14px;
      cursor: pointer;
      background: #f0f7f7;
      transition: all 0.2s ease;
      text-transform: uppercase;
      white-space: nowrap;
      z-index: 3;
      position: relative;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .frag-view-btn:hover {
      background: var(--teal, #2d5a5c);
      color: #ffffff !important;
      border-color: var(--teal, #2d5a5c);
      box-shadow: 0 3px 10px rgba(45,90,92,0.25);
    }

    .frag-view-btn svg.eye-icon {
      flex-shrink: 0;
      stroke: var(--teal, #2d5a5c);
      transition: stroke 0.2s ease, color 0.2s ease;
    }

    .frag-view-btn:hover svg.eye-icon {
      stroke: #ffffff !important;
      color: #ffffff !important;
    }

    .fragrance-card:hover { border-color: #aaa; }
    .fragrance-card.selected {
      border-color: var(--teal);
      box-shadow: 0 0 0 1px var(--teal);
    }

    .frag-view-btn:hover { color: var(--teal); border-color: var(--teal); }

    .fragrance-card .vessel-check { top: 10px; left: 10px; width: 24px; height: 24px; right: auto; }
    .fragrance-card .vessel-check svg { width: 12px; height: 12px; }

    .frag-modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.65);
      z-index: 9998;
      display: none;
      align-items: center;
      justify-content: center;
    }
    .frag-modal-overlay.open { display: flex; }

    .frag-modal {
      background: #fff;
      border-radius: 12px;
      width: 70vw;
      max-width: 800px;
      overflow: hidden;
      box-shadow: 0 24px 80px rgba(0,0,0,0.25);
    }

    .frag-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 16px 20px 12px;
    }

    .frag-modal-title {
      font-family: var(--serif);
      font-size: 18px;
      font-weight: 400;
      color: var(--text);
    }

    .frag-modal-close {
      width: 28px;
      height: 28px;
      background: none;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #999;
      font-size: 18px;
      border-radius: 50%;
      transition: background 0.2s;
    }
    .frag-modal-close:hover { background: #f5f5f5; color: #333; }

    .frag-modal-img {
      width: 100%;
      height: 450px;
      overflow: hidden;
      background: #ffffff;
      padding: 16px;
    }
    .frag-modal-img img {
      width: 100%;
      height: 100%;
      display: block;
      object-fit: cover;
      object-position: center;
    }

    .box-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 20px;
      margin-bottom: 48px;
      width: 100%;
    }

    .box-card {
      border: 1.5px solid var(--border);
      border-radius: 8px;
      cursor: pointer;
      transition: border-color 0.2s;
      position: relative;
      overflow: hidden;
      background: #fff;
      width: 100%;
    }
    .box-card:hover { border-color: #aaa; }
    .box-card.selected {
      border-color: var(--teal);
      box-shadow: 0 0 0 1px var(--teal);
    }
    .box-card .vessel-check { top: 12px; right: 12px; width: 24px; height: 24px; }
    .box-card .vessel-check svg { width: 12px; height: 12px; }

    .box-img {
      width: 100%;
      height: 200px;
      background: #f5f5f5;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }
    .box-img img {
      width: 100%;
      height: 100%;
      display: block;
      object-fit: cover;
    }

    .box-info {
      padding: 18px 20px 20px;
    }
    .box-title-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 5px;
    }
    .box-name { font-family: var(--serif); font-size: 20px; }
    .box-price { font-size: 12px; color: var(--text-muted); }
    .box-desc { font-size: 13px; color: #666; line-height: 1.6; }

    .step-nav {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-bottom: 24px;
      border-bottom: 1px solid var(--border);
      width: 100%;
      max-width: 100%;
      margin-bottom: 32px;
    }

    .btn-back {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      padding: 14px 34px;
      border: 2px solid #c8d6de;
      border-radius: 999px;
      background: #ffffff;
      color: #0f2233;
      font-family: var(--sans);
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      text-decoration: none;
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
      transition: background-color 0.25s ease, border-color 0.25s ease, color 0.25s ease, transform 0.2s ease, box-shadow 0.25s ease;
    }
    .btn-back:hover {
      background: #78C5D7;
      border-color: #78C5D7;
      color: #0f2233;
      box-shadow: 0 6px 18px rgba(120,197,215,0.25);
    }
    .btn-back:active { transform: translateY(1px); }

    .btn-next {
      background: var(--teal);
      color: #fff;
      border: none;
      padding: 16px 36px;
      font-family: var(--sans);
      font-size: 11px;
      letter-spacing: 0.18em;
      font-weight: 500;
      cursor: pointer;
      border-radius: 30px;
      transition: background 0.2s;
    }
    .btn-next:hover { background: var(--teal-light); }
    .btn-next:disabled { background: #ccc; cursor: not-allowed; }

    .btn-skip-box {
      background: #ffffff;
      color: #374151;
      border: 1.5px solid #d1d5db;
      padding: 15px 24px;
      font-family: var(--sans);
      font-size: 11px;
      letter-spacing: 0.14em;
      font-weight: 600;
      cursor: pointer;
      border-radius: 30px;
      transition: all 0.2s ease;
      text-transform: uppercase;
      white-space: nowrap;
    }
    .btn-skip-box:hover {
      background: #f3f4f6;
      border-color: #9ca3af;
      color: #111827;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.5);
      z-index: 9999;
      display: none;
      align-items: center;
      justify-content: center;
    }
    .modal-overlay.open { display: flex; }

    .modal {
      background: #fff;
      width: 480px;
      max-height: 90vh;
      overflow-y: auto;
      border-radius: 4px;
      padding: 36px;
    }

    .modal-candle-img {
      width: 100%;
      background: var(--bg-preview);
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 28px;
      border-radius: 2px;
      position: relative;
      min-height: 240px;
    }

    .modal-row {
      display: flex;
      justify-content: space-between;
      padding: 12px 0;
      border-bottom: 1px solid #f0f0f0;
      font-size: 13px;
    }
    .modal-row-key { letter-spacing: 0.08em; color: var(--text-muted); font-size: 11px; }
    .modal-row-val { color: var(--text); font-weight: 400; }

    .modal-qty {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 0;
      border-bottom: 1px solid #f0f0f0;
      font-size: 12px;
      letter-spacing: 0.08em;
      color: var(--text-muted);
    }

    .qty-ctrl {
      display: flex;
      align-items: center;
      gap: 0;
      border: 1px solid var(--border);
      border-radius: 2px;
    }
    .qty-btn {
      width: 36px;
      height: 36px;
      background: none;
      border: none;
      cursor: pointer;
      font-size: 18px;
      color: var(--text);
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.15s;
    }
    .qty-btn:hover { background: #f5f5f5; }
    .qty-num {
      width: 36px;
      text-align: center;
      font-size: 14px;
      border-left: 1px solid var(--border);
      border-right: 1px solid var(--border);
      height: 36px;
      line-height: 36px;
    }

    .promo-row {
      display: flex;
      gap: 10px;
      padding: 20px 0;
      border-bottom: 1px solid #f0f0f0;
    }
    .promo-input {
      flex: 1;
      border: 1.5px solid var(--border);
      border-radius: 2px;
      padding: 10px 14px;
      font-size: 12px;
      font-family: var(--sans);
      letter-spacing: 0.1em;
      outline: none;
    }
    .promo-input:focus { border-color: var(--teal); }
    .promo-apply {
      padding: 10px 20px;
      background: none;
      border: 1.5px solid var(--border);
      font-family: var(--sans);
      font-size: 11px;
      letter-spacing: 0.12em;
      cursor: pointer;
      border-radius: 2px;
      transition: border-color 0.2s;
    }
    .promo-apply:hover { border-color: var(--teal); color: var(--teal); }

    .price-rows { padding: 16px 0 4px; }
    .price-row {
      display: flex;
      justify-content: space-between;
      font-size: 12px;
      padding: 5px 0;
      color: #555;
    }
    .price-total {
      display: flex;
      justify-content: space-between;
      font-size: 17px;
      padding: 14px 0 6px;
      font-family: var(--serif);
      border-top: 1px solid #f0f0f0;
      margin-top: 8px;
    }

    .modal-actions {
      display: flex;
      gap: 12px;
      margin-top: 24px;
    }
    .btn-edit {
      flex: 1;
      padding: 15px;
      background: none;
      border: 1.5px solid var(--border);
      font-family: var(--sans);
      font-size: 11px;
      letter-spacing: 0.15em;
      cursor: pointer;
      border-radius: 2px;
      transition: border-color 0.2s;
    }
    .btn-edit:hover { border-color: var(--teal); }
    .btn-cart {
      flex: 2;
      padding: 15px;
      background: var(--teal);
      color: #fff;
      border: none;
      font-family: var(--sans);
      font-size: 11px;
      letter-spacing: 0.15em;
      cursor: pointer;
      border-radius: 2px;
      transition: background 0.2s;
    }
    .btn-cart:hover { background: var(--teal-light); }

    .step-content { display: none; }
    .step-content.active { display: block; }

    .wick-badge {
      display: inline-block;
      font-family: var(--sans);
      font-size: 10px;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--teal);
      background: rgba(45,90,92,0.08);
      border-radius: 20px;
      padding: 3px 10px;
      margin-top: 6px;
    }

    /* hide box step completely for vessel E */
    .step-box-hidden {
      display: none !important;
    }

    @media (max-width: 1100px) {
      .vessel-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 900px) {
      .builder-layout {
        display: flex;
        flex-direction: column;
        padding: 10px 0 !important;
      }
      .builder-main {
        padding: 20px 12px 40px !important;
        max-width: 100%;
      }
      .preview-panel {
        display: block !important;
        position: relative !important;
        top: auto !important;
        max-height: none !important;
        overflow: visible !important;
        margin: 20px 12px 30px 12px;
        order: 2;
      }
      .color-grid { grid-template-columns: repeat(2, 1fr); }
      .vessel-grid { grid-template-columns: 1fr !important; }
      .box-grid { gap: 12px; }
      .fragrance-grid { grid-template-columns: 1fr !important; }
    }

    @media (max-width: 600px) {
      .builder-main { padding: 16px 10px 40px !important; }
      .step-bar { padding: 14px 12px !important; }
      .step-dot { width: 28px !important; }
      .preview-panel { margin: 16px 10px 24px 10px; padding: 20px 16px; }
      .color-swatch { padding: 15px !important; }
      .vessel-img { padding: 20px 20px !important; }
    }
  </style>
</head>
<body>

<!-- STEP BAR -->
<div class="step-bar">
  <div class="step-info">
    <span id="stepCount">STEP 1 OF 4</span>
    <span class="step-name" id="stepNameLabel">Vessel</span>
  </div>
  <div class="step-dots">
    <div class="step-dot active" id="dot1"></div>
    <div class="step-dot" id="dot2"></div>
    <div class="step-dot" id="dot3"></div>
    <div class="step-dot" id="dot4"></div>
  </div>
</div>

<!-- BUILDER LAYOUT -->
<div class="builder-layout">

  <!-- MAIN CONTENT -->
  <div class="builder-main">

    <!-- STEP 1: VESSEL -->
    <div class="step-content active" id="step1">
      <p class="step-label">STEP 01</p>
      <h1 class="step-title">Choose your vessel.</h1>
      <p class="step-desc">The silhouette and size of your candle. All vessels include a black bamboo lid.</p>
      <div class="step-nav"><span></span><button class="btn-next" onclick="goNext(2)">CONTINUE</button></div>
      <div class="vessel-grid" id="vesselGrid">
        <?php if (!empty($categoriesList)): ?>
          <?php
          foreach ($categoriesList as $cat):
            $catName = $cat['category_name'];
            $vesselKey = trim(str_ireplace('Vessel', '', $catName));
            if (empty($vesselKey)) {
              $vesselKey = $catName;
            }
            $imgSrc = !empty($cat['image']) ? base_url('/' . ltrim($cat['image'], '/')) : base_url('/views/img/vessel2.webp');
            $dims = !empty($cat['dimensions_subtitle']) ? $cat['dimensions_subtitle'] : '';
            $burnTime = !empty($cat['burn_time_badge']) ? $cat['burn_time_badge'] : '';
            $wickType = !empty($cat['wick_type']) ? $cat['wick_type'] : '';
            $desc = !empty($cat['description']) ? $cat['description'] : '';
            $price = 30;
            if (strcasecmp($vesselKey, 'D') === 0) {
              $price = 40;
            }
            if (strcasecmp($vesselKey, 'E') === 0) {
              $price = 55;
            }
            ?>
            <div class="vessel-card" data-vessel="<?= htmlspecialchars($vesselKey); ?>" data-sku="<?= htmlspecialchars($cat['sku'] ?? $vesselKey); ?>" data-price="<?= $price; ?>" onclick="selectVessel(this)">
              <div class="vessel-check"><svg viewBox="0 0 14 14" fill="none"><path d="M2 7L5.5 10.5L12 3.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
              <div class="vessel-img"><img src="<?= htmlspecialchars($imgSrc); ?>" alt="<?= htmlspecialchars($catName); ?>"></div>
              <div class="vessel-info">
                <div class="vessel-title-row">
                  <span class="vessel-name"><?= htmlspecialchars($catName); ?></span>
                  <?php if ($burnTime): ?>
                    <span class="vessel-hours"><?= htmlspecialchars($burnTime); ?></span>
                  <?php endif; ?>
                </div>
                <?php if ($dims): ?>
                  <div class="vessel-dims"><?= htmlspecialchars($dims); ?></div>
                <?php endif; ?>
                <?php if ($desc): ?>
                  <div class="vessel-desc"><?= htmlspecialchars($desc); ?></div>
                <?php endif; ?>
                <?php if ($wickType): ?>
                  <div><span class="wick-badge"><?= htmlspecialchars($wickType); ?></span></div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="color:#6b7280; font-size:14px; grid-column:1/-1;">No active vessel categories available right now.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- STEP 2: COLOR -->
    <div class="step-content" id="step2">
      <p class="step-label">STEP 02</p>
      <h1 class="step-title">Choose your color.</h1>
      <p class="step-desc" id="colorDesc">Select a finish for your vessel. Available colors vary by vessel.</p>
      <div class="step-nav">
        <button class="btn-back" onclick="goBack(1)">
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M9 2L4 7L9 12" stroke="currentColor" stroke-width="1.5"/></svg> BACK
        </button>
        <button class="btn-next" onclick="goNext(3)">CONTINUE</button>
      </div>
      <div class="color-grid" id="colorGrid">
        <!-- Color cards will be dynamically rendered by JavaScript -->
      </div>
    </div>

    <!-- STEP 3: FRAGRANCE -->
    <div class="step-content" id="step3">
      <p class="step-label">STEP 03</p>
      <h1 class="step-title">Choose your fragrance.</h1>
      <p class="step-desc">Tap any scent to select and read its full description and notes.</p>
      <div class="step-nav">
        <button class="btn-back" onclick="goBack(2)">
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M9 2L4 7L9 12" stroke="currentColor" stroke-width="1.5"/></svg> BACK
        </button>
        <button class="btn-next" id="fragNextBtn" onclick="handleFragranceNext()">CONTINUE</button>
      </div>
      <div class="fragrance-grid" id="fragranceGrid">
        <!-- Dynamic Fragrance Cards Rendered via JS -->
      </div>
      <!-- SELECTED FRAGRANCE DETAIL BOX -->
      <div class="selected-frag-detail-box" id="selectedFragDetailBox" style="display:none; margin-top:20px; padding:18px 22px; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.04);">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
          <span style="font-size:10px; letter-spacing:0.12em; text-transform:uppercase; font-weight:700; color:var(--teal, #1b4d4f); background:#e2f1f7; padding:3px 8px; border-radius:4px;">SELECTED SCENT NOTES</span>
          <h4 id="selectedFragDetailTitle" style="font-size:16px; font-weight:600; color:#1e293b; margin:0;"></h4>
        </div>
        <div id="selectedFragDetailDesc" style="font-size:13px; color:#475569; line-height:1.6; white-space:pre-line;"></div>
      </div>
    </div>

    <!-- STEP 4: BOX (OPTIONAL) - hidden for Vessel E -->
    <div class="step-content" id="step4">
      <p class="step-label">STEP 04</p>
      <h1 class="step-title">Add a keepsake box.</h1>
      <p class="step-desc">Optional — choose a cubic box in white or black. The box matches the wick count of your vessel. You can also skip this step.</p>
      <div class="step-nav">
        <button class="btn-back" onclick="goBack(3)">
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M9 2L4 7L9 12" stroke="currentColor" stroke-width="1.5"/></svg> BACK
        </button>
        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
          <button class="btn-skip-box" onclick="checkoutWithoutPackaging()">CHECKOUT WITHOUT PACKAGING</button>
          <button class="btn-next" onclick="openReview()">REVIEW ORDER</button>
        </div>
      </div>
      <div class="box-grid" id="boxGrid">
        <!-- Box cards are dynamically rendered by JavaScript -->
      </div>
    </div>

  </div><!-- end .builder-main -->

  <!-- PREVIEW PANEL -->
  <div class="preview-panel">
    <div class="preview-label">LIVE PREVIEW</div>
    <div class="preview-candle">
      <div class="candle-visual">
        <div class="candle-flame" id="previewFlameContainer">
          <div class="flame-wrapper">
            <div class="flame-glow"></div>
            <svg class="flame-svg" width="26" height="38" viewBox="0 0 24 36" fill="none">
              <path d="M12 2C12 2 6 10 6 18C6 24 8.5 30 12 32C15.5 30 18 24 18 18C18 10 12 2 12 2Z" fill="#ff7700"/>
              <path d="M12 5C12 5 7.5 11.5 7.5 18C7.5 23 9.5 28 12 29.5C14.5 28 16.5 23 16.5 18C16.5 11.5 12 5 12 5Z" fill="#ffcc00"/>
              <path d="M12 11C12 11 9.5 15.5 9.5 20C9.5 23.5 10.5 26.5 12 27.5C13.5 26.5 14.5 23.5 14.5 20C14.5 15.5 12 11 12 11Z" fill="#ffffff" opacity="0.9"/>
              <ellipse cx="12" cy="31" rx="2.5" ry="1.2" fill="#38bdf8" opacity="0.8"/>
              <line x1="12" y1="31" x2="12" y2="36" stroke="#222" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
          </div>
        </div>
        <div class="candle-img-wrap" id="previewImgWrap" style="display:none;">
          <img id="previewImg" src="" alt="Selected candle">
        </div>
        <div class="candle-card" id="previewCard" style="display:flex;">
          <div class="candle-card-text">
            <div class="candle-brand">L V B</div>
            <div class="candle-name" id="previewName">laguna vibe</div>
            <div class="candle-loc">CALIFORNIA</div>
          </div>
        </div>
      </div>
      <!-- FRAGRANCE PREVIEW BADGE (AFTER THE IMAGE) -->
      <div class="preview-frag-badge" id="previewFragBadge" style="display:none;">
        <div class="preview-frag-info">
          <span class="preview-frag-label">SELECTED SCENT</span>
          <span class="preview-frag-name" id="previewFragTitle"></span>
          <span class="preview-frag-desc" id="previewFragDesc" style="font-size: 10.5px; margin-top: 4px; line-height: 1.45; opacity: 0.9; white-space: pre-line; display: none;"></span>
        </div>
      </div>
    </div>
    <div class="preview-specs">
      <div class="spec-row"><span class="spec-key">VESSEL</span><span class="spec-val empty" id="specVessel">—</span></div>
      <div class="spec-row"><span class="spec-key">WICK</span><span class="spec-val empty" id="specWick">—</span></div>
      <div class="spec-row"><span class="spec-key">COLOR</span><span class="spec-val empty" id="specColor">—</span></div>
      <div class="spec-row"><span class="spec-key">FRAGRANCE</span><span class="spec-val empty" id="specFrag">—</span></div>
      <div class="spec-row"><span class="spec-key">LID</span><span class="spec-val" id="specLid">Black Lid</span></div>
      <div class="spec-row"><span class="spec-key">BOX</span><span class="spec-val empty" id="specBox">—</span></div>
      <div class="spec-row"><span class="spec-key">SKU</span><span class="spec-val empty" id="specSku">—</span></div>
    </div>
  </div>

</div><!-- end .builder-layout -->

<!-- REVIEW MODAL -->
<div class="modal-overlay" id="reviewModal">
  <div class="modal">
    <div class="modal-candle-img" id="modalCandleImg">
      <div style="position:relative; display:flex; align-items:center; justify-content:center; width:100%; flex-direction:column;">
        <div id="modalFlameContainer" style="position:relative; margin-bottom:8px;"></div>
        <div id="modalImgWrap" style="width:160px; height:200px; border-radius:2px; overflow:hidden; box-shadow:0 4px 16px rgba(0,0,0,0.1); display:none;">
          <img id="modalPreviewImg" src="" style="width:100%; height:100%; object-fit:cover;">
        </div>
        <div id="modalCardPreview" style="width:160px; height:200px; border-radius:2px; background:var(--cream); display:flex; align-items:flex-end; justify-content:center; padding-bottom:16px; box-shadow:0 4px 16px rgba(0,0,0,0.1); transition:background 0.4s;">
          <div style="text-align:center; font-family:var(--serif);">
            <div style="font-size:8px; letter-spacing:0.15em; color:#999;">L V B</div>
            <div style="font-size:12px; color:#555; font-style:italic;" id="modalCandleName">laguna vibe</div>
            <div style="font-size:8px; letter-spacing:0.15em; color:#aaa;">CALIFORNIA</div>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-row"><span class="modal-row-key">VESSEL</span><span class="modal-row-val" id="mVessel">—</span></div>
    <div class="modal-row"><span class="modal-row-key">WICK COUNT</span><span class="modal-row-val" id="mWick">—</span></div>
    <div class="modal-row"><span class="modal-row-key">COLOR</span><span class="modal-row-val" id="mColor">—</span></div>
    <div class="modal-row"><span class="modal-row-key">FRAGRANCE</span><span class="modal-row-val" id="mFrag">—</span></div>
    <div class="modal-row"><span class="modal-row-key">LID</span><span class="modal-row-val">Black Lid</span></div>
    <div class="modal-row"><span class="modal-row-key">BOX</span><span class="modal-row-val" id="mBox">—</span></div>
    <div class="modal-row"><span class="modal-row-key">SKU</span><span class="modal-row-val" id="mSku" style="font-size:11px; color:#aaa;">—</span></div>
    <div class="modal-qty">
      <span>QUANTITY</span>
      <div class="qty-ctrl">
        <button class="qty-btn" onclick="changeQty(-1)">−</button>
        <span class="qty-num" id="qtyNum">1</span>
        <button class="qty-btn" onclick="changeQty(1)">+</button>
      </div>
    </div>
    <div class="promo-row">
      <input type="text" class="promo-input" placeholder="PROMO CODE" id="promoInput">
      <button class="promo-apply" onclick="applyPromo()">Apply</button>
    </div>
    <div class="price-rows">
      <div class="price-row"><span>SUBTOTAL</span><span id="mSubtotal">$40.00</span></div>
      <div class="price-row"><span>SHIPPING</span><span>$9.00</span></div>
      <div class="price-row"><span>ESTIMATED DELIVERY</span><span>5–7 business days</span></div>
    </div>
    <div class="price-total"><span>Total</span><span id="mTotal">$49.00</span></div>
    <div class="modal-actions">
      <button class="btn-edit" onclick="closeReview()">EDIT DETAILS</button>
      <button class="btn-cart">ADD TO CART</button>
    </div>
  </div>
</div>

<!-- FRAGRANCE LIGHTBOX -->
<div class="frag-modal-overlay" id="fragModal">
  <div class="frag-modal">
    <div class="frag-modal-header">
      <span class="frag-modal-title" id="fragModalTitle">Fragrance</span>
      <button class="frag-modal-close" onclick="closeFragModal()">✕</button>
    </div>
    <div class="frag-modal-img">
      <img id="fragModalImg" src="" alt="">
    </div>
    <div class="frag-modal-desc" id="fragModalDesc" style="padding:16px 20px; font-size:13px; color:#475569; line-height:1.6; white-space:pre-line; background:#f8fafc; border-top:1px solid #e2e8f0; text-align:center; display:none;"></div>
  </div>
</div>

<script src="<?php echo $base; ?>/views/frontend/home/cart.js"></script>
<script>
// ─── STATE ───────────────────────────────────────────────────────────────────
const state = {
  vessel: null,
  vesselSku: null,
  vesselPrice: 0,
  color: null,
  colorCode: null,
  colorHex: null,
  frag: null,
  fragCode: null,
  box: null,
  boxCode: null,
  boxPrice: 0,
  qty: 1
};

// Vessel → wick count map
const vesselWickMap = { C: 'Single Wick', D: 'Double Wick', E: 'Triple Wick' };

// Color data with separate images for each vessel
const colorData = [
  { 
    name: 'White Frost', 
    code: '02', 
    type: 'FROST', 
    vessels: ['C','D','E'],
    images: {
      C: 'img/color111.webp',
      D: 'https://atelier-aroma.lovable.app/assets/candle-white-matt-d-DmvfEXIx.png',
      E: 'img/vesseleimg.webp'
    }
  },
  { 
    name: 'Black Matte', 
    code: '03', 
    type: 'MATTE', 
    vessels: ['C','D','E'],
    images: {
      C: 'img/color112.webp',
      D: 'https://atelier-aroma.lovable.app/assets/candle-black-matt-d-DO2ozZuN.png',
      E: 'img/vesseleimg2.webp'
    }
  },
  { 
    name: 'Mocha Frost', 
    code: '09', 
    type: 'FROST', 
    vessels: ['C'],
    images: {
      C: 'img/color113.webp'
    }
  },
  { 
    name: 'Blue Frost', 
    code: '12', 
    type: 'FROST', 
    vessels: ['C'],
    images: {
      C: 'img/color114.webp'
    }
  },
  { 
    name: 'Blush Pink', 
    code: '01', 
    type: 'MATTE', 
    vessels: ['C','D'],
    images: {
      C: 'img/color115.webp',
      D: 'https://atelier-aroma.lovable.app/assets/candle-blush-pink-d-DGAC-dgR.png'
    }
  },
  { 
    name: 'Charcoal Grey Matte', 
    code: '08', 
    type: 'MATTE', 
    vessels: ['C','D'],
    images: {
      C: 'img/color116.webp',
      D: 'https://atelier-aroma.lovable.app/assets/candle-charcoal-grey-d-D7Nuy563.png'
    }
  },
  { 
    name: 'Silver Electroplate', 
    code: '15', 
    type: 'ELECTROPLATE', 
    vessels: ['C','D'],
    images: {
      C: 'img/color117.webp',
      D: 'https://atelier-aroma.lovable.app/assets/candle-silver-electroplate-d-DodpPBPU.png'
    }
  },
  { 
    name: 'Smoky Grey Electroplate', 
    code: '16', 
    type: 'ELECTROPLATE', 
    vessels: ['C','D'],
    images: {
      C: 'img/color118.webp',
      D: 'https://atelier-aroma.lovable.app/assets/candle-smoky-grey-electroplate-d-BU-7y32f.png'
    }
  },
  { 
    name: 'Purple Frost', 
    code: '09', 
    type: 'FROST', 
    vessels: ['C'],
    images: {
      C: 'img/color9.webp'
    }
  },
];

// Fragrance code map
const fragCodeMap = {
  'Amber Musk':         '02',
  'Champagne Luxe':     '05',
  'Citrus Agave Zest':  '06',
  'Evening Tide':       '08',
  'Fragrance Free':     '01',
  "L'Attraction":       '13',
  'Lavender Fields':    '09',
  'Mahogany Woods':     '11',
  'Pine & Salt Air':    '04',
  'Vanilla Essence':    '14',
  'Wild Lemongrass':    '10'
};

const stepNames = ['Vessel', 'Color', 'Fragrance', 'Box'];

// ─── STATE PERSISTENCE (LOCALSTORAGE) ───────────────────────────────────────
function resetBuilder() {
  state.vessel = null;
  state.vesselPrice = 0;
  state.color = null;
  state.colorCode = null;
  state.colorHex = null;
  state.frag = null;
  state.fragCode = null;
  state.box = null;
  state.boxCode = null;
  state.boxPrice = 0;
  state.qty = 1;

  // Reset preview specs UI
  setSpec('specVessel', '—');
  setSpec('specWick', '—');
  setSpec('specColor', '—');
  setSpec('specFrag', '—');
  setSpec('specBox', '—');

  // Deselect all cards
  document.querySelectorAll('.vessel-card, .color-card, .fragrance-card, .box-card').forEach(c => c.classList.remove('selected'));

  // Reset preview image & fragrance badge
  const previewImgWrap = document.getElementById('previewImgWrap');
  const previewCard = document.getElementById('previewCard');
  if (previewImgWrap) previewImgWrap.style.display = 'none';
  if (previewCard) previewCard.style.display = 'flex';

  const previewNameEl = document.getElementById('previewName');
  if (previewNameEl) previewNameEl.textContent = '';

  const previewFragBadge = document.getElementById('previewFragBadge');
  if (previewFragBadge) previewFragBadge.style.display = 'none';

  // Reset flame container
  updateWickDisplay(null);

  clearBuilderState();
}

function saveBuilderState() {
  try {
    const currentStep = getCurrentStep();
    const previewImgEl = document.getElementById('previewImg');
    const dataToSave = {
      state: state,
      step: currentStep,
      previewImgSrc: previewImgEl ? previewImgEl.src : ''
    };
    localStorage.setItem('lvb_builder_state', JSON.stringify(dataToSave));
  } catch (e) {}
}

function clearBuilderState() {
  try {
    localStorage.removeItem('lvb_builder_state');
  } catch (e) {}
}

function restoreBuilderState() {
  try {
    const saved = localStorage.getItem('lvb_builder_state');
    if (!saved) return false;
    const parsed = JSON.parse(saved);
    if (!parsed || !parsed.state) return false;

    // Restore state object
    Object.assign(state, parsed.state);

    // Restore Vessel selection
    if (state.vessel) {
      const vCard = Array.from(document.querySelectorAll('.vessel-card')).find(c => c.dataset.vessel === state.vessel);
      if (vCard) {
        selectVessel(vCard, true);
      }
    }

    // Restore Color selection
    if (state.color) {
      document.querySelectorAll('.color-card').forEach(c => {
        if (c.dataset.color === state.color) c.classList.add('selected');
        else c.classList.remove('selected');
      });
      setSpec('specColor', state.color);
    }

    // Restore Preview Image
    if (parsed.previewImgSrc && parsed.previewImgSrc.trim() !== '' && !parsed.previewImgSrc.endsWith('/')) {
      const previewImgEl = document.getElementById('previewImg');
      if (previewImgEl) {
        previewImgEl.src = parsed.previewImgSrc;
        document.getElementById('previewImgWrap').style.display = 'block';
        document.getElementById('previewCard').style.display = 'none';
      }
    }

    // Restore Fragrance selection
    if (state.frag) {
      state.fragCode = fragCodeMap[state.frag] || '01';
      document.querySelectorAll('.fragrance-card').forEach(c => {
        if ((c.dataset.frag || '').trim().toLowerCase() === state.frag.trim().toLowerCase()) {
          c.classList.add('selected');
          const fragImgEl = c.querySelector('.frag-img img');
          const fragImgSrc = fragImgEl ? fragImgEl.src : '';
          const badgeEl = document.getElementById('previewFragBadge');
          const badgeImgEl = document.getElementById('previewFragImg');
          const badgeTitleEl = document.getElementById('previewFragTitle');
          if (badgeEl && badgeTitleEl) {
            badgeTitleEl.textContent = state.frag;
            if (fragImgSrc && badgeImgEl) {
              badgeImgEl.src = fragImgSrc;
              badgeImgEl.style.display = 'block';
            }
            badgeEl.style.display = 'flex';
          }
        } else {
          c.classList.remove('selected');
        }
      });
      setSpec('specFrag', state.frag);
      const previewNameEl = document.getElementById('previewName');
      if (previewNameEl) previewNameEl.textContent = state.frag;
    }

    // Restore Box selection
    if (state.box) {
      document.querySelectorAll('.box-card').forEach(c => {
        if (c.dataset.box === state.box) c.classList.add('selected');
        else c.classList.remove('selected');
      });
      setSpec('specBox', `${state.box} (+$${state.boxPrice})`);
    }

    // Restore Quantity
    if (state.qty && state.qty > 1) {
      const qtyVal = document.getElementById('qtyVal');
      if (qtyVal) qtyVal.textContent = state.qty;
    }

    recalcPrice();

    // Restore step from URL hash or saved step
    const hash = window.location.hash;
    let targetStep = parsed.step || 1;
    if (hash && hash.startsWith('#step')) {
      const stepNum = parseInt(hash.replace('#step', ''));
      if (stepNum >= 1 && stepNum <= 4) targetStep = stepNum;
    }
    showStep(targetStep);
    return true;
  } catch (e) {
    console.error('Failed to restore builder state', e);
    return false;
  }
}

// ─── URL STATE SYNC ──────────────────────────────────────────────────────────
function syncUrlState(isPush = false) {
  const currentStep = getCurrentStep();
  const params = new URLSearchParams();

  if (currentStep >= 1 && state.vessel) params.set('vessel', state.vessel);
  if (currentStep >= 2 && state.color) params.set('color', state.color);
  if (currentStep >= 3 && state.frag) params.set('frag', state.frag);
  if (currentStep >= 4 && state.box) params.set('box', state.box);

  const queryString = params.toString();
  const basePath = window.location.pathname.replace(/\/$/, '');
  const newHash = `#step${currentStep}`;
  const newUrl = basePath + (queryString ? `?${queryString}` : '') + newHash;

  if (isPush) {
    window.history.pushState({ step: currentStep }, '', newUrl);
  } else {
    window.history.replaceState({ step: currentStep }, '', newUrl);
  }
  saveBuilderState();
}

function parseUrlState() {
  const hash = window.location.hash || '';
  const search = window.location.search || '';

  let queryString = '';
  if (hash.includes('?')) {
    queryString = hash.split('?')[1];
  } else if (search) {
    queryString = search.replace('?', '');
  }

  if (!queryString && !hash) return false;

  const params = new URLSearchParams(queryString);
  const vesselParam = params.get('vessel') ? decodeURIComponent(params.get('vessel')) : null;
  const colorParam = (params.get('color') || params.get('colorCode')) ? decodeURIComponent(params.get('color') || params.get('colorCode')) : null;
  const fragParam = params.get('frag') ? decodeURIComponent(params.get('frag')) : null;
  const boxParam = (params.get('box') || params.get('boxCode')) ? decodeURIComponent(params.get('box') || params.get('boxCode')) : null;

  let restoredSomething = false;

  // 1. Vessel
  if (vesselParam) {
    const vCard = document.querySelector(`.vessel-card[data-vessel="${vesselParam}"]`);
    if (vCard) {
      selectVessel(vCard, true);
      restoredSomething = true;
    }
  }

  // 2. Color
  if (colorParam) {
    const cCards = Array.from(document.querySelectorAll('.color-card'));
    const cleanColorParam = colorParam.trim().toLowerCase();
    const matchingColor = cCards.find(c => (c.dataset.color || '').trim().toLowerCase() === cleanColorParam || (c.dataset.code || '').trim().toLowerCase() === cleanColorParam);
    if (matchingColor) {
      selectColor(matchingColor, true);
      restoredSomething = true;
    }
  }

  // 3. Fragrance
  if (fragParam) {
    const fCards = Array.from(document.querySelectorAll('.fragrance-card'));
    const cleanFragParam = fragParam.trim().toLowerCase();
    const matchingFrag = fCards.find(c => (c.dataset.frag || '').trim().toLowerCase() === cleanFragParam);
    if (matchingFrag) {
      selectFrag(null, matchingFrag, true);
      restoredSomething = true;
    } else {
      state.frag = fragParam;
      state.fragCode = fragCodeMap[fragParam] || '01';
      setSpec('specFrag', fragParam);
      const previewNameEl = document.getElementById('previewName');
      if (previewNameEl) previewNameEl.textContent = fragParam;
      restoredSomething = true;
    }
  }

  // 4. Box
  if (boxParam) {
    const bCards = Array.from(document.querySelectorAll('.box-card'));
    const cleanBoxParam = boxParam.trim().toLowerCase();
    const matchingBox = bCards.find(c => (c.dataset.box || '').trim().toLowerCase() === cleanBoxParam || (c.dataset.boxCode || '').trim().toLowerCase() === cleanBoxParam);
    if (matchingBox) {
      selectBox(matchingBox, true);
      restoredSomething = true;
    } else if (cleanBoxParam.includes('no packaging') || cleanBoxParam === 'none') {
      checkoutWithoutPackaging();
      restoredSomething = true;
    }
  }

  // Determine Step
  let stepNum = 1;
  if (hash && hash.startsWith('#step')) {
    const stepPart = hash.split('?')[0];
    stepNum = parseInt(stepPart.replace('#step', '')) || 1;
  }
  showStep(stepNum, true);

  return restoredSomething;
}

// ─── NAVIGATION ──────────────────────────────────────────────────────────────
window.addEventListener('popstate', function(e) {
  let targetStep = 1;
  if (e.state && typeof e.state.step === 'number') {
    targetStep = e.state.step;
  } else if (window.location.hash && window.location.hash.includes('#step')) {
    const hashPart = window.location.hash.split('?')[0];
    const parsedNum = parseInt(hashPart.replace('#step', ''));
    if (!isNaN(parsedNum) && parsedNum >= 1 && parsedNum <= 4) {
      targetStep = parsedNum;
    }
  }
  showStep(targetStep, true);
});

function getCurrentStep() {
  for (let i = 1; i <= 4; i++) {
    if (document.getElementById('step' + i).classList.contains('active')) return i;
  }
  return 1;
}

const builderStepUrl = '<?= base_url('/builder/#step'); ?>';

function goNext(step) {
  showStep(step, false, true);
}

function goBack(step) {
  showStep(step, false, false);
}

function updatePreviewPanelTypography() {
  const previewPanel = document.querySelector('.preview-panel');
  if (!previewPanel) return;

  const currentStep = typeof getCurrentStep === 'function' ? getCurrentStep() : 1;

  if (currentStep === 1 || !previewPanel.style.backgroundColor || previewPanel.style.backgroundColor === '#E2F1F7' || previewPanel.style.backgroundColor === 'rgb(226, 241, 247)') {
    resetPreviewPanelStyle();
    return;
  }

  const contrastColor = previewPanel.style.color || '#FFFFFF';
  const isWhiteText = (contrastColor === '#FFFFFF' || contrastColor === 'rgb(255, 255, 255)');

  // 1. Label
  const previewLabel = previewPanel.querySelector('.preview-label');
  if (previewLabel) {
    previewLabel.style.color = isWhiteText ? 'rgba(255, 255, 255, 0.85)' : 'var(--text-muted)';
  }

  // 2. All spec-key and spec-val
  const specRows = previewPanel.querySelectorAll('.spec-row');
  specRows.forEach(row => {
    const key = row.querySelector('.spec-key');
    const val = row.querySelector('.spec-val');
    if (key) {
      key.style.color = isWhiteText ? 'rgba(255, 255, 255, 0.85)' : 'var(--text-muted)';
    }
    if (val) {
      const isValEmpty = val.classList.contains('empty') || val.textContent.trim() === '—' || val.textContent.trim() === '';
      if (!isValEmpty) {
        val.style.color = isWhiteText ? '#FFFFFF' : '#111827';
        val.style.fontWeight = '600';
      } else {
        val.style.color = isWhiteText ? 'rgba(255, 255, 255, 0.4)' : '#a1a1aa';
        val.style.fontWeight = '400';
      }
    }
  });

  // 3. Price
  const priceLabel = previewPanel.querySelector('.preview-price-label');
  const priceVal = previewPanel.querySelector('.preview-price-val');
  if (priceLabel) priceLabel.style.color = isWhiteText ? 'rgba(255, 255, 255, 0.85)' : '#777777';
  if (priceVal) priceVal.style.color = contrastColor;

  // 4. Borders
  const specsBox = previewPanel.querySelector('.preview-specs');
  if (specsBox) {
    specsBox.style.borderColor = isWhiteText ? 'rgba(255, 255, 255, 0.2)' : 'rgba(0, 0, 0, 0.1)';
  }
  const priceRow = previewPanel.querySelector('.preview-price-row');
  if (priceRow) {
    priceRow.style.borderColor = isWhiteText ? 'rgba(255, 255, 255, 0.2)' : 'rgba(0, 0, 0, 0.1)';
  }
}

function setSpec(id, val) {
  const el = document.getElementById(id);
  if (!el) return;
  if (val && val !== '—') {
    el.textContent = val;
    el.classList.remove('empty');
  } else {
    el.textContent = '—';
    el.classList.add('empty');
  }
  updatePreviewPanelTypography();
}

function resetPreviewPanelStyle() {
  const previewPanel = document.querySelector('.preview-panel');
  if (previewPanel) {
    previewPanel.style.backgroundColor = '#E2F1F7';
    previewPanel.style.color = 'inherit';

    const previewLabel = previewPanel.querySelector('.preview-label');
    if (previewLabel) previewLabel.style.color = 'var(--text-muted)';

    const specRows = previewPanel.querySelectorAll('.spec-row');
    specRows.forEach(row => {
      const key = row.querySelector('.spec-key');
      const val = row.querySelector('.spec-val');
      if (key) key.style.color = 'var(--text-muted)';
      if (val) {
        if (!val.classList.contains('empty')) {
          val.style.color = 'var(--text)';
        } else {
          val.style.color = '#ccc';
        }
      }
    });

    const priceLabel = previewPanel.querySelector('.preview-price-label');
    const priceVal = previewPanel.querySelector('.preview-price-val');
    if (priceLabel) priceLabel.style.color = '#777777';
    if (priceVal) priceVal.style.color = 'var(--text)';

    const specsBox = previewPanel.querySelector('.preview-specs');
    if (specsBox) specsBox.style.borderColor = 'rgba(0,0,0,0.1)';

    const priceRow = previewPanel.querySelector('.preview-price-row');
    if (priceRow) priceRow.style.borderColor = 'rgba(0,0,0,0.1)';
  }
}

function showStep(n, skipSync, isPush = false) {
  document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
  const targetStepEl = document.getElementById('step' + n);
  if (targetStepEl) targetStepEl.classList.add('active');

  for (let i = 1; i <= 4; i++) {
    const dot = document.getElementById('dot' + i);
    if (dot) {
      dot.className = 'step-dot';
      if (i < n) dot.classList.add('done');
      if (i === n) dot.classList.add('active');
    }
  }
  const stepCountEl = document.getElementById('stepCount');
  if (stepCountEl) stepCountEl.textContent = `STEP ${n} OF 4`;

  const stepLabelEl = document.getElementById('stepNameLabel');
  if (stepLabelEl && stepNames[n - 1]) stepLabelEl.textContent = stepNames[n - 1];

  // Clean up selections from steps ahead of current step n
  if (n < 4) {
    state.box = null;
    state.boxCode = null;
    state.boxPrice = 0;
    setSpec('specBox', '—');
    document.querySelectorAll('.box-card').forEach(c => c.classList.remove('selected'));
  }

  if (n < 3) {
    state.frag = null;
    state.fragCode = null;
    setSpec('specFrag', '—');
    const previewFragBadge = document.getElementById('previewFragBadge');
    if (previewFragBadge) previewFragBadge.style.display = 'none';
    const previewNameEl = document.getElementById('previewName');
    if (previewNameEl) previewNameEl.textContent = '';
    document.querySelectorAll('.fragrance-card').forEach(c => c.classList.remove('selected'));
  }

  if (n < 2) {
    state.color = null;
    state.colorCode = null;
    state.colorHex = null;
    setSpec('specColor', '—');
    document.querySelectorAll('.color-card').forEach(c => c.classList.remove('selected'));
  }

  // If showing step 1, sync vessel card highlight and preview panel specs
  if (n === 1) {
    resetPreviewPanelStyle();
    if (state.vessel) {
      const vCards = Array.from(document.querySelectorAll('.vessel-card'));
      const cleanV = state.vessel.trim().toUpperCase();
      const vCard = vCards.find(c => (c.dataset.vessel || '').trim().toUpperCase() === cleanV);
      if (vCard) {
        document.querySelectorAll('.vessel-card').forEach(c => c.classList.remove('selected'));
        vCard.classList.add('selected');
        const vName = 'Vessel ' + state.vessel;
        const vDims = state.vessel === 'C' ? '3" × 3.5"' : (state.vessel === 'D' ? '3.5" × 4"' : '4" × 4.5"');
        setSpec('specVessel', vName + (vDims ? (' · ' + vDims) : ''));
        setSpec('specWick', vesselWickMap[state.vessel] || '—');
        updateWickDisplay(state.vessel);

        const vesselImgEl = vCard.querySelector('.vessel-img img');
        if (vesselImgEl && vesselImgEl.src && !vesselImgEl.src.endsWith('/')) {
          const previewImgEl = document.getElementById('previewImg');
          if (previewImgEl) previewImgEl.src = vesselImgEl.src;
          const imgWrap = document.getElementById('previewImgWrap');
          if (imgWrap) imgWrap.style.display = 'block';
          const pCard = document.getElementById('previewCard');
          if (pCard) pCard.style.display = 'none';
        }
      }
    }
  }

  // If showing step 3 (Fragrance), ensure fragrance grid is populated
  if (n === 3) {
    renderFragranceCards();
  }

  // If showing step 4 (Box), ensure box grid is populated
  if (n === 4) {
    renderBoxCards(state.vessel || 'C');
  }

  // Update SKU visibility (only show SKU value on Step 4 - last step)
  const skuEl = document.getElementById('specSku');
  if (skuEl) {
    if (n === 4 && typeof generateSKU === 'function') {
      skuEl.textContent = generateSKU();
      skuEl.classList.remove('empty');
    } else {
      skuEl.textContent = '—';
      skuEl.classList.add('empty');
    }
  }

  updatePreviewPanelTypography();

  window.scrollTo({ top: 0, behavior: 'smooth' });
  if (!skipSync) syncUrlState(isPush);
}

const dbColorsData = <?= json_encode($dbColors ?? []); ?>;

// ─── RENDER COLOR CARDS ──────────────────────────────────────────────────────
function renderColorCards(vessel) {
  const grid = document.getElementById('colorGrid');
  if (!grid) return;

  vessel = vessel || state.vessel || 'C';
  if (!state.vessel) {
    state.vessel = vessel;
  }

  grid.innerHTML = '';

  const colorsToRender = (dbColorsData && dbColorsData.length > 0) ? dbColorsData : colorData;

  if (!colorsToRender || colorsToRender.length === 0) {
    grid.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:#999;">No colors available right now.</p>';
    return;
  }

  colorsToRender.forEach(color => {
    const card = document.createElement('label');
    card.className = 'color-card';
    card.dataset.color = color.name;
    card.dataset.code = color.code || ('0' + (color.id || 1));
    const hexColor = color.hex || '#687382';
    card.dataset.hex = hexColor;

    let imgSrc = color.image || '';
    if (!imgSrc && color.images) {
      imgSrc = color.images[vessel] || color.images.C || '';
    }
    card.dataset.image = imgSrc;

    card.innerHTML = `
      <input type="radio" name="builder_vessel_color" value="${color.name}">
      <span class="radio-indicator"></span>
      <span class="color-swatch-dot" style="background:${hexColor};"></span>
      <span class="color-name-label">${color.name}</span>
    `;

    card.onclick = function() { selectColor(this); };
    grid.appendChild(card);
  });

  // Highlight matching color card if previously selected by user, or auto-select first color card
  if (state.color) {
    const allCards = Array.from(grid.querySelectorAll('.color-card'));
    const matchingCard = allCards.find(c => c.dataset.color === state.color || c.dataset.code === state.colorCode);
    if (matchingCard) {
      selectColor(matchingCard, true);
    } else {
      const firstCard = grid.querySelector('.color-card');
      if (firstCard) selectColor(firstCard, true);
    }
  } else {
    const firstCard = grid.querySelector('.color-card');
    if (firstCard) {
      selectColor(firstCard, true);
    }
  }
}

// ─── STEP 1: VESSEL ──────────────────────────────────────────────────────────
function selectVessel(el, skipSync) {
  if (!el) return;
  document.querySelectorAll('.vessel-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  state.vessel = el.dataset.vessel;
  state.vesselSku = el.dataset.sku || el.dataset.vessel;
  state.vesselPrice = parseInt(el.dataset.price) || 30;

  // Update right-side live preview image
  const vesselImgEl = el.querySelector('.vessel-img img');
  if (vesselImgEl && vesselImgEl.src && !vesselImgEl.src.endsWith('/')) {
    document.getElementById('previewImg').src = vesselImgEl.src;
    document.getElementById('previewImgWrap').style.display = 'block';
    document.getElementById('previewCard').style.display = 'none';
  }

  // Update preview specs
  const vesselNameEl = el.querySelector('.vessel-name');
  const dimsEl = el.querySelector('.vessel-dims');
  const wickEl = el.querySelector('.wick-badge');
  const vName = vesselNameEl ? vesselNameEl.textContent.trim() : ('Vessel ' + state.vessel);
  const vDims = dimsEl ? dimsEl.textContent.trim() : '';
  const vWick = wickEl ? wickEl.textContent.trim() : (vesselWickMap[state.vessel] || '—');

  setSpec('specVessel', vName + (vDims ? (' · ' + vDims) : ''));
  setSpec('specWick', vWick);

  // Update flame display
  updateWickDisplay(state.vessel);

  // Render color cards for this vessel (auto-selects first color and sets preview image)
  renderColorCards(state.vessel);

  // Update description
  const descEl = document.getElementById('colorDesc');
  if (descEl) {
    descEl.textContent = 'Select a finish for ' + vName + '.';
  }

  // Update box visibility based on vessel
  updateBoxVisibility(state.vessel);

  // Update fragrance next button text
  updateFragranceButton();

  recalcPrice();
  if (!skipSync) syncUrlState();
}

const dbBoxesData = <?= json_encode($dbBoxes ?? []); ?>;

// ─── RENDER BOX CARDS ────────────────────────────────────────────────────────
function renderBoxCards(vessel) {
  const grid = document.getElementById('boxGrid');
  if (!grid) return;

  grid.innerHTML = '';

  const boxesToRender = (dbBoxesData && dbBoxesData.length > 0) ? dbBoxesData : [
    { name: 'White Cubic Box', code: 'B01W', price: 6, image: 'img/box4.webp', description: 'White cubic keepsake box.' },
    { name: 'Black Cubic Box', code: 'B01B', price: 6, image: 'img/box2.webp', description: 'Black cubic keepsake box.' }
  ];

  const wickText = vessel === 'E' ? 'Triple wick' : (vessel === 'D' ? 'Double wick' : 'Single wick');

  boxesToRender.forEach(box => {
    const card = document.createElement('div');
    card.className = 'box-card';
    card.dataset.box = box.name;
    const defaultCode = (vessel === 'D' ? 'B02' : 'B01') + (box.name.toLowerCase().includes('black') ? 'B' : 'W');
    card.dataset.boxCode = box.code || defaultCode;
    card.dataset.price = box.price || 6;

    const fallbackImg = box.name.toLowerCase().includes('black') ? 'img/box2.webp' : (vessel === 'D' ? 'img/doublebox.webp' : 'img/box4.webp');
    const imgSrc = box.image || fallbackImg;
    const desc = box.description ? box.description : `${wickText} · ${box.name}`;

    card.innerHTML = `
      <div class="vessel-check"><svg viewBox="0 0 14 14" fill="none"><path d="M2 7L5.5 10.5L12 3.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
      <div class="box-img"><img src="${imgSrc}" alt="${box.name}" onerror="this.onerror=null; this.src='${fallbackImg}';"></div>
      <div class="box-info">
        <div class="box-title-row">
          <span class="box-name">${box.name}</span>
          <span class="box-price">+$${box.price}</span>
        </div>
        <div class="box-desc">${desc}</div>
      </div>
    `;

    card.onclick = function() { selectBox(this); };
    grid.appendChild(card);
  });

  // Highlight matching selected box if any
  if (state.box) {
    const allCards = Array.from(grid.querySelectorAll('.box-card'));
    const matchingCard = allCards.find(c => c.dataset.box === state.box || c.dataset.boxCode === state.boxCode || c.dataset.box.toLowerCase().includes(state.box.toLowerCase()) || state.box.toLowerCase().includes(c.dataset.box.toLowerCase()));
    if (matchingCard) matchingCard.classList.add('selected');
  }
}

const dbFragrancesData = <?= json_encode($dbFragrances ?? []); ?>;

// ─── RENDER FRAGRANCE CARDS ───────────────────────────────────────────────────
function renderFragranceCards() {
  const grid = document.getElementById('fragranceGrid');
  if (!grid) return;

  grid.innerHTML = '';
  const fragsToRender = (dbFragrancesData && dbFragrancesData.length > 0) ? dbFragrancesData : [
    { name: 'Amber Musk', code: '12', image: 'img/02 AMBER MUSK FRAGRANT.webp', description: 'A rich and comforting blend of warm amber and sensual musk.' },
    { name: 'Champagne Luxe', code: '13', image: 'img/05 CHAMPAGNE LUXE FRAGRANT.webp', description: 'A sparkling, sophisticated fragrance with bright citrus.' },
    { name: 'Citrus Agave Zest', code: '06', image: 'img/06 CITRUS AGAVE ZEST FRAGRANT.webp', description: 'Zesty citrus and sweet agave nectar.' },
    { name: 'Evening Tide', code: '08', image: 'img/08 EVENING TIDE FRAGRANT.webp', description: 'Cool coastal evening breeze.' },
    { name: 'Fragrance Free', code: '01', image: 'img/01 FRAGRABCE FREE.webp', description: 'Pure unscented candle.' },
    { name: "L'Attraction", code: '13', image: 'img/13 L_ATTRACTION FRAGRANT.webp', description: 'Alluring, mysterious floral note.' },
    { name: 'Lavender Fields', code: '09', image: 'img/09 LAVENDER FIELD FRAGRENT.webp', description: 'Calming French lavender fields.' },
    { name: 'Mahogany Woods', code: '11', image: 'img/11 MAHOGANY WOODS FREGRENT.webp', description: 'Deep mahogany and cedarwood.' },
    { name: 'Pine & Salt Air', code: '04', image: 'img/04 PINE & SALT AIR FRAGRANT.webp', description: 'Fresh pine with crisp ocean salt.' },
    { name: 'Vanilla Essence', code: '14', image: 'img/vanila_essence.webp', description: 'Warm Madagascar vanilla bean.' },
    { name: 'Wild Lemongrass', code: '10', image: 'img/10 WILD LEMONGRASS FRAGRANT.webp', description: 'Bright invigorating lemongrass.' }
  ];

  if (!fragsToRender || fragsToRender.length === 0) {
    grid.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:#999;">No fragrances available right now.</p>';
    return;
  }

  fragsToRender.forEach(frag => {
    const card = document.createElement('div');
    card.className = 'fragrance-card';
    card.dataset.frag = frag.name;
    const code = frag.code || fragCodeMap[frag.name] || '01';
    card.dataset.fragCode = code;
    const descText = frag.description || '';
    card.dataset.description = descText;

    fragCodeMap[frag.name] = code;

    const fallbackImg = 'img/02 AMBER MUSK FRAGRANT.webp';
    const imgSrc = frag.image || fallbackImg;
    const scentNoteImgSrc = frag.scent_note_image || imgSrc;

    const safeName = (frag.name || '').replace(/'/g, "\\'");
    const safeNoteImg = (scentNoteImgSrc || '').replace(/'/g, "\\'");
    const safeDesc = (descText || '').replace(/'/g, "\\'").replace(/\r?\n/g, "\\n");

    card.innerHTML = `
      <div class="vessel-check"><svg viewBox="0 0 14 14" fill="none"><path d="M2 7L5.5 10.5L12 3.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
      <div class="frag-img">
        <img src="${imgSrc}" alt="${frag.name}" onerror="this.onerror=null; this.src='${fallbackImg}';">
      </div>
      <div class="frag-info-bar">
        <span class="frag-label">${frag.name}</span>
        <button class="frag-view-btn" type="button" onclick="openFragModal(event, '${safeName}', '${safeNoteImg}', '${safeDesc}')">
          <svg class="eye-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg> View Notes
        </button>
      </div>
    `;

    card.onclick = function(e) { selectFrag(e, this); };
    grid.appendChild(card);
  });

  if (state.frag) {
    const allCards = Array.from(grid.querySelectorAll('.fragrance-card'));
    const cleanFrag = state.frag.trim().toLowerCase();
    const matchingCard = allCards.find(c => (c.dataset.frag || '').trim().toLowerCase() === cleanFrag);
    if (matchingCard) matchingCard.classList.add('selected');
  }
}

// ─── BOX VISIBILITY ──────────────────────────────────────────────────────────
function updateBoxVisibility(vessel) {
  const step4 = document.getElementById('step4');
  if (!step4) return;
  step4.classList.remove('step-box-hidden');
  document.querySelectorAll('.step-dot').forEach(dot => dot.style.display = '');
  renderBoxCards(vessel || state.vessel || 'C');
}

// ─── UPDATE FRAGRANCE BUTTON ────────────────────────────────────────────────
function updateFragranceButton() {
  const btn = document.getElementById('fragNextBtn');
  if (btn) {
    btn.textContent = 'CONTINUE';
  }
}

// ─── HANDLE FRAGRANCE NEXT ──────────────────────────────────────────────────
function handleFragranceNext() {
  if (!state.frag) {
    alert('Please select a fragrance first.');
    return;
  }
  goNext(4);
}

// ─── FLAME DISPLAY ───────────────────────────────────────────────────────────
function updateWickDisplay(vessel) {
  const flameContainer = document.getElementById('previewFlameContainer');
  if (!flameContainer) return;

  if (!vessel) {
    flameContainer.innerHTML = '';
    return;
  }

  const singleFlame = `<div class="flame-wrapper">
    <div class="flame-glow"></div>
    <svg class="flame-svg" width="24" height="36" viewBox="0 0 24 36" fill="none">
      <path d="M12 2C12 2 6 10 6 18C6 24 8.5 30 12 32C15.5 30 18 24 18 18C18 10 12 2 12 2Z" fill="#ff7700"/>
      <path d="M12 5C12 5 7.5 11.5 7.5 18C7.5 23 9.5 28 12 29.5C14.5 28 16.5 23 16.5 18C16.5 11.5 12 5 12 5Z" fill="#ffcc00"/>
      <path d="M12 11C12 11 9.5 15.5 9.5 20C9.5 23.5 10.5 26.5 12 27.5C13.5 26.5 14.5 23.5 14.5 20C14.5 15.5 12 11 12 11Z" fill="#ffffff" opacity="0.9"/>
      <ellipse cx="12" cy="31" rx="2.5" ry="1.2" fill="#38bdf8" opacity="0.8"/>
      <line x1="12" y1="31" x2="12" y2="36" stroke="#222" stroke-width="1.6" stroke-linecap="round"/>
    </svg>
  </div>`;

  if (vessel === 'C') {
    flameContainer.innerHTML = singleFlame;
  } else if (vessel === 'D') {
    flameContainer.innerHTML = `<div style="display:flex; gap:36px; justify-content:center; align-items:center;">${singleFlame}${singleFlame}</div>`;
  } else if (vessel === 'E') {
    flameContainer.innerHTML = `<div style="display:flex; gap:24px; justify-content:center; align-items:center;">${singleFlame}${singleFlame}${singleFlame}</div>`;
  } else {
    flameContainer.innerHTML = '';
  }
}

const defaultColorImageMap = {
  '02': '<?= base_url('/public/assets/img/color111.webp'); ?>',
  '03': '<?= base_url('/public/assets/img/color112.webp'); ?>',
  '05': '<?= base_url('/public/assets/img/color5.webp'); ?>',
  '07': '<?= base_url('/public/assets/img/color7.webp'); ?>',
  '08': '<?= base_url('/public/assets/img/color114.webp'); ?>',
  '09': '<?= base_url('/public/assets/img/color9.webp'); ?>',
  '12': '<?= base_url('/public/assets/img/color115.webp'); ?>',
  '13': '<?= base_url('/public/assets/img/color116.webp'); ?>',
  '14': '<?= base_url('/public/assets/img/color1.webp'); ?>',
  '15': '<?= base_url('/public/assets/img/color117.webp'); ?>',
  '16': '<?= base_url('/public/assets/img/color118.webp'); ?>',
  '17': '<?= base_url('/public/assets/img/color4.webp'); ?>',
  '18': '<?= base_url('/public/assets/img/color3.webp'); ?>',

  'White Frost': '<?= base_url('/public/assets/img/color111.webp'); ?>',
  'Black Matte': '<?= base_url('/public/assets/img/color112.webp'); ?>',
  'Peach Frost': '<?= base_url('/public/assets/img/color5.webp'); ?>',
  'Mocha Frost': '<?= base_url('/public/assets/img/color7.webp'); ?>',
  'Blue Frost': '<?= base_url('/public/assets/img/color114.webp'); ?>',
  'Purple Frost': '<?= base_url('/public/assets/img/color9.webp'); ?>',
  'Blush Pink': '<?= base_url('/public/assets/img/color115.webp'); ?>',
  'Charcoal Grey Matte': '<?= base_url('/public/assets/img/color116.webp'); ?>',
  'Charcoal Gray Matte': '<?= base_url('/public/assets/img/color116.webp'); ?>',
  'Gold Electroplate': '<?= base_url('/public/assets/img/color1.webp'); ?>',
  'Silver Electroplate': '<?= base_url('/public/assets/img/color117.webp'); ?>',
  'Smoky Grey Electroplate': '<?= base_url('/public/assets/img/color118.webp'); ?>',
  'Smokey Gray Electroplate': '<?= base_url('/public/assets/img/color118.webp'); ?>',
  'Green Frost Dark': '<?= base_url('/public/assets/img/color4.webp'); ?>',
  'Red Frost': '<?= base_url('/public/assets/img/color3.webp'); ?>'
};

// ─── RENDER COLOR CARDS ──────────────────────────────────────────────────────
function renderColorCards(vessel) {
  const grid = document.getElementById('colorGrid');
  if (!grid) return;

  vessel = vessel || state.vessel || 'C';
  if (!state.vessel) {
    state.vessel = vessel;
  }

  grid.innerHTML = '';

  const colorsToRender = (dbColorsData && dbColorsData.length > 0) ? dbColorsData : colorData;

  if (!colorsToRender || colorsToRender.length === 0) {
    grid.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:#999;">No colors available right now.</p>';
    return;
  }

  colorsToRender.forEach(color => {
    const card = document.createElement('label');
    card.className = 'color-card';
    card.dataset.color = color.name;
    const colorCode = color.code || color.sku || ('0' + (color.id || 1));
    card.dataset.code = colorCode;

    let imgSrc = color.image || '';
    if (!imgSrc || imgSrc.trim() === '' || imgSrc.endsWith('/')) {
      if (color.images) {
        imgSrc = color.images[vessel] || color.images.C || '';
      }
      if (!imgSrc || imgSrc.trim() === '' || imgSrc.endsWith('/')) {
        imgSrc = defaultColorImageMap[color.sku] || defaultColorImageMap[colorCode] || defaultColorImageMap[color.name] || '';
      }
    }
    card.dataset.image = imgSrc;

    const hexColor = color.hex || '#687382';
    card.dataset.hex = hexColor;

    card.innerHTML = `
      <input type="radio" name="builder_vessel_color" value="${color.name}">
      <span class="radio-indicator"></span>
      <span class="color-swatch-dot" style="background:${hexColor};"></span>
      <span class="color-name-label">${color.name}</span>
    `;

    card.onclick = function() { selectColor(this); };
    grid.appendChild(card);
  });

  // Highlight matching color card if previously selected by user, or auto-select first color card
  if (state.color) {
    const allCards = Array.from(grid.querySelectorAll('.color-card'));
    const matchingCard = allCards.find(c => c.dataset.color === state.color || c.dataset.code === state.colorCode);
    if (matchingCard) {
      selectColor(matchingCard, true);
    } else {
      const firstCard = grid.querySelector('.color-card');
      if (firstCard) selectColor(firstCard, true);
    }
  } else {
    const firstCard = grid.querySelector('.color-card');
    if (firstCard) {
      selectColor(firstCard, true);
    }
  }
}

// ─── LUMINANCE / CONTRAST HELPER ─────────────────────────────────────────────
function getContrastYIQ(hexcolor) {
  if (!hexcolor || hexcolor === 'transparent') return '#111827';
  hexcolor = hexcolor.replace('#', '').trim();
  if (hexcolor.length === 3) {
    hexcolor = hexcolor[0] + hexcolor[0] + hexcolor[1] + hexcolor[1] + hexcolor[2] + hexcolor[2];
  }
  if (hexcolor.length !== 6) return '#111827';
  const r = parseInt(hexcolor.substr(0, 2), 16);
  const g = parseInt(hexcolor.substr(2, 2), 16);
  const b = parseInt(hexcolor.substr(4, 2), 16);
  const yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
  return (yiq >= 155) ? '#111827' : '#FFFFFF';
}

// ─── STEP 2: COLOR ────────────────────────────────────────────────────────────
function selectColor(el, skipSync) {
  if (!el) return;

  // Reset all color cards back to default white background and dark text
  document.querySelectorAll('.color-card').forEach(c => {
    c.classList.remove('selected');
    c.style.backgroundColor = '#FFFFFF';
    c.style.borderColor = '#E5E7EB';
    c.style.color = '#1E293B';
    const label = c.querySelector('.color-name-label');
    if (label) {
      label.style.color = '#1E293B';
      label.style.fontWeight = 'normal';
    }
    const radInd = c.querySelector('.radio-indicator');
    if (radInd) {
      radInd.style.borderColor = '#CBD5E1';
      radInd.style.backgroundColor = '#FFFFFF';
    }
    const radInput = c.querySelector('input[type="radio"]');
    if (radInput) radInput.checked = false;
  });

  // Apply selected class and radio input state
  el.classList.add('selected');
  const activeRadInput = el.querySelector('input[type="radio"]');
  if (activeRadInput) activeRadInput.checked = true;

  state.color = el.dataset.color;
  state.colorCode = el.dataset.code;
  const hexColor = el.dataset.hex || '#687382';
  state.colorHex = hexColor;
  setSpec('specColor', state.color);

  // Apply background of selected color to option card container and calculate high contrast text
  const contrastColor = getContrastYIQ(hexColor);
  el.style.backgroundColor = hexColor;
  el.style.borderColor = (contrastColor === '#FFFFFF') ? 'rgba(255,255,255,0.85)' : 'var(--teal, #1b4d4f)';
  el.style.color = contrastColor;

  const activeLabel = el.querySelector('.color-name-label');
  if (activeLabel) {
    activeLabel.style.color = contrastColor;
    activeLabel.style.fontWeight = '600';
  }

  const activeRadInd = el.querySelector('.radio-indicator');
  if (activeRadInd) {
    activeRadInd.style.borderColor = contrastColor;
    activeRadInd.style.backgroundColor = (contrastColor === '#FFFFFF') ? 'rgba(255,255,255,0.25)' : 'rgba(0,0,0,0.1)';
  }

  // Update the whole right-side preview-panel background color and text contrast ONLY if on Step 2 or above
  if (typeof getCurrentStep === 'function' && getCurrentStep() > 1) {
    const previewPanel = document.querySelector('.preview-panel');
    if (previewPanel) {
      previewPanel.style.backgroundColor = hexColor;
      previewPanel.style.color = contrastColor;

      const previewLabel = previewPanel.querySelector('.preview-label');
      if (previewLabel) {
        previewLabel.style.color = (contrastColor === '#FFFFFF') ? 'rgba(255,255,255,0.75)' : 'var(--text-muted)';
      }

      const specRows = previewPanel.querySelectorAll('.spec-row');
      specRows.forEach(row => {
        const key = row.querySelector('.spec-key');
        const val = row.querySelector('.spec-val');
        if (key) {
          key.style.color = (contrastColor === '#FFFFFF') ? 'rgba(255,255,255,0.85)' : 'var(--text-muted)';
        }
        if (val) {
          if (!val.classList.contains('empty')) {
            val.style.color = contrastColor;
          } else {
            val.style.color = (contrastColor === '#FFFFFF') ? 'rgba(255,255,255,0.4)' : '#ccc';
          }
        }
      });

      const priceLabel = previewPanel.querySelector('.preview-price-label');
      const priceVal = previewPanel.querySelector('.preview-price-val');
      if (priceLabel) priceLabel.style.color = (contrastColor === '#FFFFFF') ? 'rgba(255,255,255,0.75)' : '#777777';
      if (priceVal) priceVal.style.color = contrastColor;

      const specsBox = previewPanel.querySelector('.preview-specs');
      if (specsBox) {
        specsBox.style.borderColor = (contrastColor === '#FFFFFF') ? 'rgba(255,255,255,0.2)' : 'rgba(0,0,0,0.1)';
      }
      const priceRow = previewPanel.querySelector('.preview-price-row');
      if (priceRow) {
        priceRow.style.borderColor = (contrastColor === '#FFFFFF') ? 'rgba(255,255,255,0.2)' : 'rgba(0,0,0,0.1)';
      }
    }
  } else {
    resetPreviewPanelStyle();
  }

  // Update right-side preview image ONLY if on Step 2 or above
  if (typeof getCurrentStep === 'function' && getCurrentStep() > 1) {
    let colorImg = el.dataset.image;
    if (!colorImg || colorImg.trim() === '' || colorImg.endsWith('/')) {
      colorImg = defaultColorImageMap[state.colorCode] || defaultColorImageMap[state.color] || '';
    }

    const previewImgEl = document.getElementById('previewImg');
    const previewImgWrap = document.getElementById('previewImgWrap');
    const previewCard = document.getElementById('previewCard');

    if (previewImgEl) {
      if (colorImg && colorImg.trim() !== '' && !colorImg.endsWith('/')) {
        previewImgEl.src = colorImg;
        if (previewImgWrap) {
          previewImgWrap.style.display = 'flex';
          previewImgWrap.style.backgroundColor = 'transparent';
        }
        if (previewCard) previewCard.style.display = 'none';
      }
    }
  } else {
    // On Step 1 (Choose your vessel), ensure the preview image always displays the selected vessel image
    const selVesselCard = document.querySelector(`.vessel-card[data-vessel="${state.vessel || 'C'}"]`);
    const vesselImgEl = selVesselCard ? selVesselCard.querySelector('.vessel-img img') : null;
    const previewImgEl = document.getElementById('previewImg');
    if (previewImgEl && vesselImgEl && vesselImgEl.src && !vesselImgEl.src.endsWith('/')) {
      previewImgEl.src = vesselImgEl.src;
      const imgWrap = document.getElementById('previewImgWrap');
      if (imgWrap) imgWrap.style.display = 'block';
      const pCard = document.getElementById('previewCard');
      if (pCard) pCard.style.display = 'none';
    }
  }

  recalcPrice();
  if (!skipSync) syncUrlState();
}

function formatFragranceDescription(text) {
  if (!text) return '';
  let formatted = text
    .replace(/\s*\|\s*(?=(TOP|MID|BASE):)/gi, '')
    .replace(/(TOP:)/gi, '<strong>$1</strong>')
    .replace(/(?:<br\/?>|\s)*(MID:)/gi, '<br><strong>$1</strong>')
    .replace(/(?:<br\/?>|\s)*(BASE:)/gi, '<br><strong>$1</strong>');
  formatted = formatted.replace(/^([^\n<]+)\s+(<strong>TOP:<\/strong>)/gi, '$1<br>$2');
  return formatted;
}

// ─── STEP 3: FRAGRANCE ────────────────────────────────────────────────────────
function selectFrag(event, el, skipSync) {
  if (event && event.target && event.target.closest('.frag-view-btn')) return;
  document.querySelectorAll('.fragrance-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  state.frag = el.dataset.frag;
  state.fragCode = fragCodeMap[state.frag] || '01';
  const fragDesc = el.dataset.description || '';

  setSpec('specFrag', state.frag);
  
  // Update candle text card name if visible
  const previewNameEl = document.getElementById('previewName');
  if (previewNameEl) {
    previewNameEl.textContent = state.frag;
  }

  // Update selected fragrance detail box in Step 3
  const detailBox = document.getElementById('selectedFragDetailBox');
  const detailTitle = document.getElementById('selectedFragDetailTitle');
  const detailDesc = document.getElementById('selectedFragDetailDesc');

  if (detailBox && detailTitle && detailDesc) {
    if (fragDesc) {
      detailTitle.textContent = state.frag;
      detailDesc.innerHTML = formatFragranceDescription(fragDesc);
      detailBox.style.display = 'block';
    } else {
      detailBox.style.display = 'none';
    }
  }

  // Update right side live preview fragrance badge
  const badgeEl = document.getElementById('previewFragBadge');
  const badgeTitleEl = document.getElementById('previewFragTitle');
  const badgeDescEl = document.getElementById('previewFragDesc');

  if (badgeEl && badgeTitleEl) {
    badgeTitleEl.textContent = state.frag;
    if (badgeDescEl) {
      badgeDescEl.innerHTML = formatFragranceDescription(fragDesc);
      badgeDescEl.style.display = fragDesc ? 'block' : 'none';
    }
    badgeEl.style.display = 'flex';
  }
  if (!skipSync) syncUrlState();
}

// ─── STEP 4: BOX ──────────────────────────────────────────────────────────────
function selectBox(el, skipSync) {
  if (!el) return;
  document.querySelectorAll('.box-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  state.box = el.dataset.box;
  state.boxCode = el.dataset.boxCode;
  state.boxPrice = parseInt(el.dataset.price) || 0;
  setSpec('specBox', `${state.box} (+$${state.boxPrice})`);
  recalcPrice();
  if (!skipSync) {
    syncUrlState();
    setTimeout(() => openReview(), 350);
  }
}

function checkoutWithoutPackaging() {
  document.querySelectorAll('.box-card').forEach(c => c.classList.remove('selected'));
  state.box = null;
  state.boxCode = null;
  state.boxPrice = 0;
  setSpec('specBox', 'No Packaging');
  recalcPrice();
  syncUrlState();
  openReview();
}

// ─── SKU GENERATION ───────────────────────────────────────────────────────────
function generateSKU() {
  const container = state.vesselSku || state.vessel || 'C';
  const colorCode = state.colorCode || '01';
  const fragCode = state.fragCode || '01';
  let boxCode = '';
  if (state.box && state.box !== 'No Packaging' && state.box !== '—' && state.box !== 'NONE') {
    boxCode = state.boxCode || 'B01W';
  }
  return `${container.toUpperCase()}${colorCode}${fragCode}${boxCode}`;
}

// ─── REVIEW MODAL ─────────────────────────────────────────────────────────────
function openReview() {
  const vesselLabels = {
    C: 'Vessel C · Single Wick · 3" × 3.5"',
    D: 'Vessel D · Double Wick · 3.5" × 4"',
    E: 'Vessel E · Triple Wick · 4" × 4.5"'
  };
  document.getElementById('mVessel').textContent = state.vessel ? vesselLabels[state.vessel] : '—';
  document.getElementById('mWick').textContent = state.vessel ? vesselWickMap[state.vessel] : '—';
  document.getElementById('mColor').textContent = state.color || '—';
  document.getElementById('mFrag').textContent = state.frag || '—';
  document.getElementById('mBox').textContent = state.box ? `${state.box} (+$${state.boxPrice})` : 'No Box';
  document.getElementById('mSku').textContent = generateSKU();

  // Show preview image
  const modalCard = document.getElementById('modalCardPreview');
  const modalImgWrap = document.getElementById('modalImgWrap');
  const modalPreviewImg = document.getElementById('modalPreviewImg');
  const previewImgEl = document.getElementById('previewImg');

  if (previewImgEl && previewImgEl.src && !previewImgEl.src.endsWith('/')) {
    modalPreviewImg.src = previewImgEl.src;
    modalImgWrap.style.display = 'flex';
    modalCard.style.display = 'none';
  } else {
    modalImgWrap.style.display = 'none';
    modalCard.style.display = 'flex';
  }

  // Flames in modal
  const modalFlameContainer = document.getElementById('modalFlameContainer');
  const sf = `<svg class="flame-svg" width="16" height="24" viewBox="0 0 24 36" fill="none">
    <path d="M12 2C12 2 6 10 6 18C6 24 8.5 30 12 32C15.5 30 18 24 18 18C18 10 12 2 12 2Z" fill="#f5a623" opacity="0.9"/>
    <path d="M12 8C12 8 9 14 9 19C9 23 10.5 27 12 28C13.5 27 15 23 15 19C15 14 12 8 12 8Z" fill="#fdd835" opacity="0.85"/>
    <line x1="12" y1="32" x2="12" y2="36" stroke="#555" stroke-width="1.5"/>
  </svg>`;

  if (state.vessel === 'E') {
    modalFlameContainer.innerHTML = `<div style="display:flex; gap:20px;">${sf}${sf}${sf}</div>`;
  } else if (state.vessel === 'D') {
    modalFlameContainer.innerHTML = `<div style="display:flex; gap:30px;">${sf}${sf}</div>`;
  } else {
    modalFlameContainer.innerHTML = sf;
  }

  document.getElementById('modalCandleName').textContent = 'laguna vibe';
  recalcPrice();
  document.getElementById('reviewModal').classList.add('open');
}

function closeReview() {
  document.getElementById('reviewModal').classList.remove('open');
}

// ─── UTILS ────────────────────────────────────────────────────────────────────
function setSpec(id, val) {
  const el = document.getElementById(id);
  if (el) {
    el.textContent = val || '—';
    el.classList.toggle('empty', !val || val === '—');
  }
  const skuEl = document.getElementById('specSku');
  if (skuEl && typeof generateSKU === 'function') {
    const currentStep = typeof getCurrentStep === 'function' ? getCurrentStep() : 1;
    if (currentStep === 4) {
      skuEl.textContent = generateSKU();
      skuEl.classList.remove('empty');
    } else {
      skuEl.textContent = '—';
      skuEl.classList.add('empty');
    }
  }
}

function recalcPrice() {
  const subtotal = (state.vesselPrice + state.boxPrice) * state.qty;
  const mSub = document.getElementById('mSubtotal');
  const mTot = document.getElementById('mTotal');
  if (mSub) mSub.textContent = `$${subtotal}.00`;
  if (mTot) mTot.textContent = `$${subtotal + 9}.00`;
}

function changeQty(delta) {
  state.qty = Math.max(1, Math.min(10, state.qty + delta));
  document.getElementById('qtyNum').textContent = state.qty;
  recalcPrice();
}

function applyPromo() {
  const code = document.getElementById('promoInput').value.trim().toUpperCase();
  if (code === 'LVB10') alert('Promo code applied: 10% off!');
  else if (code) alert('Invalid promo code.');
}

document.getElementById('reviewModal').addEventListener('click', function(e) {
  if (e.target === this) closeReview();
});

// ─── FRAGRANCE LIGHTBOX ───────────────────────────────────────────────────────
function openFragModal(e, name, imgSrc, desc) {
  e.stopPropagation();
  document.getElementById('fragModalTitle').textContent = name;
  document.getElementById('fragModalImg').src = imgSrc;
  document.getElementById('fragModalImg').alt = name;
  const descEl = document.getElementById('fragModalDesc');
  if (descEl) {
    descEl.innerHTML = formatFragranceDescription(desc || '');
    descEl.style.display = desc ? 'block' : 'none';
  }
  document.getElementById('fragModal').classList.add('open');
}

function closeFragModal() {
  document.getElementById('fragModal').classList.remove('open');
}

document.getElementById('fragModal').addEventListener('click', function(e) {
  if (e.target === this) closeFragModal();
});

// ─── BROKEN IMAGE FALLBACK & INITIALIZATION ───────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('img').forEach(img => {
    img.onerror = function() {
      this.style.backgroundColor = '#e2dcd5';
      this.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"%3E%3Ctext x="50%25" y="50%25" text-anchor="middle" dy=".3em" fill="%23888" font-family="Georgia" font-size="10"%3ELVB%3C/text%3E%3C/svg%3E';
    };
  });
  
  // 1. Try URL parameter restoration first (enables shareable links like builder/#step3?vessel=C&color=01&frag=AmberMusk)
  let restored = parseUrlState();
  
  // 2. If no URL params, try restoring from localStorage
  if (!restored) {
    restored = restoreBuilderState();
  }
  
  // 3. Fallback: if nothing restored, cleanly reset both grid and preview panel so they remain in 100% sync
  if (!restored) {
    resetBuilder();
  } else {
    // Ensure colors and fragrances are rendered
    const grid = document.getElementById('colorGrid');
    if (grid && grid.children.length === 0) {
      renderColorCards(state.vessel || 'C');
    }
    renderFragranceCards();
  }
});

// ─── CART INTEGRATION ─────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  const addToCartBtn = document.querySelector('.modal-actions .btn-cart');
  if (addToCartBtn) {
    const newBtn = addToCartBtn.cloneNode(true);
    addToCartBtn.parentNode.replaceChild(newBtn, addToCartBtn);
    newBtn.addEventListener('click', function(e) {
      e.preventDefault();
      if (typeof LVBCart === 'undefined') { alert('Cart system loading, please try again.'); return; }
      if (!state.vessel) { alert('Please select a vessel first'); closeReview(); showStep(1); return; }
      if (!state.color) { alert('Please select a color first'); closeReview(); showStep(2); return; }
      if (!state.frag) { alert('Please select a fragrance first'); closeReview(); showStep(3); return; }

      const vessel = state.vessel || 'C';
      const vesselName = 'Vessel ' + vessel;
      const wickType = vessel === 'C' ? 'Single Wick' : (vessel === 'D' ? 'Double Wick' : 'Triple Wick');
      const sizeId = vessel === 'C' ? 4 : (vessel === 'D' ? 2 : 3);
      const sizeName = `${vesselName} (${wickType})`;

      const colorName = state.color || 'Default Color';
      const colorCode = state.colorCode || '01';
      const fragName = state.frag || 'Default Scent';
      const fragCode = state.fragCode || '01';

      let boxName = (state.box && state.box !== 'No Packaging' && state.box !== '—') ? state.box : null;
      let boxPrice = state.boxPrice || 0;

      const sku = generateSKU();
      const itemPrice = (state.vesselPrice || 30) + boxPrice;

      const previewImgEl = document.getElementById('previewImg');
      const imageUrl = (previewImgEl && previewImgEl.src && !previewImgEl.src.endsWith('/')) ? previewImgEl.src : '';

      let productDisplayName = `${vesselName} · ${fragName}`;
      if (boxName) {
        productDisplayName += ` + ${boxName}`;
      }

      LVBCart.addItem({
        id: sku,
        sku: sku,
        name: productDisplayName,
        scent: sizeName,
        price: itemPrice,
        image: imageUrl,
        qty: state.qty || 1,
        product_id: null,
        size_id: sizeId,
        size_name: sizeName,
        box_id: state.boxCode || null,
        box_name: boxName,
        fragrance_id: fragCode,
        fragrance_name: fragName,
        color_name: colorName,
        color_code: colorCode,
        vessel: vessel,
        wick_type: wickType
      });

      closeReview();
      clearBuilderState();

      // Reset internal builder state
      state.vessel = null;
      state.vesselPrice = 0;
      state.color = null;
      state.colorCode = null;
      state.colorHex = null;
      state.frag = null;
      state.fragCode = null;
      state.box = null;
      state.boxCode = null;
      state.boxPrice = 0;
      state.qty = 1;

      // Reset specs UI
      setSpec('specVessel', '—');
      setSpec('specWick', '—');
      setSpec('specColor', '—');
      setSpec('specFrag', '—');
      setSpec('specBox', '—');

      // Deselect all cards
      document.querySelectorAll('.vessel-card, .color-card, .fragrance-card, .box-card').forEach(c => c.classList.remove('selected'));

      // Reset preview image & fragrance badge
      const previewImgWrap = document.getElementById('previewImgWrap');
      const previewCard = document.getElementById('previewCard');
      if (previewImgWrap) previewImgWrap.style.display = 'none';
      if (previewCard) previewCard.style.display = 'flex';

      const previewFragBadge = document.getElementById('previewFragBadge');
      if (previewFragBadge) previewFragBadge.style.display = 'none';

      // Reset URL to #step1 & switch to step 1
      const basePath = window.location.pathname.replace(/\/$/, '');
      window.history.replaceState({ step: 1 }, '', basePath + '#step1');
      showStep(1, true);

      showSuccessMessage('Added to cart (SKU: ' + sku + ')');
      LVBCart.open();
    });
  }
});

// Add the showSuccessMessage function (same as shop page)
function showSuccessMessage(msg) {
  const d = document.createElement('div');
  d.className = 'cart-success';
  d.textContent = msg;
  d.style.cssText = `
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #004b66;
    color: white;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 14px;
    z-index: 3000;
    animation: slideIn 0.3s ease-out;
  `;
  document.body.appendChild(d);
  setTimeout(function() { d.remove(); }, 3000);
}

// Add keyframe animation for success message
const styleSheet = document.createElement('style');
styleSheet.textContent = `
  @keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
  }
`;
document.head.appendChild(styleSheet);

// ─── INIT: preserve URL search parameters & handle step hash ─────────────────
if (!window.location.hash || !window.location.hash.startsWith('#step')) {
  const currentSearch = window.location.search || '';
  const currentStep = getCurrentStep();
  const newUrl = window.location.pathname + currentSearch + `#step${currentStep}`;
  history.replaceState({ step: currentStep }, '', newUrl);
} else {
  const stepPart = window.location.hash.split('?')[0];
  const stepNum = parseInt(stepPart.replace('#step', ''));
  if (stepNum >= 1 && stepNum <= 4) {
    showStep(stepNum, true);
  }
}
</script>
</body>
</html>