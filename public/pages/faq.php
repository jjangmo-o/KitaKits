<?php
require_once(__DIR__ . '/../../app/config/db.php');

$managed_content = [];

try {
    $content_stmt = $conn->prepare("SELECT page_key, title, body
                                    FROM content_pages
                                    WHERE status = 'published'
                                      AND page_key IN ('faq_booking_policy', 'faq_health_privacy', 'health_advisory')
                                    ORDER BY page_key ASC");
    $content_stmt->execute();
    $managed_content = $content_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $managed_content = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ | KitaKits</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <header>
        <div class="container">
            <div class="header-logo">
                <img src="../assets/images/logo.png" alt="Kitakits Logo">
            </div>
            <div class="header-main">
                <div class="header-content">
                    <h1>Frequently Asked Questions</h1>
                    <p>Find answers to common questions about cataract missions</p>
                </div>
                <div class="header-actions" aria-label="Primary navigation">
                    <nav class="header-nav">
                        <a href="../index.php">Opening Page</a>
                        <a href="login.php">Log In</a>
                        <a href="patient_guide.php">Patient Guide</a>
                        <a href="faq.php">FAQ</a>
                        <a href="about_cataracts.php">About Cataracts</a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <main class="container">
        <a href="../index.php" class="btn-back">
            <span>← </span>
            Back to Missions
        </a>

        <div class="source-panel">
            <h3>FAQ Sources</h3>
            <p>Medical answers are cross-checked with public patient education sources. Mission logistics may vary by organizer, so follow the instructions given by the mission team.</p>
            <div class="source-links">
                <a class="source-button" href="https://www.nei.nih.gov/eye-health-information/eye-conditions-and-diseases/cataracts" target="_blank" rel="noopener">National Eye Institute Cataracts</a>
                <a class="source-button" href="https://www.mayoclinic.org/tests-procedures/cataract-surgery/about/pac-20384765" target="_blank" rel="noopener">Mayo Clinic Cataract Surgery</a>
                <a class="source-button" href="https://www.who.int/en/news-room/fact-sheets/detail/blindness-and-visual-impairment" target="_blank" rel="noopener">WHO Vision Impairment</a>
            </div>
        </div>

        <?php if ($managed_content): ?>
            <section class="patient-resources">
                <h2 class="centered-section-title">Current Admin Updates</h2>
                <div class="content-advisory-grid">
                    <?php foreach ($managed_content as $page): ?>
                        <article class="content-advisory">
                            <h3><?php echo htmlspecialchars($page['title']); ?></h3>
                            <p><?php echo nl2br(htmlspecialchars($page['body'])); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <div class="faq-category">
            <h3>❓ About Cataract Missions</h3>

            <div class="faq-item">
                <div class="faq-question">
                    <span>What is a cataract mission?</span>
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>A cataract mission is a medical outreach program where ophthalmologists and healthcare teams provide free or subsidized cataract surgery to patients in need. These missions are organized by NGOs, government health offices, hospitals, and other healthcare providers to make eye care accessible to communities that might not otherwise have access to such services.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Why are these missions free?</span>
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>These missions are funded by healthcare organizations, NGOs, government health programs, and charitable donations. The goal is to provide access to essential eye care to people who cannot afford expensive cataract surgery, preventing unnecessary blindness and improving quality of life.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Am I eligible for the mission?</span>
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>Most cataract missions accept patients of all ages who have cataracts. However, some missions may have specific eligibility criteria. You'll be assessed during health screening to ensure you're a suitable candidate. Any medical conditions or eye issues will be evaluated by the medical team.</p>
                </div>
            </div>
        </div>

        <div class="faq-category">
            <h3>📋 Booking Your Slot</h3>

            <div class="faq-item">
                <div class="faq-question">
                    <span>How do I book a mission slot?</span>
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>Log in to the Patient Portal, browse available missions, select a mission that works for you, and submit a booking request. The request starts as <strong>booked</strong>. Your slot is secured only when an admin changes the status to <strong>confirmed</strong>.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Can I book multiple slots?</span>
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>You can book slots for yourself and your family members. Each person will need to provide their own name and contact number. If you're booking for a dependent or elderly family member, you can include their information during the booking process.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Can I cancel or reschedule my booking?</span>
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>If you need to cancel or reschedule, please contact the mission organizer directly as soon as possible using the contact information provided in your booking details. This helps them manage slots and accommodate other patients waiting for treatment.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>How do I find my booking later?</span>
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>Log in to the Patient Portal. Your dashboard shows booking status, pre-screening, and printable slips for confirmed bookings.</p>
                </div>
            </div>
        </div>

        <div class="faq-category">
            <h3>📋 Before the Mission</h3>

            <div class="faq-item">
                <div class="faq-question">
                    <span>What should I bring to the mission?</span>
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>Please bring the following:</p>
                    <ul>
                        <li>A valid ID or identification document</li>
                        <li>Any existing medical records or eye prescriptions</li>
                        <li>A list of any medications you're currently taking</li>
                        <li>Comfortable, clean clothing</li>
                        <li>Someone to accompany you for support</li>
                    </ul>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Do I need to fast before surgery?</span>
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>This depends on the type of anesthesia and the organizer's specific protocol. The mission organizer will provide pre-surgery instructions, including whether fasting is required. Follow those instructions carefully for your safety.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Can I continue my regular medications?</span>
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>Most regular medications can be continued, but this should be confirmed during health screening. The medical team will assess your specific situation. Some medications (like blood thinners) may need to be adjusted before surgery. Always inform the medical team about all medications you're taking.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>What if I have other medical conditions?</span>
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>Inform the medical team about any existing conditions like diabetes, hypertension, or heart disease during health screening. Cataract surgery can often be safely performed even with these conditions. The team will assess and take necessary precautions for your safety.</p>
                </div>
            </div>
        </div>

        <div class="faq-category">
            <h3>About the Surgery</h3>

            <div class="faq-item">
                <div class="faq-question">
                    <span>How long does cataract surgery take?</span>
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>The surgical procedure is usually brief, but the entire mission visit can take longer because of check-in, screening, preparation, recovery, and discharge instructions. Bring someone with you because you should not drive immediately after surgery.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Will the surgery hurt?</span>
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>Cataract surgery is commonly done with local anesthesia that numbs the eye area. You may feel pressure, see light, or hear instruments. Tell the medical team right away if you feel pain or unusual discomfort so they can respond.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Can both eyes be operated on the same day?</span>
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>Many surgeons schedule the second eye after the first eye has healed, but timing depends on the organizer's protocol and your medical assessment. Discuss this with the medical team.</p>
                </div>
            </div>
        </div>

        <div class="faq-category">
            <h3>After the Surgery</h3>

            <div class="faq-item">
                <div class="faq-question">
                    <span>What is the recovery period?</span>
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>Many people notice vision improvement within a few days, although vision can be blurry at first while the eye heals and adjusts. Complete healing often happens within about eight weeks. Use prescribed drops and follow the medical team's activity restrictions.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>What restrictions should I follow after surgery?</span>
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>After cataract surgery, avoid:</p>
                    <ul>
                        <li>Strenuous activities and exercise for 2-4 weeks</li>
                        <li>Heavy lifting (more than 5-10 lbs)</li>
                        <li>Swimming or bathing where water might enter the eye</li>
                        <li>Driving until your doctor says your vision is safe</li>
                        <li>Rubbing or pressing on the eye</li>
                    </ul>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>How do I use the prescribed eye drops?</span>
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>Follow the schedule provided by the medical team carefully. Wash your hands before application, pull down your lower eyelid, and instill the drops. Close your eye and press gently on the inner corner for 1-2 minutes to prevent the drops from draining. Use exactly as prescribed—don't skip doses or stop early.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>When do I need follow-up appointments?</span>
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>Follow-up schedules vary. Mayo Clinic notes that patients are usually seen one day after surgery and again later, with another visit depending on the doctor's preference. Attend every appointment your mission team schedules.</p>
                </div>
            </div>
        </div>

        <div class="faq-category">
            <h3>Other Questions</h3>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Will I need glasses after surgery?</span>
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>You may still need glasses at least some of the time after surgery. Your doctor will tell you when your eye has healed enough for a final eyeglass prescription, often between one and three months after surgery.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>What if I experience complications?</span>
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>Contact your doctor immediately if you experience vision loss, pain that does not improve with medicine, increased redness, eyelid swelling, light flashes, or many new floaters. The mission organizer should provide emergency contact information.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Is my information safe and private?</span>
                    <span class="faq-toggle">▼</span>
                </div>
                <div class="faq-answer">
                    <p>The demo stores patient and intake details in the local database so coordinators can review bookings and health flags. A real deployment should add formal privacy notices, access controls, audit logs, and compliance review before collecting live patient data.</p>
                </div>
            </div>
        </div>

        <div class="faq-support">
            <h3>Still Have Questions?</h3>
            <p>If you couldn't find the answer you're looking for, check our <a href="patient_guide.php">Patient Guide</a> for more detailed information about the entire mission process.</p>
        </div>
    </main>

    <script>
        // Expand/collapse FAQ items
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', function() {
                this.parentElement.classList.toggle('open');
            });
        });
    </script>
</body>
</html>
