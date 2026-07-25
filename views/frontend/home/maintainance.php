<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Construction</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            /* Background image setup */
       background-image: url('https://images.pexels.com/photos/37162119/pexels-photo-37162119.jpeg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Transparent Glass Box */
        .glass-box {
            background: rgba(0, 0, 0, 0.6); /* Black with 60% transparency */
            color: #ffffff;
            padding: 40px;
            border-radius: 12px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px); /* Optional: creates a soft blur behind the box */
        }

        h1 {
            font-size: 2.2rem;
            margin-bottom: 15px;
        }

        p {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #e0e0e0;
        }
    </style>
</head>
<body>

    <div class="glass-box">
        <h1>🚧 Under Construction</h1>
        <p>Our website is currently undergoing planned maintenance.</p>
        <p>We'll be back online shortly!</p>
    </div>

</body>
</html>