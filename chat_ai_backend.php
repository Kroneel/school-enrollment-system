<?php
/* ====================================================
   File: chat_ai_backend.php
   Purpose:
   - Receives POST message
   - Calls OpenAI Responses API (server-side)
   - Returns JSON { reply: "..." }
   ==================================================== */

header("Content-Type: application/json");

// 1) Read message
$userMessage = trim($_POST["message"] ?? "");
if ($userMessage === "") {
  echo json_encode(["reply" => "Please type a question."]);
  exit;
}

// 2) Load API key from environment variable
$apiKey = getenv("OPENAI_API_KEY");
if (!$apiKey) {
  echo json_encode(["reply" => "AI is not configured on this server (missing OPENAI_API_KEY)."]);
  exit;
}

// 3) System instruction (keeps AI focused on your school + portal)
$systemInstruction =
  "You are the Koro High School assistant. " .
  "Answer questions about Koro High School, the student enrollment portal, teacher login/registration, " .
  "student registration/search, ICT focus, and general school contact/location info. " .
  "Keep answers short, clear, and appropriate for a school environment. " .
  "If unsure, say you are not sure and suggest contacting the school office.";

// 4) Build payload for Responses API (POST /v1/responses) :contentReference[oaicite:5]{index=5}
$payload = [
  "model" => "gpt-4.1-mini",
  "input" => [
    ["role" => "system", "content" => $systemInstruction],
    ["role" => "user", "content" => $userMessage]
  ],
  "temperature" => 0.4
];

// 5) Call OpenAI using cURL (more reliable than file_get_contents for HTTPS)
$ch = curl_init("https://api.openai.com/v1/responses");
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => [
    "Content-Type: application/json",
    "Authorization: Bearer " . $apiKey  // Bearer auth :contentReference[oaicite:6]{index=6}
  ],
  CURLOPT_POSTFIELDS => json_encode($payload),
  CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 6) Handle errors
if ($response === false) {
  echo json_encode(["reply" => "Network error contacting AI: " . $error]);
  exit;
}

if ($httpCode < 200 || $httpCode >= 300) {
  echo json_encode(["reply" => "AI service error (HTTP {$httpCode})."]);
  exit;
}

// 7) Parse response
$data = json_decode($response, true);

// The Responses API commonly provides output text in output_text :contentReference[oaicite:7]{index=7}
$reply = $data["output_text"] ?? "";

// Fallback parsing if output_text is missing
if (!$reply && isset($data["output"][0]["content"][0]["text"])) {
  $reply = $data["output"][0]["content"][0]["text"];
}

if (!$reply) {
  $reply = "Sorry, I couldn’t generate a response.";
}

// 8) Return JSON
echo json_encode(["reply" => $reply]);
