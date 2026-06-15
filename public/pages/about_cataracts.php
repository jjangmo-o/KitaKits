<?php
require_once(__DIR__ . '/../includes/layout.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Cataracts | KitaKits</title>
    <?php kk_render_favicon('pages'); ?>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="page-about-cataracts">
    <?php kk_render_header(['section' => 'pages', 'active' => 'about']); ?>
    <?php kk_render_breadcrumbs('pages', [['label' => 'About Cataracts']]); ?>

    <main class="container">
        <a href="../index.php" class="btn-back">Back to Missions</a>

        <section class="figma-page-intro about-intro">
            <h1>About Cataracts</h1>
            <p>Understand what cataracts are, how to recognize them, and how surgery restores clear vision — backed by medical evidence.</p>
            <div class="verified-chips">
                <span>Verified by:</span>
                <b>WHO Eye Health</b>
                <b>Philippine Academy of Ophthalmology</b>
                <b>American Academy of Ophthalmology</b>
            </div>
        </section>

        <div class="info-section">
            <h2>What is a Cataract?</h2>
            <p>A cataract is a clouding of the eye's natural lens, which sits behind the iris and pupil. The lens focuses light onto the retina — when it becomes cloudy, images appear blurred or hazy.</p>
            <p>Cataracts are the <strong>leading cause of blindness worldwide</strong>, but they are completely treatable. In the Philippines, free surgery missions help thousands of patients regain their sight every year.</p>
        </div>

        <div class="info-section">
            <h2>Symptoms</h2>
            <p>Common signs that may indicate cataracts are developing</p>
            <ul class="symptom-checklist">
                <li>Blurry or cloudy vision</li>
                <li>Sensitivity to light and glare</li>
                <li>Poor night vision</li>
                <li>Fading or yellowed colors</li>
                <li>Frequent prescription changes</li>
                <li>Halos around lights</li>
                <li>Difficulty reading fine print</li>
                <li>Double vision in one eye</li>
            </ul>
        </div>

        <section class="about-risk-section">
            <h2>Risk Factors</h2>
            <div class="about-risk-grid">
                <article>
                    <h3>Who is at higher risk?</h3>
                    <ul class="risk-factor-list">
                        <li><strong>Aging:</strong> The most common risk factor. Most cataracts develop after age 60, though they can occur at any age.</li>
                        <li>Diabetes or high blood sugar</li>
                        <li>Family history of cataracts</li>
                        <li>Smoking or heavy alcohol use</li>
                        <li>Long-term corticosteroid medication</li>
                        <li>Past eye injury or eye disease</li>
                    </ul>
                </article>

                <article class="risk-reduce-card">
                    <h3>Reduce your risk</h3>
                    <ul class="risk-factor-list">
                        <li>Wear UV-protective sunglasses outdoors</li>
                        <li>Use eye protection during risky work or sports</li>
                        <li>Do not smoke</li>
                        <li>Manage diabetes and other health conditions</li>
                        <li>Choose fruits, vegetables, leafy greens, nuts, and whole grains</li>
                    </ul>
                    <p><a href="https://www.mayoclinic.org/diseases-conditions/cataracts/symptoms-causes/syc-20353790" target="_blank" rel="noopener">Source: Mayo Clinic cataract prevention and risk factors</a></p>
                </article>
            </div>
        </section>

        <div class="info-section">
            <h2>Types of Cataracts</h2>
            <p>Cataracts are classified based on where they form in the lens:</p>

            <h3>Nuclear Cataracts</h3>
            <p>Form in the center (nucleus) of the lens. These are most common and typically associated with aging. They progress slowly and may initially improve near vision (temporary improvement, then worsening).</p>

            <h3>Cortical Cataracts</h3>
            <p>Develop in the lens cortex (surrounding material). They form like spokes of a wheel extending inward and often cause more glare and difficulty with contrasts.</p>

            <h3>Posterior Subcapsular Cataracts</h3>
            <p>Form at the back of the lens. These can develop rapidly and often cause more symptoms even in early stages, particularly affecting reading and night vision.</p>

            <h3>Congenital Cataracts</h3>
            <p>Present at birth or develop in early childhood. Rare, but can be caused by infection, inflammation, or genetic factors during pregnancy.</p>
        </div>

        <div class="info-section">
            <h2>Treatment Options</h2>
            <p>Treatment depends on the severity of your cataracts and how much they affect your daily life:</p>

            <div class="treatment-options">
                <div class="treatment-option">
                    <h4>Early Stage Management</h4>
                    <p>If cataracts are mild and not interfering with daily activities, your doctor may recommend:</p>
                    <ul class="compact-list">
                        <li>Stronger eyeglasses or contacts</li>
                        <li>Anti-glare sunglasses</li>
                        <li>Brighter lighting for reading</li>
                        <li>Regular eye exams to monitor progression</li>
                    </ul>
                </div>
                <div class="treatment-option">
                    <h4>Cataract Surgery</h4>
                    <p>The only definitive treatment when cataracts significantly affect vision:</p>
                    <ul class="compact-list">
                        <li>Surgical removal of the cloudy lens</li>
                        <li>Replacement with an artificial intraocular lens (IOL)</li>
                        <li>Usually a brief procedure, with a longer visit for preparation and recovery checks</li>
                        <li>Most people experience improved vision after surgery</li>
                    </ul>
                </div>
            </div>

            <div class="success-rate">
                <h4>Most Patients See Better</h4>
                <p>The National Eye Institute states that 9 out of 10 people who get cataract surgery can see better afterward. Individual results depend on eye health and other medical factors.</p>
            </div>
            <p><a href="https://www.nei.nih.gov/eye-health-information/eye-conditions-and-diseases/cataracts" target="_blank" rel="noopener">Source: National Eye Institute treatment section</a></p>
        </div>

        <div class="info-section">
            <h2>Cataract Surgery: What to Expect</h2>

            <h3>How Surgery Works</h3>
            <p>The most common surgical technique is <span class="highlight">phacoemulsification</span>:</p>
            <ul class="section-list">
                <li>A small incision (usually 2-3mm) is made in the cornea</li>
                <li>An ultrasound probe breaks the cloudy lens into tiny pieces</li>
                <li>These pieces are gently suctioned out</li>
                <li>An artificial lens (IOL) is inserted through the same small incision</li>
                <li>The incision is so small it often heals without stitches</li>
            </ul>

            <h3>Benefits of Cataract Surgery</h3>
            <ul class="section-list">
                <li>Restored clear vision</li>
                <li>Improved quality of life and independence</li>
                <li>Ability to drive safely again</li>
                <li>Restoration of color vision</li>
                <li>Improved night vision</li>
            </ul>

            <div class="medical-note">
                After surgery, vision often starts improving within a few days, but complete healing can take up to about eight weeks. Mayo Clinic advises contacting a doctor right away for vision loss, pain that does not improve with medicine, increased redness, eyelid swelling, light flashes, or many new floaters.
            </div>

            <p><strong>Note:</strong> KitaKits provides access to free or subsidized cataract surgery through our partner mission programs. Check our homepage to find upcoming missions in your area.</p>
            <p><a href="https://www.mayoclinic.org/tests-procedures/cataract-surgery/about/pac-20384765" target="_blank" rel="noopener">Source: Mayo Clinic cataract surgery recovery information</a></p>
        </div>

        <div class="info-section">
            <h2>Myths vs. Facts About Cataracts</h2>

            <div class="myth-fact">
                <h4>Myth: Cataracts can spread from one eye to the other</h4>
                <p><strong>Fact:</strong> Cataracts develop independently in each eye. However, most people develop cataracts in both eyes over time as both are exposed to the same risk factors.</p>
            </div>

            <div class="myth-fact">
                <h4>Myth: You must be elderly to have cataracts</h4>
                <p><strong>Fact:</strong> While more common in older adults, cataracts can develop at any age, including in infants. Age is just one risk factor among many.</p>
            </div>

            <div class="myth-fact">
                <h4>Myth: Cataracts can be treated with eye drops</h4>
                <p><strong>Fact:</strong> There is no medication or eye drop that can cure cataracts. Surgery is the only definitive treatment.</p>
            </div>

            <div class="myth-fact">
                <h4>Myth: Surgery is risky and often fails</h4>
                <p><strong>Fact:</strong> Cataract surgery is common and restores vision for most people, but every surgery has risks. Ask the eye care team about benefits, risks, and timing for your case.</p>
            </div>

            <div class="myth-fact fact">
                <h4>Fact: Early detection and treatment improve outcomes</h4>
                <p><strong>Why it matters:</strong> Detecting cataracts early allows your doctor to monitor progression and schedule surgery at the optimal time for best results.</p>
            </div>

            <div class="myth-fact fact">
                <h4>Fact: Vision loss from cataracts is reversible</h4>
                <p><strong>Why it matters:</strong> Cataract-related clouding can usually be treated with surgery, but final vision also depends on the health of the retina, optic nerve, and the rest of the eye.</p>
            </div>

            <div class="myth-fact fact">
                <h4>Fact: You can have both cataracts and other eye conditions</h4>
                <p><strong>Why it matters:</strong> It's possible to have cataracts alongside glaucoma, macular degeneration, or other conditions. This is why comprehensive eye exams are important.</p>
            </div>
        </div>

        <div class="info-section">
            <h2>When to See an Eye Doctor</h2>
            <p>Schedule an eye exam if you experience:</p>
            <ul class="risk-factor-list">
                <li>Progressive vision blur or haziness</li>
                <li>Difficulty driving at night</li>
                <li>Colors appearing less vibrant</li>
                <li>Increased sensitivity to glare or light</li>
                <li>Frequent changes in eyeglass prescription</li>
                <li>Double vision in one eye</li>
                <li>Any vision changes or eye discomfort</li>
            </ul>

            <div class="success-rate success-rate-alt">
                <h4>Early Diagnosis is Important</h4>
                <p>Regular eye exams can detect cataracts early, allowing for better planning and treatment timing. Don't wait until vision loss significantly impacts your life.</p>
            </div>
            <p><a href="https://www.nei.nih.gov/eye-health-information/eye-conditions-and-diseases/cataracts" target="_blank" rel="noopener">Source: National Eye Institute diagnosis and symptom guidance</a></p>
        </div>

        <div class="source-panel figma-secondary-source">
            <h3>Verified Medical Sources</h3>
            <p>This page was cross-checked against patient information from the National Eye Institute, Mayo Clinic, the World Health Organization, and the American Academy of Ophthalmology. Links open the original source pages.</p>
            <div class="source-links">
                <a class="source-button" href="https://www.nei.nih.gov/eye-health-information/eye-conditions-and-diseases/cataracts" target="_blank" rel="noopener">National Eye Institute</a>
                <a class="source-button" href="https://www.mayoclinic.org/diseases-conditions/cataracts/symptoms-causes/syc-20353790" target="_blank" rel="noopener">Mayo Clinic: Symptoms & Causes</a>
                <a class="source-button" href="https://www.mayoclinic.org/tests-procedures/cataract-surgery/about/pac-20384765" target="_blank" rel="noopener">Mayo Clinic: Surgery</a>
                <a class="source-button" href="https://www.who.int/en/news-room/fact-sheets/detail/blindness-and-visual-impairment" target="_blank" rel="noopener">WHO Vision Impairment Fact Sheet</a>
                <a class="source-button" href="https://store.aao.org/media/resources/051225/Cataract-Surgery_09-19.pdf" target="_blank" rel="noopener">AAO Cataract Surgery PDF</a>
            </div>
        </div>

        <div class="info-section callout-section">
            <h2>Ready for Treatment?</h2>
            <p>If you believe you have cataracts or have been diagnosed with cataracts, KitaKits can help you find a free or subsidized cataract surgery mission in your area.</p>
            <div class="action-spacer">
                <a href="../index.php" class="btn-primary inline-flex-link">
                    Find Missions Near You
                </a>
            </div>
        </div>
    </main>
    <?php kk_render_footer('pages'); ?>
</body>
</html>
