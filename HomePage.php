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
            color: #d32f2f;
        }

        .models-carousel {
            position: relative;
            overflow: hidden;
            width: 90%;
            margin: auto;
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

        /* Carousel Arrows */
        .arrow-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 35px;
            background: rgba(0,0,0,0.4);
            border: none;
            color: #fff;
            padding: 12px 18px;
            cursor: pointer;
            border-radius: 50%;
            transition: background 0.3s;
            z-index: 10;
        }

        .arrow-btn:hover {
            background: rgba(0,0,0,0.7);
        }

        #prev {
            left: -10px;
        }

        #next {
            right: -10px;
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

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.7);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            text-align: center;
            position: relative;
        }

        .modal-content img {
            width: 200px;
            margin-bottom: 15px;
        }

        .modal-content h2 {
            margin-bottom: 10px;
            color: #007a2d;
        }

        .modal-content p {
            font-size: 15px;
            line-height: 1.5;
        }

        .car-specs {
            text-align: left;
            margin-top: 15px;
            font-size: 14px;
            background: #f9f9f9;
            padding: 12px;
            border-radius: 8px;
        }

        .close-btn {
            position: absolute;
            top: 10px; right: 15px;
            font-size: 22px;
            cursor: pointer;
            color: #333;
        }

        .close-btn:hover {
            color: red;
        }

        /* Modal Navigation */
        .modal-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 28px;
            background: rgba(0,0,0,0.1);
            border: none;
            padding: 8px 15px;
            cursor: pointer;
            border-radius: 8px;
            transition: 0.3s;
        }
        .modal-nav:hover {
            background: rgba(0,0,0,0.3);
            color: #fff;
        }
        #modalPrev { left: -60px; }
        #modalNext { right: -60px; }

        .view-more-btn {
            display: inline-block;
            margin-top: 15px;
            padding: 12px 30px;
            background: #009739;
            color: #fff;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
        }
        .view-more-btn:hover {
            background: #007a2d;
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
        <button class="arrow-btn" id="prev">&#10094;</button>
        <div class="carousel-track" id="carouselTrack">
            <div class="model-card" data-model="Myvi">
                <img src="Myvi.png" alt="Perodua Myvi">
                <p>Perodua Myvi</p>
            </div>
            <div class="model-card" data-model="Axia">
                <img src="Axia.png" alt="Perodua Axia">
                <p>Perodua Axia</p>
            </div>
            <div class="model-card" data-model="Bezza">
                <img src="Bezza.png" alt="Perodua Bezza">
                <p>Perodua Bezza</p>
            </div>
            <div class="model-card" data-model="Ativa">
                <img src="Ativa.png" alt="Perodua Ativa">
                <p>Perodua Ativa</p>
            </div>
            <div class="model-card" data-model="Aruz">
                <img src="Aruz.png" alt="Perodua Aruz">
                <p>Perodua Aruz</p>
            </div>
            <div class="model-card" data-model="Alza">
                <img src="Alza.png" alt="Perodua Alza">
                <p>Perodua Alza</p>
            </div>
        </div>
        <button class="arrow-btn" id="next">&#10095;</button>
    </div>
</section>

<!-- Contact Section -->
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
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3324.288639940672!2d101.57069607403389!3d3.368986451763893!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31cc42462a62c803%3A0x11e19edecbedb342!2sPerodua%20Corporate%20Building!5e1!3m2!1sen!2smy!4v1759306119378!5m2!1sen!2smy" allowfullscreen="" loading="lazy"></iframe>
    </div>
</section>

<footer>
    &copy; <?php echo date("Y"); ?> Perodua Spare Part Division <br>
    <a href="https://perodua.com.my/contact-us" target="_blank" class="contact-btn">📞 Contact Us</a>
</footer>

<!-- Modal for Car Details -->
<div id="carModal" class="modal">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <button class="modal-nav" id="modalPrev">&#8592;</button>
        <button class="modal-nav" id="modalNext">&#8594;</button>
        <img id="carImage" src="" alt="Car">
        <h2 id="carTitle"></h2>
        <p id="carDesc"></p>
        <div class="car-specs">
            <p><b>Price Range:</b> <span id="carPrice"></span></p>
            <p><b>Engine Type:</b> <span id="carEngine"></span></p>
        </div>
        <a id="viewMoreBtn" href="#" target="_blank" class="view-more-btn">View More</a>
    </div>
</div>

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

        const translateX = -(currentIndex * 250) + track.offsetWidth/2 - 110;
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
            openModal(card.dataset.model, card.querySelector("img").src);
        });
    });

    window.addEventListener('resize', updateCarousel);
    updateCarousel();

    // Modal Script
    const modal = document.getElementById("carModal");
    const closeBtn = document.querySelector(".close-btn");
    const modalPrev = document.getElementById("modalPrev");
    const modalNext = document.getElementById("modalNext");

    const carDetails = {
        "Myvi": {
            desc: "Malaysia’s best-selling hatchback with modern design and safety features.",
            price: "RM 46,500 – RM 59,900",
            engine: "1.3L & 1.5L Dual VVT-i",
            link: "https://www.perodua.com.my/our-models/myvi.html"
        },
        "Axia": {
            desc: "Most affordable compact car, perfect for city driving.",
            price: "RM 22,000 – RM 44,000",
            engine: "1.0L VVT-i",
            link: "https://www.perodua.com.my/our-models/axia.html"
        },
        "Bezza": {
            desc: "A practical sedan with great fuel economy and spacious interior.",
            price: "RM 36,000 – RM 49,000",
            engine: "1.0L & 1.3L Dual VVT-i",
            link: "https://www.perodua.com.my/our-models/bezza.html"
        },
        "Ativa": {
            desc: "Compact SUV with advanced safety features and stylish design.",
            price: "RM 62,500 – RM 73,400",
            engine: "1.0L Turbo CVT",
            link: "https://www.perodua.com.my/our-models/ativa.html"
        },
        "Aruz": {
            desc: "7-seater SUV designed for families, offering comfort and versatility.",
            price: "RM 72,900 – RM 77,900",
            engine: "1.5L Dual VVT-i",
            link: "https://www.perodua.com.my/our-models/aruz.html"
        },
        "Alza": {
            desc: "Practical MPV with spacious cabin and flexible seating arrangements.",
            price: "RM 62,500 – RM 75,500",
            engine: "1.5L Dual VVT-i",
            link: "https://www.perodua.com.my/our-models/alza.html"
        }
    };

    function openModal(model, imgSrc) {
        const car = carDetails[model];
        document.getElementById("carImage").src = imgSrc;
        document.getElementById("carTitle").innerText = "Perodua " + model;
        document.getElementById("carDesc").innerText = car.desc;
        document.getElementById("carPrice").innerText = car.price;
        document.getElementById("carEngine").innerText = car.engine;
        document.getElementById("viewMoreBtn").href = car.link;
        modal.style.display = "flex";
    }

    closeBtn.addEventListener("click", () => {
        modal.style.display = "none";
    });

    window.addEventListener("click", (e) => {
        if(e.target === modal){
            modal.style.display = "none";
        }
    });

    modalPrev.addEventListener("click", () => {
        currentIndex = (currentIndex - 1 + cards.length) % cards.length;
        const card = cards[currentIndex];
        openModal(card.dataset.model, card.querySelector("img").src);
    });

    modalNext.addEventListener("click", () => {
        currentIndex = (currentIndex + 1) % cards.length;
        const card = cards[currentIndex];
        openModal(card.dataset.model, card.querySelector("img").src);
    });
</script>

</body>
</html>
