<?php
// Normalize and validate URL input
function normalizeURL($url) {
    $url = trim($url);
    
    // If it looks like a domain without protocol, add https://
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        // Simple validation - check if it looks like a domain
        if (strpos($url, '.') !== false || $url === 'localhost') {
            $url = "https://" . $url;
        }
    }
    
    return $url;
}

// Enhanced SEO Analysis Function
function fetchMetaTags($url) {
    $url = normalizeURL($url);
    
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return ['error' => 'Invalid URL format. Please enter a valid domain (e.g., google.com, example.com)'];
    }

    $context = stream_context_create([
        'http' => ['timeout' => 15, 'follow_location' => true, 'max_redirects' => 5],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
    ]);
    
    $headers = @get_headers($url, 1, $context);
    
    if ($headers === false) {
        return ['error' => 'Unable to reach the website. Please check if the URL is correct and the site is accessible.'];
    }
    
    $statusCode = isset($headers[0]) ? $headers[0] : '';
    if (strpos($statusCode, '200') === false && strpos($statusCode, '301') === false && strpos($statusCode, '302') === false) {
        return ['error' => 'Website returned status: ' . $statusCode . '. Please check if the site is online.'];
    }

    $html = @file_get_contents($url, false, $context);
    if ($html === false) {
        return ['error' => 'Could not fetch website content. The site may be blocking automated access.'];
    }

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    @$doc->loadHTML($html);
    libxml_clear_errors();
    
    $metaTags = [];
    $metaTags['original_url'] = $url;
    
    // Extract title
    $titleElement = $doc->getElementsByTagName('title')->item(0);
    $metaTags['title'] = $titleElement ? trim($titleElement->nodeValue) : '';
    
    // Extract all meta tags
    $metas = $doc->getElementsByTagName('meta');
    foreach ($metas as $meta) {
        $name = $meta->getAttribute('name') ?: $meta->getAttribute('property');
        if ($name) {
            $metaTags[strtolower($name)] = $meta->getAttribute('content');
        }
    }
    
    // Extract all links
    $links = $doc->getElementsByTagName('link');
    foreach ($links as $link) {
        $rel = $link->getAttribute('rel');
        if ($rel) {
            $metaTags['link_' . $rel] = $link->getAttribute('href');
        }
    }
    
    // Extract heading structure (H1-H6)
    $h1s = $doc->getElementsByTagName('h1');
    $h2s = $doc->getElementsByTagName('h2');
    $h3s = $doc->getElementsByTagName('h3');
    $h4s = $doc->getElementsByTagName('h4');
    $h5s = $doc->getElementsByTagName('h5');
    $h6s = $doc->getElementsByTagName('h6');
    
    $metaTags['h1_count'] = $h1s->length;
    $metaTags['h2_count'] = $h2s->length;
    $metaTags['h3_count'] = $h3s->length;
    $metaTags['h4_count'] = $h4s->length;
    $metaTags['h5_count'] = $h5s->length;
    $metaTags['h6_count'] = $h6s->length;
    
    $h1Contents = [];
    foreach ($h1s as $h1) {
        $h1Contents[] = trim($h1->nodeValue);
    }
    $metaTags['h1_contents'] = $h1Contents;
    
    // Extract image data
    $imgs = $doc->getElementsByTagName('img');
    $metaTags['img_count'] = $imgs->length;
    $metaTags['img_with_alt'] = 0;
    $metaTags['img_with_title'] = 0;
    $imageDetails = [];
    
    foreach ($imgs as $img) {
        $alt = $img->getAttribute('alt');
        $title = $img->getAttribute('title');
        if (!empty($alt)) {
            $metaTags['img_with_alt']++;
        }
        if (!empty($title)) {
            $metaTags['img_with_title']++;
        }
        $imageDetails[] = [
            'alt' => $alt,
            'title' => $title,
            'src' => $img->getAttribute('src')
        ];
    }
    $metaTags['image_details'] = $imageDetails;
    
    // Extract link analysis
    $allLinks = $doc->getElementsByTagName('a');
    $metaTags['internal_links'] = 0;
    $metaTags['external_links'] = 0;
    $metaTags['nofollow_links'] = 0;
    $metaTags['total_links'] = $allLinks->length;
    
    $urlHost = parse_url($url, PHP_URL_HOST);
    foreach ($allLinks as $link) {
        $href = $link->getAttribute('href');
        $rel = $link->getAttribute('rel');
        if (!empty($href)) {
            if (strpos($href, 'http') === 0) {
                $linkHost = parse_url($href, PHP_URL_HOST);
                if ($linkHost === $urlHost) {
                    $metaTags['internal_links']++;
                } else {
                    $metaTags['external_links']++;
                }
            } else {
                $metaTags['internal_links']++;
            }
            if (strpos($rel, 'nofollow') !== false) {
                $metaTags['nofollow_links']++;
            }
        }
    }
    
    // Extract page content statistics
    $body = $doc->getElementsByTagName('body')->item(0);
    if ($body) {
        $bodyText = $body->textContent;
        $wordCount = str_word_count($bodyText);
        $metaTags['word_count'] = $wordCount;
    } else {
        $metaTags['word_count'] = 0;
    }
    
    // Extract structured data (JSON-LD, microdata, etc)
    $scripts = $doc->getElementsByTagName('script');
    $metaTags['structured_data'] = false;
    $metaTags['json_ld_count'] = 0;
    
    foreach ($scripts as $script) {
        if ($script->getAttribute('type') === 'application/ld+json') {
            $metaTags['structured_data'] = true;
            $metaTags['json_ld_count']++;
        }
    }
    
    // Check for meta refresh (bad practice)
    $metaTags['has_meta_refresh'] = isset($metaTags['refresh']);
    
    // Check for important meta tags
    $metaTags['has_viewport'] = isset($metaTags['viewport']);
    $metaTags['has_charset'] = strpos($html, 'charset') !== false;
    $metaTags['has_lang'] = $doc->documentElement->getAttribute('lang') !== '';
    $metaTags['has_robots'] = isset($metaTags['robots']);
    $metaTags['has_canonical'] = isset($metaTags['link_canonical']);
    $metaTags['has_og_tags'] = isset($metaTags['og:title']) && isset($metaTags['og:description']);
    $metaTags['has_twitter_card'] = isset($metaTags['twitter:card']);
    
    // Check for favicon
    $metaTags['has_favicon'] = isset($metaTags['link_icon']) || isset($metaTags['link_shortcut icon']);
    
    // Check mobile-friendly meta tags
    $metaTags['is_mobile_friendly'] = $metaTags['has_viewport'] && (!isset($metaTags['viewport']) || strpos($metaTags['viewport'], 'width=device-width') !== false);
    
    // Calculate SEO Score and get recommendations
    $scoring = calculateSEOScore($metaTags);
    $metaTags['seo_score'] = $scoring['score'];
    $metaTags['score_details'] = $scoring['details'];
    $metaTags['recommendations'] = $scoring['recommendations'];
    
    return $metaTags;
}

// Calculate SEO Score with detailed breakdown and recommendations
function calculateSEOScore($metaTags) {
    $score = 0;
    $details = [];
    $recommendations = [];
    
    // 1. Title optimization (15 points)
    if (!empty($metaTags['title'])) {
        $score += 10;
        $details['title'] = ['points' => 10, 'status' => 'present'];
        $titleLength = strlen($metaTags['title']);
        if ($titleLength >= 30 && $titleLength <= 60) {
            $score += 5;
            $details['title_length'] = ['points' => 5, 'status' => 'optimal'];
        } else {
            $details['title_length'] = ['points' => 0, 'status' => 'suboptimal'];
            $recommendations[] = [
                'type' => 'title',
                'title' => 'Title Length',
                'message' => 'Your title is ' . ($titleLength > 60 ? 'too long' : 'too short') . ' (' . $titleLength . ' chars). Keep it between 30-60 characters for optimal display in search results.',
                'priority' => 'high'
            ];
        }
    } else {
        $details['title'] = ['points' => 0, 'status' => 'missing'];
        $recommendations[] = [
            'type' => 'title',
            'title' => 'Missing Title',
            'message' => 'Add a unique, descriptive title tag (30-60 characters). This is one of the most important SEO elements.',
            'priority' => 'critical'
        ];
    }
    
    // 2. Meta description (15 points)
    if (!empty($metaTags['description'])) {
        $score += 10;
        $details['description'] = ['points' => 10, 'status' => 'present'];
        $descLength = strlen($metaTags['description']);
        if ($descLength >= 120 && $descLength <= 160) {
            $score += 5;
            $details['description_length'] = ['points' => 5, 'status' => 'optimal'];
        } else {
            $details['description_length'] = ['points' => 0, 'status' => 'suboptimal'];
            $recommendations[] = [
                'type' => 'description',
                'title' => 'Description Length',
                'message' => 'Your description is ' . ($descLength > 160 ? 'too long' : 'too short') . ' (' . $descLength . ' chars). Keep it between 120-160 characters.',
                'priority' => 'high'
            ];
        }
    } else {
        $details['description'] = ['points' => 0, 'status' => 'missing'];
        $recommendations[] = [
            'type' => 'description',
            'title' => 'Missing Meta Description',
            'message' => 'Add a compelling meta description (120-160 characters) that summarizes your page content.',
            'priority' => 'critical'
        ];
    }
    
    // 3. H1 tags (10 points)
    if ($metaTags['h1_count'] == 1) {
        $score += 10;
        $details['h1'] = ['points' => 10, 'status' => 'optimal'];
    } elseif ($metaTags['h1_count'] > 1) {
        $score += 5;
        $details['h1'] = ['points' => 5, 'status' => 'suboptimal'];
        $recommendations[] = [
            'type' => 'h1',
            'title' => 'Multiple H1 Tags',
            'message' => 'You have ' . $metaTags['h1_count'] . ' H1 tags. Use only one H1 per page for better semantic structure.',
            'priority' => 'medium'
        ];
    } else {
        $details['h1'] = ['points' => 0, 'status' => 'missing'];
        $recommendations[] = [
            'type' => 'h1',
            'title' => 'Missing H1 Tag',
            'message' => 'Add one H1 tag that describes the main topic of your page.',
            'priority' => 'critical'
        ];
    }
    
    // 4. Open Graph tags (15 points)
    $og_score = 0;
    if (!empty($metaTags['og:title'])) $og_score += 5;
    if (!empty($metaTags['og:description'])) $og_score += 5;
    if (!empty($metaTags['og:image'])) $og_score += 5;
    
    $score += $og_score;
    $details['og_tags'] = ['points' => $og_score, 'status' => $og_score == 15 ? 'complete' : ($og_score > 0 ? 'partial' : 'missing')];
    
    if ($og_score < 15) {
        $recommendations[] = [
            'type' => 'og_tags',
            'title' => 'Open Graph Tags',
            'message' => 'Complete your Open Graph tags (og:title, og:description, og:image) for better social media sharing with ' . ($og_score == 0 ? 'none' : ($og_score == 5 ? '1 tag' : '2 tags')) . ' found.',
            'priority' => 'medium'
        ];
    }
    
    // 5. Twitter Card (5 points)
    if (!empty($metaTags['twitter:card'])) {
        $score += 5;
        $details['twitter_card'] = ['points' => 5, 'status' => 'present'];
    } else {
        $details['twitter_card'] = ['points' => 0, 'status' => 'missing'];
        $recommendations[] = [
            'type' => 'twitter',
            'title' => 'Twitter Card Missing',
            'message' => 'Add Twitter Card meta tags for better sharing on Twitter/X.',
            'priority' => 'low'
        ];
    }
    
    // 6. Canonical URL (10 points)
    if ($metaTags['has_canonical']) {
        $score += 10;
        $details['canonical'] = ['points' => 10, 'status' => 'present'];
    } else {
        $details['canonical'] = ['points' => 0, 'status' => 'missing'];
        $recommendations[] = [
            'type' => 'canonical',
            'title' => 'Missing Canonical URL',
            'message' => 'Add a canonical URL to prevent duplicate content issues.',
            'priority' => 'medium'
        ];
    }
    
    // 7. Mobile viewport (10 points)
    if ($metaTags['has_viewport']) {
        $score += 10;
        $details['viewport'] = ['points' => 10, 'status' => 'present'];
    } else {
        $details['viewport'] = ['points' => 0, 'status' => 'missing'];
        $recommendations[] = [
            'type' => 'viewport',
            'title' => 'Missing Viewport Meta Tag',
            'message' => 'Add <meta name="viewport" content="width=device-width, initial-scale=1.0"> for mobile responsiveness.',
            'priority' => 'critical'
        ];
    }
    
    // 8. Structured data (10 points)
    if ($metaTags['structured_data']) {
        $score += 10;
        $details['structured_data'] = ['points' => 10, 'status' => 'present', 'count' => $metaTags['json_ld_count']];
    } else {
        $details['structured_data'] = ['points' => 0, 'status' => 'missing'];
        $recommendations[] = [
            'type' => 'structured_data',
            'title' => 'Add Structured Data',
            'message' => 'Implement JSON-LD structured data (schema.org) to help search engines better understand your content.',
            'priority' => 'medium'
        ];
    }
    
    // 9. Image optimization (5 points)
    if ($metaTags['img_count'] > 0) {
        $altPercentage = ($metaTags['img_with_alt'] / $metaTags['img_count']) * 100;
        if ($altPercentage >= 80) {
            $score += 5;
            $details['image_alt'] = ['points' => 5, 'status' => 'good', 'percentage' => $altPercentage];
        } else {
            $details['image_alt'] = ['points' => 0, 'status' => 'needs_work', 'percentage' => $altPercentage];
            $recommendations[] = [
                'type' => 'images',
                'title' => 'Image Alt Text',
                'message' => 'Only ' . round($altPercentage) . '% of your images have alt text. Add descriptive alt text to all images for better accessibility and SEO.',
                'priority' => 'high'
            ];
        }
    } else {
        $details['image_alt'] = ['points' => 0, 'status' => 'na'];
    }
    
    // 10. Robots meta tag (5 points)
    if ($metaTags['has_robots']) {
        $score += 5;
        $details['robots'] = ['points' => 5, 'status' => 'present'];
    } else {
        $details['robots'] = ['points' => 0, 'status' => 'missing'];
    }
    
    // Bonus points
    if ($metaTags['has_charset']) {
        $score += 2;
    }
    if ($metaTags['has_lang']) {
        $score += 2;
    }
    if ($metaTags['has_favicon']) {
        $score += 2;
    }
    
    // Content analysis recommendations
    if ($metaTags['word_count'] < 300) {
        $recommendations[] = [
            'type' => 'content',
            'title' => 'Content Length',
            'message' => 'Your page has only ' . $metaTags['word_count'] . ' words. Aim for at least 300-500 words for better search visibility.',
            'priority' => 'medium'
        ];
    }
    
    if ($metaTags['h2_count'] == 0) {
        $recommendations[] = [
            'type' => 'structure',
            'title' => 'Missing Subheadings',
            'message' => 'Add H2 and H3 tags to structure your content and improve readability.',
            'priority' => 'medium'
        ];
    }
    
    if ($metaTags['has_meta_refresh']) {
        $recommendations[] = [
            'type' => 'technical',
            'title' => 'Meta Refresh Detected',
            'message' => 'Avoid using meta refresh tags. Use redirects instead.',
            'priority' => 'low'
        ];
    }
    
    // Sort recommendations by priority
    usort($recommendations, function($a, $b) {
        $priority = ['critical' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
        return $priority[$a['priority']] - $priority[$b['priority']];
    });
    
    $score = min($score, 100);
    
    return [
        'score' => $score,
        'details' => $details,
        'recommendations' => $recommendations
    ];
}

// Process form submission
$metaData = [];
$url = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
    $url = trim($_POST['url']);
    if (!empty($url)) {
        $metaData = fetchMetaTags($url);
    }
}

// Helper functions for scoring display
function getScoringClass($score) {
    if ($score >= 80) return 'excellent';
    if ($score >= 60) return 'good';
    if ($score >= 40) return 'average';
    return 'poor';
}

function getScoringText($score) {
    if ($score >= 80) return '✓ Excellent SEO';
    if ($score >= 60) return '✓ Good SEO';
    if ($score >= 40) return '⚠ Average SEO';
    return '✗ Needs Improvement';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Toolkit - Analyze Your Website</title>
    <meta name="description" content="Advanced SEO analysis tool for web developers. Extract and analyze meta tags, check SEO health score, and optimize your website for search engines.">
    <meta name="og:title" content="SEO Toolkit">
    <meta name="og:description" content="Complete SEO analysis tool with health scoring and recommendations">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <i class="fas fa-chart-line"></i> SEO Toolkit
            </div>
            <button id="darkModeToggle" class="dark-mode-btn" title="Toggle Dark Mode">
                <i class="fas fa-moon"></i>
            </button>
        </div>
    </nav>

    <!-- SVG Gradient Definition -->
    <svg width="0" height="0">
        <defs>
            <linearGradient id="scoreGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:#2563eb;stop-opacity:1" />
                <stop offset="100%" style="stop-color:#f59e0b;stop-opacity:1" />
            </linearGradient>
        </defs>
    </svg>

    <div class="container">
        <header class="main-header">
            <div class="header-content">
                <h1><i class="fas fa-search"></i>SEO Toolkit</h1>
                <p>Complete SEO analysis for web developers. Extract meta tags, check SEO score, and optimize your website.</p>
            </div>
        </header>
        
        <div class="search-section">
            <form id="metaForm" method="POST" class="search-form">
                <div class="input-wrapper">
                    <input type="text" name="url" id="url" placeholder="Enter website URL (e.g., google.com or https://example.com)" 
                           value="<?php echo htmlspecialchars($url); ?>" required>
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Analyze
                    </button>
                </div>
                <div class="url-examples">
                    <small>Try: google.com, github.com, or any website - https:// is optional</small>
                </div>
            </form>
        </div>
        
        <?php if (!empty($metaData)): ?>
            <?php if (isset($metaData['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong>Error:</strong>
                        <p><?php echo htmlspecialchars($metaData['error']); ?></p>
                    </div>
                </div>
            <?php else: ?>
                <div class="results-section">
                    <!-- URL Display -->
                    <div class="url-header">
                        <h2><i class="fas fa-link"></i> Analysis for: <span class="url-text"><?php echo htmlspecialchars($metaData['original_url'] ?? $url); ?></span></h2>
                        <div class="action-buttons">
                            <button onclick="exportJSON()" class="export-btn" title="Export as JSON">
                                <i class="fas fa-download"></i> JSON
                            </button>
                            <button onclick="copyURL()" class="export-btn" title="Copy URL">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>

                    <!-- Recommendations Section -->
                    <?php if (!empty($metaData['recommendations']) && count($metaData['recommendations']) > 0): ?>
                    <div class="recommendations-section">
                        <div class="recommendations-header">
                            <h3><i class="fas fa-lightbulb"></i> SEO Recommendations</h3>
                            <p><?php echo count($metaData['recommendations']); ?> issues found</p>
                        </div>
                        <div class="recommendations-list">
                            <?php foreach ($metaData['recommendations'] as $rec): ?>
                            <div class="recommendation-item priority-<?php echo $rec['priority']; ?>">
                                <div class="rec-header">
                                    <span class="rec-priority <?php echo $rec['priority']; ?>">
                                        <?php 
                                        $icons = ['critical' => 'fa-exclamation-circle', 'high' => 'fa-exclamation-triangle', 'medium' => 'fa-info-circle', 'low' => 'fa-lightbulb'];
                                        echo '<i class="fas ' . $icons[$rec['priority']] . '"></i>';
                                        ?>
                                    </span>
                                    <span class="rec-title"><?php echo htmlspecialchars($rec['title']); ?></span>
                                </div>
                                <p class="rec-message"><?php echo htmlspecialchars($rec['message']); ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- SEO Score Card -->
                    <div class="seo-score-card">
                        <div class="score-display">
                            <div class="score-circle">
                                <svg viewBox="0 0 100 100">
                                    <circle cx="50" cy="50" r="45" class="score-background"></circle>
                                    <circle cx="50" cy="50" r="45" class="score-progress" 
                                            style="--score: <?php echo $metaData['seo_score']; ?>%"></circle>
                                </svg>
                                <div class="score-number"><?php echo $metaData['seo_score']; ?>/100</div>
                            </div>
                            <div class="score-info">
                                <h3>SEO Health Score</h3>
                                <p class="score-status <?php echo getScoringClass($metaData['seo_score']); ?>">
                                    <?php echo getScoringText($metaData['seo_score']); ?>
                                </p>
                                <div class="score-breakdown">
                                    <div class="breakdown-item">
                                        <span class="check-icon <?php echo !empty($metaData['title']) ? 'pass' : 'fail'; ?>">
                                            <i class="fas <?php echo !empty($metaData['title']) ? 'fa-check' : 'fa-times'; ?>"></i>
                                        </span>
                                        <label>Page Title</label>
                                    </div>
                                    <div class="breakdown-item">
                                        <span class="check-icon <?php echo !empty($metaData['description']) ? 'pass' : 'fail'; ?>">
                                            <i class="fas <?php echo !empty($metaData['description']) ? 'fa-check' : 'fa-times'; ?>"></i>
                                        </span>
                                        <label>Meta Description</label>
                                    </div>
                                    <div class="breakdown-item">
                                        <span class="check-icon <?php echo $metaData['has_viewport'] ? 'pass' : 'fail'; ?>">
                                            <i class="fas <?php echo $metaData['has_viewport'] ? 'fa-check' : 'fa-times'; ?>"></i>
                                        </span>
                                        <label>Mobile Viewport</label>
                                    </div>
                                    <div class="breakdown-item">
                                        <span class="check-icon <?php echo $metaData['structured_data'] ? 'pass' : 'fail'; ?>">
                                            <i class="fas <?php echo $metaData['structured_data'] ? 'fa-check' : 'fa-times'; ?>"></i>
                                        </span>
                                        <label>Structured Data</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs Navigation -->
                    <div class="tabs-container">
                        <div class="tabs-nav">
                            <button class="tab-btn active" data-tab="overview">
                                <i class="fas fa-bars"></i> Overview
                            </button>
                            <button class="tab-btn" data-tab="meta">
                                <i class="fas fa-tag"></i> Meta Tags
                            </button>
                            <button class="tab-btn" data-tab="content">
                                <i class="fas fa-file-alt"></i> Content
                            </button>
                            <button class="tab-btn" data-tab="technical">
                                <i class="fas fa-cogs"></i> Technical
                            </button>
                            <button class="tab-btn" data-tab="raw">
                                <i class="fas fa-code"></i> Raw Data
                            </button>
                        </div>

                        <!-- Overview Tab -->
                        <div class="tab-content active" id="overview-tab">
                            <div class="results-grid">
                                <!-- Title Card -->
                                <div class="info-card">
                                    <div class="card-header">
                                        <i class="fas fa-heading"></i> Page Title
                                    </div>
                                    <div class="card-body">
                                        <p class="value"><?php echo htmlspecialchars($metaData['title'] ?? 'Not found'); ?></p>
                                        <div class="meta-hint">
                                            <span class="length">Length: <?php echo strlen($metaData['title'] ?? ''); ?> characters</span>
                                            <?php $titleLen = strlen($metaData['title'] ?? ''); ?>
                                            <span class="status <?php echo ($titleLen >= 30 && $titleLen <= 60) ? 'optimal' : 'warning'; ?>">
                                                <?php echo ($titleLen >= 30 && $titleLen <= 60) ? '✓ Optimal' : ($titleLen > 60 ? '⚠ Too long' : '⚠ Too short'); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Description Card -->
                                <div class="info-card">
                                    <div class="card-header">
                                        <i class="fas fa-align-left"></i> Meta Description
                                    </div>
                                    <div class="card-body">
                                        <p class="value"><?php echo htmlspecialchars($metaData['description'] ?? 'Not found'); ?></p>
                                        <div class="meta-hint">
                                            <span class="length">Length: <?php echo strlen($metaData['description'] ?? ''); ?> characters</span>
                                            <?php $descLen = strlen($metaData['description'] ?? ''); ?>
                                            <span class="status <?php echo ($descLen >= 120 && $descLen <= 160) ? 'optimal' : 'warning'; ?>">
                                                <?php echo ($descLen >= 120 && $descLen <= 160) ? '✓ Optimal' : ($descLen > 160 ? '⚠ Too long' : '⚠ Too short'); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Language Card -->
                                <div class="info-card">
                                    <div class="card-header">
                                        <i class="fas fa-globe"></i> Language
                                    </div>
                                    <div class="card-body">
                                        <p class="value"><?php echo htmlspecialchars($metaData['og:locale'] ?? 'Not specified'); ?></p>
                                        <div class="meta-hint">
                                            <span class="status <?php echo $metaData['has_lang'] ? 'optimal' : 'warning'; ?>">
                                                <?php echo $metaData['has_lang'] ? '✓ Lang attribute found' : '⚠ Missing lang attribute'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Canonical URL Card -->
                                <div class="info-card">
                                    <div class="card-header">
                                        <i class="fas fa-anchor"></i> Canonical URL
                                    </div>
                                    <div class="card-body">
                                        <?php if ($metaData['has_canonical']): ?>
                                            <p class="value">
                                                <a href="<?php echo htmlspecialchars($metaData['link_canonical']); ?>" target="_blank" class="url-link">
                                                    <?php echo htmlspecialchars($metaData['link_canonical']); ?>
                                                </a>
                                            </p>
                                            <span class="status optimal">✓ Canonical URL found</span>
                                        <?php else: ?>
                                            <p class="value">Not found</p>
                                            <span class="status warning">⚠ Missing canonical URL</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Heading Structure Card -->
                                <div class="info-card">
                                    <div class="card-header">
                                        <i class="fas fa-stream"></i> Heading Structure
                                    </div>
                                    <div class="card-body">
                                        <div class="heading-stats">
                                            <div class="stat">
                                                <span class="label">H1</span>
                                                <span class="count <?php echo $metaData['h1_count'] == 1 ? 'optimal' : 'warning'; ?>">
                                                    <?php echo $metaData['h1_count']; ?>
                                                </span>
                                            </div>
                                            <div class="stat">
                                                <span class="label">H2</span>
                                                <span class="count"><?php echo $metaData['h2_count']; ?></span>
                                            </div>
                                            <div class="stat">
                                                <span class="label">H3</span>
                                                <span class="count"><?php echo $metaData['h3_count']; ?></span>
                                            </div>
                                        </div>
                                        <div class="meta-hint" style="margin-top: 10px;">
                                            <span class="status <?php echo $metaData['h1_count'] == 1 ? 'optimal' : 'warning'; ?>">
                                                <?php echo $metaData['h1_count'] == 1 ? '✓ Correct structure' : '⚠ Optimize heading hierarchy'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Links Card -->
                                <div class="info-card">
                                    <div class="card-header">
                                        <i class="fas fa-link"></i> Link Analysis
                                    </div>
                                    <div class="card-body">
                                        <div class="link-stats">
                                            <div class="stat">
                                                <span class="label">Total</span>
                                                <span class="count"><?php echo $metaData['total_links']; ?></span>
                                            </div>
                                            <div class="stat">
                                                <span class="label">Internal</span>
                                                <span class="count internal"><?php echo $metaData['internal_links']; ?></span>
                                            </div>
                                            <div class="stat">
                                                <span class="label">External</span>
                                                <span class="count external"><?php echo $metaData['external_links']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Meta Tags Tab -->
                        <div class="tab-content" id="meta-tab">
                            <div class="results-grid">
                                <!-- Open Graph Card -->
                                <div class="info-card full-width">
                                    <div class="card-header">
                                        <i class="fab fa-facebook"></i> Open Graph Tags
                                    </div>
                                    <div class="card-body">
                                        <?php 
                                        $ogTags = [
                                            'og:title' => 'OG Title',
                                            'og:description' => 'OG Description',
                                            'og:image' => 'OG Image',
                                            'og:type' => 'OG Type',
                                            'og:url' => 'OG URL'
                                        ];
                                        ?>
                                        <div class="tags-list">
                                            <?php foreach ($ogTags as $tag => $label):
                                                $hasTag = isset($metaData[$tag]) && !empty($metaData[$tag]);
                                            ?>
                                                <div class="tag-item <?php echo $hasTag ? 'found' : 'missing'; ?>">
                                                    <span class="tag-name"><?php echo $label; ?>:</span>
                                                    <span class="tag-value">
                                                        <?php 
                                                        if ($hasTag) {
                                                            echo htmlspecialchars($metaData[$tag]);
                                                        } else {
                                                            echo '<em>Not found</em>';
                                                        }
                                                        ?>
                                                    </span>
                                                    <span class="tag-status <?php echo $hasTag ? 'pass' : 'fail'; ?>">
                                                        <i class="fas <?php echo $hasTag ? 'fa-check' : 'fa-times'; ?>"></i>
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Twitter Card -->
                                <div class="info-card full-width">
                                    <div class="card-header">
                                        <i class="fab fa-twitter"></i> Twitter Card
                                    </div>
                                    <div class="card-body">
                                        <?php 
                                        $twitterTags = [
                                            'twitter:card' => 'Card Type',
                                            'twitter:title' => 'Title',
                                            'twitter:description' => 'Description',
                                            'twitter:image' => 'Image'
                                        ];
                                        ?>
                                        <div class="tags-list">
                                            <?php foreach ($twitterTags as $tag => $label):
                                                $hasTag = isset($metaData[$tag]) && !empty($metaData[$tag]);
                                            ?>
                                                <div class="tag-item <?php echo $hasTag ? 'found' : 'missing'; ?>">
                                                    <span class="tag-name"><?php echo $label; ?>:</span>
                                                    <span class="tag-value">
                                                        <?php 
                                                        if ($hasTag) {
                                                            echo htmlspecialchars($metaData[$tag]);
                                                        } else {
                                                            echo '<em>Not found</em>';
                                                        }
                                                        ?>
                                                    </span>
                                                    <span class="tag-status <?php echo $hasTag ? 'pass' : 'fail'; ?>">
                                                        <i class="fas <?php echo $hasTag ? 'fa-check' : 'fa-times'; ?>"></i>
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Content Tab -->
                        <div class="tab-content" id="content-tab">
                            <div class="results-grid">
                                <!-- Keywords Card -->
                                <div class="info-card">
                                    <div class="card-header">
                                        <i class="fas fa-key"></i> Keywords
                                    </div>
                                    <div class="card-body">
                                        <p class="value">
                                            <?php 
                                            if (!empty($metaData['keywords'])) {
                                                echo htmlspecialchars($metaData['keywords']);
                                            } else {
                                                echo '<em>Not found</em>';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                </div>

                                <!-- Images Card -->
                                <div class="info-card">
                                    <div class="card-header">
                                        <i class="fas fa-images"></i> Images
                                    </div>
                                    <div class="card-body">
                                        <div class="image-stats">
                                            <div class="stat">
                                                <span class="label">Total Images</span>
                                                <span class="count"><?php echo $metaData['img_count']; ?></span>
                                            </div>
                                            <div class="stat">
                                                <span class="label">With Alt Text</span>
                                                <span class="count optimal"><?php echo $metaData['img_with_alt']; ?></span>
                                            </div>
                                            <div class="stat">
                                                <span class="label">Without Alt</span>
                                                <span class="count warning"><?php echo $metaData['img_count'] - $metaData['img_with_alt']; ?></span>
                                            </div>
                                        </div>
                                        <?php $altPercentage = $metaData['img_count'] > 0 ? round(($metaData['img_with_alt'] / $metaData['img_count']) * 100) : 0; ?>
                                        <div class="meta-hint" style="margin-top: 15px;">
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo $altPercentage; ?>%;"></div>
                                            </div>
                                            <span class="status <?php echo $altPercentage >= 80 ? 'optimal' : 'warning'; ?>">
                                                <?php echo $altPercentage; ?>% images have alt text
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- H1 Contents Card -->
                                <?php if (!empty($metaData['h1_contents'])): ?>
                                <div class="info-card full-width">
                                    <div class="card-header">
                                        <i class="fas fa-heading"></i> H1 Contents
                                    </div>
                                    <div class="card-body">
                                        <ul class="content-list">
                                            <?php foreach ($metaData['h1_contents'] as $h1): ?>
                                                <li>
                                                    <i class="fas fa-circle"></i>
                                                    <?php echo htmlspecialchars($h1); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Technical Tab -->
                        <div class="tab-content" id="technical-tab">
                            <div class="results-grid">
                                <!-- Technical Checks -->
                                <div class="info-card full-width">
                                    <div class="card-header">
                                        <i class="fas fa-check-circle"></i> Technical SEO Checks
                                    </div>
                                    <div class="card-body">
                                        <div class="checklist">
                                            <div class="check-item <?php echo $metaData['has_charset'] ? 'pass' : 'fail'; ?>">
                                                <span class="check-icon">
                                                    <i class="fas <?php echo $metaData['has_charset'] ? 'fa-check' : 'fa-times'; ?>"></i>
                                                </span>
                                                <label>Character Set Defined</label>
                                            </div>
                                            <div class="check-item <?php echo $metaData['has_viewport'] ? 'pass' : 'fail'; ?>">
                                                <span class="check-icon">
                                                    <i class="fas <?php echo $metaData['has_viewport'] ? 'fa-check' : 'fa-times'; ?>"></i>
                                                </span>
                                                <label>Mobile Viewport Meta Tag</label>
                                            </div>
                                            <div class="check-item <?php echo $metaData['has_lang'] ? 'pass' : 'fail'; ?>">
                                                <span class="check-icon">
                                                    <i class="fas <?php echo $metaData['has_lang'] ? 'fa-check' : 'fa-times'; ?>"></i>
                                                </span>
                                                <label>Language Attribute</label>
                                            </div>
                                            <div class="check-item <?php echo $metaData['has_canonical'] ? 'pass' : 'fail'; ?>">
                                                <span class="check-icon">
                                                    <i class="fas <?php echo $metaData['has_canonical'] ? 'fa-check' : 'fa-times'; ?>"></i>
                                                </span>
                                                <label>Canonical URL</label>
                                            </div>
                                            <div class="check-item <?php echo $metaData['has_robots'] ? 'pass' : 'fail'; ?>">
                                                <span class="check-icon">
                                                    <i class="fas <?php echo $metaData['has_robots'] ? 'fa-check' : 'fa-times'; ?>"></i>
                                                </span>
                                                <label>Robots Meta Tag</label>
                                            </div>
                                            <div class="check-item <?php echo $metaData['structured_data'] ? 'pass' : 'fail'; ?>">
                                                <span class="check-icon">
                                                    <i class="fas <?php echo $metaData['structured_data'] ? 'fa-check' : 'fa-times'; ?>"></i>
                                                </span>
                                                <label>Structured Data (JSON-LD)</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Additional Meta Tags -->
                                <div class="info-card full-width">
                                    <div class="card-header">
                                        <i class="fas fa-tag"></i> Additional Meta Tags
                                    </div>
                                    <div class="card-body">
                                        <div class="tags-list">
                                            <?php 
                                            $additionalTags = [
                                                'author' => 'Author',
                                                'robots' => 'Robots',
                                                'theme-color' => 'Theme Color',
                                                'apple-mobile-web-app-capable' => 'PWA Capable'
                                            ];
                                            foreach ($additionalTags as $tag => $label):
                                                $hasTag = isset($metaData[$tag]);
                                            ?>
                                                <div class="tag-item <?php echo $hasTag ? 'found' : 'missing'; ?>">
                                                    <span class="tag-name"><?php echo $label; ?>:</span>
                                                    <span class="tag-value">
                                                        <?php 
                                                        if ($hasTag) {
                                                            echo htmlspecialchars($metaData[$tag]);
                                                        } else {
                                                            echo '<em>Not found</em>';
                                                        }
                                                        ?>
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Raw Data Tab -->
                        <div class="tab-content" id="raw-tab">
                            <div class="results-grid">
                                <div class="info-card full-width">
                                    <div class="card-header">
                                        <i class="fas fa-code"></i> Raw Meta Data (JSON)
                                        <button id="copyRaw" class="copy-btn" title="Copy to clipboard">
                                            <i class="far fa-copy"></i> Copy
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <pre id="rawMeta"><?php echo htmlspecialchars(json_encode($metaData, JSON_PRETTY_PRINT)); ?></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- Footer -->
        <footer class="footer">
            <div class="footer-content">
                <p><strong>SEO Toolkit</strong> &copy; <?php echo date('Y'); ?></p>
                <p class="footer-desc">Advanced SEO analysis tool designed for web developers and digital marketers</p>
            </div>
        </footer>
    </div>
    
    <div id="toast"></div>
    
    <script src="script.js"></script>
</body>
</html>
