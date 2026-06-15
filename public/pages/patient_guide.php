<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/../includes/layout.php');

// Ensure the page is served as UTF-8 to avoid character decoding issues
header('Content-Type: text/html; charset=utf-8');
$managed_content = [];

try {
    $content_stmt = $conn->prepare("SELECT page_key, title, body
                                    FROM content_pages
                                    WHERE status = 'published'
                                      AND page_key IN ('patient_guide_preparation', 'mission_guidelines', 'day_of_instructions')
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
    <title>Patient Guide | KitaKits</title>
    <?php kk_render_favicon('pages'); ?>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="page-patient-guide">
    <?php kk_render_header(['section' => 'pages', 'active' => 'guide']); ?>
    <?php kk_render_breadcrumbs('pages', [['label' => 'Patient Guide']]); ?>

    <main class="container">
        <a href="../index.php" class="btn-back">Back to Missions</a>

        <section class="figma-page-intro">
            <h1>Patient Guide</h1>
            <p>Everything you need to know before, during, and after your free cataract surgery — in one place.</p>
        </section>

        <div class="guide-section guide-journey">
            <div class="section-header">
                <div>
                    <h2>Your Surgery Journey</h2>
                    <p>A step-by-step overview of the entire process</p>
                </div>
            </div>

            <div class="timeline">
                <div class="timeline-item">
                    <h4>Book Your Slot</h4>
                    <p>Choose a mission and reserve your spot online. Save your contact number — you'll need it to retrieve your booking.</p>
                </div>
                <div class="timeline-item">
                    <h4>Pre-Op Preparation</h4>
                    <p>Complete required lab tests (CBC, blood sugar). Stop blood thinners as instructed by your doctor.</p>
                </div>
                <div class="timeline-item">
                    <h4>Day Before</h4>
                    <p>Fast starting midnight (or 6-8 hours prior), arrange your companion, and prepare all IDs and documents.</p>
                </div>
                <div class="timeline-item">
                    <h4>Arrival &amp; Screening</h4>
                    <p>Arrive 30 minutes early. A medical team screens you and verifies your eligibility before surgery.</p>
                </div>
                <div class="timeline-item">
                    <h4>Surgery</h4>
                    <p>Phacoemulsification takes 20-30 minutes under local anesthesia. You remain awake but completely pain-free.</p>
                </div>
                <div class="timeline-item">
                    <h4>Recovery &amp; Follow-up</h4>
                    <p>Rest for 1-2 hours post-op. Follow up at 1 week and 1 month, and use all prescribed eye drops.</p>
                </div>
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

        <div class="source-panel figma-secondary-source">
            <h3>Guide Sources</h3>
            <p>Preparation and recovery notes are general patient education, not a substitute for the mission team's instructions. Always follow the eye surgeon's specific instructions.</p>
            <div class="source-links">
                <a class="source-button" href="https://www.mayoclinic.org/tests-procedures/cataract-surgery/about/pac-20384765" target="_blank" rel="noopener">Mayo Clinic Cataract Surgery</a>
                <a class="source-button" href="https://store.aao.org/media/resources/051225/Cataract-Surgery_09-19.pdf" target="_blank" rel="noopener">American Academy of Ophthalmology PDF</a>
                <a class="source-button" href="https://www.nei.nih.gov/eye-health-information/eye-conditions-and-diseases/cataracts" target="_blank" rel="noopener">National Eye Institute Cataracts</a>
            </div>
        </div>

        <div class="guide-section">
            <h2>What to Bring</h2>
            <h3>Essential Documents</h3>
            <ul class="checklist">
                <li>Valid ID or identification document</li>
                <li>Any existing medical records</li>
                <li>Current eye prescription (if you wear glasses/contacts)</li>
                <li>List of current medications and allergies</li>
            </ul>

            <h3>Personal Items</h3>
            <ul class="checklist">
                <li>Comfortable, clean clothing</li>
                <li>Slippers or comfortable shoes (easy to put on/remove)</li>
                <li>Sunglasses (for after surgery - light sensitivity is common)</li>
                <li>A small bag for your belongings</li>
            </ul>

            <div class="warning-box">
                <h4>Important</h4>
                <p>Bring someone with you to accompany you home. You won't be able to drive immediately after surgery due to anesthesia and eye drops.</p>
            </div>
        </div>

        <div class="guide-section">
            <h2>Before the Mission</h2>

            <h3>1-2 Weeks Before</h3>
            <ul class="checklist">
                <li>Check your booking status in the Patient Portal</li>
                <li>Note the exact date, time, and location</li>
                <li>Inform your family/guardians about the mission</li>
                <li>Arrange transportation and a companion</li>
                <li>Gather all required documents</li>
            </ul>

            <h3>3-5 Days Before</h3>
            <ul class="checklist">
                <li>Review pre-surgery instructions from the mission organizer (if provided)</li>
                <li>Check if fasting is required</li>
                <li>Note any medication adjustments needed</li>
                <li>Prepare your ID and medical documents</li>
            </ul>

            <h3>Day Before</h3>
            <ul class="checklist">
                <li>Get good rest</li>
                <li>Wash your hair (you may not be able to for a few days after surgery)</li>
                <li>Lay out comfortable clothing</li>
                <li>Prepare your companion - inform them about the timeline</li>
                <li>Set multiple alarms for the morning</li>
            </ul>

            <h3>Morning of Mission</h3>
            <ul class="checklist">
                <li>Follow fasting instructions (if required)</li>
                <li>Eat a light meal if fasting is not required</li>
                <li>Avoid heavy eye makeup</li>
                <li>Wear comfortable clothing and avoid tight collars</li>
                <li>Leave 15 minutes earlier than planned</li>
                <li>Bring all required documents</li>
            </ul>

            <div class="tip-box">
                <h4>Pro Tip</h4>
                <p>Set a reminder on your phone with all mission details. Take a screenshot of your booking confirmation for easy reference.</p>
            </div>
        </div>

        <div class="guide-section">
            <h2>At the Mission</h2>

            <h3>Upon Arrival (30 minutes before scheduled time)</h3>
            <ul class="checklist">
                <li>Report to the registration desk</li>
                <li>Present your ID and booking information</li>
                <li>Complete patient registration forms</li>
                <li>Disclose all medical conditions and allergies</li>
                <li>List all medications you're currently taking</li>
            </ul>

            <h3>Health Screening</h3>
            <ul class="checklist">
                <li>Have blood pressure and blood sugar checked</li>
                <li>Complete eye examination</li>
                <li>Answer medical history questions honestly</li>
                <li>Ask the doctor any questions or concerns</li>
            </ul>

            <h3>Before Surgery</h3>
            <ul class="checklist">
                <li>Put on the provided surgical gown/clothing</li>
                <li>Remove jewelry, watches, and valuables</li>
                <li>Use the restroom before being called to the operating room</li>
                <li>Take prescribed pre-surgery medications if given</li>
                <li>Stay calm - the medical team is experienced and ready to help</li>
            </ul>

            <h3>During Surgery</h3>
            <ul class="checklist">
                <li>The surgeon will apply local anesthesia to numb the eye area</li>
                <li>You may see light and hear surgical instruments</li>
                <li>Keep your eye as still as possible</li>
                <li>Follow the surgeon's instructions (e.g., "Look up," "Don't move")</li>
                <li>The procedure is usually brief, but the full visit takes longer because of registration, screening, preparation, recovery, and discharge instructions</li>
            </ul>

            <h3>After Surgery</h3>
            <ul class="checklist">
                <li>Rest in the recovery area as instructed</li>
                <li>The medical team will provide aftercare instructions</li>
                <li>Receive prescribed eye drops and medications</li>
                <li>Get appointment details for follow-up visits</li>
                <li>Don't remove the eye patch until instructed</li>
            </ul>
        </div>

        <div class="guide-section">
            <h2>Recovery at Home</h2>

            <h3>First 24 Hours</h3>
            <ul class="checklist">
                <li>Rest with your head elevated</li>
                <li>Keep the eye patch on as instructed</li>
                <li>Use prescribed eye drops exactly as directed</li>
                <li>Avoid any strenuous activity</li>
                <li>Don't rub or touch your eye</li>
                <li>Avoid water in your eye (no swimming or bathing the operative eye)</li>
            </ul>

            <h3>First Week</h3>
            <ul class="checklist">
                <li>Use eye drops on schedule (set phone reminders)</li>
                <li>Wear sunglasses to protect your eye</li>
                <li>Avoid dust and dirty environments</li>
                <li>Don't drive until your doctor says your vision is safe for driving</li>
                <li>Avoid strenuous exercise and heavy lifting</li>
                <li>Sleep with your head elevated on multiple pillows</li>
                <li>Attend your first follow-up appointment if scheduled by the medical team</li>
            </ul>

            <h3>2-4 Weeks Recovery</h3>
            <ul class="checklist">
                <li>Continue eye drops as prescribed</li>
                <li>Gradually increase daily activities as approved</li>
                <li>Return to light work or daily tasks</li>
                <li>Avoid swimming and water sports</li>
                <li>Be cautious with dusty or windy environments</li>
                <li>Attend all scheduled follow-up appointments</li>
            </ul>

            <h3>4-8 Weeks (Healing Period)</h3>
            <ul class="checklist">
                <li>Continue eye drops if still prescribed</li>
                <li>Gradually return to normal activities</li>
                <li>Resume exercise (with doctor approval)</li>
                <li>Return to water activities (swimming, etc.)</li>
                <li>Get new glasses prescription if needed</li>
                <li>Attend any final follow-up appointment set by the eye care team</li>
            </ul>

            <div class="dos-donts">
                <div class="do-box">
                    <h4>DO</h4>
                    <ul>
                        <li>Use prescribed eye drops exactly</li>
                        <li>Protect your eye from dust and injury</li>
                        <li>Wear sunglasses outdoors</li>
                        <li>Keep all appointments</li>
                        <li>Get adequate rest</li>
                        <li>Report any unusual symptoms</li>
                    </ul>
                </div>
                <div class="dont-box">
                    <h4>DON'T</h4>
                    <ul>
                        <li>Rub or press on your eye</li>
                        <li>Get water in your eye</li>
                        <li>Do strenuous activities too soon</li>
                        <li>Lift anything heavy (>10 lbs)</li>
                        <li>Skip eye drops or appointments</li>
                        <li>Ignore warning signs/symptoms</li>
                    </ul>
                </div>
            </div>

            <div class="medical-note">
                Mayo Clinic notes that vision often starts improving within a few days and complete healing often happens within eight weeks. Follow-up schedules vary; many patients are checked the next day and again later, depending on the surgeon's plan.
                <a href="https://www.mayoclinic.org/tests-procedures/cataract-surgery/about/pac-20384765" target="_blank" rel="noopener">Read the original Mayo Clinic guidance.</a>
            </div>
        </div>

        <div class="guide-section">
            <h2>Warning Signs</h2>
            <p>Contact your doctor immediately if you experience any of the following:</p>
            <ul class="checklist">
                <li>Sudden or severe eye pain</li>
                <li>Sudden loss of vision or blurred vision</li>
                <li>Persistent redness or swelling</li>
                <li>Eye discharge or pus</li>
                <li>Light flashes or new floaters</li>
                <li>Nausea or vomiting (severe)</li>
                <li>Bleeding from the eye</li>
            </ul>

            <div class="warning-box">
                <h4>Emergency</h4>
                <p>If you experience sudden vision loss, severe pain, or bleeding, go to the nearest hospital emergency room immediately. Don't delay - these could indicate serious complications requiring urgent attention.</p>
            </div>
            <p><a href="https://www.mayoclinic.org/tests-procedures/cataract-surgery/about/pac-20384765" target="_blank" rel="noopener">Source: Mayo Clinic post-surgery warning signs</a></p>
        </div>

        <div class="guide-section">
            <h2>What to Expect After Recovery</h2>
            <p>After successful cataract surgery and recovery, many patients experience:</p>
            <ul class="checklist">
                <li>Clearer vision</li>
                <li>Brighter, more vibrant colors</li>
                <li>Better ability to read and drive</li>
                <li>Improved quality of life and independence</li>
                <li>Greater enjoyment of daily activities</li>
            </ul>

            <div class="tip-box">
                <h4>Remember</h4>
                <p>You may need glasses at least some of the time after cataract surgery. Your doctor will tell you when your eye has healed enough for a final eyeglass prescription.</p>
            </div>
        </div>

        <div class="guide-section callout-section">
            <h2>Need Help?</h2>
            <p>If you have additional questions:</p>
            <ul class="checklist">
                <li>Check our <a href="faq.php" class="guide-link">FAQ page</a> for common questions</li>
                <li>Contact the mission organizer directly with specific questions</li>
                <li>Talk to the medical team during your visits</li>
                <li>Don't hesitate to ask - your health and safety are our priority</li>
            </ul>
        </div>
    </main>
    <?php kk_render_footer('pages'); ?>
</body>
</html>
