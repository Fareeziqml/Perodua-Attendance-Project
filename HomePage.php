<!DOCTYPE html>
<html>
<head>
    <title>Perodua Spare Part Division - Home</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        /* Header */
        header {
            background: #009739;
            color: white;
            text-align: center;
            padding: 50px 20px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        }
        header img {
            height: 100px;
            margin-bottom: 20px;
        }
        header h1 {
            margin: 10px 0;
            font-size: 32px;
        }
        header p {
            font-size: 18px;
            margin: 5px 0;
        }

        /* Main content */
        .home-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: calc(100vh - 180px); /* adjust for header */
            text-align: center;
            padding: 20px;
        }

        .home-content h2 {
            font-size: 28px;
            color: #009739;
            margin-bottom: 30px;
        }

        /* Enter System Button */
        .enter-btn {
            font-size: 20px;
            padding: 15px 40px;
            background-color: #009739;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .enter-btn:hover {
            background-color: #007a2d;
            transform: scale(1.08);
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        }

        /* Footer */
        footer {
            background: #009739;
            color: white;
            text-align: center;
            padding: 15px 0;
            position: relative;
            bottom: 0;
            width: 100%;
            box-shadow: 0 -3px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<header>
    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/3/31/Perodua_Logo_%282008_-_Present%29.svg/330px-Perodua_Logo_%282008_-_Present%29.svg.png" alt="Perodua Logo">
    <h1>Perodua Spare Part Division</h1>
    <p>Daily Attendance Board</p>
</header>

<div class="home-content">
    <h2>Welcome to the Daily Attendance System</h2>
    <a href="LoginPerodua.php" class="enter-btn">Enter the System</a>
</div>

<footer>
    &copy; <?php echo date("Y"); ?> Perodua Spare Part Division
</footer>

</body>
</html>
