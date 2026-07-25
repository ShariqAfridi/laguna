<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Contact Section</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500&family=Jost:wght@300;400;500&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      background: #ffffff;
      font-family: 'Jost', sans-serif;
    }

    .contact-section {
        margin: 0 auto;
      display: flex;
      align-items: center;
      gap: 60px;
      background: #ffffff;
      padding: 60px 80px;
      max-width: 1100px;
      width: 100%;
    }

    /* ── LEFT COLUMN ── */
    .contact-info {
      flex: 0 0 auto;
      display: flex;
      flex-direction: column;
      gap: 60px;
      min-width: 400px;
    }

    .info-item {
      display: flex;
      align-items: flex-start;
      gap: 18px;
    }

    .icon-wrap {
      width: 46px;
      height: 46px;
      border-radius: 50%;
      background: #dde8ef;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .icon-wrap svg {
      width: 18px;
      height: 18px;
      stroke: #4a7a95;
      fill: none;
      stroke-width: 1.6;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .info-text {
      display: flex;
      flex-direction: column;
      gap: 2px;
      padding-top: 4px;
    }

    .info-label {
      font-family: 'Jost', sans-serif;
      font-size: 10px;
      font-weight: 500;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: #8aa6b4;
      margin-bottom: 2px;
    }

    .info-value {
      font-family: 'Jost', sans-serif;
      font-size: 15px;
      font-weight: 400;
      color: #2c3e4a;
      line-height: 1.55;
    }

    /* HOURS */
    .hours-block {
      padding-left: 0;
      padding-top: 4px;
    }

    .hours-label {
      font-family: 'Jost', sans-serif;
      font-size: 10px;
      font-weight: 500;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: #8aa6b4;
      margin-bottom: 8px;
    }

    .hours-main {
      font-size: 15px;
      font-weight: 400;
      color: #2c3e4a;
      line-height: 1.6;
    }

    .hours-closed {
      font-size: 15px;
      font-weight: 400;
      color: #9db3be;
      line-height: 1.6;
    }

    /* ── MAP ── */
    .map-wrap {
      flex: 1;
      border-radius: 18px;
      overflow: hidden;
      box-shadow: 0 4px 24px rgba(60, 90, 110, 0.13);
      min-height: 500px;
      height: 500px;
    }

    .map-wrap iframe {
      width: 100%;
      height: 100%;
      border: none;
      display: block;
    }
    @media (max-width: 900px) {
  .contact-section {
    flex-direction: column; /* Stacks text on top of map */
    padding: 40px 20px;     /* Reduces heavy side padding for small screens */
    gap: 40px;
  }

  .contact-info {
    min-width: 100%;       /* Allows text to take full width */
    gap: 30px;
  }

  .map-wrap {
    width: 100%;           /* Ensures map fills the screen width */
    height: 350px;         /* Slightly shorter height for better mobile scrolling */
    min-height: 300px;
  }
}
  </style>
</head>
<body>
  <div class="contact-section">

    <!-- Left: Contact Info -->
    <div class="contact-info">

      <!-- Address -->
      <div class="info-item">
        <div class="icon-wrap">
          <svg viewBox="0 0 24 24">
            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
            <circle cx="12" cy="9" r="2.5"/>
          </svg>
        </div>
        <div class="info-text">
          <span class="info-label">Studio</span>
          <span class="info-value">22961 Triton Way, Unit A & B
Laguna Hills, CA 92653</span>
        </div>
      </div>

      <!-- Email -->
      <div class="info-item">
        <div class="icon-wrap">
          <svg viewBox="0 0 24 24">
            <rect x="2" y="4" width="20" height="16" rx="2"/>
            <polyline points="2,4 12,13 22,4"/>
          </svg>
        </div>
        <div class="info-text">
          <span class="info-label">Email</span>
          <span class="info-value">info@lagunavibe.com</span>
        </div>
      </div>

      <!-- Phone -->
      <div class="info-item">
        <div class="icon-wrap">
          <svg viewBox="0 0 24 24">
            <path d="M6.6 10.8a15.05 15.05 0 0 0 6.6 6.6l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.58.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1C10.01 21 3 13.99 3 5c0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.58.11.35.03.74-.24 1.02L6.6 10.8z"/>
          </svg>
        </div>
        <div class="info-text">
          <span class="info-label">Phone</span>
          <span class="info-value">+1 (949) 775–7158</span>
        </div>
      </div>

<!-- Hours -->
<div class="hours-block">
  <div class="hours-label">Hours</div>
  <div class="hours-main">Monday – Saturday · 9:00am – 4:00pm</div>
  <div class="hours-closed">Closed Sunday – Open by appointment · (949) 775-7158</div>
  
</div>

    </div>

    <!-- Right: Map -->
    <div class="map-wrap">
  <iframe
  src="https://maps.google.com/maps?q=22961%20Triton%20Way%20Laguna%20Hills%20CA%2092653&t=&z=15&ie=UTF8&iwloc=&output=embed"
  allowfullscreen=""
  loading="lazy"
  referrerpolicy="no-referrer-when-downgrade"
  title="Laguna Hills Studio">
</iframe>
    </div>

  </div>
</body>
</html>