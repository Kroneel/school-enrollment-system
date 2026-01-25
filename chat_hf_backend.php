<?php
/* ====================================================
   File: chat_hf_backend.php
   Purpose:
   - Hugging Face AI backend for chatbot
   - Handles model cold-start / busy errors more gracefully
   ==================================================== */

header("Content-Type: application/json");

// 1) Read user message
$message = trim($_POST["message"] ?? "");
if ($message === "") {
  echo json_encode(["reply" => "Please type a question."]);
  exit;
}

// 2) Token from Apache SetEnv
$hfToken = getenv("HF_TOKEN");
if (!$hfToken) {
  echo json_encode(["reply" => "AI is not configured (missing HF_TOKEN)."]);
  exit;
}

// 3) Model (keep this for now)
$model = "google/flan-t5-base";
$url   = "https://api-inference.huggingface.co/models/" . $model;

// 4) Prompt (school-focused)
$prompt =
"You are the Koro High School assistant.
Answer only about Koro High School, teacher login/registration, student enrollment/search, ICT focus, and contact/location.

User: {$message}
Assistant:";

// 5) Payload
$payload = [
  "inputs" => $prompt,
  "parameters" => [
    "max_new_tokens" => 120,
    "temperature" => 0.4,
    "return_full_text" => false
  ]
];

// Helper function: call HF once
function call_hf($url, $hfToken, $payload) {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
      "Authorization: Bearer " . $hfToken,
      "Content-Type: application/json",
      // This header asks HF to wait for cold-start models instead of immediately 503’ing :contentReference[oaicite:1]{index=1}
      "X-Wait-For-Model: true"
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 60
  ]);

  $response = curl_exec($ch);
  $error    = curl_error($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  return [$httpCode, $response, $error];
}

// 6) Try request (and retry once if HF is busy/loading)
[$httpCode, $response, $error] = call_hf($url, $hfToken, $payload);

if ($response === false) {
  echo json_encode(["reply" => "Network error contacting AI: " . $error]);
  exit;
}

// If busy/loading (503/504), retry once after short sleep
if ($httpCode === 503 || $httpCode === 504) {
  sleep(2);
  [$httpCode, $response, $error] = call_hf($url, $hfToken, $payload);
}

// 7) If still not OK, return real HF error (so you can see what’s happening)
if ($httpCode < 200 || $httpCode >= 300) {
  $data = json_decode($response, true);
  $hfError = $data["error"] ?? "Service temporarily unavailable";
  echo json_encode(["reply" => "AI service unavailable: " . $hfError]);
  exit;
}

// 8) Parse success response
$data = json_decode($response, true);

$reply = "";
if (is_array($data) && isset($data[0]["generated_text"])) {
  $reply = trim($data[0]["generated_text"]);
}

if ($reply === "") {
  $reply = "Sorry, I couldn't generate a response. Please try again.";
}

echo json_encode(["reply" => $reply]);
