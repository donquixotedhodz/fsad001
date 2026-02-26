<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';

MainController::requireAuth();

header('Content-Type: application/json');

const FAQ_UNAVAILABLE_MESSAGE = 'This information is not available in the system FAQ.';

function getChatbotSettings($conn)
{
    $settings = [
        'enabled' => 1,
        'match_threshold' => 0.35,
        'related_limit' => 3
    ];

    try {
        $stmt = $conn->prepare("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ('chatbot_enabled', 'chatbot_match_threshold', 'chatbot_related_limit')");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as $row) {
            $key = $row['setting_key'] ?? '';
            $value = $row['setting_value'] ?? '';

            if ($key === 'chatbot_enabled') {
                $settings['enabled'] = (int) $value === 1 ? 1 : 0;
            }
            if ($key === 'chatbot_match_threshold') {
                $threshold = (float) $value;
                if ($threshold >= 0.1 && $threshold <= 0.95) {
                    $settings['match_threshold'] = $threshold;
                }
            }
            if ($key === 'chatbot_related_limit') {
                $limit = (int) $value;
                if ($limit >= 1 && $limit <= 10) {
                    $settings['related_limit'] = $limit;
                }
            }
        }
    }
    catch (Exception $e) {
    // Use defaults when settings table is unavailable
    }

    return $settings;
}

function normalizeText($text)
{
    $text = strtolower(trim((string) $text));
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

function tokenize($text)
{
    $stopwords = [
        'the', 'is', 'a', 'an', 'to', 'for', 'of', 'in', 'on', 'and', 'or', 'by', 'with', 'how', 'what', 'can',
        'i', 'do', 'my', 'me', 'you', 'your', 'this', 'that', 'it', 'from', 'are', 'be', 'as', 'at', 'we', 'our'
    ];

    $words = explode(' ', normalizeText($text));
    $tokens = [];
    foreach ($words as $word) {
        if ($word !== '' && strlen($word) > 1 && !in_array($word, $stopwords)) {
            $tokens[] = $word;
        }
    }
    return array_values(array_unique($tokens));
}

function overlapScore($tokensA, $tokensB)
{
    if (empty($tokensA) || empty($tokensB)) {
        return 0;
    }

    $common = array_intersect($tokensA, $tokensB);
    $union = array_unique(array_merge($tokensA, $tokensB));
    if (count($union) === 0) {
        return 0;
    }

    return count($common) / count($union);
}

function bestFaqMatch($question, $faqItems, $threshold = 0.35)
{
    $normalizedQuestion = normalizeText($question);
    $questionTokens = tokenize($question);

    $best = null;
    $bestScore = 0;

    foreach ($faqItems as $faq) {
        $faqQuestion = $faq['question'] ?? '';
        $normalizedFaqQuestion = normalizeText($faqQuestion);

        if ($normalizedFaqQuestion === $normalizedQuestion) {
            $faq['_score'] = 1.0;
            return $faq;
        }

        similar_text($normalizedQuestion, $normalizedFaqQuestion, $percent);
        $similarityScore = $percent / 100;

        $faqTokens = tokenize($faqQuestion . ' ' . ($faq['answer'] ?? ''));
        $tokenOverlap = overlapScore($questionTokens, $faqTokens);

        $substringBonus = (strpos($normalizedFaqQuestion, $normalizedQuestion) !== false || strpos($normalizedQuestion, $normalizedFaqQuestion) !== false) ? 0.1 : 0;

        $score = ($similarityScore * 0.55) + ($tokenOverlap * 0.35) + $substringBonus;

        if ($score > $bestScore) {
            $bestScore = $score;
            $faq['_score'] = $score;
            $faq['_token_overlap'] = $tokenOverlap;
            $best = $faq;
        }
    }

    if ($best && $bestScore >= $threshold) {
        return $best;
    }

    return null;
}

function keywordFallbackMatch($question, $faqItems)
{
    $questionTokens = tokenize($question);
    if (empty($questionTokens)) {
        return null;
    }

    $best = null;
    $maxOverlapCount = 0;

    foreach ($faqItems as $faq) {
        $faqTokens = tokenize(($faq['question'] ?? '') . ' ' . ($faq['answer'] ?? ''));
        $common = array_intersect($questionTokens, $faqTokens);
        $overlapCount = count($common);

        if ($overlapCount > $maxOverlapCount) {
            $maxOverlapCount = $overlapCount;
            $faq['_fallback_overlap'] = $overlapCount;
            $best = $faq;
        }
    }

    if ($best && $maxOverlapCount >= 2) {
        return $best;
    }

    return null;
}

function getRelatedQuestions($baseFaq, $faqItems, $limit = 3)
{
    $baseId = $baseFaq['id'] ?? 0;
    $baseCategory = $baseFaq['category'] ?? '';
    $baseTokens = tokenize(($baseFaq['question'] ?? '') . ' ' . ($baseFaq['answer'] ?? ''));

    $related = [];
    foreach ($faqItems as $faq) {
        if (($faq['id'] ?? 0) == $baseId) {
            continue;
        }

        $faqTokens = tokenize(($faq['question'] ?? '') . ' ' . ($faq['answer'] ?? ''));
        $score = overlapScore($baseTokens, $faqTokens);

        if (($faq['category'] ?? '') === $baseCategory) {
            $score += 0.1;
        }

        if ($score > 0) {
            $related[] = [
                'id' => $faq['id'],
                'question' => $faq['question'],
                'category' => $faq['category'],
                '_score' => $score
            ];
        }
    }

    usort($related, function ($a, $b) {
        return $b['_score'] <=> $a['_score'];
    });

    return array_slice($related, 0, $limit);
}

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? 'ask';
    $chatbotSettings = getChatbotSettings($conn);

    if ((int) $chatbotSettings['enabled'] !== 1) {
        echo json_encode([
            'success' => true,
            'intent' => 'faq_answer',
            'match_type' => 'disabled',
            'answer' => FAQ_UNAVAILABLE_MESSAGE,
            'faq' => null,
            'related_questions' => []
        ]);
        exit;
    }

    $faqStmt = $conn->prepare("SELECT id, category, question, answer, display_order FROM faq WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
    $faqStmt->execute();
    $faqItems = $faqStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    if ($action === 'topics') {
        $topics = [];
        foreach ($faqItems as $item) {
            if (!in_array($item['category'], $topics)) {
                $topics[] = $item['category'];
            }
        }

        echo json_encode([
            'success' => true,
            'intent' => 'list_topics',
            'topics' => $topics,
            'message' => !empty($topics)
                ? 'Here are the available FAQ topics in the system.'
                : FAQ_UNAVAILABLE_MESSAGE
        ]);
        exit;
    }

    if ($action === 'related') {
        $faqId = intval($_POST['faq_id'] ?? $_GET['faq_id'] ?? 0);
        $currentFaq = null;
        foreach ($faqItems as $item) {
            if ((int) $item['id'] === $faqId) {
                $currentFaq = $item;
                break;
            }
        }

        if (!$currentFaq) {
            echo json_encode([
                'success' => true,
                'intent' => 'related_questions',
                'related_questions' => [],
                'message' => FAQ_UNAVAILABLE_MESSAGE
            ]);
            exit;
        }

        $relatedQuestions = getRelatedQuestions($currentFaq, $faqItems, (int) $chatbotSettings['related_limit']);
        echo json_encode([
            'success' => true,
            'intent' => 'related_questions',
            'related_questions' => $relatedQuestions,
            'message' => !empty($relatedQuestions)
                ? 'You may also want to check these related FAQ questions.'
                : FAQ_UNAVAILABLE_MESSAGE
        ]);
        exit;
    }

    $userQuestion = trim($_POST['question'] ?? $_GET['question'] ?? '');
    if ($userQuestion === '') {
        echo json_encode([
            'success' => false,
            'message' => 'Please enter a question.'
        ]);
        exit;
    }

    $match = bestFaqMatch($userQuestion, $faqItems, (float) $chatbotSettings['match_threshold']);
    $matchType = 'best_match';

    if (!$match) {
        $match = keywordFallbackMatch($userQuestion, $faqItems);
        $matchType = 'keyword_fallback';
    }

    if (!$match) {
        echo json_encode([
            'success' => true,
            'intent' => 'faq_answer',
            'match_type' => 'not_found',
            'answer' => FAQ_UNAVAILABLE_MESSAGE,
            'faq' => null,
            'related_questions' => []
        ]);
        exit;
    }

    $relatedQuestions = getRelatedQuestions($match, $faqItems, (int) $chatbotSettings['related_limit']);

    echo json_encode([
        'success' => true,
        'intent' => 'faq_answer',
        'match_type' => $matchType,
        'answer' => "Great question! " . $match['answer'],
        'faq' => [
            'id' => $match['id'],
            'category' => $match['category'],
            'question' => $match['question']
        ],
        'related_questions' => $relatedQuestions
    ]);
}
catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Unable to process chatbot request at the moment.'
    ]);
}
