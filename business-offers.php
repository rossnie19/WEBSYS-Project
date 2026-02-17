<?php
/**
 * Business Offers - Mezzanine Restaurant
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
    $company_name = mysqli_real_escape_string($conn, trim($_POST['company_name']));
    $contact_person = mysqli_real_escape_string($conn, trim($_POST['contact_person']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $event_date = mysqli_real_escape_string($conn, $_POST['event_date']);
    $number_of_attendees = intval($_POST['attendees']);
    $event_type = mysqli_real_escape_string($conn, $_POST['event_type']);
    $requirements = mysqli_real_escape_string($conn, trim($_POST['requirements']));
    
    // Validate required fields
    if (empty($company_name) || empty($contact_person) || empty($email) || empty($phone)) {
        $error = "All required fields must be filled out.";
    } else {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            // Insert business inquiry into database
            $sql = "INSERT INTO business_inquiries 
                    (company_name, contact_person, email, phone, event_date, 
                     number_of_attendees, event_type, requirements, status) 
                    VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?, 'new')";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssiss", 
                $company_name, 
                $contact_person, 
                $email, 
                $phone, 
                $event_date, 
                $number_of_attendees, 
                $event_type, 
                $requirements
            );
            
            if ($stmt->execute()) {
                $success = true;
            } else {
                $error = "Error submitting inquiry. Please try again.";
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
    <title>Business Offers | Mezzanine</title>

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
            background-image: url("businessoffers.jpeg");
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

        .intro-block {
            text-align: center;
            max-width: 700px;
            margin: 0 auto;
            font-size: 1.1rem;
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

        /* ========== BUSINESS OFFERS PAGE ========== */
        .business-content {
            max-width: 1100px;
            margin: -80px auto 50px;
            background: white;
            padding: 4rem;
            position: relative;
            z-index: 10;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .offers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 3rem;
            margin-top: 3rem;
        }

        .offer-card {
            background: var(--color-light);
            padding: 2.5rem;
            border-top: 3px solid var(--color-accent);
            transition: all 0.3s ease;
        }

        .offer-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .offer-card h3 {
            font-size: 1.8rem;
            color: var(--color-primary);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .offer-card p {
            font-size: 0.95rem;
            line-height: 1.8;
            color: #555;
            margin-bottom: 1.5rem;
        }

        .offer-card ul {
            list-style: disc;
            margin-left: 1.5rem;
            margin-bottom: 2rem;
        }

        .offer-card li {
            margin-bottom: 0.8rem;
            font-size: 0.95rem;
            color: #555;
        }

        /* ========== INQUIRY FORM ========== */
        .inquiry-form {
            max-width: 700px;
            margin: 4rem auto 0;
            padding: 3rem;
            background: var(--color-light);
            border-radius: 8px;
        }

        .inquiry-form h3 {
            font-size: 1.8rem;
            color: var(--color-primary);
            margin-bottom: 2rem;
            text-align: center;
        }

        /* ========== FORM STYLING ========== */
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

            .business-content {
                margin: -40px 15px 30px;
                padding: 2rem;
            }

            .offers-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .inquiry-form { padding: 2rem; }

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
            <h1>Business Offers</h1>
            <div class="hero-sub">The perfect setting for business meetings that impress</div><br>
        </div>
    </header>

    <main class="business-content">
        <div class="section-header">
            <h2 class="section-title">Elevate Your Events</h2>
            <div class="section-divider"></div>
            <p class="intro-block">
                Whether hosting corporate dinners, private celebrations, or brand collaborations,
                Mezzanine offers refined spaces and bespoke service for memorable business occasions.
            </p>
        </div>

        <div class="offers-grid">
            <div class="offer-card">
                <h3>Corporate Dinners & Meetings</h3>
                <p>Host your executive team or clients in an atmosphere of elegance and discretion. Our private dining
                    spaces provide the perfect setting for important business discussions.</p>
                <ul>
                    <li>Private dining rooms available</li>
                    <li>Customizable menu options</li>
                    <li>Audio-visual equipment available</li>
                    <li>Dedicated service team</li>
                    <li>Complimentary Wi-Fi</li>
                </ul>
                <a href="#inquiry" class="reserve-btn">Inquire Now</a>
            </div>

            <div class="offer-card">
                <h3>Private Events</h3>
                <p>Celebrate life's special moments in style. From intimate gatherings to milestone celebrations, we
                    create unforgettable experiences tailored to your vision.</p>
                <ul>
                    <li>Capacity: up to 50 guests</li>
                    <li>Personalized menu planning</li>
                    <li>Custom decorations available</li>
                    <li>Premium beverage packages</li>
                    <li>Event coordination service</li>
                </ul>
                <a href="#inquiry" class="reserve-btn">Inquire Now</a>
            </div>

            <div class="offer-card">
                <h3>Brand Collaborations & Events</h3>
                <p>Partner with Mezzanine to create unique brand experiences. Our distinctive setting and culinary
                    excellence provide the perfect backdrop for product launches and brand activations.</p>
                <ul>
                    <li>Full venue buyout options</li>
                    <li>Co-branded marketing opportunities</li>
                    <li>Custom menu development</li>
                    <li>Professional photography services</li>
                    <li>Social media collaboration</li>
                </ul>
                <a href="#inquiry" class="reserve-btn">Inquire Now</a>
            </div>
        </div>

        <!-- Inquiry Form -->
        <div class="inquiry-form" id="inquiry">
            <h3>Request a Business Inquiry</h3>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <strong>Inquiry Submitted Successfully!</strong><br>
                    Thank you for your interest. Our events team will contact you within 24 hours to discuss your requirements.
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <strong>Error:</strong> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="company_name">Company Name *</label>
                    <input type="text" id="company_name" name="company_name" required value="<?php echo isset($_POST['company_name']) ? htmlspecialchars($_POST['company_name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="contact_person">Contact Person *</label>
                    <input type="text" id="contact_person" name="contact_person" required value="<?php echo isset($_POST['contact_person']) ? htmlspecialchars($_POST['contact_person']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" required value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="event_type">Event Type</label>
                    <select id="event_type" name="event_type">
                        <option value="">Select Event Type</option>
                        <option value="Corporate Dinner" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] == 'Corporate Dinner') ? 'selected' : ''; ?>>Corporate Dinner</option>
                        <option value="Business Meeting" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] == 'Business Meeting') ? 'selected' : ''; ?>>Business Meeting</option>
                        <option value="Private Event" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] == 'Private Event') ? 'selected' : ''; ?>>Private Event</option>
                        <option value="Brand Collaboration" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] == 'Brand Collaboration') ? 'selected' : ''; ?>>Brand Collaboration</option>
                        <option value="Product Launch" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] == 'Product Launch') ? 'selected' : ''; ?>>Product Launch</option>
                        <option value="Other" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="event_date">Preferred Event Date</label>
                    <input type="date" id="event_date" name="event_date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo isset($_POST['event_date']) ? htmlspecialchars($_POST['event_date']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="attendees">Number of Attendees</label>
                    <input type="number" id="attendees" name="attendees" min="1" max="100" value="<?php echo isset($_POST['attendees']) ? htmlspecialchars($_POST['attendees']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="requirements">Special Requirements / Additional Information</label>
                    <textarea id="requirements" name="requirements"><?php echo isset($_POST['requirements']) ? htmlspecialchars($_POST['requirements']) : ''; ?></textarea>
                </div>

                <button type="submit" class="reserve-btn" style="width:100%; text-align:center;">Submit Inquiry</button>
            </form>
        </div>

        <div class="section-header" style="margin-top: 5rem;" id="contact">
            <h2 class="section-title">Get in Touch</h2>
            <div class="section-divider"></div>
            <p class="intro-block">
                Ready to plan your event? Contact our events team to discuss your requirements
                and receive a customized proposal.
            </p>
            <a href="tel:+639171383612" class="reserve-btn">Call +63 917 138 3612</a>
        </div>
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
