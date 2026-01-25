<?php
/* ====================================================
   File: chat_widget.php
   Purpose:
   - Floating popup chatbot for Koro High School
   - Shows a "Chat" button at bottom-right
   - On click, opens a small chat window
   - Uses AI backend (chat_hf_backend.php) with fallback
     to rule-based chatbot (chat.php)
   ==================================================== */
?>

<!-- Floating Chat Button -->
<div id="koro-chat-fab" class="koro-chat-fab">
  Chat
</div>

<!-- Popup Chat Window -->
<div id="koro-chat-popup" class="koro-chat-popup card shadow">
  <div class="card-header d-flex justify-content-between align-items-center py-2">
    <span class="fw-semibold small">Koro High School Assistant</span>
    <button type="button" id="koro-chat-close" class="btn btn-sm btn-light px-2 py-0">
      &times;
    </button>
  </div>

  <div class="card-body p-2">
    <!-- Chat messages area -->
    <div id="chatBox" class="border rounded bg-white p-2 mb-2 small"
         style="height: 220px; overflow-y: auto;">
      <div class="text-muted small">
        <b>Bot:</b> Hello! How can I help you today?
      </div>
    </div>

    <!-- User input -->
    <div class="input-group input-group-sm">
      <input id="chatInput" type="text" class="form-control"
             placeholder="Type your question...">
      <button id="chatSend" class="btn btn-primary">Send</button>
    </div>

    <div class="text-muted small mt-1">
      Note: AI responses may not always be accurate. For official info,
      contact the school office.
    </div>
  </div>
</div>

<script>
/* ====================================================
   Floating Chatbot Script
   - Prevents double loading
   - Handles open/close + send message
   ==================================================== */
if (window.__koroChatLoaded) {
  // Already initialized on this page
} else {
  window.__koroChatLoaded = true;

  (function () {
    const fab       = document.getElementById("koro-chat-fab");
    const popup     = document.getElementById("koro-chat-popup");
    const closeBtn  = document.getElementById("koro-chat-close");
    const chatBox   = document.getElementById("chatBox");
    const chatInput = document.getElementById("chatInput");
    const chatSend  = document.getElementById("chatSend");

    if (!fab || !popup || !chatBox || !chatInput || !chatSend) {
      return; // safety check
    }

    // Show/hide popup
    fab.addEventListener("click", function () {
      popup.classList.toggle("open");
      if (popup.classList.contains("open")) {
        chatInput.focus();
      }
    });

    closeBtn.addEventListener("click", function () {
      popup.classList.remove("open");
    });

    // Add a message line to the chat box
    function addMessage(sender, text) {
      const div = document.createElement("div");
      div.className = "small mb-1";
      div.innerHTML = "<b>" + sender + ":</b> " + String(text);
      chatBox.appendChild(div);
      chatBox.scrollTop = chatBox.scrollHeight;
    }

    // Call backend endpoint and return JSON
    async function callEndpoint(endpoint, msg) {
      const res = await fetch(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "message=" + encodeURIComponent(msg)
      });
      return await res.json();
    }

    // Main send logic
    async function sendMessage() {
      const msg = chatInput.value.trim();
      if (!msg) return;

      addMessage("You", msg);
      chatInput.value = "";

      try {
        // 1) Try AI backend first
        const ai = await callEndpoint("chat_hf_backend.php", msg);

        if (ai && ai.reply) {
          const lower = ai.reply.toLowerCase();

          // If AI service unavailable, fall back to rule-based
          if (lower.includes("ai service unavailable") ||
              lower.includes("missing hf_token")) {
            const fallback = await callEndpoint("chat.php", msg);
            addMessage("Bot", fallback.reply || "Sorry, I couldn't answer that.");
            return;
          }

          addMessage("Bot", ai.reply);
          return;
        }

        // 2) If AI gave no reply, fall back
        const fallback = await callEndpoint("chat.php", msg);
        addMessage("Bot", fallback.reply || "Sorry, I couldn't answer that.");
      } catch (error) {
        // 3) If AI call fails, fall back
        try {
          const fallback = await callEndpoint("chat.php", msg);
          addMessage("Bot", fallback.reply || "Sorry, I couldn't answer that.");
        } catch (e) {
          addMessage("Bot", "Chat service is currently unavailable.");
        }
      }
    }

    // Event listeners for send button + Enter key
    chatSend.addEventListener("click", sendMessage);
    chatInput.addEventListener("keydown", function (e) {
      if (e.key === "Enter") {
        sendMessage();
      }
    });

  })();
}
</script>
