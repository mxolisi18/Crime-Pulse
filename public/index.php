<?php
/*require_once __DIR__ . '/../vendor/autoload.php';*/
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crime Report System - Anonymous Crime Reporting</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1>🛡️ Crime Report System</h1>
            <p>Report crimes securely. Your voice matters, your identity stays protected. Help make your community safer today.</p>
            <div class="cta-buttons">
                <a href="anonymous_report.php" class="cta-button">Report Anonymously</a>
                <a href="login_register.php" class="cta-button secondary">Login/Register to Report</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="features-container">
            <h2>Why Use Our System?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h3>100% Anonymous</h3>
                    <p>Your identity remains completely confidential. Report without fear of retaliation or exposure.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3>Fast & Secure</h3>
                    <p>Submit reports instantly with secure encryption. Your data is protected at all times.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">👮</div>
                    <h3>Direct to Authorities</h3>
                    <p>Reports go directly to law enforcement for immediate action and investigation.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3>Easy to Use</h3>
                    <p>Simple interface works on any device. Report crimes in minutes from anywhere.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🌍</div>
                    <h3>24/7 Available</h3>
                    <p>Report crimes anytime, day or night. The system is always ready to receive your reports.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Track Progress</h3>
                    <p>Stay informed about your report's status while maintaining complete anonymity.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Information Section -->
    <section class="info-section">
        <h2>How It Works</h2>
        <div class="info-content">
            <ul>
                <li>
                    <div>
                        <strong>Create Your Account:</strong> Register with a secure username and password. No personal information required.
                    </div>
                </li>
                <li>
                    <div>
                        <strong>Submit Your Report:</strong> Fill out a simple form with details about the incident. Include as much information as you can.
                    </div>
                </li>
                <li>
                    <div>
                        <strong>Stay Anonymous:</strong> Your identity is never shared with anyone. All reports are completely confidential.
                    </div>
                </li>
                <li>
                    <div>
                        <strong>Authorities Take Action:</strong> Law enforcement receives your report and begins investigation immediately.
                    </div>
                </li>
                <li>
                    <div>
                        <strong>Make a Difference:</strong> Your report helps keep the community safe and brings criminals to justice.
                    </div>
                </li>
            </ul>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Crime Report System. All rights reserved.</p>
        <p style="margin-top: 10px; opacity: 0.8;">Your safety and anonymity are our top priorities.</p>
    </footer>
</body>
</html>