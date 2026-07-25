<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>privacy</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital@0;1&family=Inter:wght@300;400&display=swap" rel="stylesheet">
</head>
<body>

    <section class="hero-banner">
        <div class="content">
            <h2 class="subtitle">LEGAL</h2>
            <h1 class="title">Terms of Service</h1>
            <p class="description">
                Last updated: June 2026
            </p>
        </div>
    </section>

</body>
</html>

<style>
    /* Reset defaults */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background-color: #CFE2EB; /* Fallback color */
}

.hero-banner {
    width: 100%;
    height: 50vh;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
     /* Creating the soft lighting effect from the center */
  background: linear-gradient(to bottom, #F7FCFD 0%, #DEEFF5 100%);          
  padding: 20px;
}

.content {
    max-width: 600px;
}

.subtitle {
    font-family: 'Inter', sans-serif;
    font-size: 12px;
    font-weight: 400;
    letter-spacing: 0.4em; /* Key for that luxury look */
    color: #6d8491;
    margin-bottom: 20px;
    text-transform: uppercase;
}

.title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 64px; /* Scaled for desktop */
    font-weight: 400;
    color: #1a2b3c;
    margin-bottom: 20px;
    line-height: 1.1;
}

.description {
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    line-height: 1.6;
    color: #5a6d7a;
    font-weight: 300;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .title {
        font-size: 48px;
    }
    .description {
        font-size: 16px;
    }
}
</style>