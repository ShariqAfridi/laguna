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
      grid-template-columns: 1fr 380px;
      min-height: calc(100vh - 60px);
      background: linear-gradient(to bottom, #F6FBFD, #DEEFF5);
      padding: 10px 50px;
    }

    .builder-main {
      padding: 60px 56px 80px;
      max-width: 940px;
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
      max-width: 600px;
      margin-bottom: 48px;
    }

    .preview-panel {
      background: #E2F1F7;
      position: relative;
      top: auto;
      height: fit-content;
      max-height: calc(200vh - 100px);
      display: flex;
      flex-direction: column;
      padding: 36px 32px;
      border-radius: 16px;
      box-shadow: 0 20px 50px rgba(0,0,0,0.08);
      overflow: hidden;
      margin-top: 20px;
    }

    .preview-label {
      font-size: 10px;
      letter-spacing: 0.2em;
      color: var(--text-muted);
      font-weight: 500;
      margin-bottom: 28px;
    }

    .preview-candle {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 28px;
      min-height: 280px;
      position: relative;
    }

    .candle-visual {
      width: 240px;
      height: 280px;
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .candle-card {
      width: 200px;
      height: 240px;
      background: var(--cream);
      border-radius: 2px;
      position: relative;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-end;
      padding-bottom: 24px;
      box-shadow: 0 3px 6px rgba(0,0,0,0.015), 0 7px 14px rgba(0,0,0,0.03), 0 12px 25px rgba(0,0,0,0.05), 0 20px 45px rgba(0,0,0,0.06);
      transition: background 0.4s, box-shadow 0.4s;
    }

    .candle-card-text {
      text-align: center;
      font-family: var(--serif);
      line-height: 1.4;
    }
    .candle-brand { font-size: 9px; letter-spacing: 0.15em; color: #999; }
    .candle-name { font-size: 15px; color: #555; font-style: italic; }
    .candle-loc { font-size: 9px; letter-spacing: 0.15em; color: #aaa; }

    .candle-flame {
      position: absolute;
      top: -34px;
      left: 50%;
      transform: translateX(-50%);
    }
    .flame-svg { animation: flicker 1.8s ease-in-out infinite alternate; }
    @keyframes flicker {
      0% { transform: scaleX(1) scaleY(1); opacity: 1; }
      50% { transform: scaleX(0.85) scaleY(1.08); opacity: 0.9; }
      100% { transform: scaleX(1.1) scaleY(0.95); opacity: 1; }
    }

    .candle-img-wrap {
      width: 200px;
      height: 240px;
      border-radius: 2px;
      overflow: hidden;
      display: none;
      box-shadow: 0 4px 20px rgba(0,0,0,0.12);
    }
    .candle-img-wrap img { width: 100%; height: 100%; object-fit: cover; }

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
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      margin-bottom: 48px;
    }

    .vessel-card {
      background: linear-gradient(to bottom, #EFF7FA 70%, #FFFFFF 60%);
      border: 1px solid #D1E5ED;
      border-radius: 12px;
      padding: 0;
      cursor: pointer;
      transition: all 0.2s ease-in-out;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      position: relative;
    }

    .vessel-img {
      width: 100%;
      aspect-ratio: 1 / 1;
      display: flex;
      align-items: center;
      justify-content: center;
      background: transparent;
      box-sizing: border-box;
    }

    .vessel-img img {
      display: block;
      width: 100%;
      height: 100%;
      object-fit: contain;
      object-position: center;
    }

    .vessel-info {
      padding: 20px 24px;
      background: #FFFFFF;
      flex-grow: 1;
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
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
      margin-bottom: 48px;
    }

    .color-card {
      background: #FFFFFF;
      border: 1px solid #D1E5ED;
      border-radius: 12px;
      padding: 0;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      transition: all 0.2s ease;
      cursor: pointer;
      position: relative;
    }

    .color-card.hidden {
      display: none;
    }

    .color-swatch {
      width: 100%;
      aspect-ratio: 1 / 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 25px 15px;
      background: transparent !important;
    }

    .color-swatch img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 6px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    .color-card-info {
      padding: 10px 20px;
      background: #FFFFFF;
      text-align: center;
      font-family: var(--sans);
    }

    .color-card:hover { border-color: #aaa; }
    .color-card.selected {
      border-color: var(--teal);
      box-shadow: 0 0 0 1px var(--teal);
    }

    .color-card-name { font-size: 13px; font-weight: 400; margin-bottom: 3px; }
    .color-card-type { font-size: 10px; letter-spacing: 0.12em; color: var(--text-muted); }

    .color-card .vessel-check { top: 10px; right: 10px; width: 22px; height: 22px; }
    .color-card .vessel-check svg { width: 11px; height: 11px; }

    .fragrance-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
      margin-bottom: 48px;
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
    }

    .frag-img {
      width: 100%;
      aspect-ratio: 130 / 90;
      overflow: hidden;
      background: #fff;
      flex-shrink: 0;
      padding: 0;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .frag-img img {
      display: block;
      width: 100%;
      height: 100%;
      object-fit: contain;
      object-position: center;
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
      font-size: 9px;
      font-weight: 500;
      letter-spacing: 0.12em;
      color: var(--text-muted);
      border: 1px solid #D1E5ED;
      border-radius: 40px;
      padding: 5px 14px;
      cursor: pointer;
      background: #fff;
      transition: color 0.2s, border-color 0.2s;
      text-transform: uppercase;
      white-space: nowrap;
      z-index: 3;
      position: relative;
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
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
    }

    .box-card {
      border: 1.5px solid var(--border);
      border-radius: 8px;
      cursor: pointer;
      transition: border-color 0.2s;
      position: relative;
      overflow: hidden;
      background: #fff;
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
      padding-top: 16px;
      border-top: 1px solid var(--border);
      max-width: 680px;
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
      <div class="vessel-grid" id="vesselGrid">

        <div class="vessel-card" data-vessel="C" data-price="30" onclick="selectVessel(this)">
          <div class="vessel-check"><svg viewBox="0 0 14 14" fill="none"><path d="M2 7L5.5 10.5L12 3.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
          <div class="vessel-img"><img src="img/vessel2.webp" alt="Vessel C"></div>
          <div class="vessel-info">
            <div class="vessel-title-row">
              <span class="vessel-name">Vessel C</span>
              <span class="vessel-hours">45 hours</span>
            </div>
            <div class="vessel-dims">3" DIAMETER × 3.5" HEIGHT</div>
            <div class="vessel-desc">A refined single-wick clear glass tumbler with a lower profile for everyday burning.</div>
            <div><span class="wick-badge">Single Wick</span></div>
          </div>
        </div>

        <div class="vessel-card" data-vessel="D" data-price="40" onclick="selectVessel(this)">
          <div class="vessel-check"><svg viewBox="0 0 14 14" fill="none"><path d="M2 7L5.5 10.5L12 3.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
          <div class="vessel-img"><img src="img/vessel1.webp" alt="Vessel D"></div>
          <div class="vessel-info">
            <div class="vessel-title-row">
              <span class="vessel-name">Vessel D</span>
              <span class="vessel-hours">60 hours</span>
            </div>
            <div class="vessel-dims">3.5" DIAMETER × 4" HEIGHT</div>
            <div class="vessel-desc">A sculptural double-wick clear glass silhouette with a longer, more even burn.</div>
            <div><span class="wick-badge">Double Wick</span></div>
          </div>
        </div>

        <div class="vessel-card" data-vessel="E" data-price="55" onclick="selectVessel(this)">
          <div class="vessel-check"><svg viewBox="0 0 14 14" fill="none"><path d="M2 7L5.5 10.5L12 3.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
          <div class="vessel-img"><img src="img/vessel3.webp" alt="Vessel E"></div>
          <div class="vessel-info">
            <div class="vessel-title-row">
              <span class="vessel-name">Vessel E</span>
              <span class="vessel-hours">80 hours</span>
            </div>
            <div class="vessel-dims">4" DIAMETER × 4.5" HEIGHT</div>
            <div class="vessel-desc">A statement triple-wick vessel with maximum fragrance throw and an extended burn.</div>
            <div><span class="wick-badge">Triple Wick</span></div>
          </div>
        </div>

      </div>
      <div class="step-nav"><span></span><button class="btn-next" onclick="goNext(2)">CONTINUE</button></div>
    </div>

    <!-- STEP 2: COLOR -->
    <div class="step-content" id="step2">
      <p class="step-label">STEP 02</p>
      <h1 class="step-title">Choose your color.</h1>
      <p class="step-desc" id="colorDesc">Select a finish for your vessel. Available colors vary by vessel.</p>
      <div class="color-grid" id="colorGrid">
        <!-- Color cards will be dynamically rendered by JavaScript -->
      </div>
      <div class="step-nav">
        <button class="btn-back" onclick="goBack(1)">
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M9 2L4 7L9 12" stroke="currentColor" stroke-width="1.5"/></svg> BACK
        </button>
        <button class="btn-next" onclick="goNext(3)">CONTINUE</button>
      </div>
    </div>

    <!-- STEP 3: FRAGRANCE -->
    <div class="step-content" id="step3">
      <p class="step-label">STEP 03</p>
      <h1 class="step-title">Choose your fragrance.</h1>
      <p class="step-desc">Tap any scent to read its full description and notes.</p>
      <div class="fragrance-grid">

        <div class="fragrance-card" data-frag="Amber Musk" onclick="selectFrag(event, this)">
          <div class="vessel-check"><svg viewBox="0 0 14 14" fill="none"><path d="M2 7L5.5 10.5L12 3.5" stroke="white" stroke-width="2"/></svg></div>
          <div class="frag-img"><img src="img/02 AMBER MUSK FRAGRANT.webp" alt="Amber Musk"></div>
          <div class="frag-info-bar">
            <span class="frag-label">Amber Musk</span>
            <button class="frag-view-btn" onclick="openFragModal(event,'Amber Musk','img/02 AMBER MUSK FRAGRANT.webp')">View Larger</button>
          </div>
        </div>

        <div class="fragrance-card" data-frag="Champagne Luxe" onclick="selectFrag(event, this)">
          <div class="vessel-check"><svg viewBox="0 0 14 14" fill="none"><path d="M2 7L5.5 10.5L12 3.5" stroke="white" stroke-width="2"/></svg></div>
          <div class="frag-img"><img src="img/05 CHAMPAGNE LUXE FRAGRANT.webp" alt="Champagne Luxe"></div>
          <div class="frag-info-bar">
            <span class="frag-label">Champagne Luxe</span>
            <button class="frag-view-btn" onclick="openFragModal(event,'Champagne Luxe','img/05 CHAMPAGNE LUXE FRAGRANT.webp')">View Larger</button>
          </div>
        </div>

        <div class="fragrance-card" data-frag="Citrus Agave Zest" onclick="selectFrag(event, this)">
          <div class="vessel-check"><svg viewBox="0 0 14 14" fill="none"><path d="M2 7L5.5 10.5L12 3.5" stroke="white" stroke-width="2"/></svg></div>
          <div class="frag-img"><img src="img/06 CITRUS AGAVE ZEST FRAGRANT.webp" alt="Citrus Agave Zest"></div>
          <div class="frag-info-bar">
            <span class="frag-label">Citrus Agave Zest</span>
            <button class="frag-view-btn" onclick="openFragModal(event,'Citrus Agave Zest','img/06 CITRUS AGAVE ZEST FRAGRANT.webp')">View Larger</button>
          </div>
        </div>

        <div class="fragrance-card" data-frag="Evening Tide" onclick="selectFrag(event, this)">
          <div class="vessel-check"><svg viewBox="0 0 14 14" fill="none"><path d="M2 7L5.5 10.5L12 3.5" stroke="white" stroke-width="2"/></svg></div>
          <div class="frag-img"><img src="img/08 EVENING TIDE FRAGRANT.webp" alt="Evening Tide"></div>
          <div class="frag-info-bar">
            <span class="frag-label">Evening Tide</span>
            <button class="frag-view-btn" onclick="openFragModal(event,'Evening Tide','img/08 EVENING TIDE FRAGRANT.webp')">View Larger</button>
          </div>
        </div>

        <div class="fragrance-card" data-frag="Fragrance Free" onclick="selectFrag(event, this)">
          <div class="vessel-check"><svg viewBox="0 0 14 14" fill="none"><path d="M2 7L5.5 10.5L12 3.5" stroke="white" stroke-width="2"/></svg></div>
          <div class="frag-img"><img src="img/01 FRAGRABCE FREE.webp" alt="Fragrance Free"></div>
          <div class="frag-info-bar">
            <span class="frag-label">Fragrance Free</span>
            <button class="frag-view-btn" onclick="openFragModal(event,'Fragrance Free','img/01 FRAGRABCE FREE.webp')">View Larger</button>
          </div>
        </div>

        <div class="fragrance-card" data-frag="L'Attraction" onclick="selectFrag(event, this)">
          <div class="vessel-check"><svg viewBox="0 0 14 14" fill="none"><path d="M2 7L5.5 10.5L12 3.5" stroke="white" stroke-width="2"/></svg></div>
          <div class="frag-img"><img src="img/13 L_ATTRACTION FRAGRANT.webp" alt="L'Attraction"></div>
          <div class="frag-info-bar">
            <span class="frag-label">L'Attraction</span>
            <button class="frag-view-btn" onclick="openFragModal(event,'L\'Attraction','img/13 L_ATTRACTION FRAGRANT.webp')">View Larger</button>
          </div>
        </div>

        <div class="fragrance-card" data-frag="Lavender Fields" onclick="selectFrag(event, this)">
          <div class="vessel-check"><svg viewBox="0 0 14 14" fill="none"><path d="M2 7L5.5 10.5L12 3.5" stroke="white" stroke-width="2"/></svg></div>
          <div class="frag-img"><img src="img/09 LAVENDER FIELD FRAGRENT.webp" alt="Lavender Fields"></div>
          <div class="frag-info-bar">
            <span class="frag-label">Lavender Fields</span>
            <button class="frag-view-btn" onclick="openFragModal(event,'Lavender Fields','img/09 LAVENDER FIELD FRAGRENT.webp')">View Larger</button>
          </div>
        </div>

        <div class="fragrance-card" data-frag="Mahogany Woods" onclick="selectFrag(event, this)">
          <div class="vessel-check"><svg viewBox="0 0 14 14" fill="none"><path d="M2 7L5.5 10.5L12 3.5" stroke="white" stroke-width="2"/></svg></div>
          <div class="frag-img"><img src="img/11 MAHOGANY WOODS FREGRENT.webp" alt="Mahogany Woods"></div>
          <div class="frag-info-bar">
            <span class="frag-label">Mahogany Woods</span>
            <button class="frag-view-btn" onclick="openFragModal(event,'Mahogany Woods','img/11 MAHOGANY WOODS FREGRENT.webp')">View Larger</button>
          </div>
        </div>

        <div class="fragrance-card" data-frag="Pine & Salt Air" onclick="selectFrag(event, this)">
          <div class="vessel-check"><svg viewBox="0 0 14 14" fill="none"><path d="M2 7L5.5 10.5L12 3.5" stroke="white" stroke-width="2"/></svg></div>
          <div class="frag-img"><img src="img/04 PINE & SALT AIR FRAGRANT.webp" alt="Pine & Salt Air"></div>
          <div class="frag-info-bar">
            <span class="frag-label">Pine &amp; Salt Air</span>
            <button class="frag-view-btn" onclick="openFragModal(event,'Pine & Salt Air','img/04 PINE & SALT AIR FRAGRANT.webp')">View Larger</button>
          </div>
        </div>

        <div class="fragrance-card" data-frag="Vanilla Essence" onclick="selectFrag(event, this)">
          <div class="vessel-check"><svg viewBox="0 0 14 14" fill="none"><path d="M2 7L5.5 10.5L12 3.5" stroke="white" stroke-width="2"/></svg></div>
          <div class="frag-img"><img src="img/vanila_essence.webp" alt="Vanilla Essence"></div>
          <div class="frag-info-bar">
            <span class="frag-label">Vanilla Essence</span>
            <button class="frag-view-btn" onclick="openFragModal(event,'Vanilla Essence','img/vanila_essence.webp')">View Larger</button>
          </div>
        </div>

        <div class="fragrance-card" data-frag="Wild Lemongrass" onclick="selectFrag(event, this)">
          <div class="vessel-check"><svg viewBox="0 0 14 14" fill="none"><path d="M2 7L5.5 10.5L12 3.5" stroke="white" stroke-width="2"/></svg></div>
          <div class="frag-img"><img src="img/10 WILD LEMONGRASS FRAGRANT.webp" alt="Wild Lemongrass"></div>
          <div class="frag-info-bar">
            <span class="frag-label">Wild Lemongrass</span>
            <button class="frag-view-btn" onclick="openFragModal(event,'Wild Lemongrass','img/10 WILD LEMONGRASS FRAGRANT.webp')">View Larger</button>
          </div>
        </div>

      </div>
      <div class="step-nav">
        <button class="btn-back" onclick="goBack(2)">
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M9 2L4 7L9 12" stroke="currentColor" stroke-width="1.5"/></svg> BACK
        </button>
        <button class="btn-next" id="fragNextBtn" onclick="handleFragranceNext()">CONTINUE</button>
      </div>
    </div>

    <!-- STEP 4: BOX (OPTIONAL) - hidden for Vessel E -->
    <div class="step-content" id="step4">
      <p class="step-label">STEP 04</p>
      <h1 class="step-title">Add a keepsake box.</h1>
      <p class="step-desc">Optional — choose a cubic box in white or black. The box matches the wick count of your vessel. You can also skip this step.</p>
      <div class="box-grid" id="boxGrid">

        <div class="box-card" data-box="Single Wick White Cubic Box" data-box-code="B01W" data-price="6" data-wick="single" onclick="selectBox(this)">
          <div class="vessel-check"><svg viewBox="0 0 14 14" fill="none"><path d="M2 7L5.5 10.5L12 3.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
          <div class="box-img"><img src="img/box4.webp" alt="Single Wick White Cubic Box"></div>
          <div class="box-info">
            <div class="box-title-row">
              <span class="box-name">White Cubic Box</span>
              <span class="box-price">+$6</span>
            </div>
            <div class="box-desc">Single wick · White cubic keepsake box.</div>
          </div>
        </div>

        <div class="box-card" data-box="Single Wick Black Cubic Box" data-box-code="B01B" data-price="6" data-wick="single" onclick="selectBox(this)">
          <div class="vessel-check"><svg viewBox="0 0 14 14" fill="none"><path d="M2 7L5.5 10.5L12 3.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
          <div class="box-img"><img src="img/box2.webp" alt="Single Wick Black Cubic Box"></div>
          <div class="box-info">
            <div class="box-title-row">
              <span class="box-name">Black Cubic Box</span>
              <span class="box-price">+$6</span>
            </div>
            <div class="box-desc">Single wick · Black cubic keepsake box.</div>
          </div>
        </div>

        <div class="box-card" data-box="Double Wick White Cubic Box" data-box-code="B02W" data-price="6" data-wick="double" onclick="selectBox(this)">
          <div class="vessel-check"><svg viewBox="0 0 14 14" fill="none"><path d="M2 7L5.5 10.5L12 3.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
          <div class="box-img"><img src="img/doublebox.webp" alt="Double Wick White Cubic Box"></div>
          <div class="box-info">
            <div class="box-title-row">
              <span class="box-name">White Cubic Box</span>
              <span class="box-price">+$6</span>
            </div>
            <div class="box-desc">Double wick · White cubic keepsake box.</div>
          </div>
        </div>

        <div class="box-card" data-box="Double Wick Black Cubic Box" data-box-code="B02B" data-price="6" data-wick="double" onclick="selectBox(this)">
          <div class="vessel-check"><svg viewBox="0 0 14 14" fill="none"><path d="M2 7L5.5 10.5L12 3.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
          <div class="box-img"><img src="img/doubleboxb.webp" alt="Double Wick Black Cubic Box"></div>
          <div class="box-info">
            <div class="box-title-row">
              <span class="box-name">Black Cubic Box</span>
              <span class="box-price">+$6</span>
            </div>
            <div class="box-desc">Double wick · Black cubic keepsake box.</div>
          </div>
        </div>

      </div>
      <div class="step-nav">
        <button class="btn-back" onclick="goBack(3)">
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M9 2L4 7L9 12" stroke="currentColor" stroke-width="1.5"/></svg> BACK
        </button>
        <button class="btn-next" onclick="openReview()">REVIEW ORDER</button>
      </div>
    </div>

  </div><!-- end .builder-main -->

  <!-- PREVIEW PANEL -->
  <div class="preview-panel">
    <div class="preview-label">LIVE PREVIEW</div>
    <div class="preview-candle">
      <div class="candle-visual">
        <div class="candle-flame" id="previewFlameContainer">
          <svg class="flame-svg" width="24" height="36" viewBox="0 0 24 36" fill="none">
            <path d="M12 2C12 2 6 10 6 18C6 24 8.5 30 12 32C15.5 30 18 24 18 18C18 10 12 2 12 2Z" fill="#f5a623" opacity="0.9"/>
            <path d="M12 8C12 8 9 14 9 19C9 23 10.5 27 12 28C13.5 27 15 23 15 19C15 14 12 8 12 8Z" fill="#fdd835" opacity="0.85"/>
            <line x1="12" y1="32" x2="12" y2="36" stroke="#555" stroke-width="1.5"/>
          </svg>
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
    </div>
    <div class="preview-specs">
      <div class="spec-row"><span class="spec-key">VESSEL</span><span class="spec-val empty" id="specVessel">—</span></div>
      <div class="spec-row"><span class="spec-key">WICK</span><span class="spec-val empty" id="specWick">—</span></div>
      <div class="spec-row"><span class="spec-key">COLOR</span><span class="spec-val empty" id="specColor">—</span></div>
      <div class="spec-row"><span class="spec-key">FRAGRANCE</span><span class="spec-val empty" id="specFrag">—</span></div>
      <div class="spec-row"><span class="spec-key">LID</span><span class="spec-val" id="specLid">Black Lid</span></div>
      <div class="spec-row"><span class="spec-key">BOX</span><span class="spec-val empty" id="specBox">—</span></div>
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
  </div>
</div>

<script src="/views/cart.js"></script>
<script>
// ─── STATE ───────────────────────────────────────────────────────────────────
const state = {
  vessel: null,
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

// ─── NAVIGATION ──────────────────────────────────────────────────────────────
window.addEventListener('popstate', function(e) {
  const currentStep = getCurrentStep();
  if (currentStep > 1) {
    showStep(currentStep - 1);
  }
});

function getCurrentStep() {
  for (let i = 1; i <= 4; i++) {
    if (document.getElementById('step' + i).classList.contains('active')) return i;
  }
  return 1;
}

function goNext(step) {
  history.pushState({ step }, '', '#step' + step);
  showStep(step);
}

function goBack(step) {
  history.pushState({ step }, '', '#step' + step);
  showStep(step);
}

function showStep(n) {
  document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
  document.getElementById('step' + n).classList.add('active');
  for (let i = 1; i <= 4; i++) {
    const dot = document.getElementById('dot' + i);
    dot.className = 'step-dot';
    if (i < n) dot.classList.add('done');
    if (i === n) dot.classList.add('active');
  }
  document.getElementById('stepCount').textContent = `STEP ${n} OF 4`;
  document.getElementById('stepNameLabel').textContent = stepNames[n - 1];
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ─── RENDER COLOR CARDS ──────────────────────────────────────────────────────
function renderColorCards(vessel) {
  const grid = document.getElementById('colorGrid');
  grid.innerHTML = '';
  
  // Get colors available for this vessel
  const availableColors = colorData.filter(c => c.vessels.includes(vessel));
  
  if (availableColors.length === 0) {
    grid.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:#999;">No colors available for this vessel.</p>';
    return;
  }
  
  availableColors.forEach(color => {
    const card = document.createElement('div');
    card.className = 'color-card';
    card.dataset.color = color.name;
    card.dataset.code = color.code;
    card.dataset.type = color.type;
    card.dataset.vessels = color.vessels.join(',');
    
    // Get the image for this vessel
    const imgSrc = color.images[vessel] || color.images.C || 'img/placeholder.webp';
    
    card.innerHTML = `
      <div class="vessel-check"><svg viewBox="0 0 14 14" fill="none"><path d="M2 7L5.5 10.5L12 3.5" stroke="white" stroke-width="2"/></svg></div>
      <div class="color-swatch"><img src="${imgSrc}" alt="${color.name}"></div>
      <div class="color-card-info">
        <div class="color-card-name">${color.name}</div>
        <div class="color-card-type">${color.type}</div>
      </div>
    `;
    
    card.onclick = function() { selectColor(this); };
    grid.appendChild(card);
  });
}

// ─── STEP 1: VESSEL ──────────────────────────────────────────────────────────
function selectVessel(el) {
  document.querySelectorAll('.vessel-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  state.vessel = el.dataset.vessel;
  state.vesselPrice = parseInt(el.dataset.price) || 30;

  // Update preview
  const dims = {
    C: 'Vessel C · 3" × 3.5"',
    D: 'Vessel D · 3.5" × 4"',
    E: 'Vessel E · 4" × 4.5"'
  };
  setSpec('specVessel', dims[state.vessel] || state.vessel);
  setSpec('specWick', vesselWickMap[state.vessel] || '—');

  // Update candle card height
  const card = document.getElementById('previewCard');
  if (state.vessel === 'E') card.style.height = '290px';
  else if (state.vessel === 'D') card.style.height = '265px';
  else card.style.height = '240px';

  // Update flame display
  updateWickDisplay(state.vessel);

  // Render color cards for this vessel
  renderColorCards(state.vessel);

  // Clear previously selected color
  document.querySelectorAll('.color-card').forEach(c => c.classList.remove('selected'));
  state.color = null;
  state.colorCode = null;
  setSpec('specColor', '—');
  document.getElementById('previewCard').style.display = 'flex';
  document.getElementById('previewImgWrap').style.display = 'none';

  // Update description
  const descEl = document.getElementById('colorDesc');
  if (state.vessel === 'C') {
    descEl.textContent = 'Choose from all 9 available finishes for Vessel C.';
  } else if (state.vessel === 'D') {
    descEl.textContent = 'Six finishes available for Vessel D.';
  } else if (state.vessel === 'E') {
    descEl.textContent = 'Two finishes available for Vessel E.';
  }

  // Update box visibility based on vessel
  updateBoxVisibility(state.vessel);

  // Update fragrance next button text
  updateFragranceButton();

  recalcPrice();
  setTimeout(() => goNext(2), 350);
}

// ─── BOX VISIBILITY ──────────────────────────────────────────────────────────
function updateBoxVisibility(vessel) {
  const step4 = document.getElementById('step4');
  const boxGrid = document.querySelector('.box-grid');
  
  if (vessel === 'E') {
    // Hide box step completely
    step4.classList.add('step-box-hidden');
    // Reset box selection
    document.querySelectorAll('.box-card').forEach(c => c.classList.remove('selected'));
    state.box = null;
    state.boxCode = null;
    state.boxPrice = 0;
    setSpec('specBox', '—');
    // Update step dots - we have 3 steps for vessel E
    document.querySelectorAll('.step-dot').forEach((dot, idx) => {
      if (idx === 3) dot.style.display = 'none'; // hide 4th dot
    });
  } else {
    step4.classList.remove('step-box-hidden');
    document.querySelectorAll('.step-dot').forEach(dot => dot.style.display = '');
    // show/hide based on wick
    const allBoxCards = document.querySelectorAll('.box-card');
    allBoxCards.forEach(card => {
      const wick = card.dataset.wick;
      if (vessel === 'C' && wick === 'single') {
        card.style.display = '';
      } else if (vessel === 'D' && wick === 'double') {
        card.style.display = '';
      } else {
        card.style.display = 'none';
      }
    });
  }
}

// ─── UPDATE FRAGRANCE BUTTON ────────────────────────────────────────────────
function updateFragranceButton() {
  const btn = document.getElementById('fragNextBtn');
  if (state.vessel === 'E') {
    btn.textContent = 'REVIEW ORDER';
  } else {
    btn.textContent = 'CONTINUE';
  }
}

// ─── HANDLE FRAGRANCE NEXT ──────────────────────────────────────────────────
function handleFragranceNext() {
  if (!state.frag) {
    alert('Please select a fragrance first.');
    return;
  }
  if (state.vessel === 'E') {
    // Skip box step, go directly to review
    openReview();
  } else {
    goNext(4);
  }
}

// ─── FLAME DISPLAY ───────────────────────────────────────────────────────────
function updateWickDisplay(vessel) {
  const flameContainer = document.getElementById('previewFlameContainer');
  const singleFlame = `<svg class="flame-svg" width="20" height="30" viewBox="0 0 24 36" fill="none">
    <path d="M12 2C12 2 6 10 6 18C6 24 8.5 30 12 32C15.5 30 18 24 18 18C18 10 12 2 12 2Z" fill="#f5a623" opacity="0.9"/>
    <path d="M12 8C12 8 9 14 9 19C9 23 10.5 27 12 28C13.5 27 15 23 15 19C15 14 12 8 12 8Z" fill="#fdd835" opacity="0.85"/>
    <line x1="12" y1="32" x2="12" y2="36" stroke="#555" stroke-width="1.5"/>
  </svg>`;

  if (vessel === 'C') {
    flameContainer.innerHTML = `<svg class="flame-svg" width="24" height="36" viewBox="0 0 24 36" fill="none">
      <path d="M12 2C12 2 6 10 6 18C6 24 8.5 30 12 32C15.5 30 18 24 18 18C18 10 12 2 12 2Z" fill="#f5a623" opacity="0.9"/>
      <path d="M12 8C12 8 9 14 9 19C9 23 10.5 27 12 28C13.5 27 15 23 15 19C15 14 12 8 12 8Z" fill="#fdd835" opacity="0.85"/>
      <line x1="12" y1="32" x2="12" y2="36" stroke="#555" stroke-width="1.5"/>
    </svg>`;
  } else if (vessel === 'D') {
    flameContainer.innerHTML = `<div style="display:flex; gap:36px; justify-content:center; align-items:center;">${singleFlame}${singleFlame}</div>`;
  } else if (vessel === 'E') {
    flameContainer.innerHTML = `<div style="display:flex; gap:24px; justify-content:center; align-items:center;">${singleFlame}${singleFlame}${singleFlame}</div>`;
  }
}

// ─── STEP 2: COLOR ────────────────────────────────────────────────────────────
function selectColor(el) {
  document.querySelectorAll('.color-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  state.color = el.dataset.color;
  state.colorCode = el.dataset.code;
  setSpec('specColor', state.color);

  // Get the image for the current vessel
  const vessel = state.vessel || 'C';
  const colorInfo = colorData.find(c => c.name === state.color);
  
  if (colorInfo && colorInfo.images[vessel]) {
    const imgSrc = colorInfo.images[vessel];
    document.getElementById('previewImg').src = imgSrc;
    document.getElementById('previewCard').style.display = 'none';
    document.getElementById('previewImgWrap').style.display = 'block';
  }

  recalcPrice();
  setTimeout(() => goNext(3), 350);
}

// ─── STEP 3: FRAGRANCE ────────────────────────────────────────────────────────
function selectFrag(event, el) {
  if (event.target.closest('.frag-view-btn')) return;
  document.querySelectorAll('.fragrance-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  state.frag = el.dataset.frag;
  state.fragCode = fragCodeMap[state.frag] || '01';
  setSpec('specFrag', state.frag);
  
  // Auto-advance after fragrance selection
  setTimeout(() => handleFragranceNext(), 400);
}

// ─── STEP 4: BOX ──────────────────────────────────────────────────────────────
function selectBox(el) {
  if (state.vessel === 'E') {
    alert('Keepsake boxes are not available for Vessel E.');
    return;
  }
  document.querySelectorAll('.box-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  state.box = el.dataset.box;
  state.boxCode = el.dataset.boxCode;
  state.boxPrice = parseInt(el.dataset.price) || 0;
  setSpec('specBox', `${state.box} (+$${state.boxPrice})`);
  recalcPrice();
  setTimeout(() => openReview(), 350);
}

// ─── SKU GENERATION ───────────────────────────────────────────────────────────
function generateSKU() {
  const container = state.vessel || 'C';
  const colorCode = state.colorCode || '01';
  const fragCode = state.fragCode || '01';
  const boxCode = state.boxCode || 'B01W';
  return `${container}${colorCode}${fragCode}${boxCode}`;
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
    modalFlameContainer.innerHTML = `<svg class="flame-svg" width="20" height="30" viewBox="0 0 24 36" fill="none">
      <path d="M12 2C12 2 6 10 6 18C6 24 8.5 30 12 32C15.5 30 18 24 18 18C18 10 12 2 12 2Z" fill="#f5a623" opacity="0.9"/>
      <path d="M12 8C12 8 9 14 9 19C9 23 10.5 27 12 28C13.5 27 15 23 15 19C15 14 12 8 12 8Z" fill="#fdd835" opacity="0.85"/>
      <line x1="12" y1="32" x2="12" y2="36" stroke="#555" stroke-width="1.5"/>
    </svg>`;
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
function openFragModal(e, name, imgSrc) {
  e.stopPropagation();
  document.getElementById('fragModalTitle').textContent = name;
  document.getElementById('fragModalImg').src = imgSrc;
  document.getElementById('fragModalImg').alt = name;
  document.getElementById('fragModal').classList.add('open');
}

function closeFragModal() {
  document.getElementById('fragModal').classList.remove('open');
}

document.getElementById('fragModal').addEventListener('click', function(e) {
  if (e.target === this) closeFragModal();
});

// ─── BROKEN IMAGE FALLBACK ────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('img').forEach(img => {
    img.onerror = function() {
      this.style.backgroundColor = '#e2dcd5';
      this.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"%3E%3Ctext x="50%25" y="50%25" text-anchor="middle" dy=".3em" fill="%23888" font-family="Georgia" font-size="10"%3ELVB%3C/text%3E%3C/svg%3E';
    };
  });
  
  // Initialize with default vessel (C)
  const defaultVessel = document.querySelector('.vessel-card[data-vessel="C"]');
  if (defaultVessel) {
    defaultVessel.classList.add('selected');
    state.vessel = 'C';
    state.vesselPrice = 30;
    renderColorCards('C');
    setSpec('specVessel', 'Vessel C · 3" × 3.5"');
    setSpec('specWick', 'Single Wick');
    updateWickDisplay('C');
    updateBoxVisibility('C');
    updateFragranceButton();
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
      
      // Generate proper SKU using the same format as the shop page
      const vesselMap = { C: 'C', D: 'D', E: 'E' };
      const container = vesselMap[state.vessel] || 'C';
      
      // Map vessel to size ID (matching shop page logic)
      const vesselSizeId = state.vessel === 'C' ? 4 : (state.vessel === 'D' ? 2 : 3);
      
      // Get box ID from box code
      const boxIdMap = {
        'B01W': 1,
        'B02W': 3,
        'B01B': 2,
        'B02B': 4
      };
      const boxId = state.boxCode ? boxIdMap[state.boxCode] || null : null;
      
      // Get size ID for SKU generation (matching shop page)
      const sizeId = state.vessel === 'C' ? 4 : (state.vessel === 'D' ? 2 : 3);
      
      // Generate SKU using the same logic as shop page
      function generateSKU(sizeId, colorCode, fragCode, boxId) {
        const containerMap = { 2: 'D', 4: 'C', 3: 'E' };
        const container = containerMap[sizeId] || 'C';
        
        const boxMap = { 1: 'B01W', 2: 'B01B', 3: 'B02W', 4: 'B02B' };
        const boxCode = (boxId && boxMap[boxId]) ? boxMap[boxId] : 'B01W';
        
        return container + colorCode + fragCode + boxCode;
      }
      
      const sku = generateSKU(sizeId, state.colorCode, state.fragCode, boxId);
      
      const itemPrice = state.vesselPrice + state.boxPrice;
      const previewImgEl = document.getElementById('previewImg');
      const imageUrl = previewImgEl ? previewImgEl.src : '';
      
      const vesselName = 'Vessel ' + state.vessel;
      const wickType = state.vessel === 'C' ? 'Single Wick' : (state.vessel === 'D' ? 'Double Wick' : 'Triple Wick');
      
      // Get box name
      let boxName = state.box ? state.box : null;
      
      // Build product name
      let productDisplayName = `${vesselName} · ${state.frag}`;
      if (boxName) {
        productDisplayName += ` + ${boxName}`;
      }
      
      // Get color name
      const colorInfo = colorData.find(c => c.name === state.color);
      const colorName = colorInfo ? colorInfo.name : state.color;
      
      // Build size name for display
      const sizeName = `${vesselName} (${wickType})`;
      
      LVBCart.addItem({
        id: sku, // Use SKU as ID for consistency
        sku: sku,
        name: productDisplayName,
        scent: sizeName,
        price: itemPrice,
        image: imageUrl,
        qty: state.qty,
        product_id: null, // Not applicable for custom builds
        size_id: sizeId,
        size_name: sizeName,
        box_id: boxId,
        box_name: boxName,
        fragrance_id: state.fragCode,
        fragrance_name: state.frag,
        color_name: colorName,
        color_code: state.colorCode,
        vessel: state.vessel,
        wick_type: wickType
      });
      
      closeReview();
      // Show success message like shop page
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

// ─── INIT: push initial history state ─────────────────────────────────────────
history.replaceState({ step: 1 }, '', '#step1');
</script>
</body>
</html>