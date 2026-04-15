<?php
require_once "../config.php";

use \Tsugi\Core\LTIX;

$LTI = LTIX::requireData();
$p = $CFG->dbprefix;

// 1. Handle Configuration Saving (Instructors Only)
if ( $USER->instructor && isset($_POST['page_path']) ) {
    $PDOX->queryDie("INSERT INTO {$p}eo_learn_pages
        (link_id, page_path, user_id,updated_at)
        VALUES ( :LI, :PP, :UID, NOW() )
        ON DUPLICATE KEY UPDATE page_path=:PP, updated_at = NOW()",
        array(
            ':LI' => $LINK->id,
            ':UID' => $USER->id,
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

?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Ensure the search box matches the Tsugi/Bootstrap style */
    .select2-container .select2-selection--single {
        height: 38px !important;
        padding: 5px;
    }
</style>
<?php


$OUTPUT->bodyStart();
$OUTPUT->flashMessages();

// --- INSTRUCTOR VIEW: Configuration UI ---
if ( $USER->instructor ) {
    echo("<h2>Grav Page Configuration</h2>");
    
    // Fetch pages from sitemap
    //$sitemapUrl = "https://learn.openochem.org/learn/sitemap.xml";
    $sitemapUrl = "https://learn.openochem.org/learn/sitemap"; 
    $pages = [];
    $seenUrls = []; // This is critical for filtering

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $html = file_get_contents($sitemapUrl);

    if ($html) {
        $doc->loadHTML($html);
        $links = $doc->getElementsByTagName('a');
        
        foreach ($links as $link) {
            $url = trim($link->getAttribute('href'));
            $title = trim($link->nodeValue);

            // 1. Normalize the URL immediately
            if (strpos($url, 'http') !== 0) {
                $url = "https://learn.openochem.org" . $url;
            }

            // 2. STICKY FILTERING
            // Only include if /learn/ is in the URL 
            // AND it's not just a jump link (hash)
            // AND the title isn't a single character (like a '>' icon)
            if (strpos($url, '/learn/') !== false && 
                strpos($url, '#') === false && 
                strlen($title) > 2) {

                // 3. THE FINAL CHECK: If we haven't seen this EXACT URL yet
                if (!isset($seenUrls[$url])) {
                    $pages[] = [
                        'url' => $url,
                        'title' => $title
                    ];
                    // Use the URL as a key for O(1) lookup speed
                    $seenUrls[$url] = true; 
                }
            }
        }
    }
    libxml_clear_errors();

    //var_dump($pages);

    ?>
    <div class="well">  
        <form method="post">
            <label for="page_path">Search for a Grav Page:</label>
            <select name="page_path" id="grav-page-select" class="form-control">
                <option value="">-- Start typing a chapter or topic... --</option>
                <?php foreach ($pages as $page): ?>
                    <option value="<?= htmlspecialchars($page['url']) ?>" <?= ($currentPage == $page['url']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($page['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br/>
            <input type="submit" class="btn btn-primary" value="Save Configuration">
        </form>
    </div>
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
?>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize Select2 on our dropdown
    $('#grav-page-select').select2({
        placeholder: "Search for a page...",
        allowClear: true,
        width: '100%' // Ensure it fills the well
    });
});
</script>
<?php
$OUTPUT->footerEnd();
