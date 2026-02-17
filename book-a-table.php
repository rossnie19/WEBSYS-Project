<?php
/**
 * Book a Table - Mezzanine Restaurant
 * PHP version with database integration
 */

// Include database connection
require_once 'db_connect.php';

// Initialize variables
$success = false;
$error = false;

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get and sanitize form data
    $customer_name = mysqli_real_escape_string($conn, trim($_POST['fname']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $reservation_date = mysqli_real_escape_string($conn, $_POST['date']);
    $reservation_time = mysqli_real_escape_string($conn, $_POST['time']);
    $number_of_guests = intval($_POST['guests']);
    $special_requests = mysqli_real_escape_string($conn, trim($_POST['requests']));
    
    // Validate required fields
    if (empty($customer_name) || empty($email) || empty($phone) || 
        empty($reservation_date) || empty($reservation_time) || empty($number_of_guests)) {
        
        $error = "All required fields must be filled out.";
        
    } else {
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            
            // Convert time format from "12:00 PM" to "12:00:00"
            $time_24hr = date("H:i:s", strtotime($reservation_time));
            
            // Insert reservation into database
            $sql = "INSERT INTO reservations 
                    (customer_name, email, phone, reservation_date, reservation_time, 
                     number_of_guests, special_requests, status) 
                    VALUES 
                    (?, ?, ?, ?, ?, ?, ?, 'pending')";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssis", 
                $customer_name, 
                $email, 
                $phone, 
                $reservation_date, 
                $time_24hr, 
                $number_of_guests, 
                $special_requests
            );
            
            if ($stmt->execute()) {
                $success = true;
            } else {
                $error = "Error submitting reservation. Please try again.";
            }
            
            $stmt->close();
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Table | Mezzanine</title>

    <style>
        /* ========== IMPORTS ========== */
        @import url('https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&family=Playfair+Display:ital,wght@0,400;0,600;1,400&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;1,400&display=swap');

        /* ========== CSS CUSTOM PROPERTIES ========== */
        :root {
            --color-primary: #7C1309;
            --color-accent: #D6A34F;
            --color-light: #FFF7EF;
            --color-white: #FFFFFF;
            --color-dark: #1a1a1a;
            --font-serif: 'Playfair Display', serif;
            --font-sans: 'Lato', sans-serif;
        }

        /* ========== GLOBAL RESET ========== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* ========== BODY ========== */
        body {
            font-family: var(--font-sans);
            background-color: var(--color-light);
            color: var(--color-dark);
            line-height: 1.6;
        }

        /* ========== TYPOGRAPHY ========== */
        h1, h2, h3 { font-family: var(--font-serif); }

        a {
            text-decoration: none;
            color: inherit;
            transition: color 0.3s ease;
        }

        ul { list-style: none; }

        /* ========== NAVIGATION BAR ========== */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 1.2rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            background-color: transparent;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.4s ease;
        }

        .navbar.scrolled {
            background-color: var(--color-white);
            padding: 0.8rem 3rem;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            border-bottom: none;
        }

        .brand-logo {
            font-family: var(--font-serif);
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--color-white);
        }

        .navbar.scrolled .brand-logo { color: var(--color-primary); }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-link {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            font-weight: 700;
            color: var(--color-white);
            position: relative;
        }

        .navbar.scrolled .nav-link { color: var(--color-dark); }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 1px;
            bottom: -5px;
            left: 0;
            background-color: var(--color-accent);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after { width: 100%; }

        .nav-dropdown {
            position: relative;
            display: flex;
            align-items: center;
        }

        .dropdown-menu {
            position: absolute;
            top: 120%;
            left: 0;
            background: var(--color-white);
            min-width: 200px;
            padding: 0.8rem 0;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s ease;
            z-index: 999;
        }

        .dropdown-menu a {
            display: block;
            padding: 0.6rem 1.2rem;
            font-size: 0.75rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--color-dark);
        }

        .dropdown-menu a:hover {
            background: var(--color-light);
            color: var(--color-primary);
        }

        .nav-dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        /* ========== HERO SECTION ========== */
        .hero {
            height: 100vh;
            background-image: url("bookatable.jpg");
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: var(--color-white);
        }

        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                linear-gradient(rgba(124, 19, 9, 0.25), rgba(124, 19, 9, 0.25)),
                rgba(0, 0, 0, 0.25);
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-size: 5rem;
            margin-bottom: 0.5rem;
            font-style: italic;
        }

        .hero-sub {
            font-size: 1rem;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            margin-bottom: 3rem;
            border-bottom: 1px solid var(--color-accent);
            padding-bottom: 0.5rem;
            display: inline-block;
        }

        /* ========== SECTION HEADER ========== */
        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-title {
            font-size: 48px;
            color: var(--color-primary);
            text-align: center;
            font-weight: 400;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }

        .section-divider {
            width: 80px;
            height: 3px;
            background-color: var(--color-accent);
            margin: 0 auto 60px auto;
        }

        /* ========== RESERVATION BUTTON ========== */
        .reserve-btn {
            display: inline-block;
            margin-top: 2rem;
            padding: 1.2rem 3.5rem;
            background-color: transparent;
            color: var(--color-primary);
            border: 1px solid var(--color-primary);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.3em;
            font-weight: 700;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1);
            z-index: 1;
        }

        .reserve-btn::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background-color: var(--color-primary);
            transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1);
            z-index: -1;
        }

        .reserve-btn:hover {
            background-color: var(--color-primary);
            color: var(--color-white);
            letter-spacing: 0.35em;
            box-shadow: 0 5px 15px rgba(124, 19, 9, 0.15);
        }

        .reserve-btn:hover::before { left: 0; }

        /* ========== BOOK A TABLE PAGE ========== */
        .reservation-content {
            max-width: 900px;
            margin: -80px auto 50px;
            background: white;
            padding: 4rem;
            position: relative;
            z-index: 10;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 3rem;
            margin-bottom: 4rem;
        }

        .info-card h3 {
            font-size: 1.2rem;
            color: var(--color-primary);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .info-card p {
            font-size: 0.95rem;
            line-height: 1.8;
            color: #555;
        }

        .info-card ul {
            list-style: disc;
            margin-left: 1.5rem;
            margin-top: 0.5rem;
        }

        .info-card li {
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
            color: #555;
        }

        /* ========== FORM STYLING ========== */
        fieldset {
            border: none;
            margin-bottom: 0;
        }

        legend {
            font-family: var(--font-sans);
            font-size: 1.5rem;
            color: var(--color-dark);
            margin-bottom: 2rem;
            display: block;
            width: 100%;
            border-bottom: none;
            padding-bottom: 0;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 1.5rem;
        }

        label {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 700;
            color: var(--color-dark);
            display: none;
        }

        input,
        select,
        textarea {
            padding: 15px 18px;
            border: 1px solid #ddd;
            font-family: var(--font-sans);
            outline: none;
            transition: 0.3s;
            font-size: 0.95rem;
            background: white;
            border-radius: 4px;
        }

        input::placeholder,
        select::placeholder,
        textarea::placeholder {
            color: #666;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 1px var(--color-primary);
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        .required-message {
            color: #c41e3a;
            font-size: 0.8rem;
            margin-top: -8px;
            margin-bottom: 1rem;
            display: none;
        }

        .form-group.error .required-message { display: block; }

        .form-group.error input,
        .form-group.error select,
        .form-group.error textarea {
            border-color: #c41e3a;
        }

        /* ========== SUCCESS/ERROR ALERTS ========== */
        .alert {
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-radius: 4px;
            text-align: center;
            font-size: 1rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* ========== FOOTER ========== */
        footer {
            background-color: #111;
            color: #ccc;
            text-align: center;
            padding: 5rem 1rem 3rem;
            border-top: 3px solid var(--color-primary);
        }

        .footer-logo {
            font-family: var(--font-serif);
            font-size: 2.5rem;
            color: var(--color-white);
            margin-bottom: 4rem;
            display: inline-block;
            letter-spacing: 2px;
        }

        .footer-details {
            max-width: 1100px;
            margin: 0 auto 3rem;
            font-size: 0.9rem;
            line-height: 2;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 2rem;
        }

        .footer-section {
            flex: 1;
            min-width: 200px;
            margin-bottom: 1rem;
        }

        .footer-section strong {
            color: var(--color-accent);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-size: 0.8rem;
            display: block;
            margin-bottom: 1rem;
        }

        .contact-number {
            color: var(--color-white);
            font-weight: 700;
        }

        .copyright {
            font-size: 0.75rem;
            opacity: 0.5;
            border-top: 1px solid #333;
            padding-top: 2rem;
            margin-top: 2rem;
            max-width: 1100px;
            margin-left: auto;
            margin-right: auto;
        }

        /* ========== RESPONSIVE (MOBILE) ========== */
        @media (max-width: 768px) {
            .navbar { padding: 1rem; }
            .nav-links { display: none; }
            .hero h1 { font-size: 3.5rem; }

            .reservation-content {
                margin: -40px 15px 30px;
                padding: 2rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .footer-details {
                flex-direction: column;
                align-items: center;
                gap: 3rem;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <div class="brand-logo">Mezzanine</div>
        <div class="nav-links">
            <a href="MezzanineMain.html" class="nav-link">Home</a>
            <div class="nav-dropdown">
                <a href="cuisine.html" class="nav-link">Cuisine</a>
                <div class="dropdown-menu">
                    <a href="fine-dining.html">Fine Dining</a>
                    <a href="a-la-carte.html">À La Carte</a>
                    <a href="special-offers.html">Special Offers</a>
                </div>
            </div>
            <a href="gallery.html" class="nav-link">Gallery</a>
            <div class="nav-dropdown">
                <a href="reservations.html" class="nav-link">Reservations</a>
                <div class="dropdown-menu">
                    <a href="book-a-table.php" class="dropdown-link">Book a Table</a>
                    <a href="business-offers.php" class="dropdown-link">Business Offers</a>
                    <a href="location.html" class="dropdown-link">Location</a>
                </div>
            </div>
            <a href="reviews.html" class="nav-link">Reviews</a>
            <div class="nav-dropdown">
                <a href="about-us.html" class="nav-link">About Us</a>
                <div class="dropdown-menu">
                    <a href="our-ingredients.html" class="dropdown-link">Our Ingredients</a>
                    <a href="team.html" class="dropdown-link">About Our Team</a>
                </div>
            </div>
            <a href="contact-us.html" class="nav-link">Contact Us</a>
        </div>
    </nav>

    <header class="hero">
        <div class="hero-content">
            <h1>Book a Table</h1>
            <div class="hero-sub">Reserve your moment of culinary perfection</div><br>
        </div>
    </header>

    <main class="reservation-content">
        <div class="section-header">
            <h2 class="section-title">Book Your Experience</h2>
            <div class="section-divider"></div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <strong>Reservation Submitted Successfully!</strong><br>
                Thank you for your reservation. We will confirm your booking within 24 hours via email or phone.
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <strong>Error:</strong> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="info-grid">
            <div class="info-card">
                <h3>Online Reservation Form</h3>
                <p>Complete the form below to reserve your table. We'll confirm your booking within 24 hours.</p>
            </div>
            <div class="info-card">
                <h3>Cancellation Policy</h3>
                <p>Please notify us at least 24 hours in advance if you need to cancel or modify your reservation.</p>
            </div>
            <div class="info-card">
                <h3>Dining Guidelines</h3>
                <ul>
                    <li>Smart casual dress code</li>
                    <li>Reservations held for 15 minutes</li>
                </ul>
            </div>
        </div>

        <form method="POST" action="">
            <fieldset>
                <legend>Reservation Request</legend>

                <div class="form-group">
                    <input type="text" id="fname" name="fname" placeholder="NAME" required value="<?php echo isset($_POST['fname']) ? htmlspecialchars($_POST['fname']) : ''; ?>">
                    <span class="required-message">The field is required.</span>
                </div>

                <div class="form-group">
                    <input type="tel" id="phone" name="phone" placeholder="CONTACT NUMBER" required value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    <span class="required-message">The field is required.</span>
                </div>

                <div class="form-group">
                    <input type="email" id="email" name="email" placeholder="EMAIL" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    <span class="required-message">The field is required.</span>
                </div>

                <div class="form-group">
                    <input type="number" id="guests" name="guests" placeholder="NUMBER OF GUESTS" min="1" max="8" required value="<?php echo isset($_POST['guests']) ? htmlspecialchars($_POST['guests']) : ''; ?>">
                    <span class="required-message">The field is required.</span>
                </div>

                <div class="form-group">
                    <input type="date" id="date" name="date" placeholder="dd/mm/yyyy" min="<?php echo date('Y-m-d'); ?>" required value="<?php echo isset($_POST['date']) ? htmlspecialchars($_POST['date']) : ''; ?>">
                    <span class="required-message">The field is required.</span>
                </div>

                <div class="form-group">
                    <select id="time" name="time" required>
                        <option value="">TIME OF RESERVATION</option>
                        <option value="11:00 AM" <?php echo (isset($_POST['time']) && $_POST['time'] == '11:00 AM') ? 'selected' : ''; ?>>11:00 AM</option>
                        <option value="12:00 PM" <?php echo (isset($_POST['time']) && $_POST['time'] == '12:00 PM') ? 'selected' : ''; ?>>12:00 PM</option>
                        <option value="1:00 PM" <?php echo (isset($_POST['time']) && $_POST['time'] == '1:00 PM') ? 'selected' : ''; ?>>1:00 PM</option>
                        <option value="2:00 PM" <?php echo (isset($_POST['time']) && $_POST['time'] == '2:00 PM') ? 'selected' : ''; ?>>2:00 PM</option>
                        <option value="6:00 PM" <?php echo (isset($_POST['time']) && $_POST['time'] == '6:00 PM') ? 'selected' : ''; ?>>6:00 PM</option>
                        <option value="7:00 PM" <?php echo (isset($_POST['time']) && $_POST['time'] == '7:00 PM') ? 'selected' : ''; ?>>7:00 PM</option>
                        <option value="8:00 PM" <?php echo (isset($_POST['time']) && $_POST['time'] == '8:00 PM') ? 'selected' : ''; ?>>8:00 PM</option>
                        <option value="9:00 PM" <?php echo (isset($_POST['time']) && $_POST['time'] == '9:00 PM') ? 'selected' : ''; ?>>9:00 PM</option>
                    </select>
                    <span class="required-message">The field is required.</span>
                </div>

                <div class="form-group">
                    <textarea id="requests" name="requests" placeholder="SPECIAL INSTRUCTIONS"><?php echo isset($_POST['requests']) ? htmlspecialchars($_POST['requests']) : ''; ?></textarea>
                </div>
            </fieldset>

            <button type="submit" class="reserve-btn">Confirm Reservation</button>
        </form>
    </main>

    <footer>
        <div class="footer-logo">MEZZANINE</div>

        <div class="footer-details">
            <div class="footer-section">
                <strong>Visit Us</strong>
                <p>Level M, Active Fun Bldg.,<br>9th Avenue Cor. 28th St.,<br>BGC, Taguig City.</p>
            </div>

            <div class="footer-section">
                <strong>Opening Hours</strong>
                <p>Monday to Sunday<br>11:00 AM – 11:00 PM</p>
            </div>

            <div class="footer-section">
                <strong>Contact</strong>
                <p>Connect with us via Call or WhatsApp:<br><span class="contact-number">+63 917 138 3612</span></p>
            </div>
        </div>

        <div class="copyright">
            &copy; 2024 Mezzanine Group. All Rights Reserved.
        </div>
    </footer>

    <script>
        const navbar = document.querySelector('.navbar');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>

</html>
