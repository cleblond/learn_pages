<?php
require_once "../config.php";

use \Tsugi\Core\LTIX;

$LTI = LTIX::requireData();
$p = $CFG->dbprefix;

// 1. Handle Configuration Saving (Instructors Only)
if ( $USER->instructor && isset($_POST['page_path']) ) {
    $PDOX->queryDie("INSERT INTO {$p}eo_learn_pages
        (link_id, page_path, updated_at)
        VALUES ( :LI, :PP, NOW() )
        ON DUPLICATE KEY UPDATE page_path=:PP, updated_at = NOW()",
        array(
            ':LI' => $LINK->id,
            ':PP' => $_POST["page_path"]
        )
    );
    $_SESSION['success'] = "Page configuration updated.";
    header('Location: '.addSession('index.php'));
    return;
}

// 2. Retrieve the currently selected page for this Link
$row = $PDOX->rowDie("SELECT page_path FROM {$p}eo_learn_pages
    WHERE link_id = :LI",
    array(':LI' => $LINK->id)
);
$currentPage = $row ? $row['page_path'] : false;

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();

// --- INSTRUCTOR VIEW: Configuration UI ---
if ( $USER->instructor ) {
    echo("<h2>Grav Page Configuration</h2>");
    
    // Fetch pages from sitemap
    $sitemapUrl = "https://learn.openochem.org/learn/sitemap.xml";
    $pages = [];
    try {
        $xml = simplexml_load_file($sitemapUrl);
        if ($xml) {
            foreach ($xml->url as $urlItem) {
                $pages[] = (string)$urlItem->loc;
            }
        }
    } catch (Exception $e) {
        echo("<p class='alert alert-danger'>Error loading sitemap.</p>");
    }

    ?>
    <div class="well">
        <form method="post">
            <label for="page_path">Select the Grav page to display to students:</label>
            <select name="page_path" id="page_path" class="form-control">
                <option value="">-- Select a Page --</option>
                <?php foreach ($pages as $url): ?>
                    <option value="<?= $url ?>" <?= ($currentPage == $url) ? 'selected' : '' ?>>
                        <?= str_replace('https://learn.openochem.org/', '', $url) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br/>
            <input type="submit" class="btn btn-primary" value="Save Configuration">
        </form>
    </div>
    <hr>
    <h4>Preview:</h4>
    <?php
}

// --- STUDENT & PREVIEW VIEW: The Injected Content ---
if ($currentPage) {
    // We use an iframe to inject the Grav page. 
    // Note: Ensure your Grav site allows iframing (X-Frame-Options or CSP headers)
    ?>
    <div id="grav-container" style="width: 100%; height: 800px;">
        <iframe src="<?= $currentPage ?>?chromeless=1" 
                style="width: 100%; height: 100%; border: none;">
        </iframe>
    </div>
    <?php
} else {
    echo("<p>No page has been configured yet. Please contact your instructor.</p>");
}

$OUTPUT->footerStart();
$OUTPUT->footerEnd();
