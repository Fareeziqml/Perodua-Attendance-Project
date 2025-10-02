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
            height: 70vh;
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
            background: rgba(1, 29, 12, 0.55);
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

        /* Models Section */
        .models-section {
            text-align: center;
            padding: 50px 20px;
            background: #fff;
            position: relative;
        }

        .models-section h2 {
            font-size: 32px;
            margin-bottom: 30px;
            color: #222;
        }

        .models-section h2 span {
            color: #d32f2f; /* highlight color */
        }

        .models-carousel {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .arrow-btn {
            font-size: 30px;
            background: rgba(0,0,0,0.1);
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 8px;
            transition: background 0.3s;
            z-index: 10;
        }

        .arrow-btn:hover {
            background: rgba(0,0,0,0.3);
            color: white;
        }

        .carousel-track {
            display: flex;
            align-items: center;
            transition: transform 0.5s ease;
        }

        .model-card {
            text-align: center;
            margin: 0 15px;
            transition: transform 0.5s, opacity 0.5s;
            cursor: pointer;
        }

        .model-card img {
            width: 220px;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
            transition: transform 0.5s ease, opacity 0.5s ease;
        }

        .model-card p {
            margin-top: 10px;
            font-size: 16px;
            font-weight: bold;
            color: #444;
        }

        /* Contact Section */
        .contact-section {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            background: #fff;
            padding: 40px 50px;
            box-shadow: 0 -2px 6px rgba(0,0,0,0.1);
        }

        .contact-info {
            flex: 1;
            min-width: 280px;
            margin-bottom: 30px;
            text-align: center;
        }

        .contact-logo {
            height: 50px;
            margin-bottom: 10px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .contact-info h3 {
            color: #009739;
            margin-bottom: 15px;
        }

        .contact-info p {
            margin: 8px 0;
            font-size: 15px;
        }

        .contact-map {
            flex: 2;
            min-width: 300px;
        }

        .contact-map iframe {
            width: 100%;
            height: 250px;
            border: none;
            border-radius: 10px;
        }

        /* Footer */
        footer {
            background: #0f0f0f;
            color: white;
            text-align: center;
            padding: 15px 0;
            width: 100%;
            font-size: 14px;
            font-weight: 300;
        }

        .contact-btn {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 25px;
            background: white;
            color: #0f0f0f;
            border-radius: 25px;
            font-weight: bold;
            text-decoration: none;
            transition: 0.3s;
        }

        .contact-btn:hover {
            background: #009739;
            color: white;
        }
    </style>
</head>
<body>

<header>
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

<!-- Perodua Models Carousel -->
<section class="models-section">
    <h2>Our <span>Perodua Models</span></h2>
    <div class="models-carousel">
        <button class="arrow-btn" id="prev">&#8592;</button>
        <div class="carousel-track" id="carouselTrack">
            <div class="model-card">
                <img src="Myvi.png" alt="Perodua Myvi">
                <p>Perodua Myvi</p>
            </div>
            <div class="model-card">
                <img src="Axia.png" alt="Perodua Axia">
                <p>Perodua Axia</p>
            </div>
            <div class="model-card">
                <img src="Bezza.png" alt="Perodua Bezza">
                <p>Perodua Bezza</p>
            </div>
            <div class="model-card">
                <img src="Ativa.png" alt="Perodua Ativa">
                <p>Perodua Ativa</p>
            </div>
            <div class="model-card">
                <img src="Aruz.png" alt="Perodua Aruz">
                <p>Perodua Aruz</p>
            </div>
            <div class="model-card">
                <img src="Alza.png" alt="Perodua Alza">
                <p>Perodua Alza</p>
            </div>
        </div>
        <button class="arrow-btn" id="next">&#8594;</button>
    </div>
</section>

<!-- Contact Section moved below models carousel -->
<section class="contact-section">
    <div class="contact-info">
        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/3/31/Perodua_Logo_%282008_-_Present%29.svg/330px-Perodua_Logo_%282008_-_Present%29.svg.png" 
             alt="Perodua Logo" class="contact-logo">
        <h3>📍 Perodua Sales Sdn Bhd</h3>
        <p><b>Alamat:</b> Locked Bag 226, Sungai Choh, 48009, Rawang, Selangor</p>
        <p><b>Jabatan:</b> Spare Part Division</p>
        <p><b>Sektor:</b> Perdagangan Jual Borong, dan Jual Runcit, Pembaikan Kenderaan</p>
    </div>
    <div class="contact-map">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3324.288639940672!2d101.57069607403389!3d3.368986451763893!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31cc42462a62c803%3A0x11e19edecbedb342!2sPerodua%20Corporate%20Building!5e1!3m2!1sen!2smy!4v1759306119378!5m2!1sen!2smy" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>
</section>

<footer>
    &copy; <?php echo date("Y"); ?> Perodua Spare Part Division <br>
    <a href="https://perodua.com.my/contact-us" target="_blank" class="contact-btn">📞 Contact Us</a>
</footer>

<script>
    const track = document.getElementById("carouselTrack");
    const cards = Array.from(track.getElementsByClassName("model-card"));
    let currentIndex = 0;

    function updateCarousel() {
        cards.forEach((card, index) => {
            const offset = index - currentIndex;
            if(offset === 0){
                card.style.transform = 'scale(1.2) translateX(0)';
                card.style.opacity = '1';
                card.style.zIndex = '10';
            } else {
                card.style.transform = `scale(0.8) translateX(${offset * 220}px)`;
                card.style.opacity = '0.5';
                card.style.zIndex = '5';
            }
        });

        const translateX = -(currentIndex * 220) + track.offsetWidth/2 - 110; // 110 = half card width
        track.style.transform = `translateX(${translateX}px)`;
    }

    document.getElementById("prev").addEventListener("click", () => {
        currentIndex = (currentIndex - 1 + cards.length) % cards.length;
        updateCarousel();
    });

    document.getElementById("next").addEventListener("click", () => {
        currentIndex = (currentIndex + 1) % cards.length;
        updateCarousel();
    });

    cards.forEach((card, i) => {
        card.addEventListener("click", () => {
            currentIndex = i;
            updateCarousel();
        });
    });

    window.addEventListener('resize', updateCarousel);
    updateCarousel();
</script>

</body>
</html>
