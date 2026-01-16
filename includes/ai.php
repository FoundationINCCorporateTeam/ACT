<?php
/**
 * ACT AI Tutor - AI API Utilities
 * 
 * Handles communication with the AI API for generating lessons,
 * quizzes, explanations, and chat responses.
 */

/**
 * Send a request to the AI API
 * 
 * @param array $messages The messages to send
 * @param string $model The AI model to use
 * @param float $temperature The temperature for generation (0-1)
 * @return array Result with success status and response/error
 */
function ai_request($messages, $model = null, $temperature = 0.7) {
    $model = $model ?: DEFAULT_AI_MODEL;
    
    $data = [
        'model' => $model,
        'messages' => $messages,
        'temperature' => $temperature
    ];
    
    $ch = curl_init(AI_API_ENDPOINT);
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . AI_API_KEY
        ],
        CURLOPT_TIMEOUT => AI_REQUEST_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        error_log("AI API cURL error: $error");
        return ['success' => false, 'message' => "Connection error: $error"];
    }
    
    if ($httpCode !== 200) {
        error_log("AI API HTTP error: $httpCode - $response");
        return ['success' => false, 'message' => "API error (HTTP $httpCode)"];
    }
    
    $result = json_decode($response, true);
    
    if (!$result || !isset($result['choices'][0]['message']['content'])) {
        error_log("AI API invalid response: $response");
        return ['success' => false, 'message' => 'Invalid API response'];
    }
    
    return [
        'success' => true,
        'content' => $result['choices'][0]['message']['content'],
        'model' => $model,
        'usage' => $result['usage'] ?? null
    ];
}

/**
 * Generate a lesson using AI
 * 
 * @param string $subject The subject (English, Math, etc.)
 * @param string $topic The specific topic
 * @param string $difficulty The difficulty level
 * @param string $length The lesson length (short, medium, long)
 * @param array $focusAreas The focus areas to include
 * @param string $model The AI model to use
 * @return array Result with lesson content
 */
function ai_generate_lesson($subject, $topic, $difficulty, $length, $focusAreas = [], $model = null) {
    $lengthMinutes = ['short' => 5, 'medium' => 15, 'long' => 30][$length] ?? 15;
    $focusText = !empty($focusAreas) ? implode(', ', $focusAreas) : 'Concepts, Examples, Practice Problems';
    
    $prompt = <<<PROMPT
Create a comprehensive ACT test preparation lesson on the following:

**Subject:** $subject
**Topic:** $topic
**Difficulty Level:** $difficulty
**Length:** Approximately $lengthMinutes minutes of reading/study time
**Focus Areas:** $focusText

Please structure the lesson as follows:

# $topic

## Introduction
A brief introduction to the topic and its importance for the ACT.

## Key Concepts
Explain the fundamental concepts clearly with examples.

## Strategies for ACT
Specific strategies for answering ACT questions on this topic.

## Practice Examples
Include 3-5 practice problems with step-by-step solutions.
For math problems, use LaTeX notation for formulas (e.g., \$x = \frac{-b \pm \sqrt{b^2-4ac}}{2a}\$).

## Common Mistakes to Avoid
List common errors students make and how to avoid them.

## Quick Reference
A summary of key points to remember.

## Practice Problems
Include 5 additional practice problems for the student to try (with answers at the end).

Format the lesson using Markdown. Use clear headings, bullet points, and numbered lists where appropriate.
For mathematical expressions, use LaTeX notation with dollar signs for inline math (\$...\$) and double dollar signs for display math (\$\$...\$\$).
PROMPT;

    $messages = [
        ['role' => 'system', 'content' => 'You are an expert ACT tutor with years of experience helping students achieve high scores. Create detailed, engaging, and effective lessons that help students master ACT content.'],
        ['role' => 'user', 'content' => $prompt]
    ];
    
    return ai_request($messages, $model, 0.7);
}

/**
 * Generate quiz questions using AI
 * 
 * @param string $subject The subject
 * @param string $topic The topic
 * @param int $numQuestions Number of questions
 * @param string $difficulty Difficulty level
 * @param string $model The AI model to use
 * @return array Result with quiz questions
 */
function ai_generate_quiz($subject, $topic, $numQuestions, $difficulty, $model = null) {
    $prompt = <<<PROMPT
Create an ACT-style multiple choice quiz with exactly $numQuestions questions.

**Subject:** $subject
**Topic:** $topic
**Difficulty:** $difficulty

Return the quiz in the following JSON format only (no markdown, no explanation, just valid JSON):

{
  "questions": [
    {
      "id": 1,
      "question": "The question text here",
      "options": {
        "A": "First option",
        "B": "Second option",
        "C": "Third option",
        "D": "Fourth option"
      },
      "correct": "A",
      "explanation": "Detailed explanation of why A is correct and why other options are wrong"
    }
  ]
}

Requirements:
- Questions should be ACT-style with exactly 4 options (A, B, C, D)
- Difficulty should match $difficulty level
- Explanations should be thorough
- For math questions, use LaTeX notation with dollar signs for formulas
- Ensure all questions are unique and test different aspects of the topic
- Return ONLY valid JSON, no other text
PROMPT;

    $messages = [
        ['role' => 'system', 'content' => 'You are an ACT test expert. Generate high-quality, authentic ACT-style questions. Return only valid JSON.'],
        ['role' => 'user', 'content' => $prompt]
    ];
    
    $result = ai_request($messages, $model, 0.8);
    
    if (!$result['success']) {
        return $result;
    }
    
    // Parse JSON from response
    $content = $result['content'];
    
    // Try to extract JSON if wrapped in markdown code blocks
    if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $content, $matches)) {
        $content = $matches[1];
    }
    
    $quiz = json_decode(trim($content), true);
    
    if (!$quiz || !isset($quiz['questions'])) {
        error_log("Failed to parse quiz JSON: $content");
        return ['success' => false, 'message' => 'Failed to parse quiz data'];
    }
    
    return [
        'success' => true,
        'questions' => $quiz['questions'],
        'model' => $result['model']
    ];
}

/**
 * Generate an explanation for a quiz question
 * 
 * @param array $question The question data
 * @param string $userAnswer The user's answer
 * @param string $model The AI model to use
 * @return array Result with explanation
 */
function ai_explain_question($question, $userAnswer, $model = null) {
    $isCorrect = $userAnswer === $question['correct'];
    $status = $isCorrect ? 'correctly' : 'incorrectly';
    
    $prompt = <<<PROMPT
A student answered this ACT question $status.

Question: {$question['question']}

Options:
A) {$question['options']['A']}
B) {$question['options']['B']}
C) {$question['options']['C']}
D) {$question['options']['D']}

Student's Answer: $userAnswer
Correct Answer: {$question['correct']}

Please provide:
1. A detailed explanation of why the correct answer is right
2. Why each incorrect option is wrong
3. The key concept or rule being tested
4. A tip for recognizing similar questions in the future
5. A related practice suggestion

Use clear, encouraging language appropriate for a high school student.
For any math, use LaTeX notation with dollar signs.
PROMPT;

    $messages = [
        ['role' => 'system', 'content' => 'You are a supportive ACT tutor helping students understand their mistakes and learn from them.'],
        ['role' => 'user', 'content' => $prompt]
    ];
    
    return ai_request($messages, $model, 0.7);
}

/**
 * Generate a chat response
 * 
 * @param array $conversationHistory Previous messages
 * @param string $userMessage The new user message
 * @param string $model The AI model to use
 * @param array $context Additional context (user progress, weak areas, etc.)
 * @return array Result with chat response
 */
function ai_chat($conversationHistory, $userMessage, $model = null, $context = []) {
    $systemPrompt = "You are an expert ACT tutor and study coach. You help students prepare for the ACT test by:
- Explaining concepts clearly with examples
- Providing practice problems and strategies
- Offering encouragement and motivation
- Adapting explanations to the student's level
- Using LaTeX notation (with dollar signs) for mathematical expressions
- Using Markdown formatting for structured responses

Be friendly, supportive, and encouraging. Keep responses focused and helpful.";
    
    if (!empty($context['weak_areas'])) {
        $systemPrompt .= "\n\nThe student's weak areas are: " . implode(', ', $context['weak_areas']);
    }
    
    if (!empty($context['current_score'])) {
        $systemPrompt .= "\n\nThe student's current practice test score is: " . $context['current_score'];
    }
    
    if (!empty($context['target_score'])) {
        $systemPrompt .= "\n\nThe student's target ACT score is: " . $context['target_score'];
    }
    
    $messages = [
        ['role' => 'system', 'content' => $systemPrompt]
    ];
    
    // Add conversation history (limited to last 20 messages)
    $history = array_slice($conversationHistory, -20);
    foreach ($history as $msg) {
        $messages[] = [
            'role' => $msg['role'],
            'content' => $msg['content']
        ];
    }
    
    // Add new user message
    $messages[] = ['role' => 'user', 'content' => $userMessage];
    
    return ai_request($messages, $model, 0.8);
}

/**
 * Generate an essay prompt
 * 
 * @param string $category The category (Education, Technology, Society)
 * @param string $model The AI model to use
 * @return array Result with essay prompt
 */
function ai_generate_essay_prompt($category, $model = null) {
    $prompt = <<<PROMPT
Generate an ACT Writing Test style essay prompt on the topic of $category.

The prompt should:
1. Present a debatable issue relevant to high school students
2. Include a brief introductory paragraph explaining the issue
3. Present three distinct perspectives on the issue
4. Include the standard ACT essay task instructions

Format the response as valid JSON:
{
  "title": "Brief title of the issue",
  "introduction": "The introductory paragraph explaining the issue",
  "perspectives": [
    {"label": "Perspective One", "description": "Description of the first perspective"},
    {"label": "Perspective Two", "description": "Description of the second perspective"},
    {"label": "Perspective Three", "description": "Description of the third perspective"}
  ],
  "task": "The essay task instructions"
}

Return ONLY valid JSON.
PROMPT;

    $messages = [
        ['role' => 'system', 'content' => 'You are an ACT Writing Test expert. Create authentic, thought-provoking essay prompts.'],
        ['role' => 'user', 'content' => $prompt]
    ];
    
    $result = ai_request($messages, $model, 0.9);
    
    if (!$result['success']) {
        return $result;
    }
    
    $content = $result['content'];
    if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $content, $matches)) {
        $content = $matches[1];
    }
    
    $promptData = json_decode(trim($content), true);
    
    if (!$promptData) {
        return ['success' => false, 'message' => 'Failed to parse essay prompt'];
    }
    
    return ['success' => true, 'prompt' => $promptData];
}

/**
 * Grade an essay using AI
 * 
 * @param string $prompt The essay prompt
 * @param string $essay The student's essay
 * @param string $model The AI model to use
 * @return array Result with grades and feedback
 */
function ai_grade_essay($prompt, $essay, $model = null) {
    $gradingPrompt = <<<PROMPT
Grade this ACT essay according to the official ACT Writing rubric.

ESSAY PROMPT:
$prompt

STUDENT'S ESSAY:
$essay

Provide grades and detailed feedback in the following JSON format:
{
  "scores": {
    "ideas_analysis": 4,
    "development_support": 4,
    "organization": 4,
    "language_use": 4
  },
  "overall_score": 8,
  "feedback": {
    "ideas_analysis": "Detailed feedback on Ideas and Analysis...",
    "development_support": "Detailed feedback on Development and Support...",
    "organization": "Detailed feedback on Organization...",
    "language_use": "Detailed feedback on Language Use..."
  },
  "strengths": ["Strength 1", "Strength 2", "Strength 3"],
  "improvements": ["Area for improvement 1", "Area for improvement 2", "Area for improvement 3"],
  "specific_examples": ["Quote from essay with feedback", "Another quote with feedback"],
  "grammar_issues": ["Grammar issue 1", "Grammar issue 2"],
  "vocabulary_suggestions": ["Suggestion 1", "Suggestion 2"],
  "overall_feedback": "Overall summary of the essay quality and recommendations for improvement"
}

Score each domain from 1-6 according to ACT rubric.
Overall score is 2-12 (sum of domain scores divided by 2, rounded).
Return ONLY valid JSON.
PROMPT;

    $messages = [
        ['role' => 'system', 'content' => 'You are an experienced ACT essay grader. Provide fair, constructive, and detailed feedback.'],
        ['role' => 'user', 'content' => $gradingPrompt]
    ];
    
    $result = ai_request($messages, $model, 0.7);
    
    if (!$result['success']) {
        return $result;
    }
    
    $content = $result['content'];
    if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $content, $matches)) {
        $content = $matches[1];
    }
    
    $grades = json_decode(trim($content), true);
    
    if (!$grades) {
        return ['success' => false, 'message' => 'Failed to parse grades'];
    }
    
    return ['success' => true, 'grades' => $grades];
}

/**
 * Generate flashcards from a topic
 * 
 * @param string $subject The subject
 * @param string $topic The topic
 * @param int $count Number of flashcards
 * @param string $model The AI model to use
 * @return array Result with flashcards
 */
function ai_generate_flashcards($subject, $topic, $count = 10, $model = null) {
    $prompt = <<<PROMPT
Create $count flashcards for ACT $subject preparation on the topic: $topic

Return as JSON:
{
  "flashcards": [
    {
      "front": "The question or term",
      "back": "The answer or definition",
      "hint": "Optional hint"
    }
  ]
}

Requirements:
- Cover key concepts, formulas, rules, and vocabulary
- Make questions clear and specific
- Make answers concise but complete
- For math, use LaTeX notation with dollar signs
- Return ONLY valid JSON
PROMPT;

    $messages = [
        ['role' => 'system', 'content' => 'You are an ACT study expert creating effective flashcards for test preparation.'],
        ['role' => 'user', 'content' => $prompt]
    ];
    
    $result = ai_request($messages, $model, 0.8);
    
    if (!$result['success']) {
        return $result;
    }
    
    $content = $result['content'];
    if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $content, $matches)) {
        $content = $matches[1];
    }
    
    $data = json_decode(trim($content), true);
    
    if (!$data || !isset($data['flashcards'])) {
        return ['success' => false, 'message' => 'Failed to parse flashcards'];
    }
    
    return ['success' => true, 'flashcards' => $data['flashcards']];
}

/**
 * Generate a study plan
 * 
 * @param array $params Study plan parameters
 * @param string $model The AI model to use
 * @return array Result with study plan
 */
function ai_generate_study_plan($params, $model = null) {
    $currentScore = $params['current_score'] ?? 'Not taken yet';
    $targetScore = $params['target_score'];
    $testDate = $params['test_date'];
    $hoursPerDay = $params['hours_per_day'];
    $daysPerWeek = $params['days_per_week'];
    $weakAreas = implode(', ', $params['weak_areas'] ?? []);
    $strongAreas = implode(', ', $params['strong_areas'] ?? []);
    $learningStyle = $params['learning_style'] ?? 'Reading';
    
    $daysUntilTest = ceil((strtotime($testDate) - time()) / 86400);
    
    $prompt = <<<PROMPT
Create a personalized ACT study plan with the following parameters:

- Current Score: $currentScore
- Target Score: $targetScore
- Test Date: $testDate ($daysUntilTest days from now)
- Available Hours Per Day: $hoursPerDay
- Days Per Week Available: $daysPerWeek
- Weak Areas: $weakAreas
- Strong Areas: $strongAreas
- Learning Style: $learningStyle

Create a detailed week-by-week study plan in JSON format:
{
  "summary": "Brief overview of the study plan",
  "weekly_hours": 10,
  "total_weeks": 8,
  "milestones": [
    {"week": 2, "goal": "Master basic algebra concepts", "target_score_improvement": 2}
  ],
  "weeks": [
    {
      "week": 1,
      "theme": "Foundation Building",
      "days": [
        {
          "day": "Monday",
          "tasks": [
            {"time": "30 min", "activity": "English Grammar Review", "type": "lesson"},
            {"time": "20 min", "activity": "Math Pre-Algebra Practice", "type": "quiz"}
          ]
        }
      ],
      "practice_test": false,
      "review_session": true
    }
  ],
  "recommendations": ["Recommendation 1", "Recommendation 2"],
  "resources": ["Resource 1", "Resource 2"]
}

Focus more time on weak areas while maintaining strong areas.
Include regular practice tests (every 2-3 weeks).
Include review sessions and rest days.
Return ONLY valid JSON.
PROMPT;

    $messages = [
        ['role' => 'system', 'content' => 'You are an expert ACT study coach creating personalized, effective study plans.'],
        ['role' => 'user', 'content' => $prompt]
    ];
    
    $result = ai_request($messages, $model, 0.7);
    
    if (!$result['success']) {
        return $result;
    }
    
    $content = $result['content'];
    if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $content, $matches)) {
        $content = $matches[1];
    }
    
    $plan = json_decode(trim($content), true);
    
    if (!$plan) {
        return ['success' => false, 'message' => 'Failed to parse study plan'];
    }
    
    return ['success' => true, 'plan' => $plan];
}

/**
 * Generate practice test questions for a section
 * 
 * @param string $section The section (english, math, reading, science)
 * @param int $numQuestions Number of questions
 * @param string $model The AI model to use
 * @return array Result with questions
 */
function ai_generate_practice_test_section($section, $numQuestions, $model = null) {
    $sectionInfo = ACT_SECTIONS[$section] ?? null;
    if (!$sectionInfo) {
        return ['success' => false, 'message' => 'Invalid section'];
    }
    
    $sectionName = $sectionInfo['name'];
    
    $prompt = <<<PROMPT
Generate $numQuestions ACT $sectionName section questions in authentic ACT format.

Return as JSON:
{
  "passage": "For Reading/Science/English sections, include a passage here. For Math, this can be null.",
  "questions": [
    {
      "id": 1,
      "question": "Question text",
      "options": {
        "A": "Option A",
        "B": "Option B", 
        "C": "Option C",
        "D": "Option D"
      },
      "correct": "B",
      "explanation": "Detailed explanation",
      "topic": "Specific topic being tested",
      "difficulty": "medium"
    }
  ]
}

Requirements:
- Questions should match authentic ACT style and difficulty
- Include a realistic reading passage for Reading/Science sections
- For English, include an edited passage with questions
- For Math, use LaTeX notation for formulas
- Vary difficulty across questions
- Return ONLY valid JSON
PROMPT;

    $messages = [
        ['role' => 'system', 'content' => "You are an ACT test development expert creating authentic $sectionName section questions."],
        ['role' => 'user', 'content' => $prompt]
    ];
    
    $result = ai_request($messages, $model, 0.8);
    
    if (!$result['success']) {
        return $result;
    }
    
    $content = $result['content'];
    if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $content, $matches)) {
        $content = $matches[1];
    }
    
    $data = json_decode(trim($content), true);
    
    if (!$data || !isset($data['questions'])) {
        return ['success' => false, 'message' => 'Failed to parse test questions'];
    }
    
    return ['success' => true, 'data' => $data];
}

/**
 * Analyze test results and provide recommendations
 * 
 * @param array $results The test results
 * @param string $model The AI model to use
 * @return array Result with analysis
 */
function ai_analyze_test_results($results, $model = null) {
    $resultsJson = json_encode($results);
    
    $prompt = <<<PROMPT
Analyze these ACT practice test results and provide personalized recommendations:

$resultsJson

Provide analysis as JSON:
{
  "summary": "Overall performance summary",
  "strengths": ["Strength 1", "Strength 2"],
  "weaknesses": ["Weakness 1", "Weakness 2"],
  "priority_areas": [
    {"topic": "Topic name", "reason": "Why this needs focus", "resources": ["Suggested resource"]}
  ],
  "score_projection": {
    "current_trajectory": 25,
    "with_improvement": 28,
    "timeline": "With 4 weeks of focused study"
  },
  "study_recommendations": ["Recommendation 1", "Recommendation 2"],
  "next_steps": ["Step 1", "Step 2", "Step 3"]
}

Return ONLY valid JSON.
PROMPT;

    $messages = [
        ['role' => 'system', 'content' => 'You are an ACT score analyst providing actionable insights to help students improve.'],
        ['role' => 'user', 'content' => $prompt]
    ];
    
    $result = ai_request($messages, $model, 0.7);
    
    if (!$result['success']) {
        return $result;
    }
    
    $content = $result['content'];
    if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $content, $matches)) {
        $content = $matches[1];
    }
    
    $analysis = json_decode(trim($content), true);
    
    if (!$analysis) {
        return ['success' => false, 'message' => 'Failed to parse analysis'];
    }
    
    return ['success' => true, 'analysis' => $analysis];
}
