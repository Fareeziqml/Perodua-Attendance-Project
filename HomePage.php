<!DOCTYPE html>
<html>
<head>
    <title>Perodua Spare Part Division - Home</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: #f4f4f4;
            color: #333;
        }

        /* Header with video background */
        header {
            position: relative;
            height: 70vh; /* video header height */
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
            overflow: hidden;
        }

        header video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }

        header::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(1, 29, 12, 0.55); /* green overlay for readability */
            z-index: -1;
        }

        header img {
            height: 90px;
            margin-bottom: 15px;
        }

        header h1 {
            font-size: 40px;
            margin: 10px 0;
            font-weight: bold;
        }

        header p {
            font-size: 20px;
            margin: 0;
            font-weight: 300;
        }

        /* Main content */
        .home-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 50px 20px;
        }

        .home-content h2 {
            font-size: 28px;
            color: #007a2d;
            margin-bottom: 20px;
        }

        /* Enter System Button */
        .enter-btn {
            font-size: 22px;
            padding: 15px 45px;
            background: linear-gradient(135deg, #009739, #007a2d);
            color: white;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-weight: bold;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        .enter-btn:hover {
            background: linear-gradient(135deg, #007a2d, #005d22);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 6px 18px rgba(0,0,0,0.4);
        }

        /* Footer */
        footer {
            background: #0f0f0fff;
            color: white;
            text-align: center;
            padding: 15px 0;
            width: 100%;
            box-shadow: 0 -3px 6px rgba(0,0,0,0.15);
            font-size: 14px;
            font-weight: 300;
        }
    </style>
</head>
<body>

<header>
    <!-- Video Background -->
    <video autoplay muted loop playsinline>
        <source src="Perodua_Video.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>

    <div>
        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/3/31/Perodua_Logo_%282008_-_Present%29.svg/330px-Perodua_Logo_%282008_-_Present%29.svg.png" alt="Perodua Logo">
        <h1>Perodua Spare Part Division</h1>
        <p>Daily Attendance Board</p>
    </div>
</header>

<div class="home-content">
    <h2>Welcome to the Daily Attendance System</h2>
    <a href="LoginPerodua.php" class="enter-btn"> Enter the System</a>
</div>

<footer>
    &copy; <?php echo date("Y"); ?> Perodua Spare Part Division
</footer>

</body>
</html>
