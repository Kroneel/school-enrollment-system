<?php
/* ====================================================
   File: chat.php
   Purpose:
   - Simple rule-based chatbot (FAQ style)
   - Gives different answers based on keywords/phrases
   - Used as reliable fallback when AI is unavailable
   ==================================================== */

header("Content-Type: application/json");

// Get user message (lowercase for easier matching)
$message = strtolower(trim($_POST["message"] ?? ""));

if ($message === "") {
    echo json_encode(["reply" => "Please type a question about the school or system."]);
    exit;
}

/*
  Default reply if no rule matches
*/
$reply = "Sorry, I’m not sure. Try asking about: school info, enrollment, login, ICT, contact, or location.";

/*
  Helper function:
  - Checks if ANY of the keywords appear as whole words/phrases
  - Uses regex with word boundaries (\b) so:
    - 'hi' will NOT match 'high'
    - 'koro high school' will match correctly
*/
function message_has_keyword(string $message, array $keywords): bool {
    foreach ($keywords as $keyword) {
        $keyword = trim($keyword);
        if ($keyword === "") continue;

        // Escape keyword for regex safely
        $pattern = '/\b' . preg_quote($keyword, '/') . '\b/u';

        if (preg_match($pattern, $message)) {
            return true;
        }
    }
    return false;
}

/*
  Rules:
  - Order matters: more specific rules first
  - Each rule:
      'keywords' => [ ... ],
      'answer'   => '...'
*/
$rules = [

    // 1) About the school (specific phrases)
    [
        "keywords" => [
            "koro high school",
            "about koro high school",
            "about the school",
            "about school"
        ],
        "answer"   => "Koro High School provides quality education in a safe and inclusive environment in Fiji, supporting academics, culture, sports, and ICT learning."
    ],

    // 2) School mission / vision
    [
        "keywords" => ["mission", "vision", "goal"],
        "answer"   => "The mission of Koro High School is to develop disciplined, confident, and responsible students through academic excellence, good values, and teamwork."
    ],

    // 3) ICT / digital learning
    [
        "keywords" => ["ict", "computer", "technology", "digital"],
        "answer"   => "Koro High School promotes ICT and digital learning to support teaching, administration, and to prepare students with practical skills for future study and careers."
    ],

    // 4) Teacher login
    [
        "keywords" => ["login", "log in", "sign in"],
        "answer"   => "Teachers can login using their Teacher ID and password on the Login page. If you are a new teacher, please register first using the Register Now button."
    ],

    // 5) Teacher registration
    [
        "keywords" => ["register teacher", "teacher registration", "new teacher"],
        "answer"   => "To register as a teacher, go to the Register Now page, fill in your details, and create a password. The system securely hashes the password before saving it in the database."
    ],

    // 6) Student enrollment / registration
    [
        "keywords" => ["enroll student", "enrol student", "register student", "student enrollment", "student enrolment", "enroll","enrol","how to enroll student"],
        "answer"   => " To enroll you must register as a student with a email and password.<br> 
                            Upon login to the student portal you can apply and fill out necessary details.<br> After which you can choose
                            your class and subjects.<br>You can check you application status as pending, approved or rejected "
                    
    ],

    // 7) Search student
    [
        "keywords" => ["search student", "find student", "student profile"],
        "answer"   => "You can search a student by name or index number using the Search Student feature. The system will display the student’s profile and photo."
    ],

    // 8) Opening hours
    [
        "keywords" => ["opening hours", "hours", "school hours", "schoolhours", "openinghours"],
        "answer"   => "Our school hours are:<br>Monday to Thursday: 8:00 a.m. to 4:00 p.m.<br>Friday: 8:00 a.m. to 3:30 p.m."
    ],

    // 9) Contact info
    [
        "keywords" => ["contact", "phone", "email"],
        "answer"   => "For official enquiries, please use the Contact page (when available) or visit the school office during working hours."
    ],

    // 9) Location
    [
        "keywords" => ["where is the school", "location", "address"],
        "answer"   => "Koro High School is located in Fiji. The exact location and directions will be shown on the Contact/Location page in the system."
    ],

    
    // 10) Thank you / appreciation
    [
        "keywords" => ["thank you", "thanks", "vinaka", "danke", "cheers"],
        "answer"   => "You’re welcome! I’m glad I could help. Have a nice day 🙂"
    ],

    // 11) Greetings (kept last so it doesn’t override others)
    [
        "keywords" => ["hi", "hello", "bula", "hey", "good morning", "good afternoon"],
        "answer"   => "Hello! I am the Koro High School assistant. You can ask me about the school, teacher login, student enrollment, ICT, or contact details."
    ],

];

/*
  Matching logic:
  - Go through each rule in order
  - If message_has_keyword() returns true, use that rule's answer
*/
foreach ($rules as $rule) {
    if (message_has_keyword($message, $rule["keywords"])) {
        $reply = $rule["answer"];
        break;
    }
}

// Return reply as JSON
echo json_encode(["reply" => $reply]);
